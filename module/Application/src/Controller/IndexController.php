<?php
/**
 * IndexController.php - Index Controller
 *
 * Main Controller Application Module
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
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Adapter\Adapter;

class IndexController extends CoreController {
    /**
     * Application Home - Main Index
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function indexAction() {
        # Set Layout based on users theme
        $this->layout('layout/layout-'.CoreController::$oSession->oUser->getTheme());

        $aMeasureEnd = getrusage();
        $this->logPerfomance('application-index',$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime"),$this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime"));

        return new ViewModel([
            'oUser'=>CoreController::$oSession->oUser,
        ]);
    }

    public function themesAction() {
        $this->setThemeBasedLayout('application');

        return new ViewModel([]);
    }

    public function updateAction() {
        $oRequest = $this->getRequest();

        if(!$oRequest->isPost()) {
            $this->setThemeBasedLayout('application');

            $aInfo = [
                'install' => [],
                'update' => [],
            ];

            foreach(glob($_SERVER['DOCUMENT_ROOT'].'/../vendor/oneplace/*', GLOB_ONLYDIR) as $sModulePath) {
                $sModule = basename($sModulePath);
                $sModuleName = explode('-',$sModule)[1];

                if($sModuleName == 'tag') {
                    $sModuleName = 'core_tag';
                }

                try {
                    $oBaseTbl = new TableGateway($sModuleName,CoreController::$oDbAdapter);
                    $oBaseTbl->select();
                } catch(\RuntimeException $e) {
                    $aInfo['install'][] = $sModuleName;
                }
            }

            return new ViewModel([
                'aInfo'=>$aInfo,
            ]);
        } else {
            $aInfo = [
                'install' => [],
                'update' => [],
            ];

            foreach(glob($_SERVER['DOCUMENT_ROOT'].'/../vendor/oneplace/*', GLOB_ONLYDIR) as $sModulePath) {
                $sModule = basename($sModulePath);
                $sModuleName = explode('-',$sModule)[1];

                if($sModuleName == 'tag') {
                    $sModuleName = 'core_tag';
                }

                try {
                    $oBaseTbl = new TableGateway($sModuleName,CoreController::$oDbAdapter);
                    $oBaseTbl->select();
                } catch(\RuntimeException $e) {
                    $aInfo['install'][] = $sModule;
                }
            }

            $this->layout('layout/json');
            foreach($aInfo['install'] as $sInstallMod) {
                # Core DB Structure
                $filename = $_SERVER['DOCUMENT_ROOT'] . '/../vendor/oneplace/'.$sInstallMod.'/data/install.sql';
                echo 'update '.$sInstallMod;
                if (file_exists($filename)) {
                    echo 'go';
                    $this->parseSQLInstallFile($filename, CoreController::$oDbAdapter);
                }
            }

            return $this->redirect()->toRoute('home');
        }
    }

    /**
     * Add new theme to oneplace
     *
     * @return \Laminas\Http\Response|ViewModel
     */
    public function addthemeAction() {
        $this->setThemeBasedLayout('application');

        # Check if zip extension is loaded
        if(!extension_loaded('zip')) {
            $this->flashMessenger()->addErrorMessage('You need php-zip extension enabled on your webserver to add new themes');
            $this->redirect()->toRoute('application',['action'=>'themes']);
        }

        $oRequest = $this->getRequest();

        if(!$oRequest->isPost()) {
            return new ViewModel([]);
        } else {
            $this->layout('layout/json');

            /**
             * Upload ZIP File
             */
            $filename = $_FILES["zip_file"]["name"];
            $source = $_FILES["zip_file"]["tmp_name"];
            $type = $_FILES["zip_file"]["type"];

            # Check file extension
            $name = explode(".", $filename);
            $accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
            foreach($accepted_types as $mime_type) {
                if($mime_type == $type) {
                    $okay = true;
                    break;
                }
            }

            # allow only zip
            $continue = strtolower($name[1]) == 'zip' ? true : false;
            if(!$continue) {
                $message = "The file you are trying to upload is not a .zip file. Please try again.";
            }

            # create theme dir if its the first theme
            if(!is_dir($_SERVER['DOCUMENT_ROOT'].'/../data/themes')) {
                mkdir($_SERVER['DOCUMENT_ROOT'].'/../data/themes');
            }

            # upload theme
            $target_path = $_SERVER['DOCUMENT_ROOT'].'/themes/'.$filename;  // change this to the correct site path
            if(move_uploaded_file($source, $target_path)) {
                # unzip theme
                $zip = new \ZipArchive();
                $x = $zip->open($target_path);
                if ($x === true) {
                    $zip->extractTo($_SERVER['DOCUMENT_ROOT'].'/themes/'); // change this to the correct site path
                    $zip->close();

                    unlink($target_path);
                }
                $message = "Your .zip file was uploaded and unpacked.";
            } else {
                $message = "There was a problem with the upload. Please try again.";
            }

            echo $message;

            # todo: theme name MUST be dynamic - define how to determine correct name, maybe add a json to theme
            # because currently we only have zip name as indicator which is like nothing
            $sThemeName = 'vuze';

            # Install Layouts
            foreach(glob($_SERVER['DOCUMENT_ROOT'].'/themes/'.$sThemeName.'/view/layout/*',GLOB_NOSORT) as $sThemeFile) {
                rename($sThemeFile,$_SERVER['DOCUMENT_ROOT'].'/../module/Application/view/layout/'.basename($sThemeFile));
            }
            # Install Partials
            foreach(glob($_SERVER['DOCUMENT_ROOT'].'/themes/'.$sThemeName.'/view/partial/*',GLOB_NOSORT) as $sThemeFile) {
                rename($sThemeFile,$_SERVER['DOCUMENT_ROOT'].'/../module/Application/view/partial/'.basename($sThemeFile));
            }

            echo 'theme files installed';

            return $this->redirect()->toRoute('application',['action'=>'themes']);
        }
    }

    /**
     * Parse SQL File from Installer and save to database
     *
     * @param string $sFile location of sql file
     * @param AdapterInterface $oAdapter database connection
     * @since 1.0.2.1
     */
    private function parseSQLInstallFile($sFile,$oAdapter) {
        $templine = '';
        $lines = file($sFile);
        // Loop through each line
        foreach ($lines as $line)  {
            if (substr($line, 0, 2) == '--' || $line == '')
                continue;
            // Add this line to the current segment
            $templine .= $line;
            // If it has a semicolon at the end, it's the end of the query
            if (substr(trim($line), -1, 1) == ';')
            {
                $results = $oAdapter->query($templine, $oAdapter::QUERY_MODE_EXECUTE);
                $templine = '';
            }
        }
    }

    /**
     * Server-Side for Filepond Upload
     *
     * @return bool
     * @since 1.0.7
     */
    public function filepondAction() {
        $this->layout('layout/json');

        $oRequest = $this->getRequest();

        if($oRequest->isPost()) {
            $iEntityID = (int)$_REQUEST['entity_id'];
            $sEntityType = $_REQUEST['type'];

            $sPath = '';
            $oEntityTbl = false;
            switch($sEntityType) {
                case 'skeleton':
                    $oEntityTbl = CoreController::$oServiceManager->get(\OnePlace\Skeleton\Model\SkeletonTable::class);
                    $oEntity = $oEntityTbl->getSingle($iEntityID);
                    if($oEntity) {
                        $sPath = $_SERVER['DOCUMENT_ROOT'].'/data/'.$sEntityType.'/'.$oEntity->getID().'/';
                    }
                    break;
                case 'user':
                    $oEntityTbl = CoreController::$oServiceManager->get(\OnePlace\User\Model\UserTable::class);
                    $oEntity = $oEntityTbl->getSingle($iEntityID);
                    if($oEntity) {
                        $sPath = $_SERVER['DOCUMENT_ROOT'].'/data/'.$sEntityType.'/'.$oEntity->getID().'/';
                    }
                    break;
                default:
                    $oForm = CoreController::$aCoreTables['core-form']->select(['form_key'=>$sEntityType.'-single']);
                    if(count($oForm) > 0) {
                        $oForm = $oForm->current();
                        $oEntityTbl = CoreController::$oServiceManager->get($oForm->entity_tbl_class);
                        $oEntity = $oEntityTbl->getSingle($iEntityID);
                        if($oEntity) {
                            $sPath = $_SERVER['DOCUMENT_ROOT'].'/data/'.$sEntityType.'/'.$oEntity->getID().'/';
                        }
                    }
                    break;
            }

            if($sPath != '') {
                if(!is_dir($sPath)) {
                    mkdir($sPath);
                }

                if(array_key_exists('filepond',$_FILES)) {
                    $aFile = $_FILES['filepond'];
                    if(move_uploaded_file($aFile['tmp_name'],$sPath.'/'.$aFile['name'])) {
                        switch($sEntityType) {
                            case 'user':
                                $oEntityTbl->updateAttribute('featured_image', $aFile['name'], 'User_ID', $iEntityID);
                                break;
                            default:
                                if(is_object($oEntityTbl)) {
                                    $oEntityTbl->updateAttribute('featured_image', $aFile['name'], ucfirst($sEntityType).'_ID', $iEntityID);
                                }
                                break;
                        }
                    }
                }
            }
            return false;
        } else {
            var_dump($_REQUEST);

            return false;
        }
    }

    /**
     * Server Side for UPPY Fileupload
     *
     * @return bool
     * @since 1.0.7
     */
    public function uppyAction() {
        $this->layout('layout/json');

        $iEntityID = (int)$_REQUEST['entity_id'];
        $sEntityType = $_REQUEST['entity_type'];
        $sPath = '';
        $oEntityTbl = false;

        switch($sEntityType) {
            default:
                $oForm = CoreController::$aCoreTables['core-form']->select(['form_key'=>$sEntityType.'-single']);
                if(count($oForm) > 0) {
                    $oForm = $oForm->current();
                    $oEntityTbl = CoreController::$oServiceManager->get($oForm->entity_tbl_class);
                    $oEntity = $oEntityTbl->getSingle($iEntityID);
                    if($oEntity) {
                        $sPath = $_SERVER['DOCUMENT_ROOT'].'/data/'.$sEntityType.'/'.$oEntity->getID().'/';
                    }
                    if(!is_dir($sPath)) {
                        mkdir($sPath);
                    }
                }
                $aFile = $_FILES['files'];
                if(move_uploaded_file($aFile['tmp_name'][0],$sPath.'/'.trim($aFile['name'][0]))) {
                    if(isset($oForm)) {
                        CoreController::$aCoreTables['core-gallery-media']->insert([
                            'filename'=>trim($aFile['name'][0]),
                            'entity_idfs'=>$iEntityID,
                            'entity_type'=>$sEntityType,
                            'is_public'=>0,
                            'created_by'=>CoreController::$oSession->oUser->getID(),
                            'created_date'=>date('Y-m-d H:i:s',time()),
                            'modified_by'=>CoreController::$oSession->oUser->getID(),
                            'modified_date'=>date('Y-m-d H:i:s',time()),
                            'sort_id'=>0,
                        ]);
                    }
                }
                break;
        }

        return false;
    }

    /**
     * Sorting for uppy galleries
     *
     * @return bool no viewfile. echo json
     * @since 1.0.12
     */
    public function updateuppysortAction() {
        $this->layout('layout/json');


        $oRequest = $this->getRequest();
        $aImagesToSort = $oRequest->getPost('images');

        $oGalleryTbl = CoreController::$aCoreTables['core-gallery-media'];

        $iSortID = 0;
        # Loop over all columns provided
        foreach($aImagesToSort as $sImgInfo) {
            $iMediaID = substr($sImgInfo,strlen('gallery-media-'));
            $oGalleryTbl->update(['sort_id'=>$iSortID],'Media_ID = '.$iMediaID);
            $iSortID++;
        }

        $aReturn = ['state'=>'success','message'=>'gallery successfully updated'];

        echo json_encode($aReturn);

        return false;
    }

    /**
     * Toggle Media Public Status
     *
     * @return \Laminas\Http\Response
     * @since 1.0.12
     */
    public function togglemediapubAction() {
        $this->layout('layout/json');

        $iMediaID = $this->params()->fromRoute('id',0);

        if($iMediaID != 0) {
            $oGalleryTbl = CoreController::$aCoreTables['core-gallery-media'];
            $oMedia = $oGalleryTbl->select(['Media_ID'=>$iMediaID]);
            if(count($oMedia) > 0) {
                $oMedia = $oMedia->current();
                $bPublic = ($oMedia->is_public == 1) ? 0 : 1;
                $oGalleryTbl->update(['is_public'=>$bPublic],'Media_ID = '.$iMediaID);

                return $this->redirect()->toRoute($oMedia->entity_type,['action'=>'view','id'=>$oMedia->entity_idfs]);

            }
        }

        return $this->redirect()->toRoute('home');
    }

    /**
     * Quicksearch across all registered entities
     *
     * @return bool
     * @since 1.0.12
     */
    public function quicksearchAction() {
        $this->layout('layout/json');

        $aResponse = [
            'results' => [],
        ];

        /**
         * Example DATA

        $aResponse = [
            'results' => [
                [
                    'text' => 'Articles',
                    'children' => [
                        ['id' => 1,'text' => 'Test Skel','view_link'=>'/article/view/##ID##'],
                        ['id' => 2,'text' => 'Test Skel 2','view_link'=>'/article/view/##ID##'],
                    ]
                ],
                [
                    'text' => 'Users',
                    'children' => [
                        ['id' => 1,'text' => 'Admin','view_link'=>'/user/view/##ID##'],
                    ]
                ]
            ],
        ];
         * */

        echo json_encode($aResponse);

        return false;
    }
}
