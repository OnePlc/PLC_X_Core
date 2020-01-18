<?php
/**
 * ApiController.php - Skeleton Api Controller
 *
 * Main Controller for Skeleton Api
 *
 * @category Controller
 * @package Application
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
use Skeleton\Model\SkeletonTable;
use Laminas\Db\Adapter\AdapterInterface;

class ApiController extends CoreController {
    /**
     * Skeleton Table Object
     *
     * @since 1.0.0
     */
    private $oTableGateway;

    /**
     * ApiController constructor.
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
     * API Home - Main Index
     *
     * @return bool - no View File
     * @since 1.0.0
     */
    public function indexAction() {
        $this->layout('layout/json');

        $aReturn = ['state'=>'success','message'=>'Welcome to onePlace Skeleton API'];
        echo json_encode($aReturn);

        return false;
    }

    /**
     * List all Entities of Skeletons
     *
     * @return bool - no View File
     * @since 1.0.0
     */
    public function listAction() {
        $this->layout('layout/json');

        /**
         * todo: enforce to use /api/skeleton instead of /skeleton/api so we can do security checks in main api controller
        if(!\Application\Controller\ApiController::$bSecurityCheckPassed) {
            # Print List with all Entities
            $aReturn = ['state'=>'error','message'=>'no direct access allowed','aItems'=>[]];
            echo json_encode($aReturn);
            return false;
        }
        **/

        $aItems = [];

        # Get All Skeleton Entities from Database
        $oItemsDB = $this->oTableGateway->fetchAll(false);
        if(count($oItemsDB) > 0) {
            foreach($oItemsDB as $oItem) {
                $aItems[] = $oItem;
            }
        }

        # Print List with all Entities
        $aReturn = ['state'=>'success','message'=>'List all Skeletons','aItems'=>$aItems];
        echo json_encode($aReturn);

        return false;
    }

    /**
     * Get a single Entity of Skeleton
     *
     * @return bool - no View File
     * @since 1.0.0
     */
    public function getAction() {
        $this->layout('layout/json');

        # Get Skeleton ID from route
        $iItemID = $this->params()->fromRoute('id', 0);

        # Try to get Skeleton
        try {
            $oItem = $this->oTableGateway->getSingle($iItemID);
        } catch (\RuntimeException $e) {
            # Display error message
            $aReturn = ['state'=>'error','message'=>'Skeleton not found','oItem'=>[]];
            echo json_encode($aReturn);
            return false;
        }

        # Print Entity
        $aReturn = ['state'=>'success','message'=>'Skeleton found','oItem'=>$oItem];
        echo json_encode($aReturn);

        return false;
    }
}
