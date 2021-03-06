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
use Laminas\View\View;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class IndexController extends CoreController
{

    /**
     * Home Router - go to Webpage or login or api
     * depending in mode this oneplace is working in
     *
     * @return mixed
     * @since 1.0.34
     */
    public function routeAction()
    {
        if(isset(CoreController::$aGlobalSettings['home-route'])) {
            return $this->redirect()->toRoute(CoreController::$aGlobalSettings['home-route']);
        } else {
            return $this->redirect()->toRoute('app-home');
        }
    }
    /**
     * Application Home - Main Index
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function indexAction()
    {
        if(!is_dir($_SERVER['DOCUMENT_ROOT'].'/vendor')) {
            $this->layout('layout/json');
            echo '<b style="color:red">Assets are missing. Please install them first.</b>';
            echo '<br>Switch to your onePlace Core Directory (default is /var/www/plc) and execute:';
            echo '<br><code>yarn install</code><br>Checkout <a href="https://docs.1plc.ch" target="_blank">onePlace Docs</a> for more info';
            echo '<br/><br/><a href="/logout">Logout</a>';
            return false;
        }
        # Set Layout based on users theme
        $this->layout('layout/layout-'.CoreController::$oSession->oUser->getTheme());

        $aMeasureEnd = getrusage();
        $sRuTime = $this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"utime");
        $sRsTime = $this->rutime($aMeasureEnd,CoreController::$aPerfomanceLogStart,"stime");
        $this->logPerfomance('application-index',$sRuTime,$sRsTime);

        return new ViewModel([
            'oUser'=>CoreController::$oSession->oUser,
        ]);
    }

    /**
     * Themes Overview
     *
     * @return ViewModel - View Object with Data from Controller
     * @since 1.0.18
     */
    public function themesAction()
    {
        $this->setThemeBasedLayout('application');

        return new ViewModel([]);
    }

    /**
     * System Update Page
     *
     * @return ViewModel - View Object with Data from Controller
     * @since 1.0.18
     */
    public function updateAction()
    {
        $oRequest = $this->getRequest();

        $oModTbl = new TableGateway('core_module', CoreUpdateController::$oDbAdapter);
        $oCurrentMod = $oModTbl->select([
            'module_key'=>'oneplace-core',
        ])->current();

        if(!$oRequest->isPost()) {
            return new ViewModel([
                'oCurrentMod' => $oCurrentMod,
            ]);
        }

        $sNewVer = \Application\Module::VERSION;
        $oModTbl->update([
            'version' => $sNewVer,
        ],[
            'module_key'=>'oneplace-core',
        ]);
        return $this->redirect()->toRoute('home');

    }

    /**
     * Add new theme to oneplace
     *
     * @return \Laminas\Http\Response|ViewModel
     */
    public function addthemeAction()
    {
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
    private function parseSQLInstallFile($sFile,$oAdapter)
    {
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
    public function filepondAction()
    {
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
     * Quicksearch across all registered entities
     *
     * @return array data for json response
     * @since 1.0.12
     */
    public function quicksearchAction()
    {
        $this->layout('layout/json');

        # Get Term from Query (usually Select2 jQuery Plugin)
        $sQueryTerm = (isset($_REQUEST['term'])) ? $_REQUEST['term'] : '';

        # Get all registered forms
        $aFormsRegistered = CoreController::$aCoreTables['core-form']->select();
        $aResults = [];
        $aProtectedForms = ['apikey-single'=>true];

        # if we have a search term and at least 1 form, lets start searching
        if(count($aFormsRegistered) > 0 && $sQueryTerm != '') {
            # loop over all forms (which all entity forms)
            foreach($aFormsRegistered as $oForm) {
                if(array_key_exists($oForm->form_key,$aProtectedForms)) {
                    continue;
                }
                # Get Entity Table for Form
                try {
                    $oEntityTbl = CoreController::$oServiceManager->get($oForm->entity_tbl_class);
                } catch(ServiceNotFoundException $e) {
                    //echo 'could not load entity table for form '.$oForm->label;
                }

                # Only proceed if we have an Entity Table
                if(isset($oEntityTbl)) {
                    $sEntityType = explode('-',$oForm->form_key)[0];

                    # Lets try to match by label like first
                    $aMatchedEntities = [];
                    if(array_key_exists('quicksearch-'.$sEntityType.'-likeall',CoreController::$aGlobalSettings)) {
                        $aMatchedEntities = $oEntityTbl->fetchAll(false,['label-lkall'=>$sQueryTerm]);
                    } else {
                        if(array_key_exists('quicksearch-'.$sEntityType.'-customlabel',CoreController::$aGlobalSettings)) {
                            $sCustomLabel = CoreController::$aGlobalSettings['quicksearch-'.$sEntityType.'-customlabel'];
                            if($sCustomLabel == 'disable') {
                                // skip this entity
                            } else {
                                $aMatchedEntities = $oEntityTbl->fetchAll(false,[$sCustomLabel.'-like'=>$sQueryTerm]);
                            }
                        } else {
                            $aMatchedEntities = $oEntityTbl->fetchAll(false,['label-like'=>$sQueryTerm]);
                        }
                    }

                    # check for custom search attributes
                    if(array_key_exists('quicksearch-'.$sEntityType.'-customattribute',CoreController::$aGlobalSettings)) {
                        $sCustomKey = CoreController::$aGlobalSettings['quicksearch-'.$sEntityType.'-customattribute'];
                        $aCustomMatch = $oEntityTbl->fetchAll(false,[$sCustomKey.'-lkall'=>$sQueryTerm]);
                        $aCustomSearchResults = [];
                        if(count($aMatchedEntities) > 0) {
                            foreach($aMatchedEntities as $oRes) {
                                $aCustomSearchResults[] = $oRes;
                            }
                        }
                        if(count($aCustomMatch) > 0) {
                            foreach($aCustomMatch as $oMat) {
                                $aCustomSearchResults[] = $oMat;
                            }
                        }

                        $aMatchedEntities = $aCustomSearchResults;
                    }

                    # add results
                    if(count($aMatchedEntities) > 0) {
                        # add category for select2 to group results by entity type
                        $aEntityResults = ['text'=>$oForm->label,'children'=>[]];

                        # special handling for matched entity tags (like categories and so on)
                        if($sEntityType == 'entitytag') {
                            # loop matched tags
                            foreach($aMatchedEntities as $oEntity) {
                                # get form for tag
                                $oEntityForm =  CoreController::$aCoreTables['core-form']->select(['form_key'=>$oEntity->entity_form_idfs]);
                                if(count($oEntityForm) > 0) {
                                    $oEntityForm = $oEntityForm->current();
                                    # get entity table for tag form (= linked entity e.G Category)
                                    try {
                                        $oTagEntityTbl = CoreController::$oServiceManager->get($oEntityForm->entity_tbl_class);
                                    } catch(\RuntimeException $e ) {
                                        echo 'cloud not load entity tag table for entity '.$oEntityForm->form_key;
                                    }

                                    # if all is present, continue
                                    if(isset($oTagEntityTbl)) {
                                        # we also need tag itself because we dont know its name
                                        $oTag = CoreController::$aCoreTables['core-tag']->select(['Tag_ID'=>$oEntity->tag_idfs]);
                                        if(count($oTag) > 0) {
                                            $oTag = $oTag->current();
                                            # lets get form field to determine if tag is used as single or multiselect
                                            $oEntityTagFormField = CoreController::$aCoreTables['core-form-field']->select([
                                                'form'=>$oEntityForm->form_key,
                                                'fieldkey'=>$oTag->tag_key
                                            ]);
                                            if(count($oEntityTagFormField) == 0) {
                                                $oEntityTagFormField = CoreController::$aCoreTables['core-form-field']->select([
                                                    'form'=>$oEntityForm->form_key,
                                                    'fieldkey'=>substr($oTag->tag_key,0,strlen($oTag->tag_key)-1).'ies',
                                                ]);
                                                if(count($oEntityTagFormField) == 0) {
                                                    $oEntityTagFormField = CoreController::$aCoreTables['core-form-field']->select([
                                                        'form'=>$oEntityForm->form_key,
                                                        'fieldkey'=>$oTag->tag_key.'_idfs',
                                                    ]);
                                                }
                                            }

                                            # if we found form field for tag, proceed
                                            if(count($oEntityTagFormField) > 0) {
                                                $oEntityTagFormField = $oEntityTagFormField->current();

                                                # lets match linked entities to tag based on field type
                                                switch($oEntityTagFormField->type) {
                                                    case 'select':
                                                        $aEntityTagResults = ['text'=>$oEntityForm->label,'text_append_by'=>$oTag->tag_label,'text_append_2'=>$oEntity->getLabel(),'children'=>[]];
                                                        $sFieldName = $oTag->tag_key.'_idfs';
                                                        $sTagEntityType = explode('-',$oEntityForm->form_key)[0];
                                                        $aMatchedTagEntities = $oTagEntityTbl->fetchAll(false,[$sFieldName=>$oEntity->getID()]);
                                                        if(count($aMatchedTagEntities) > 0) {
                                                            foreach($aMatchedTagEntities as $oTagEntity) {
                                                                $aEntityTagResults['children'][] = ['id'=>$oTagEntity->getID(),'text'=>$oTagEntity->getLabel(),'view_link'=>'/'.$sTagEntityType.'/view/##ID##'];
                                                            }
                                                        }
                                                        $aResults[] = $aEntityTagResults;
                                                        break;
                                                    case 'multiselect':
                                                        $aEntityTagResults = ['text'=>$oEntityForm->label,'text_append_by'=>$oTag->tag_label,'text_append_2'=>$oEntity->getLabel(),'children'=>[]];
                                                        $sTagEntityType = explode('-',$oEntityForm->form_key)[0];
                                                        $aMatchedTagEntities = $oTagEntityTbl->fetchAll(false,['multi_tag'=>$oEntity->getID()]);
                                                        if(count($aMatchedTagEntities) > 0) {
                                                            foreach($aMatchedTagEntities as $oTagEntity) {
                                                                $aEntityTagResults['children'][] = ['id'=>$oTagEntity->getID(),'text'=>$oTagEntity->getLabel(),'view_link'=>'/'.$sTagEntityType.'/view/##ID##'];
                                                            }
                                                        }
                                                        $aResults[] = $aEntityTagResults;
                                                        break;
                                                    default:
                                                        break;
                                                }

                                            }
                                        }
                                    }
                                }
                                //$aEntityResults['children'][] = ['id'=>$oEntity->getID(),'text'=>$oEntity->getLabel(),'view_link'=>'/'.$sEntityType.'/view/##ID##'];
                            }
                        } else {
                            # add matched entities to results
                            foreach($aMatchedEntities as $oEntity) {
                                $sLabel = $oEntity->getLabel();
                                if(array_key_exists('quicksearch-'.$sEntityType.'-customresultattribute',CoreController::$aGlobalSettings)) {
                                    $sAttribute = CoreController::$aGlobalSettings['quicksearch-'.$sEntityType.'-customresultattribute'];
                                    $oAttr = json_decode($sAttribute);
                                    if(is_object($oAttr)) {
                                        $aFields = $oAttr->fields;
                                        $sLabel .= ' (';
                                        foreach($aFields as $sFieldKey) {
                                            switch($oAttr->format) {
                                                case 'datetime':
                                                    $sLabel.= date('d.m.Y H:i',strtotime($oEntity->getTextField($sFieldKey))).$oAttr->seperator;
                                                    break;
                                                case 'date':
                                                    $sLabel.= date('d.m.Y',strtotime($oEntity->getTextField($sFieldKey))).$oAttr->seperator;
                                                    break;
                                                default:
                                                    $sLabel.= $oEntity->getTextField($sFieldKey).$oAttr->seperator;
                                                    break;
                                            }
                                        }
                                        # strip last sep
                                        $sLabel = substr($sLabel,0,strlen($sLabel)-strlen($oAttr->seperator));
                                        $sLabel .= ')';
                                    } else {
                                        $sLabel .= ' ('.$oEntity->getTextField($sAttribute).')';
                                    }
                                }
                                $sViewLink = (array_key_exists('quicksearch-'.$sEntityType.'-viewlink',CoreController::$aGlobalSettings))
                                    ? CoreController::$aGlobalSettings['quicksearch-'.$sEntityType.'-viewlink'] : '/'.$sEntityType.'/view/##ID##';
                                $aEntityResults['children'][] = ['id'=>$oEntity->getID(),'text'=>$sLabel,'view_link'=>$sViewLink];
                            }
                            $aResults[] = $aEntityResults;
                        }
                    }
                }
            }
        }

        # send results to view for final processing
        return [
            'aResults'=>$aResults,
        ];
    }

    public function updatefieldsortAction()
    {
        $this->setThemeBasedLayout('application');

        $oRequest = $this->getRequest();

        if(!$oRequest->isPost()) {
            $sFormKey = $this->params()->fromRoute('formname','none');
            $oForm = CoreController::$aCoreTables['core-form']->select(['form_key' => $sFormKey]);

            if(count($oForm) == 0) {
                echo 'form not found';
                return false;
            }

            $oForm = $oForm->current();

            # Add Links for Breadcrumb
            $this->layout()->aNavLinks = [
                (object)['label' => 'Form Field Sorting'],
                (object)['label' => $oForm->label],
            ];

            # Add Buttons for breadcrumb
            $this->setViewButtons('core-updateformsorting');

            $aMyFormFieldsDB = CoreController::$oSession->oUser->getMyFormFields();
            $oFormFieldsDB = [];
            if(count($aMyFormFieldsDB[$sFormKey]) > 0) {
                foreach(array_keys($aMyFormFieldsDB[$sFormKey]) as $iFieldID) {
                    $oFormFieldsDB[] = CoreController::$aCoreTables['core-form-field']->select(['Field_ID'=>$iFieldID])->current();
                }
            }
            $aFieldsByTabs = [];
            if(count($oFormFieldsDB) > 0) {
                foreach($oFormFieldsDB as $oField) {
                    if(!array_key_exists($oField->tab,$aFieldsByTabs)) {
                        $oTab = CoreController::$aCoreTables['core-form-tab']->select(['Tab_ID'=>$oField->tab]);
                        $sTabName = $oField->tab;
                        if(count($oTab) > 0) {
                            $oTab = $oTab->current();
                            $sTabName = $oTab->title.' - '.$oTab->subtitle;
                        }
                        $aFieldsByTabs[$oField->tab] = ['oTab'=>$oTab,'aFields'=>[]];
                    }
                    $aFieldsByTabs[$oField->tab]['aFields'][] = $oField;
                }
            }

            return new ViewModel([
                'oForm' => $oForm,
                'aFieldsByTabs' => $aFieldsByTabs,
            ]);
        } else {
            $this->layout('layout/json');

            //$sTabName = $oRequest->getPost('tab_name');
            $aNewFieldIDs = $oRequest->getPost('new_order');

            $iSortID = 0;
            foreach($aNewFieldIDs as $iFieldID) {
                $iFieldID = str_replace(['plc-formfield-'],[''],$iFieldID);
                if(is_numeric($iFieldID) && !empty($iFieldID)) {
                    CoreController::$aCoreTables['form-field']->update([
                        'sort_id'=>$iSortID,
                    ],[
                        'user_idfs'=>CoreController::$oSession->oUser->getID(),
                        'field_idfs'=>$iFieldID,
                    ]);
                    $iSortID++;
                }
            }

            $aReturn = ['state' => 'success','message' => 'order updated'];

            echo json_encode($aReturn);

            return false;
        }
    }

    public function selectboolAction()
    {
        $this->layout('layout/json');

        $aResults = [];
        $aResults[] = ['id'=>'1','text'=>'No'];
        $aResults[] = ['id'=>'2','text'=>'Yes'];

        return new ViewModel([
            'aResults'=>$aResults,
        ]);
    }

    public function checkforupdatesAction()
    {
        $this->setThemeBasedLayout('application');

        $oModuleTbl = new TableGateway('core_module',CoreController::$oDbAdapter);
        $aModulesInstalled = [];
        $oModulesDB = $oModuleTbl->select();
        if(count($oModulesDB) > 0) {
            foreach($oModulesDB as $oMod) {
                $aModulesInstalled[$oMod->module_key] = $oMod;
            }
        }


        return new ViewModel([
            'aModulesInstalled' => $aModulesInstalled
        ]);
    }

    public function updatetabsortAction()
    {
        $this->layout('layout/json');

        $sForm = $_REQUEST['form'];
        $aTabs = $_REQUEST['tabs'];

        $iSortID = 0;
        foreach($aTabs as $sTab) {
            $sTab = substr($sTab,strlen('tab-'));
            echo $sTab;
            CoreController::$aCoreTables['form-tab']->update([
                'sort_id' => $iSortID,
            ],[
                'tab_idfs' => $sTab,
                'user_idfs' => CoreController::$oSession->oUser->getID(),
            ]);
            $iSortID++;
        }

        return false;
    }

    public function updatethemeAction()
    {
        $this->setThemeBasedLayout('core');

        $sTheme = $this->params()->fromRoute('theme', 'default');
        $sThemeFile = \Application\Module::getModuleDir().'../../public/themes/'.$sTheme.'/theme.json';
        $oThemeInfo = false;
        if(file_exists($sThemeFile)) {
            $oThemeInfo = json_decode(file_get_contents($sThemeFile));
        }

        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PHP'
                ]
            ]
        ];
        $oContext = stream_context_create($opts);

        $sNewInfo = file_get_contents('https://api.github.com/repos/OnePlc/oneplace-theme-'.$sTheme.'/releases/latest', false, $oContext);
        $oNewInfo = json_decode($sNewInfo);

        $sChangeLog = file_get_contents('https://github.com/OnePlc/oneplace-theme-'.$sTheme.'/raw/'.$oNewInfo->tag_name.'/CHANGELOG.md', false, $oContext);
        $oRequest = $this->getRequest();

        if(!$oRequest->isPost()) {
            return new ViewModel([
                'sTheme' => $sTheme,
                'oThemeInfo' => $oThemeInfo,
                'oNewInfo' => $oNewInfo,
                'sChangeLog' => $sChangeLog,
            ]);
        }

        $this->layout('layout/json');
        $oNewTheme = file_get_contents('https://github.com/OnePlc/oneplace-theme-'.$sTheme.'/archive/refs/tags/'.$oNewInfo->tag_name.'.zip', false, $oContext);
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/themes/'.$sTheme.'-update.zip',$oNewTheme);
        # unzip theme
        $zip = new \ZipArchive();
        $x = $zip->open($_SERVER['DOCUMENT_ROOT'].'/themes/'.$sTheme.'-update.zip');
        if ($x === true) {
            $zip->extractTo($_SERVER['DOCUMENT_ROOT'].'/themes/'); // change this to the correct site path
            $zip->close();

            unlink($_SERVER['DOCUMENT_ROOT'].'/themes/'.$sTheme.'-update.zip');
            IndexController::deleteDir($_SERVER['DOCUMENT_ROOT'].'/themes/oneplace-theme-'.$sTheme.'-'.$oNewInfo->tag_name.'/assets');
            IndexController::recurseCopy($_SERVER['DOCUMENT_ROOT'].'/themes/'.$sTheme.'/assets', $_SERVER['DOCUMENT_ROOT'].'/themes/oneplace-theme-'.$sTheme.'-'.$oNewInfo->tag_name.'/assets');
            IndexController::deleteDir($_SERVER['DOCUMENT_ROOT'].'/themes/'.$sTheme);
            rename($_SERVER['DOCUMENT_ROOT'].'/themes/oneplace-theme-'.$sTheme.'-'.$oNewInfo->tag_name,$_SERVER['DOCUMENT_ROOT'].'/themes/'.$sTheme);

            $this->setThemeBasedLayout('core');
            return new ViewModel([
                'sTheme' => $sTheme,
                'oThemeInfo' => $oThemeInfo,
                'oNewInfo' => $oNewInfo,
                'sChangeLog' => $sChangeLog,
            ]);
        }
    }

    private static function recurseCopy($src,$dst, $childFolder='') {

        $dir = opendir($src);
        mkdir($dst);
        if ($childFolder!='') {
            mkdir($dst.'/'.$childFolder);

            while(false !== ( $file = readdir($dir)) ) {
                if (( $file != '.' ) && ( $file != '..' )) {
                    if ( is_dir($src . '/' . $file) ) {
                        IndexController::recurseCopy($src . '/' . $file,$dst.'/'.$childFolder . '/' . $file);
                    }
                    else {
                        copy($src . '/' . $file, $dst.'/'.$childFolder . '/' . $file);
                    }
                }
            }
        }else{
            // return $cc;
            while(false !== ( $file = readdir($dir)) ) {
                if (( $file != '.' ) && ( $file != '..' )) {
                    if ( is_dir($src . '/' . $file) ) {
                        IndexController::recurseCopy($src . '/' . $file,$dst . '/' . $file);
                    }
                    else {
                        copy($src . '/' . $file, $dst . '/' . $file);
                    }
                }
            }
        }

        closedir($dir);
    }

    private static function deleteDir($dirPath) {
        if (! is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
}
