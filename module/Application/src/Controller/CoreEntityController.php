<?php
/**
 * CoreEntityController.php - Core Entity Controller
 *
 * Basic Controller for all other onePlace Module Controllers
 *
 * @category Controller
 * @package Application
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

namespace Application\Controller;

use Application\Model\CoreEntityModel;
use Laminas\View\Exception\RuntimeException;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;
use Laminas\Mail;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;

class CoreEntityController extends CoreController {
    private static $aEntityHooks = [];
    public function __construct(AdapterInterface $oDbAdapter,$oTableGateway = false,$oServiceManager) {
        parent::__construct($oDbAdapter,$oTableGateway,$oServiceManager);
    }

    public function generateIndexView($sKey) {
        # Set Layout based on users theme
        $this->setThemeBasedLayout($sKey);

        $this->layout()->aNavLinks = [
            (object)['label'=>ucfirst($sKey.'s')],
        ];

        # Check license
        if(!$this->checkLicense($sKey)) {
            $this->flashMessenger()->addErrorMessage('You have no active license for '.$sKey);
            $this->redirect()->toRoute('home');
        }

        # Add Buttons for breadcrumb
        $this->setViewButtons($sKey.'-index');

        # Set Table Rows for Index
        $this->setIndexColumns($sKey.'-index');

        ##USERID##
        /**
         * ['state_idfs'] = # Entity Tag IDFS - tag_value = "available"
         *
         */

        # Get Paginator
        $oPaginator = false;
        if(array_key_exists($sKey.'-index-before-paginator',CoreEntityController::$aEntityHooks)) {
            foreach(CoreEntityController::$aEntityHooks[$sKey.'-index-before-paginator'] as $oHook) {
                $sHookFunc = $oHook->sFunction;
                $oPaginator = $oHook->oItem->$sHookFunc();
            }
        } else {
            $oPaginator = $this->oTableGateway->fetchAll(true);
        }

        $iPage = (int) $this->params()->fromQuery('page', 1);
        $iPage = ($iPage < 1) ? 1 : $iPage;
        if($oPaginator) {
            $oPaginator->setCurrentPageNumber($iPage);
            $oPaginator->setItemCountPerPage(3);
        }

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance($sKey.'-index',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        return new ViewModel([
            'sTableName'=>$sKey.'-index',
            'aItems'=>$oPaginator,
        ]);
    }

    protected function generateAddView($sKey,$sSingleForm = '') {
        # Set Layout based on users theme
        $this->setThemeBasedLayout($sKey);

        # Add Links for Breadcrumb
        $this->layout()->aNavLinks = [
            (object)['label'=>ucfirst($sKey.'s'),'href'=>'/'.$sKey],
            (object)['label'=>'Add '.ucfirst($sKey)],
        ];

        # Check license
        if(!$this->checkLicense($sKey)) {
            $this->flashMessenger()->addErrorMessage('You have no active license for '.$sKey);
            $this->redirect()->toRoute('home');
        }

        # Get Request to decide wether to save or display form
        $oRequest = $this->getRequest();

        # Display Add Form
        if(!$oRequest->isPost()) {
            # Add Buttons for breadcrumb
            $this->setViewButtons($sKey.'-single');

            # Load Tabs for View Form
            $this->setViewTabs($this->sSingleForm);

            # Load Fields for View Form
            $this->setFormFields($this->sSingleForm);

            $aViewExtraData = [];
            /**
             * CHeck for hooks - execute them if found
             */
            if(array_key_exists($sKey.'-add-before',CoreEntityController::$aEntityHooks)) {
                foreach(CoreEntityController::$aEntityHooks[$sKey.'-add-before'] as $oHook) {
                    $sHookFunc = $oHook->sFunction;
                    $aHookExtraData = $oHook->oItem->$sHookFunc();
                    if(is_array($aHookExtraData)) {
                        foreach(array_keys($aHookExtraData) as $sHookKey) {
                            $aViewExtraData[$sHookKey] = $aHookExtraData[$sHookKey];
                        }
                    }
                }
            }

            # Log Performance in DB
            $aMeasureEnd = getrusage();
            $this->logPerfomance($sKey.'-add',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

            $this->sSingleForm = ($sSingleForm != '') ? $sSingleForm : $sKey.'-single';

            $aViewData = [
                'sFormName' => $this->sSingleForm,
            ];

            $aViewData = array_merge($aViewData,$aViewExtraData);

            return new ViewModel($aViewData);
        }

        # Get and validate Form Data
        $aFormData = $this->parseFormData($_REQUEST);

        # Save Add Form
        $oSkeletonBasedObject = $this->oTableGateway->generateNew();
        $oSkeletonBasedObject->exchangeArray($aFormData);
        $iSkeletonID = $this->oTableGateway->saveSingle($oSkeletonBasedObject);
        $oSkeletonBasedObject = $this->oTableGateway->getSingle($iSkeletonID);

        # Save Multiselect
        $this->updateMultiSelectFields($_REQUEST,$oSkeletonBasedObject,$sKey.'-single');

        # Add XP for creating a new entity
        CoreController::$oSession->oUser->addXP($sKey.'-add');

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance($sKey.'-save',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        # Display Success Message and View New Skeleton
        $this->flashMessenger()->addSuccessMessage('Skeleton successfully created');
        return $this->redirect()->toRoute($sKey,['action'=>'view','id'=>$iSkeletonID]);
    }

    public function generateViewView($sKey,$sSingleForm = '') {
        # Set Layout based on users theme
        $this->setThemeBasedLayout($sKey);

        # Check license
        if(!$this->checkLicense($sKey)) {
            $this->flashMessenger()->addErrorMessage('You have no active license for '.$sKey);
            $this->redirect()->toRoute('home');
        }

        # Get Skeleton ID from URL
        $iSkeletonID = $this->params()->fromRoute('id', 0);

        # Try to get Skeleton
        try {
            $oSkeleton = $this->oTableGateway->getSingle($iSkeletonID);
        } catch (\RuntimeException $e) {
            echo 'Skeleton Not found';
            return false;
        }

        # Add Links for Breadcrumb
        $this->layout()->aNavLinks = [
            (object)['label'=>ucfirst($sKey.'s'),'href'=>'/'.$sKey],
            (object)['label'=>$oSkeleton->getLabel()],
        ];

        # Attach Skeleton Entity to Layout
        $this->setViewEntity($oSkeleton);

        # Add Buttons for breadcrumb
        $this->setViewButtons($sKey.'-view');

        # Load Tabs for View Form
        $this->setViewTabs($this->sSingleForm);

        # Load Fields for View Form
        $this->setFormFields($this->sSingleForm);

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance($sKey.'-view',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        $this->sSingleForm = ($sSingleForm != '') ? $sSingleForm : $sKey.'-single';

        return new ViewModel([
            'sFormName'=>$this->sSingleForm,
            'oItem'=>$oSkeleton,
        ]);
    }

    public function generateEditView($sKey,$sSingleForm = '') {
        # Set Layout based on users theme
        $this->setThemeBasedLayout($sKey);

        # Check license
        if(!$this->checkLicense($sKey)) {
            $this->flashMessenger()->addErrorMessage('You have no active license for '.$sKey);
            $this->redirect()->toRoute('home');
        }

        # Add Links for Breadcrumb
        $this->layout()->aNavLinks = [
            (object)['label'=>ucfirst($sKey.'s'),'href'=>'/'.$sKey],
            (object)['label'=>'Edit '.ucfirst($sKey)],
        ];

        # Get Request to decide wether to save or display form
        $oRequest = $this->getRequest();

        # Display Edit Form
        if(!$oRequest->isPost()) {

            # Get Skeleton ID from URL
            $iSkeletonID = $this->params()->fromRoute('id', 0);

            # Try to get Skeleton
            try {
                $oSkeleton = $this->oTableGateway->getSingle($iSkeletonID);
            } catch (\RuntimeException $e) {
                echo $sKey.' Not found';
                return false;
            }

            # Attach Skeleton Entity to Layout
            $this->setViewEntity($oSkeleton);

            # Add Buttons for breadcrumb
            $this->setViewButtons($sKey.'-single');

            # Load Tabs for View Form
            $this->setViewTabs($this->sSingleForm);

            # Load Fields for View Form
            $this->setFormFields($this->sSingleForm);

            # Log Performance in DB
            $aMeasureEnd = getrusage();
            $this->logPerfomance($sKey.'-edit',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

            $this->sSingleForm = ($sSingleForm != '') ? $sSingleForm : $sKey.'-single';

            return new ViewModel([
                'sFormName' => $this->sSingleForm,
                'oItem' => $oSkeleton,
            ]);
        }

        $iSkeletonID = $oRequest->getPost('Item_ID');
        $oSkeleton = $this->oTableGateway->getSingle($iSkeletonID);

        # Update Skeleton with Form Data
        $oSkeleton = $this->attachFormData($_REQUEST,$oSkeleton);

        # Save Skeleton
        $iSkeletonID = $this->oTableGateway->saveSingle($oSkeleton);

        $this->layout('layout/json');

        # Parse Form Data
        $aFormData = $this->parseFormData($_REQUEST);

        # Save Multiselect
        $this->updateMultiSelectFields($aFormData,$oSkeleton,$sKey.'-single');

        # Add XP for creating a new entity
        CoreController::$oSession->oUser->addXP($sKey.'-edit');

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance($sKey.'-save',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        # Display Success Message and View New User
        $this->flashMessenger()->addSuccessMessage(ucfirst($sKey).' successfully saved');
        return $this->redirect()->toRoute($sKey,['action'=>'view','id'=>$iSkeletonID]);
    }

    public static function addHook($sHook,$oHook) {
        if(!array_key_exists($sHook,CoreEntityController::$aEntityHooks)) {
            CoreEntityController::$aEntityHooks[$sHook] = [];
        }
        CoreEntityController::$aEntityHooks[$sHook][] = $oHook;
    }
}