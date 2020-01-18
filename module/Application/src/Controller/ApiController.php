<?php
/**
 * ApiController.php - Api Controller
 *
 * Main Controller API for all Modules
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

namespace Application\Controller;
use Laminas\View\Model\ViewModel;

class ApiController extends CoreController {

    public static $bSecurityCheckPassed = false;
    /**
     * API Home - Main Index
     *
     * @return mixed - Redirect to Module API or Welcome Message
     * @since 1.0.0
     */
    public function indexAction() {
        $this->layout('layout/json');

        $sModule = $this->params()->fromRoute('module','core');
        if($sModule != 'core') {
            # Redirect to Modules API
            return $this->redirect()->toRoute($sModule.'-api',['action'=>'index'],['query'=>['authkey'=>$_REQUEST['authkey']]]);
        } else {
            # Welcome Message to API
            $aReturn = ['state'=>'success','message'=>'Welcome to onePlace API'];
            echo json_encode($aReturn);
            return false;
        }
    }

    /**
     * List Action - List all Entities of selected Module
     *
     * @return bool - no View File
     * @since 1.0.0
     */
    public function listAction() {
        $this->layout('layout/json');

        $sModule = $this->params()->fromRoute('module','core');
        $iID = $this->params()->fromRoute('id',0);
        return $this->redirect()->toRoute($sModule.'-api',['action'=>'list','id'=>$iID],['query'=>['authkey'=>$_REQUEST['authkey']]]);
    }

    /**
     * List Action - List all Entities of selected Module
     *
     * @return bool - no View File
     * @since 1.0.0
     */
    public function getAction() {
        $this->layout('layout/json');

        $sModule = $this->params()->fromRoute('module','core');
        $iID = $this->params()->fromRoute('id',0);
        return $this->redirect()->toRoute($sModule.'-api',['action'=>'get','id'=>$iID],['query'=>['authkey'=>$_REQUEST['authkey']]]);
    }
}
