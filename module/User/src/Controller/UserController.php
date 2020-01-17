<?php
/**
 * UserController.php - Main Controller
 *
 * Main Controller User Module
 *
 * @category Controller
 * @package User
 * @author Verein onePlace
 * @copyright (C) 2020 Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace User\Controller;

use User\Model\User;
use User\Model\UserTable;
use Application\Controller\CoreController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Session\Container;

class UserController extends CoreController {
    /**
     * User Table Object
     *
     * @var UserTable Gateway to UserTable
     * @since 1.0.0
     */
    private $oTableGateway;

    /**
     * UserController constructor.
     *
     * @param AdapterInterface $oDbAdapter
     * @param UserTable $oTableGateway
     * @since 1.0.0
     */
    public function __construct(AdapterInterface $oDbAdapter,UserTable $oTableGateway) {
        parent::__construct($oDbAdapter);
        $this->oTableGateway = $oTableGateway;
        $this->sSingleForm = 'user-single';
    }

    /**
     * User Index
     *
     * @return ViewModel - View Object with Data from Controller
     * @since 1.0.0
     */
    public function indexAction() {
        # Set Layout based on users theme
        $this->setThemeBasedLayout('user');

        # Add Buttons for breadcrumb
        $this->setViewButtons('user-index');

        # Set Table Rows for Index
        $this->setIndexColumns('user-index');

        # Get Paginator
        $oPaginator = $this->oTableGateway->fetchAll(true);
        $iPage = (int) $this->params()->fromQuery('page', 1);
        $iPage = ($iPage < 1) ? 1 : $iPage;
        $oPaginator->setCurrentPageNumber($iPage);
        $oPaginator->setItemCountPerPage(3);

        $aMeasureEnd = getrusage();
        $this->logPerfomance('user-index',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        return new ViewModel([
            'sTableName'=>'user-index',
            'aItems'=>$oPaginator,
        ]);
    }

    /**
     * User Login
     *
     * @return ViewModel|Request - View Object with Data from Controller|Redirect to Login
     * @since 1.0.0
     */
    public function loginAction() {
        $this->layout('layout/login');

        # Check if user is already logged in
        if(isset(CoreController::$oSession->oUser)) {
            // already logged in
            return $this->redirect()->toRoute('home');
        }

        # Get current Request - if post - perform login - otherwise show for,m
        $oRequest = $this->getRequest();
        if($oRequest->isPost()) {
            # Get User from Login Form
            $sUser = $oRequest->getPost('plc_login_user');

            try {
                # Try Login by E-Mail
                $oUser = $this->oTableGateway->getSingle($sUser,'email');
            } catch(\Exception $e) {
                try {
                    # Try Login by Username
                    $oUser = $this->oTableGateway->getSingle($sUser,'username');
                } catch(\Exception $e) {
                    echo $e->getMessage();
                    # Show Login Form
                    return new ViewModel();
                }
            }

            # Login Successful - redirect to Dashboard
            CoreController::$oSession->oUser = $oUser;
            return $this->redirect()->toRoute('home');
        } else {
            # Show Login Form
            return new ViewModel();
        }
    }

    /**
     * User Logout
     *
     * @return Request - Redirect to Login
     * @since 1.0.0
     */
    public function logoutAction() {
        # Remove User from Session
        unset(CoreController::$oSession->oUser);

        # Back to Login
        return $this->redirect()->toRoute('login');
    }

    /**
     * Denied - No Permission Page
     *
     * @return ViewModel - View Object with Data from Controller
     * @since 1.0.0
     */
    public function deniedAction() {
        # Set Layout based on users theme
        $this->setThemeBasedLayout('user');

        $sPermission = $this->params()->fromRoute('id', 'Def');

        return new ViewModel([
            'sPermission'=>$sPermission,
        ]);
    }

    /**
     * User Add Form
     *
     * @return ViewModel - View Object with Data from Controller
     * @since 1.0.0
     */
    public function addAction() {
        # Set Layout based on users theme
        $this->setThemeBasedLayout('user');

        # Get Request to decide wether to save or display form
        $oRequest = $this->getRequest();

        # Display Add Form
        if(!$oRequest->isPost()) {
            # Add Buttons for breadcrumb
            $this->setViewButtons('user-single');

            # Load Tabs for Add Form
            $this->setViewTabs($this->sSingleForm);

            # Load Fields for Add Form
            $this->setFormFields($this->sSingleForm);

            # Get User Permissions
            $aPartialData = [
                'aPermissions'=>$this->getPermissions(),
            ];
            $this->setPartialData('permissions',$aPartialData);

            # Get User Index Columns
            $aPartialData = [
                'aColumns'=>$this->getIndexTablesWithColumns(),
                'aUserColumns'=>[],
            ];
            $this->setPartialData('indexcolumns',$aPartialData);

            # Get User Tabs
            $aPartialData = [
                'aTabs'=>$this->getFormTabs(),
                'aUserTabs'=>[],
            ];
            $this->setPartialData('tabs',$aPartialData);

            # Get User Fields
            $aPartialData = [
                'aFields'=>$this->getFormFields(),
                'aUserFields'=>[],
            ];
            $this->setPartialData('formfields',$aPartialData);

            # Log Performance in DB
            $aMeasureEnd = getrusage();
            $this->logPerfomance('user-add',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

            # Pass Data to View
            return new ViewModel([
                'sFormName'=>$this->sSingleForm,
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
        $oUser = new User($this->oDbAdapter);
        $oUser->exchangeArray($aFormData);
        $iUserID = $this->oTableGateway->saveSingle($oUser);
        $oUser = $this->oTableGateway->getSingle($iUserID);

        # Update Permissions
        $aDataPermission = (is_array($_REQUEST[$this->sSingleForm.'-permissions'])) ? $_REQUEST[$this->sSingleForm.'-permissions'] : [];
        $oUser->updatePermissions($aDataPermission);

        # Update Index Columns
        $aDataIndexColumn = (is_array($_REQUEST[$this->sSingleForm.'-indexcolumns'])) ? $_REQUEST[$this->sSingleForm.'-indexcolumns'] : [];
        $oUser->updateIndexColumns($aDataIndexColumn);

        # Update Form Tabs
        $aDataTabs = (is_array($_REQUEST[$this->sSingleForm.'-tabs'])) ? $_REQUEST[$this->sSingleForm.'-tabs'] : [];
        $oUser->updateFormTabs($aDataTabs);

        # Update Form Fields
        $aDataFields = (is_array($_REQUEST[$this->sSingleForm.'-formfields'])) ? $_REQUEST[$this->sSingleForm.'-formfields'] : [];
        $oUser->updateFormFields($aDataFields);

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance('user-save',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        # Display Success Message and View New User
        $this->flashMessenger()->addSuccessMessage('User successfully created');
        return $this->redirect()->toRoute('user',['action'=>'view','id'=>$iUserID]);
    }

    /**
     * User View Form
     *
     * @return ViewModel - View Object with Data from Controller
     * @since 1.0.0
     */
    public function viewAction() {
        # Set Layout based on users theme
        $this->setThemeBasedLayout('user');

        # Get User ID from route
        $iUserID = $this->params()->fromRoute('id', 0);

        $oUser = $this->oTableGateway->getSingle($iUserID);

        # Attach User Entity to Layout
        $this->setViewEntity($oUser);

        # Add Buttons for breadcrumb
        $this->setViewButtons('user-view');

        # Load Tabs for Add Form
        $this->setViewTabs($this->sSingleForm);

        # Load Fields for Add Form
        $this->setFormFields($this->sSingleForm);

        # Get User Permissions
        $aPartialData = [
            'aPermissions'=>$this->getPermissions(),
            'aUserPermissions'=>$oUser->getMyPermissions(),
        ];
        $this->setPartialData('permissions',$aPartialData);

        # Get User Index Columns
        $aPartialData = [
            'aColumns'=>$this->getIndexTablesWithColumns(),
            'aUserColumns'=>$oUser->getMyIndexTablesWithColumns(),
        ];
        $this->setPartialData('indexcolumns',$aPartialData);

        # Get User Tabs
        $aPartialData = [
            'aTabs'=>$this->getFormTabs(),
            'aUserTabs'=>$oUser->getMyTabs(),
        ];
        $this->setPartialData('tabs',$aPartialData);

        # Get User Fields
        $aPartialData = [
            'aFields'=>$this->getFormFields(),
            'aUserFields'=>$oUser->getMyFormFields(),
        ];
        $this->setPartialData('formfields',$aPartialData);

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance('user-view',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        return new ViewModel([
            'sFormName'=>$this->sSingleForm,
            'oUser'=>$oUser,
        ]);
    }

    /**
     * User Edit Form
     *
     * @return ViewModel - View Object with Data from Controller
     * @since 1.0.0
     */
    public function editAction() {
        # Set Layout based on users theme
        $this->setThemeBasedLayout('user');

        # Get Request to decide wether to save or display form
        $oRequest = $this->getRequest();

        # Display Edit Form
        if(!$oRequest->isPost()) {
            # Get User ID from route
            $iUserID = $this->params()->fromRoute('id', 0);

            # Load User Entity
            $oUser = $this->oTableGateway->getSingle($iUserID);

            # Attach User Entity to Layout
            $this->setViewEntity($oUser);

            # Add Buttons for breadcrumb
            $this->setViewButtons('user-single');

            # Load Tabs for Edit Form
            $this->setViewTabs($this->sSingleForm);

            # Load Fields for Edit Form
            $this->setFormFields($this->sSingleForm);

            # Get User Permissions
            $aPartialData = [
                'aPermissions'=>$this->getPermissions(),
                'aUserPermissions'=>$oUser->getMyPermissions(),
            ];
            $this->setPartialData('permissions',$aPartialData);

            # Get User Index Columns
            $aPartialData = [
                'aColumns'=>$this->getIndexTablesWithColumns(),
                'aUserColumns'=>$oUser->getMyIndexTablesWithColumns(),
            ];
            $this->setPartialData('indexcolumns',$aPartialData);

            # Get User Tabs
            $aPartialData = [
                'aTabs'=>$this->getFormTabs(),
                'aUserTabs'=>$oUser->getMyTabs(),
            ];
            $this->setPartialData('tabs',$aPartialData);

            # Get User Fields
            $aPartialData = [
                'aFields'=>$this->getFormFields(),
                'aUserFields'=>$oUser->getMyFormFields(),
            ];
            $this->setPartialData('formfields',$aPartialData);

            # Log Performance in DB
            $aMeasureEnd = getrusage();
            $this->logPerfomance('user-edit',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

            # Pass Data to View
            return new ViewModel([
                'sFormName'=>$this->sSingleForm,
                'oUser'=>$oUser,
            ]);
        }

        $iUserID = $oRequest->getPost('Item_ID');
        $oUser = $this->oTableGateway->getSingle($iUserID);

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
                        if(!$oUser->setTextField($sFieldName,$_REQUEST[$sKey])) {
                            echo 'could not save field '.$sFieldName;
                        }
                    }
                    break;
            }
        }

        # Save User
        $iUserID = $this->oTableGateway->saveSingle($oUser);

        # Update Permissions
        $aDataPermission = (is_array($_REQUEST[$this->sSingleForm.'-permissions'])) ? $_REQUEST[$this->sSingleForm.'-permissions'] : [];
        $oUser->updatePermissions($aDataPermission);

        # Update Index Columns
        $aDataIndexColumn = (is_array($_REQUEST[$this->sSingleForm.'-indexcolumns'])) ? $_REQUEST[$this->sSingleForm.'-indexcolumns'] : [];
        $oUser->updateIndexColumns($aDataIndexColumn);

        # Update Form Tabs
        $aDataTabs = (is_array($_REQUEST[$this->sSingleForm.'-tabs'])) ? $_REQUEST[$this->sSingleForm.'-tabs'] : [];
        $oUser->updateFormTabs($aDataTabs);

        # Update Form Fields
        $aDataFields = (is_array($_REQUEST[$this->sSingleForm.'-formfields'])) ? $_REQUEST[$this->sSingleForm.'-formfields'] : [];
        $oUser->updateFormFields($aDataFields);

        # Log Performance in DB
        $aMeasureEnd = getrusage();
        $this->logPerfomance('user-save',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        # Display Success Message and View New User
        $this->flashMessenger()->addSuccessMessage('User successfully saved');
        return $this->redirect()->toRoute('user',['action'=>'view','id'=>$iUserID]);
    }

    /**
     * Update Sorting for given Table Columns
     *
     * @return string JSON Response
     * @since 1.0.0
     */
    public function updateindexcolumnsortAction() {
        # Set JSON Raw Layout
        $this->layout('layout/json');

        # Get Data from Reust
        $oRequest = $this->getRequest();

        # Prepare JSON Answer
        $aReturn = ['state'=>'success','message'=>'nothing todo'];

        if($oRequest->isPost()) {
            $sTable = $oRequest->getPost('table');
            $aColumns = $oRequest->getPost('columns');

            $iSortID = 0;
            # Loop over all columns provided
            foreach($aColumns as $sColInfo) {
                # Parse info
                $aInfo = explode('_',$sColInfo);
                $sTable = $aInfo[0];
                $sColumn = substr($sColInfo,strlen($sTable.'_'));

                # Check if table exists
                $oTable = $this->aCoreTables['table-index']->select(['table_name'=>$sTable]);
                if(count($oTable) > 0) {

                    # check if field exists
                    $oTable = $oTable->current();
                    $oField = $this->aCoreTables['core-form-field']->select(['form'=>$oTable->form,'fieldkey'=>$sColumn]);
                    if(count($oField) > 0) {
                        $oField = $oField->current();

                        # check if column exists for used
                        $oColFound = $this->aCoreTables['table-col']->select([
                            'field_idfs'=>$oField->Field_ID,
                            'user_idfs'=>CoreController::$oSession->oUser->getID(),
                            'tbl_name'=>$sTable
                        ]);

                        # update column sortid
                        if(count($oColFound) > 0) {
                            $oColFound = $oColFound->current();
                            $this->aCoreTables['table-col']->update([
                                'sortID'=>$iSortID,
                            ],[
                                'field_idfs'=>$oField->Field_ID,
                                'user_idfs'=>CoreController::$oSession->oUser->getID(),
                                'tbl_name'=>$sTable
                            ]);

                            $aReturn = ['state'=>'success','message'=>'column sorting updated'];

                            $iSortID++;
                        }
                    }
                }
            }
        }

        echo json_encode($aReturn);

        # No View File
        return false;
    }
}
