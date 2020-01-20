<?php
/**
 * CoreController.php - Core Controller
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
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Exception\RuntimeException;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;

class CoreController extends AbstractActionController {
    /**
     * onePlace Session Object
     *
     * @var mixed
     * @since 1.0.0
     */
    public static $oSession;

    /**
     * Core Tables Cache
     *
     * @var array
     * @since 1.0.0
     */
    public static $aCoreTables;

    /**
     * Database Connection
     *
     * @var AdapterInterface Active Database connection
     * @since 1.0.0
     */
    protected $oDbAdapter;

    /**
     * Single Form
     *
     * @var string Single Form Name
     * @since 1.0.0
     */
    protected $sSingleForm;

    /**
     * Rusage Store for Perfomance Log
     *
     * @var array rusage information
     * @since 1.0.0
     */
    public static $aPerfomanceLogStart = [];

    public static $oServiceManager;

    /**
     * CoreController constructor.
     *
     * @param AdapterInterface $oDbAdapter
     * @since 1.0.0
     */
    public function __construct(AdapterInterface $oDbAdapter,$oTableGateway = false,$oServiceManager) {
        # Get onePlace User Session
        CoreController::$oSession = new Container('plcauth');
        CoreController::$oServiceManager = $oServiceManager;
        $this->oDbAdapter = $oDbAdapter;
        CoreController::$aCoreTables = [];

        # Init Core Tables
        CoreController::$aCoreTables['core-log-performance'] = new TableGateway('core_perfomance_log',$this->oDbAdapter);
        CoreController::$aCoreTables['form-button'] = new TableGateway('core_form_button',$this->oDbAdapter);
        CoreController::$aCoreTables['core-form'] = new TableGateway('core_form',$this->oDbAdapter);
        CoreController::$aCoreTables['core-form-tab'] = new TableGateway('core_form_tab',$this->oDbAdapter);
        CoreController::$aCoreTables['form-tab'] = new TableGateway('user_form_tab',$this->oDbAdapter);
        CoreController::$aCoreTables['form-field'] = new TableGateway('user_form_field',$this->oDbAdapter);
        CoreController::$aCoreTables['core-form-field'] = new TableGateway('core_form_field',$this->oDbAdapter);
        CoreController::$aCoreTables['table-col'] = new TableGateway('user_table_column',$this->oDbAdapter);
        CoreController::$aCoreTables['core-entity-tag'] = new TableGateway('core_entity_tag',$this->oDbAdapter);
        CoreController::$aCoreTables['core-entity-tag-entity'] = new TableGateway('core_entity_tag_entity',$this->oDbAdapter);
        CoreController::$aCoreTables['table-index'] = new TableGateway('core_index_table',$this->oDbAdapter);
        CoreController::$aCoreTables['permission'] = new TableGateway('permission',$this->oDbAdapter);
    }

    /**
     * Get Buttons for current View and current User
     *
     * @param string $sView
     * @return array
     * @since 1.0.0
     */
    public function getViewButtons($sView) {
        $aButtonsDB = CoreController::$aCoreTables['form-button']->select(['form'=>$sView]);
        $aButtons = [];
        if(count($aButtonsDB) > 0) {
            foreach($aButtonsDB as $oBtn) {
                $aButtons[] = $oBtn;
            }
            return $aButtons;
        } else {
            return [];
        }
    }

    /**
     * Set Buttons for current View
     *
     * @param string $sView
     * @since 1.0.0
     */
    public function setViewButtons($sView) {
        $this->layout()->aButtons = $this->getViewButtons($sView);
    }

    /**
     * Get Tabs for current View and current User
     *
     * @param string $sView
     * @return array
     * @since 1.0.0
     */
    public function getViewTabs($sView) {
        # Build Query to get User Based Tabs
        $oTabSel = new Select(CoreController::$aCoreTables['form-tab']->getTable());
        $oTabSel->join(['core_tab'=>'core_form_tab'],'core_tab.Tab_ID = user_form_tab.tab_idfs');
        $oTabSel->where(['user_form_tab.user_idfs'=>CoreController::$oSession->oUser->getID(),'core_tab.form'=>$sView]);
        $oTabSel->order('user_form_tab.sort_id ASC');

        # Get User Based Tabs
        $aTabsDB = CoreController::$aCoreTables['form-tab']->selectWith($oTabSel);
        $aTabs = [];
        if(count($aTabsDB) > 0) {
            foreach($aTabsDB as $oTab) {
                $aTabs[] = $oTab;
            }
            return $aTabs;
        } else {
            return [];
        }
    }

    /**
     * Set Tabs for current View
     *
     * @param string $sView
     * @since 1.0.0
     */
    public function setViewTabs($sView) {
        $this->layout()->aTabs = $this->getViewTabs($sView);
    }

    /**
     * Get Fields for select Form and current User
     *
     * @param string $sForm
     * @return array
     * @since 1.0.0
     */
    public function getUserFormFields($sForm) {
        # Build Query to get User Based Formfields
        $oFieldSel = new Select(CoreController::$aCoreTables['form-field']->getTable());
        $oFieldSel->join(['core_field'=>'core_form_field'],'core_field.Field_ID = user_form_field.field_idfs');
        $oFieldSel->where(['user_form_field.user_idfs'=>CoreController::$oSession->oUser->getID(),'core_field.form'=>$sForm]);
        $oFieldSel->order('user_form_field.sort_id ASC');

        # Get User Based Fields
        $aFieldsDB = CoreController::$aCoreTables['form-field']->selectWith($oFieldSel);
        $aFieldsByTab = [];
        if(count($aFieldsDB) > 0) {
            foreach($aFieldsDB as $oField) {
                if(!array_key_exists($oField->tab,$aFieldsByTab)) {
                    $aFieldsByTab[$oField->tab] = [];
                }
                $aFieldsByTab[$oField->tab][] = $oField;
            }
            return $aFieldsByTab;
        } else {
            return [];
        }
    }

    /**
     * Set Fields for current Form
     *
     * @param string $sForm
     * @since 1.0.0
     */
    public function setFormFields($sForm) {
        $this->layout()->aFormFieldsByTab = $this->getUserFormFields($sForm);
    }

    /**
     * Get all possible fields for form
     * or all forms
     *
     * @param string $sForm
     * @param array $aExcludeTypes
     * @param string $sOnlyType get only this type
     * @return array
     * @since 1.0.0
     */
    public function getFormFields($sForm = '',$aExcludeTypes = [],$sOnlyType = '') {
        # Build Query to get User Based Formfields
        $oFieldSel = new Select(CoreController::$aCoreTables['core-form-field']->getTable());
        $aWhere = [];
        if($sForm != '') {
            $aWhere['form'] = $sForm;
        }
        if($sOnlyType != '') {
            $aWhere['type'] = $sOnlyType;
        }
        $oFieldSel->where($aWhere);
        //$oFieldSel->order('user_form_field.sort_id ASC');

        # Get Form Based Fields
        $aFieldsDB = CoreController::$aCoreTables['core-form-field']->selectWith($oFieldSel);
        $aFields = [];
        if(count($aFieldsDB) > 0) {
            foreach($aFieldsDB as $oField) {
                # Order By Forms if all forms
                if($sForm == '') {
                    if(!array_key_exists($oField->form,$aFields)) {
                        $oForm = CoreController::$aCoreTables['core-form']->select(['form_key'=>$oField->form]);
                        if(count($oForm) > 0) {
                            $aFields[$oField->form] = [
                                'oForm' => $oForm->current(),
                                'aFields' => [],
                            ];
                        }
                    }
                    $aFields[$oField->form]['aFields'][] = $oField;
                } else {
                    if(!array_key_exists($oField->type,$aExcludeTypes)) {
                        $aFields[] = $oField;
                    }
                }
            }
        }
        return $aFields;
    }

    /**
     * Get Columns for current View
     *
     * @param string $sView
     * @return array
     * @since 1.0.0
     */
    public function getIndexColumns($sView) {
        # Build Query to get User Based Columns
        $oColumnSel = new Select(CoreController::$aCoreTables['table-col']->getTable());
        $oColumnSel->join(['core_field'=>'core_form_field'],'core_field.Field_ID = user_table_column.field_idfs');
        $oColumnSel->where(['user_idfs'=>CoreController::$oSession->oUser->getID(),'user_table_column.tbl_name'=>$sView]);
        $oColumnSel->order('sortID ASC');

        # Get User Based Fields
        $aColumnsDB = CoreController::$aCoreTables['table-col']->selectWith($oColumnSel);
        $aColumns = [];
        if(count($aColumnsDB) > 0) {
            foreach($aColumnsDB as $oCol) {
                $aColumns[] = $oCol;
            }
            return $aColumns;
        } else {
            return [];
        }
    }

    /**
     * Set Columns for current View
     *
     * @param string $sView
     * @since 1.0.0
     */
    public function setIndexColumns($sView) {
        $this->layout()->aIndexColumns = $this->getIndexColumns($sView);
    }

    /**
     * Get Index Tables
     *
     * @return array
     * @since 1.0.0
     */
    public function getIndexTables() {
        # Get Index Tables
        $aIndexTables = [];
        $oTablesDB = CoreController::$aCoreTables['table-index']->select();
        foreach($oTablesDB as $oTbl) {
            $aIndexTables[] = $oTbl;
        }
        return $aIndexTables;
    }

    /**
     * Get Index Tables with
     * all possible fields (columns)
     *
     * @return array
     * @since 1.0.0
     */
    public function getIndexTablesWithColumns() {
        $aTablesWithColumns = [];

        # Get Tables
        $aTables = $this->getIndexTables();

        # Get Fields for Tables
        foreach($aTables as $oTbl) {
            # Get Fields but filter unnecessary types
            $aFields = $this->getFormFields($oTbl->form,['partial'=>true,'password'=>true]);
            $aTablesWithColumns[$oTbl->table_name] = ['oTable'=>$oTbl,'aFields'=>$aFields];
        }

        return $aTablesWithColumns;
    }

    /**
     * Set Entity for current View
     *
     * @param mixed $oItem
     * @since 1.0.0
     */
    public function setViewEntity($oItem) {
        $this->layout()->oItem = $oItem;
    }

    /**
     * Get Tabs for all Forms
     *
     * @return array
     * @since 1.0.0
     */
    public function getFormTabs() {
        $aTabsByForms = [];

        # Get Tabs from Database
        $oTabsDB = CoreController::$aCoreTables['core-form-tab']->select();
        foreach($oTabsDB as $oTab) {
            # Order by Form
            if(!array_key_exists($oTab->form,$aTabsByForms)) {
                # Load Form Info
                $oForm = CoreController::$aCoreTables['core-form']->select(['form_key'=>$oTab->form]);
                if(count($oForm) > 0) {
                    $aTabsByForms[$oTab->form] = [
                        'oForm'=>$oForm->current(),
                        'aTabs'=>[],
                    ];
                }
            }
            $aTabsByForms[$oTab->form]['aTabs'][$oTab->Tab_ID] = $oTab;
        }

        return $aTabsByForms;
    }

    /**
     * Load all permissions based on Modules
     *
     * @return array
     * @since 1.0.0
     */
    protected function getPermissions() {
        $aPermissionsByModules = [];

        # Load Permissions from database
        $oPermsFromDB = CoreController::$aCoreTables['permission']->select();
        foreach($oPermsFromDB as $oPerm) {
            $sModule = str_replace(['\\'],['-'],$oPerm->module);
            # Order by module
            if(!array_key_exists($sModule,$aPermissionsByModules)) {
                $aPermissionsByModules[$sModule] = [];
            }
            $aPermissionsByModules[$sModule][] = $oPerm;
        }

        return $aPermissionsByModules;
    }

    /**
     * Set Partial Data in Layout
     *
     * @param $sPartial
     * @param $aData
     * @since 1.0.0
     */
    protected function setPartialData($sPartial,$aData) {
        $aPartialData = [];
        if(!isset($this->layout()->aPartialData)) {
            $this->layout()->aPartialData = [];
        }
        $aPartialData[$sPartial] = array_merge($aData);
        $this->layout()->aPartialData = array_merge($this->layout()->aPartialData,$aPartialData);
    }

    /**
     * Parse Raw Data from Form based on DB Fields
     *
     * @param array $aRawData
     * @return array parsed Form Data
     * @since 1.0.0
     */
    protected function parseFormData(array $aRawData) {
        $aFormData = [];

        foreach(array_keys($aRawData) as $sKey) {
            $sFieldName = substr($sKey,strlen($this->sSingleForm.'_'));
            $oField = CoreController::$aCoreTables['core-form-field']->select(['form'=>$this->sSingleForm,'fieldkey'=>$sFieldName]);
            if(count($oField) > 0) {
                $oField = $oField->current();
                switch($oField->type) {
                    case 'password':
                        $aFormData[$sFieldName] = password_hash($_REQUEST[$sKey],PASSWORD_DEFAULT);
                        break;
                    case 'datetime':
                        $aFormData[$sFieldName] = $_REQUEST[$sKey].' '.$_REQUEST[$sKey.'-time'];
                        break;
                    default:
                        $aFormData[$sFieldName] = $_REQUEST[$sKey];
                        break;
                }
            }
        }

        return $aFormData;
    }

    /**
     * Attach Raw Data from Form based on DB Fields
     *
     * @param array $aRawData
     * @param mixed $oEntity
     * @return mixed Entity with data from Form
     * @since 1.0.0
     */
    protected function attachFormData(array $aRawData,$oEntity) {
        foreach(array_keys($aRawData) as $sKey) {
            $sFieldName = substr($sKey,strlen($this->sSingleForm.'_'));
            $oField = CoreController::$aCoreTables['core-form-field']->select(['form'=>$this->sSingleForm,'fieldkey'=>$sFieldName]);
            if(count($oField) > 0) {
                $oField = $oField->current();
                switch($oField->type) {
                    case 'password':
                        // DO NEVER UPDATE PASSWORD
                        break;
                    case 'datetime':
                        if(!$oEntity->setTextField($sFieldName,$aRawData[$sKey].' '.$aRawData[$sKey.'-time'])) {
                            echo 'could not save field '.$sFieldName;
                        }
                        break;
                    default:
                        if(!$oEntity->setTextField($sFieldName,$aRawData[$sKey])) {
                            echo 'could not save field '.$sFieldName;
                        }
                        break;
                }
            }
        }

        return $oEntity;
    }

    /**
     * Sets Layout based on theme
     *
     * @param string $sLayout Module or Custom Layout
     * @since 1.0.0
     */
    protected function setThemeBasedLayout(string $sLayout) {
        # Check if Theme based module layout exists
        $template = 'layout/'.$sLayout.'-'.CoreController::$oSession->oUser->getTheme();
        $resolver = $this->getEvent()
            ->getApplication()
            ->getServiceManager()
            ->get('Laminas\View\Resolver\TemplatePathStack');

        if (false === $resolver->resolve($template)) {
            # Theme not found in module

            # check if theme exists in general
            $template = 'layout/layout-'.CoreController::$oSession->oUser->getTheme();
            $resolver = $this->getEvent()
                ->getApplication()
                ->getServiceManager()
                ->get('Laminas\View\Resolver\TemplatePathStack');

            if (false === $resolver->resolve($template)) {
                # Theme not found at all

                # Set default layout
                $this->layout('layout/layout-default');
            } else {
                $this->layout('layout/layout-'.CoreController::$oSession->oUser->getTheme());
            }
        } else {
            $this->layout('layout/'.$sLayout.'-'.CoreController::$oSession->oUser->getTheme());
        }
    }

    protected function updateMultiSelectFields($aRawFormData,$oItem,$sForm,$sEntityTypeOverWrite = '') {
        $aFields = $this->getFormFields($sForm,[],'multiselect');
        $sEntityType = ($sEntityTypeOverWrite != '') ? $sEntityTypeOverWrite : explode('-',$sForm)[0];
        if(count($aFields) > 0) {
            # lets loop over all multiselect fields of this form
            foreach($aFields as $oField) {
                # lets see if we find data for this field
                if(array_key_exists($sForm.'_'.$oField->fieldkey,$aRawFormData)) {
                    # Reset all tags for this entity
                    CoreController::$aCoreTables['core-entity-tag-entity']->delete([
                        'entity_idfs'=>$oItem->getID(),
                        'entity_type'=>$sEntityType,
                    ]);
                    # save new tags for entity
                    if(count($aRawFormData[$sForm.'_'.$oField->fieldkey]) > 0) {
                        foreach($aRawFormData[$sForm.'_'.$oField->fieldkey] as $iVal) {
                            CoreController::$aCoreTables['core-entity-tag-entity']->insert([
                                'entity_idfs'=>$oItem->getID(),
                                'entity_tag_idfs'=>$iVal,
                                'entity_type'=>$sEntityType,
                            ]);
                        }
                    }
                }
            }
        }
    }

    protected function logPerfomance($sAction,$fUtime,$fStime) {
        CoreController::$aCoreTables['core-log-performance']->insert([
            'action'=>$sAction,
            'utime'=>(float)$fUtime,
            'stime'=>(float)$fStime,
            'date'=>date('Y-m-d H:i:s',time()),
        ]);
        $this->layout()->recentPerfU = (float)$fUtime;
        $this->layout()->recentPerfS = (float)$fStime;
    }

    protected function rutime($ru, $rus, $index) {
        return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
            -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
    }
}