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

    public function addthemeAction() {
        $this->setThemeBasedLayout('application');

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
}
