<?php
/**
 * CoreEntityModel.php - Core Entity
 *
 * Basic Entity for all onePlace Single Entities
 *
 * @category Model
 * @package Application
 * @author Verein onePlace
 * @copyright (C) 2020 Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

namespace Application\Model;

use Application\Controller\CoreController;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class CoreEntityModel {
    /**
     * Entity ID
     *
     * @var int
     * @since 1.0.0
     */
    public $id;

    /**
     * Database Connection
     *
     * @var AdapterInterface open connection to database
     * @since 1.0.0
     */
    public static $oDbAdapter;

    /**
     * Entity TableGateways
     *
     * @var array Cached TableGateways for Entity
     * @since 1.0.0
     */
    public static $aEntityTables = [];

    /**
     * Single Form Name
     *
     * @var string Name of Single Form
     * @since 1.0.0
     */
    protected $sSingleForm;

    public $created_by;

    public $created_date;

    public $modified_by;

    public $modified_date;

    /**
     * CoreEntityModel constructor.
     * @param AdapterInterface $oDbAdapter
     * @since 1.0.0
     */
    public function __construct($oDbAdapter) {
        CoreEntityModel::$oDbAdapter = $oDbAdapter;

        # Core Form Fields
        if(!isset(CoreEntityModel::$aEntityTables['core-form-fields'])) {
            CoreEntityModel::$aEntityTables['core-form-fields'] = new TableGateway('core_form_field', CoreEntityModel::$oDbAdapter);
        }
    }

    /**
     * Get Entity ID as int
     * @return int
     * @since 1.0.0
     */
    public function getID() {
        return $this->id;
    }

    /**
     * Get Entity Label as String
     *
     * @return string
     * @since 1.0.0
     */
    public function getLabel() {
        return $this->label;
    }

    /**
     * Get Value for Text Field
     *
     * @param string $sField
     * @return bool
     * @since 1.0.0
     */
    public function getTextField($sField) {
        if(property_exists($this,$sField)) {
            return $this->$sField;
        } else {
            return false;
        }
    }

    public function getCurrencyField($sField) {
        if(property_exists($this,$sField)) {
            if (array_key_exists('article-currency', CoreController::$aGlobalSettings)) {
                if (CoreController::$aGlobalSettings['article-currency'] == 'EUR') {
                    $iVal = str_replace([',', '.'], ['.', ','], $this->$sField);
                    return $iVal;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Set new Value for Text Field
     *
     * @param string $sField
     * @param string $sValue
     * @return bool
     * @since 1.0.0
     */
    public function setTextField($sField,$sValue) {
        # Only Set Value of Property already exists
        if(property_exists($this,$sField)) {
            $this->$sField = $sValue;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get Real Value (ID) of select field
     *
     * @param $sField name of field (fieldkey)
     * @return int ID of linked entity
     * @since 1.0.0
     */
    public function getSelectFieldID($sField) {
        # Only Set Value of Property already exists
        if(property_exists($this,$sField)) {
            return $this->$sField;
        } else {
            return 0;
        }
    }

    /**
     * Get linked object of select field
     *
     * @param $sField name of field (fieldkey)
     * @return bool|mixed false if not found, otherwise Entity Model
     * @since 1.0.0
     */
    public function getSelectField($sField) {
        # Only Set Value of Property already exists
        if(property_exists($this,$sField)) {
            $iSelectIDFS = $this->$sField;
            if($iSelectIDFS != 0) {
                $oField = CoreEntityModel::$aEntityTables['core-form-fields']->select(['fieldkey' => $sField,'form'=>$this->sSingleForm]);
                if (count($oField) > 0) {
                    $oField = $oField->current();
                    if($oField->tbl_cached_name != '') {
                        if (!array_key_exists($oField->tbl_cached_name, CoreEntityModel::$aEntityTables)) {
                            CoreEntityModel::$aEntityTables[$oField->tbl_cached_name] = CoreController::$oServiceManager->get($oField->tbl_class);
                            //CoreEntityModel::$aEntityTables[$oField->tbl_name] = CoreController::$oServiceManager->get('OnePlace\Contact\Model\ContactTable');
                        }
                        try {
                            return CoreEntityModel::$aEntityTables[$oField->tbl_cached_name]->getSingle($iSelectIDFS);
                        } catch(\RuntimeException $e) {
                            return false;
                        }

                    } else {
                        if($oField->tbl_class == 'OnePlace\BoolSelect') {
                            return ($this->$sField == 2) ? 'Yes' : 'No';
                        } else {
                            return $this->$sField;
                        }
                    }
                }
            }
        }

        # Item Not found
        return false;
    }

    /**
     * Get Real Values (IDs) of multiselect field
     *
     * @param $sField name of field (fieldkey)
     * @return array array with IDs of linked entity
     * @since 1.0.2
     */
    public function getMultiSelectFieldIDs($sField) {
        $oField = CoreController::$aCoreTables['core-form-field']->select([
            'fieldkey'=>$sField,
            'form'=>$this->sSingleForm
        ]);
        if(count($oField) > 0) {
            $oField = $oField->current();
            $sEntityType = explode('-',$this->sSingleForm)[0];
            $aFieldIDs = [];

            $sTagKey = $oField->tag_key;
            $bIsIDFS = stripos($sTagKey,'_idfs');
            if($bIsIDFS === false) {

            } else {
                # its a like
                $sTagKey = substr($sTagKey,0,strlen('_idfs'));
            }

            $oTag = CoreController::$aCoreTables['core-tag']->select(['tag_key'=>$sTagKey]);
            if(count($oTag) > 0) {
                $oTag = $oTag->current();

                $oMultiSel = new Select(CoreController::$aCoreTables['core-entity-tag-entity']->getTable());
                $oMultiSel->join(['core_entity_tag' => 'core_entity_tag'], 'core_entity_tag.Entitytag_ID = core_entity_tag_entity.entity_tag_idfs');
                $oMultiSel->where([
                    'core_entity_tag_entity.entity_idfs' => $this->getID(),
                    'core_entity_tag_entity.entity_type' => $sEntityType,
                    'core_entity_tag.tag_idfs' => $oTag->Tag_ID,
                ]);

                $oIDsFromDB = CoreController::$aCoreTables['core-entity-tag-entity']->selectWith($oMultiSel);
                if (count($oIDsFromDB) > 0) {
                    foreach ($oIDsFromDB as $oEntityTag) {
                        $aFieldIDs[] = $oEntityTag->entity_tag_idfs;
                    }
                }
            }

            return $aFieldIDs;

        } else {
            throw new \RuntimeException(sprintf(
                'Could not find field with identifier %s',
                $sField
            ));
        }
    }

    /**
     * Get array with linked objects of multiselect field
     *
     * @param $sField name of field (fieldkey)
     * @param bool $bReturnModels return array (for select2) or entity models
     * @return bool|array false if not found, otherwise array with Entity Models
     * @since 1.0.2
     */
    public function getMultiSelectField($sField,$bReturnModels = false) {
        $aEntityTagIDs = $this->getMultiSelectFieldIDs($sField);
        $aEntityModels = [];
        if(count($aEntityTagIDs) > 0) {
            foreach($aEntityTagIDs as $iEntityTagID) {
                $oEntityTag = CoreController::$aCoreTables['core-entity-tag']->select(['Entitytag_ID'=>$iEntityTagID]);
                if(count($oEntityTag) > 0) {
                    $oEntityTag = $oEntityTag->current();
                    if($bReturnModels) {
                        /*
                        //echo 'get form for '.$oEntityTag->tag_value;
                        try {
                           // $oForm = CoreController::$aCoreTables['core-form']->select(['form_key'=>$oEntityTag->entity_form_idfs]);
                            //if(count($oForm) > 0) {
                            //    $oForm = $oForm->current();
                            //}
                        } catch (ServiceNotFoundException $e) {

                        } */
                        //if(isset($oForm)) {
                            try {
                                $oEntityTable = CoreController::$oServiceManager->get(\OnePlace\Tag\Model\EntityTagTable::class);
                                $aEntityModels[] = $oEntityTable->getSingle($iEntityTagID);
                            } catch (ServiceNotFoundException $e) {

                            }
                        //}
                    } else {
                        // skeleton-single = \OnePlace\Skeleton\Model\SkeletonTable -> getSingle()
                        $aEntityModels[] = (object)['id'=>$iEntityTagID,'text'=>$oEntityTag->tag_value];
                    }
                }
            }
        }

        return $aEntityModels;
    }

    /**
     * Attach Dynamic Fields to Model
     *
     * @since 1.0.0
     */
    protected function attachDynamicFields() {
        # Get Dynamic Entity Fields from Database
        $oMyFieldsDB = CoreEntityModel::$aEntityTables['core-form-fields']->select(['form'=>$this->sSingleForm]);
        if(count($oMyFieldsDB) > 0) {
            foreach($oMyFieldsDB as $oField) {
                $sFieldName = $oField->fieldkey;
                if(!property_exists($this,$sFieldName)) {
                    # Assign Value from Object to Data based on type
                    switch($oField->type) {
                        case 'text':
                        case 'textarea':
                        case 'email':
                        case 'url':
                        case 'featuredimage':
                        case 'tel':
                        case 'upload':
                            $this->$sFieldName = '';
                            break;
                        case 'select':
                        case 'currency':
                        case 'hidden':
                        case 'number':
                        case 'boolselect':
                            $this->$sFieldName = 0;
                            break;
                        case 'date':
                        case 'datetime':
                            $this->$sFieldName = '0000-00-00 00:00:00';
                            break;
                        default:
                            break;
                    }
                }
            }
        }
    }

    /**
     * Update Entity Dynamic Fields based on data given
     *
     * @param array $aData
     * @since 1.0.0
     */
    protected function updateDynamicFields(array $aData) {
        foreach(array_keys($aData) as $sField) {
            if(property_exists($this,$sField)) {
                # todo: type based switch - but we dont have type here - we need to get field database entity here
                $this->$sField = $aData[$sField];
            }
        }
    }

    /**
     * Checks if Entity has featured image
     *
     * @return bool true if has, false otherwise
     * @since 1.0.5
     */
    public function hasFeaturedImage() {
        if(property_exists($this,'featured_image')) {
            return true;
        } else {
            return false;
        }
    }
}