<?php
/**
 * SkeletonController.php - Main Controller
 *
 * Main Controller Skeleton Module
 *
 * @category Controller
 * @package Skeleton
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Skeleton\Controller;

use Application\Controller\CoreController;
use Laminas\View\Model\ViewModel;
use Skeleton\Model\Skeleton;
use Skeleton\Model\SkeletonTable;
use Laminas\Db\Adapter\AdapterInterface;

class SkeletonController extends CoreController {
    /**
     * Skeleton Table Object
     *
     * @since 1.0.0
     */
    private $oTableGateway;
    private $sSingleForm;

    /**
     * SkeletonController constructor.
     *
     * @param AdapterInterface $oDbAdapter
     * @param SkeletonTable $oTableGateway
     * @since 1.0.0
     */
    public function __construct(AdapterInterface $oDbAdapter,SkeletonTable $oTableGateway) {
        parent::__construct($oDbAdapter);
        $this->oTableGateway = $oTableGateway;
        $this->sSingleForm = 'skeleton-single';
    }

    /**
     * Skeleton Index
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function indexAction() {
        # Set Layout based on users theme
        $this->layout('layout/skeleton-'.CoreController::$oSession->oUser->getTheme());

        # Add Buttons for breadcrumb
        $this->setViewButtons('skeleton-index');

        # Set Table Rows for Index
        $this->setIndexColumns('skeleton-index');

        # Get Paginator
        $oPaginator = $this->oTableGateway->fetchAll(true);
        $iPage = (int) $this->params()->fromQuery('page', 1);
        $iPage = ($iPage < 1) ? 1 : $iPage;
        $oPaginator->setCurrentPageNumber($iPage);
        $oPaginator->setItemCountPerPage(3);

        return new ViewModel([
            'sTableName'=>'skeleton-index',
            'aItems'=>$oPaginator,
        ]);
    }

    /**
     * Skeleton Add Form
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function addAction() {
        # Set Layout based on users theme
        $this->layout('layout/skeleton-'.CoreController::$oSession->oUser->getTheme());

        # Get Request to decide wether to save or display form
        $oRequest = $this->getRequest();

        # Display Add Form
        if(!$oRequest->isPost()) {
            # Add Buttons for breadcrumb
            $this->setViewButtons('skeleton-single');

            # Load Tabs for View Form
            $this->setViewTabs($this->sSingleForm);

            # Load Fields for View Form
            $this->setFormFields($this->sSingleForm);

            return new ViewModel([
                'sFormName' => $this->sSingleForm,
            ]);
        }

        # Get and validate Form Data
        $aFormData = [];
        foreach(array_keys($_REQUEST) as $sKey) {
            $sFieldName = substr($sKey,strlen($this->sSingleForm.'_'));
            switch($sFieldName) {
                case 'password':
                    $aFormData[$sFieldName] = password_hash($_REQUEST[$sKey],PASSWORD_DEFAULT);
                    break;
                default:
                    $aFormData[$sFieldName] = $_REQUEST[$sKey];
                    break;
            }
        }

        # Save Add Form
        $oSkeleton = new Skeleton($this->oDbAdapter);
        $oSkeleton->exchangeArray($aFormData);
        $iSkeletonID = $this->oTableGateway->saveSingle($oSkeleton);
        $oSkeleton = $this->oTableGateway->getSingle($iSkeletonID);

        # Display Success Message and View New Skeleton
        $this->flashMessenger()->addSuccessMessage('Skeleton successfully created');
        return $this->redirect()->toRoute('skeleton',['action'=>'view','id'=>$iSkeletonID]);
    }

    /**
     * Skeleton Edit Form
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function editAction() {
        # Set Layout based on users theme
        $this->layout('layout/skeleton-'.CoreController::$oSession->oUser->getTheme());

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
                echo 'Skeleton Not found';
                return false;
            }

            # Attach Skeleton Entity to Layout
            $this->setViewEntity($oSkeleton);

            # Add Buttons for breadcrumb
            $this->setViewButtons('skeleton-single');

            # Load Tabs for View Form
            $this->setViewTabs($this->sSingleForm);

            # Load Fields for View Form
            $this->setFormFields($this->sSingleForm);

            return new ViewModel([
                'sFormName' => $this->sSingleForm,
                'oSkeleton' => $oSkeleton,
            ]);
        }

        $iSkeletonID = $oRequest->getPost('Item_ID');
        $oSkeleton = $this->oTableGateway->getSingle($iSkeletonID);

        # Get and validate Form Data
        $aFormData = [];
        foreach(array_keys($_REQUEST) as $sKey) {
            $sFieldName = substr($sKey,strlen($this->sSingleForm.'_'));
            switch($sFieldName) {
                case 'password':
                    //$aFormData[$sFieldName] = password_hash($_REQUEST[$sKey],PASSWORD_DEFAULT);
                    break;
                default:
                    if($sFieldName != '') {
                        if(!$oSkeleton->setTextField($sFieldName,$_REQUEST[$sKey])) {
                            echo 'could not save field '.$sFieldName;
                        }
                    }
                    break;
            }
        }

        # Save Skeleton
        $iSkeletonID = $this->oTableGateway->saveSingle($oSkeleton);

        # Display Success Message and View New User
        $this->flashMessenger()->addSuccessMessage('Skeleton successfully saved');
        return $this->redirect()->toRoute('skeleton',['action'=>'view','id'=>$iSkeletonID]);
    }

    /**
     * Skeleton View Form
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function viewAction() {
        # Set Layout based on users theme
        $this->layout('layout/skeleton-'.CoreController::$oSession->oUser->getTheme());

        # Get Skeleton ID from URL
        $iSkeletonID = $this->params()->fromRoute('id', 0);

        # Try to get Skeleton
        try {
            $oSkeleton = $this->oTableGateway->getSingle($iSkeletonID);
        } catch (\RuntimeException $e) {
            echo 'Skeleton Not found';
            return false;
        }

        # Attach Skeleton Entity to Layout
        $this->setViewEntity($oSkeleton);

        # Add Buttons for breadcrumb
        $this->setViewButtons('skeleton-view');

        # Load Tabs for View Form
        $this->setViewTabs($this->sSingleForm);

        # Load Fields for View Form
        $this->setFormFields($this->sSingleForm);

        return new ViewModel([
            'sFormName'=>$this->sSingleForm,
            'oSkeleton'=>$oSkeleton,
        ]);
    }
}
