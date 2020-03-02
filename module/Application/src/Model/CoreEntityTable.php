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

use Application\Controller\CoreController;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Paginator\Paginator;
use Laminas\Paginator\Adapter\DbSelect;

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
                        case 'upload':
                        case 'tel':
                        case 'url':
                            $aData[$sFieldName] = $oObject->$sFieldName;
                            break;
                        case 'currency':
                            $aData[$sFieldName] = (float)$oObject->$sFieldName;
                            break;
                        case 'select':
                        case 'hidden':
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

    /**
     * Fetch All Skeleton Entities based on Filters
     *
     * @param bool $bPaginated
     * @param array $aWhere
     * @return Paginator Paginated Table Connection
     * @since 1.0.0
     */
    public function fetchAll($bPaginated = false,$aWhere = []) {
        $oSel = new Select($this->oTableGateway->getTable());

        # Build where
        $oWh = new Where();
        foreach(array_keys($aWhere) as $sWh) {
            $bIsLike = stripos($sWh,'-like');
            if($bIsLike === false) {

            } else {
                # its a like
                $oWh->like(substr($sWh,0,strlen($sWh)-strlen('-like')),$aWhere[$sWh].'%');
            }

            $bIsIDFS = stripos($sWh,'_idfs');
            if($bIsIDFS === false) {

            } else {
                # its a like
                $oWh->equalTo($sWh,$aWhere[$sWh]);
            }
        }
        if(array_key_exists('created_by',$aWhere)) {
            $oWh->equalTo('created_by',$aWhere['created_by']);
        }
        if(array_key_exists('modified_by',$aWhere)) {
            $oWh->equalTo('modified_by',$aWhere['modified_by']);
        }
        if(array_key_exists('multi_tag',$aWhere)) {
            $oSel->join(['category_tag'=>'core_entity_tag_entity'],'category_tag.entity_idfs = article.Article_ID');
            $oWh->equalTo('category_tag.entity_tag_idfs',$aWhere['multi_tag']);
            $oWh->like('category_tag.entity_type',explode('-',$this->sSingleForm)[0]);
        }
        $oSel->where($oWh);
        $oSel->order('created_date DESC');

        # Return Paginator or Raw ResultSet based on selection
        if ($bPaginated) {
            # Create result set for user entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype($this->generateNew());

            # Create a new pagination adapter object
            $oPaginatorAdapter = new DbSelect(
            # our configured select object
                $oSel,
                # the adapter to run it against
                $this->oTableGateway->getAdapter(),
                # the result set to hydrate
                $resultSetPrototype
            );
            # Create Paginator with Adapter
            $oPaginator = new Paginator($oPaginatorAdapter);
            return $oPaginator;
        } else {
            $oResults = $this->oTableGateway->selectWith($oSel);
            return $oResults;
        }
    }

    /**
     * Get Skeleton Entity
     *
     * @param int $id
     * @param string $sKey custom key
     * @return mixed
     * @since 1.0.0
     */
    public function getSingleEntity($id,$sKey = 'Skeleton_ID') {
        $id = (int) $id;
        $rowset = $this->oTableGateway->select([$sKey => $id]);
        $row = $rowset->current();
        if (! $row) {
            throw new \RuntimeException(sprintf(
                'Could not find skeleton with identifier %d',
                $id
            ));
        }

        return $row;
    }

    /**
     * Save Skeleton Entity
     *
     * @param $oSkeleton
     * @return int Skeleton ID
     * @since 1.0.0
     */
    public function saveSingleEntity($oSkeleton,$sIDKey = 'Skeleton_ID',$aDefaultData = []) {
        $aData = [
            'label' => $oSkeleton->label,
        ];

        $aData = $this->attachDynamicFields($aData,$oSkeleton);

        $id = (int) $oSkeleton->id;

        $iUserID = (isset(CoreController::$oSession->oUser)) ? CoreController::$oSession->oUser->getID() : 0;

        if ($id === 0) {
            # Add Metadata
            $aData['created_by'] = $iUserID;
            $aData['created_date'] = date('Y-m-d H:i:s',time());
            $aData['modified_by'] = $iUserID;
            $aData['modified_date'] = date('Y-m-d H:i:s',time());

            # Insert Skeleton
            $this->oTableGateway->insert($aData);

            # Return ID
            return $this->oTableGateway->lastInsertValue;
        }

        # Check if Skeleton Entity already exists
        try {
            $this->getSingle($id);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException(sprintf(
                'Cannot update skeleton with identifier %d; does not exist',
                $id
            ));
        }

        # Update Metadata
        $aData['modified_by'] = $iUserID;
        $aData['modified_date'] = date('Y-m-d H:i:s',time());

        # Update Skeleton
        $this->oTableGateway->update($aData, [$sIDKey => $id]);

        return $id;
    }

    /**
     * Generate daily stats for skeleton
     *
     * @since 1.0.5
     */
    public function generateDailyStats() {
        # get all skeletons
        $iTotal = count($this->fetchAll(false));
        # get newly created skeletons
        $iNew = count($this->fetchAll(false,['created_date-like' => date('Y-m-d',time())]));

        $aDailyStatData = ['new'=>$iNew,'total'=>$iTotal];

        $oStateTag = CoreController::$aCoreTables['core-tag']->select(['tag_key' => 'state']);
        if(count($oStateTag) > 0) {
            $oState = $oStateTag->current();
            $aEntityStatesDB = CoreController::$aCoreTables['core-entity-tag']->select([
                'tag_idfs' => $oState->Tag_ID,
                'entity_form_idfs' => $this->sSingleForm,
            ]);
            if(count($aEntityStatesDB) > 0) {
                foreach($aEntityStatesDB as $oState) {
                    $iStateCount = count($this->fetchAll(false,['state_idfs' => $oState->Entitytag_ID]));
                    $aDailyStatData[strtolower(utf8_encode($oState->tag_value))] = $iStateCount;
                }
            }
        }


        # add statistics
        CoreController::$aCoreTables['core-statistic']->insert([
            'stats_key'=>explode('-',$this->sSingleForm)[0].'-daily',
            'data'=>json_encode($aDailyStatData),
            'date'=>date('Y-m-d H:i:s',time()),
        ]);
    }

    public function updateAttribute($sAttribute,$sVal,$sIDKey,$iEntityID) {
        $this->oTableGateway->update([$sAttribute => $sVal], [$sIDKey => $iEntityID]);
    }
}