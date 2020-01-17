<?php
/**
 * CoreEntityTable.php - Core Table
 *
 * Basic Model for all onePlace Table Entities
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

class CoreEntityTable {
    /**
     * Table Object
     *
     * @var TableGateway
     */
    protected $oTableGateway;

    /**
     * Single Form Name
     *
     * @var string Name of Single Form
     * @since 1.0.0
     */
    protected $sSingleForm;

    /**
     * CoreEntityTable constructor.
     *
     * @param TableGateway $tableGateway
     * @since 1.0.0
     */
    public function __construct(TableGateway $tableGateway) {
        $this->oTableGateway = $tableGateway;
    }

    /**
     * Attach Dynamic Fields to Data
     *
     * @param array $aData
     * @param mixed $oObject
     * @return array data merged with dynamic fields
     * @since 1.0.0
     */
    protected function attachDynamicFields(array $aData,$oObject) {
        # Get Dynamic Entity Fields from Database
        $oMyFieldsDB = CoreEntityModel::$aEntityTables['core-form-fields']->select(['form'=>$this->sSingleForm]);
        if(count($oMyFieldsDB) > 0) {
            foreach($oMyFieldsDB as $oField) {
                $sFieldName = $oField->fieldkey;
                $sFieldDefVal = '';
                if(!array_key_exists($sFieldName,$aData) && property_exists($oObject,$sFieldName)) {
                    # Assign Value from Object to Data based on type
                    switch($oField->type) {
                        case 'text':
                        case 'textarea':
                        case 'email':
                        case 'tel':
                            $aData[$sFieldName] = $oObject->$sFieldName;
                            break;
                        case 'date':
                        case 'datetime':
                            if($oObject->$sFieldName != '' && $oObject->$sFieldName != '0000-00-00 00:00:00') {
                                $aData[$sFieldName] = date('Y-m-d H:i:s',strtotime($oObject->$sFieldName));
                            } else {
                                $aData[$sFieldName] = '0000-00-00 00:00:00';
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        return $aData;
    }
}