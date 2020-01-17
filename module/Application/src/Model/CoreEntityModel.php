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

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;

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
                        case 'tel':
                            $this->$sFieldName = '';
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
}