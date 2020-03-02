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

    /**
     * CoreEntityController constructor.
     *
     * @param AdapterInterface $oDbAdapter database connection
     * @param bool $oTableGateway
     * @param $oServiceManager service manager
     * @since 1.0.0
     */
    public function __construct(AdapterInterface $oDbAdapter,$oTableGateway = false,$oServiceManager)
    {
        parent::__construct($oDbAdapter,$oTableGateway,$oServiceManager);
    }

    /**
     * Generate Index View for Skeleton Based Entities
     *
     * @param $sKey
     * @param array $aItems
     * @param array $aWhereForce
     * @return ViewModel
     * @since 1.0.5
     */
    public function generateIndexView($sKey,$aItems = [],$aWhereForce = [],$sLicense = '')
    {
        # Set Layout based on users theme
        $this->setThemeBasedLayout($sKey);
        $aWhere = [];

        $this->layout()->aNavLinks = [
            (object)['label'=>ucfirst($sKey.'s')],
        ];

        if($sLicense == '') {
            $sLicense = $sKey;
        }

        # Check license
        if (! $this->checkLicense($sLicense)) {
            $this->flashMessenger()->addErrorMessage('You have no active license for '.$sLicense);
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
        $iPage = (int) $this->params()->fromQuery('page', 1);
        $iPage = ($iPage < 1) ? 1 : $iPage;

        if (array_key_exists($sKey.'-index-before-paginator',CoreEntityController::$aEntityHooks)) {
            foreach(CoreEntityController::$aEntityHooks[$sKey.'-index-before-paginator'] as $oHook) {
                $sHookFunc = $oHook->sFunction;
                $oPaginator = $oHook->oItem->$sHookFunc();
            }
        } else {
            # Paginator can be overwritten by parameter
            if(count($aItems) > 0) {
                $oPaginator = $aItems;
            } else {
                # where can be overwritten by parameter
                if (count($aWhereForce) > 0) {
                    # if so, save to session for pagination
                    if(! isset(CoreEntityController::$oSession->aCurrentIndexFilter)) {
                        CoreEntityController::$oSession->aCurrentIndexFilter = [];
                    }
                    CoreEntityController::$oSession->aCurrentIndexFilter[$sKey] = $aWhereForce;
                    $aWhere = $aWhereForce;
                } else {
                    # if we have a saved filter (pagination) load it
                    if (isset(CoreEntityController::$oSession->aCurrentIndexFilter) && isset($_REQUEST['page'])) {
                        if (array_key_exists($sKey,CoreEntityController::$oSession->aCurrentIndexFilter)) {
                            $aWhere = CoreEntityController::$oSession->aCurrentIndexFilter[$sKey];
                        }
                    }
                }
                # get items as paginator with selected filters applied
                $oPaginator = $this->oTableGateway->fetchAll(true,$aWhere);
            }
        }

        # some adjustments to paginator
        if ($oPaginator) {
            $oPaginator->setCurrentPageNumber($iPage);
            $iItemsPerPage = (CoreEntityController::$oSession->oUser->getSetting($sKey.'-index-items-per-page'))
                ? CoreEntityController::$oSession->oUser->getSetting($sKey.'-index-items-per-page') : 10;
            $oPaginator->setItemCountPerPage($iItemsPerPage);
        }
        # clean filters cache if we come from index
        if (isset(CoreEntityController::$oSession->aCurrentIndexFilter) && !isset($_REQUEST['page']) && count($aWhereForce) == 0) {
            if (array_key_exists($sKey,CoreEntityController::$oSession->aCurrentIndexFilter)) {
                unset(CoreEntityController::$oSession->aCurrentIndexFilter[$sKey]);
            }
        }

        $sRoute = (isset($sRoute)) ? $sRoute : explode('-',$sKey)[0];

        $aSavedSearches = CoreController::$aCoreTables['user-search']->select([
            'user_idfs' => CoreController::$oSession->oUser->getID(),
            'list_name' => $sKey.'-index',
        ]);

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance($sKey.'-index',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        return new ViewModel([
            'sTableName' => $sKey.'-index',
            'aItems' => $oPaginator,
            'aFilters' => $aWhere,
            'aSavedSearches' => $aSavedSearches,
            'sRoute' => $sKey,
        ]);
    }

    /**
     * Generate Add View For Skeleton Entities
     *
     * @param $sKey
     * @param string $sSingleForm
     * @param string $sRoute
     * @param string $sRouteAction
     * @param int $iRouteID
     * @param array $aExtraViewData
     * @return ViewModel
     * @since 1.0.5
     */
    protected function generateAddView($sKey,$sSingleForm = '',$sRoute = '',$sRouteAction = 'view',$iRouteID = 0,$aExtraViewData = []) {
        # Set Layout based on users theme
        $this->setThemeBasedLayout($sKey);

        $sRoute = ($sRoute == '') ? $sKey : $sRoute;

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
                            # dont overwrite existing array, append data to them
                            if(array_key_exists($sHookKey,$aViewExtraData)) {
                                if(is_array($aViewExtraData[$sHookKey])) {
                                    foreach(array_keys($aHookExtraData[$sHookKey]) as $sSubHook) {
                                        $aViewExtraData[$sHookKey][$sSubHook] = $aHookExtraData[$sHookKey][$sSubHook];
                                    }
                                }
                            } else {
                                $aViewExtraData[$sHookKey] = $aHookExtraData[$sHookKey];
                            }
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
            $aViewData = array_merge($aViewData,$aExtraViewData);

            return new ViewModel($aViewData);
        }

        # Get and validate Form Data
        $aFormData = $this->parseFormData($_REQUEST);

        # Save Add Form
        $oSkeletonBasedObject = $this->oTableGateway->generateNew();
        $oSkeletonBasedObject->exchangeArray($aFormData);
        /**
         * CHeck for hooks - execute them if found
         */
        if(array_key_exists($sKey.'-add-before-save',CoreEntityController::$aEntityHooks)) {
            foreach(CoreEntityController::$aEntityHooks[$sKey.'-add-before-save'] as $oHook) {
                $sHookFunc = $oHook->sFunction;
                $oSkeletonBasedObject = $oHook->oItem->$sHookFunc($oSkeletonBasedObject,$_REQUEST);
            }
        }
        $iSkeletonID = $this->oTableGateway->saveSingle($oSkeletonBasedObject);
        $oSkeletonBasedObject = $this->oTableGateway->getSingle($iSkeletonID);

        # Save Multiselect
        $this->updateMultiSelectFields($_REQUEST,$oSkeletonBasedObject,$sKey.'-single');

        # Add XP for creating a new entity
        CoreController::$oSession->oUser->addXP($sKey.'-add');

        /**
         * CHeck for hooks - execute them if found
         */
        if(array_key_exists($sKey.'-add-after-save',CoreEntityController::$aEntityHooks)) {
            foreach(CoreEntityController::$aEntityHooks[$sKey.'-add-after-save'] as $oHook) {
                $sHookFunc = $oHook->sFunction;
                $oHook->oItem->$sHookFunc($oSkeletonBasedObject,$_REQUEST,true);
            }
        }

        $iRouteID = ($iRouteID == 0) ? $iSkeletonID : $iRouteID;

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance($sKey.'-save',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        # Display Success Message and View New Skeleton
        $this->flashMessenger()->addSuccessMessage('Skeleton successfully created');
        return $this->redirect()->toRoute($sRoute,['action'=>$sRouteAction,'id'=>$iRouteID]);
    }

    /**
     * Generate View Form for Skeleton based Entities
     *
     * @param $sKey
     * @param string $sSingleForm
     * @return bool|ViewModel
     * @since 1.0.5
     */
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

        /**
         * CHeck for hooks - execute them if found
         */
        $aViewExtraData = [];
        if(array_key_exists($sKey.'-view-before',CoreEntityController::$aEntityHooks)) {
            foreach(CoreEntityController::$aEntityHooks[$sKey.'-view-before'] as $oHook) {
                $sHookFunc = $oHook->sFunction;
                $aHookExtraData = $oHook->oItem->$sHookFunc($oSkeleton);
                if(is_array($aHookExtraData)) {
                    foreach(array_keys($aHookExtraData) as $sHookKey) {
                        # dont overwrite existing array, append data to them
                        if(array_key_exists($sHookKey,$aViewExtraData)) {
                            if(is_array($aViewExtraData[$sHookKey])) {
                                foreach(array_keys($aHookExtraData[$sHookKey]) as $sSubHook) {
                                    $aViewExtraData[$sHookKey][$sSubHook] = $aHookExtraData[$sHookKey][$sSubHook];
                                }
                            }
                        } else {
                            $aViewExtraData[$sHookKey] = $aHookExtraData[$sHookKey];
                        }
                    }
                }
            }
        }

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance($sKey.'-view',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        $this->sSingleForm = ($sSingleForm != '') ? $sSingleForm : $sKey.'-single';

        $aViewData = [
            'sFormName'=>$this->sSingleForm,
            'oItem'=>$oSkeleton,
        ];
        $aViewData = array_merge($aViewData,$aViewExtraData);

        return new ViewModel($aViewData);
    }

    /**
     * Generate Edit Form for Skeleton based Entities
     *
     * @param $sKey
     * @param string $sSingleForm
     * @return bool|ViewModel
     * @since 1.0.5
     */
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

            $aViewExtraData = [];

            /**
             * CHeck for hooks - execute them if found
             */
            if(array_key_exists($sKey.'-edit-before',CoreEntityController::$aEntityHooks)) {
                foreach(CoreEntityController::$aEntityHooks[$sKey.'-edit-before'] as $oHook) {
                    $sHookFunc = $oHook->sFunction;
                    $aHookExtraData = $oHook->oItem->$sHookFunc($oSkeleton);
                    if(is_array($aHookExtraData)) {
                        foreach(array_keys($aHookExtraData) as $sHookKey) {
                            # dont overwrite existing array, append data to them
                            if(array_key_exists($sHookKey,$aViewExtraData)) {
                                if(is_array($aViewExtraData[$sHookKey])) {
                                    foreach(array_keys($aHookExtraData[$sHookKey]) as $sSubHook) {
                                        # attach result of hook to view
                                        $aViewExtraData[$sHookKey][$sSubHook] = $aHookExtraData[$sHookKey][$sSubHook];
                                    }
                                }
                            } else {
                                # attach result of hook to view
                                $aViewExtraData[$sHookKey] = $aHookExtraData[$sHookKey];
                            }

                        }
                    }
                }
            }

            # Log Performance in DB
            $aMeasureEnd = getrusage();
            $this->logPerfomance($sKey.'-edit',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

            $this->sSingleForm = ($sSingleForm != '') ? $sSingleForm : $sKey.'-single';

            $aBasicViewData = [
                'sFormName' => $this->sSingleForm,
                'oItem' => $oSkeleton,
            ];

            $aViewData = array_merge($aBasicViewData,$aViewExtraData);

            return new ViewModel($aViewData);
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

        /**
         * CHeck for hooks - execute them if found
         */
        if(array_key_exists($sKey.'-edit-after-save',CoreEntityController::$aEntityHooks)) {
            foreach(CoreEntityController::$aEntityHooks[$sKey.'-edit-after-save'] as $oHook) {
                $sHookFunc = $oHook->sFunction;
                $oHook->oItem->$sHookFunc($oSkeleton,$_REQUEST,true);
            }
        }

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance($sKey.'-save',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        # Display Success Message and View New User
        $this->flashMessenger()->addSuccessMessage(ucfirst($sKey).' successfully saved');
        return $this->redirect()->toRoute($sKey,['action'=>'view','id'=>$iSkeletonID]);
    }

    /**
     * Register hook in core
     *
     * @param $sHook
     * @param $oHook
     * @since 1.0.7
     */
    public static function addHook($sHook,$oHook) {
        if(!array_key_exists($sHook,CoreEntityController::$aEntityHooks)) {
            CoreEntityController::$aEntityHooks[$sHook] = [];
        }
        CoreEntityController::$aEntityHooks[$sHook][] = $oHook;
    }
}