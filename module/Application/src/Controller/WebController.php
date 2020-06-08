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

class WebController extends CoreController
{
    /**
     * Application Home - Main Index
     *
     * @since 1.0.0
     * @return ViewModel - View Object with Data from Controller
     */
    public function indexAction()
    {
        # Set Layout based on users theme
        $this->layout('layout/becho');

        return new ViewModel([
        ]);
    }

    public function indexaltAction()
    {
        # Set Layout based on users theme
        $this->layout('layout/becho');

        return new ViewModel([
        ]);
    }

    public function settingsAction()
    {
        # Set Layout based on users theme
        $this->setThemeBasedLayout('core');

        $oRequest = $this->getRequest();

        if($oRequest->isPost()) {
            $oSettingsTbl = new TableGateway('settings', CoreController::$oDbAdapter);
            /**
             * Website Title
             */
            $sWebTitle = $oRequest->getPost('website_title');
            if($sWebTitle != '') {
                $oSettingsTbl->update([
                    'settings_value' => $sWebTitle
                ], ['settings_key' => 'web-title']);
            }
            /**
             * Website Subtitle
             */
            $sWebSubTitle = $oRequest->getPost('website_subtitle');
            if($sWebSubTitle != '') {
                $oSettingsTbl->update([
                    'settings_value' => $sWebSubTitle
                ], ['settings_key' => 'web-subtitle']);
            }
            /**
             * Favicon
             */
            if(isset($_FILES['logo_favicon'])) {
                move_uploaded_file($_FILES['logo_favicon']['tmp_name'],$_SERVER['DOCUMENT_ROOT'].'/img/favicon.ico');
            }
            /**
             * Logo App
             */
            if(isset($_FILES['logo_app'])) {
                move_uploaded_file($_FILES['logo_app']['tmp_name'],$_SERVER['DOCUMENT_ROOT'].'/img/logo.png');
            }
            /**
             * Logo Web
             */
            if(isset($_FILES['logo_web'])) {
                move_uploaded_file($_FILES['logo_web']['tmp_name'],$_SERVER['DOCUMENT_ROOT'].'/img/becho/logo.png');
            }
            /**
             * Logo Web Footer
             */
            if(isset($_FILES['logo_footer'])) {
                move_uploaded_file($_FILES['logo_footer']['tmp_name'],$_SERVER['DOCUMENT_ROOT'].'/img/becho/logo_white.png');
            }
            /**
             * Showcase
             */
            if(isset($_FILES['showcase_image'])) {
                move_uploaded_file($_FILES['showcase_image']['tmp_name'],$_SERVER['DOCUMENT_ROOT'].'/img/becho/showcase.jpg');
            }
            $sShowcaseTtl = $oRequest->getPost('showcase_title');
            if($sShowcaseTtl != '') {
                $oSettingsTbl->update([
                    'settings_value' => $sShowcaseTtl
                ], ['settings_key' => 'showcase-title']);
                $oSettingsTbl->update([
                    'settings_value' => $oRequest->getPost('showcase_description')
                ], ['settings_key' => 'showcase-description']);
                $oSettingsTbl->update([
                    'settings_value' => $oRequest->getPost('showcase_url')
                ], ['settings_key' => 'showcase-url']);
            }

            /**
             * Rss Feed Home
             */
            $sHomeFeed = $oRequest->getPost('rssfeed_home');
            if($sHomeFeed != '') {
                $oSettingsTbl->update([
                    'settings_value' => $sHomeFeed
                ], ['settings_key' => 'rssfeed-home']);
            }
            /**
             * Icon 1
             */
            $sSpotIcon1 = $oRequest->getPost('spoticon1_icon');
            if($sSpotIcon1 != '') {
                $oSettingsTbl->update([
                    'settings_value' => $sSpotIcon1
                ], ['settings_key' => 'spoticon1-icon']);
                $oSettingsTbl->update([
                    'settings_value' => $oRequest->getPost('spoticon1_title')
                ], ['settings_key' => 'spoticon1-title']);
                $oSettingsTbl->update([
                    'settings_value' => $oRequest->getPost('spoticon1_text')
                ], ['settings_key' => 'spoticon1-text']);
                $oSettingsTbl->update([
                    'settings_value' => $oRequest->getPost('spoticon1_url')
                ], ['settings_key' => 'spoticon1-url']);
            }
            /**
             * Icon 2
             */
            $sSpotIcon2 = $oRequest->getPost('spoticon2_icon');
            if($sSpotIcon2 != '') {
                $oSettingsTbl->update([
                    'settings_value' => $sSpotIcon2
                ], ['settings_key' => 'spoticon2-icon']);
                $oSettingsTbl->update([
                    'settings_value' => $oRequest->getPost('spoticon2_title')
                ], ['settings_key' => 'spoticon2-title']);
                $oSettingsTbl->update([
                    'settings_value' => $oRequest->getPost('spoticon2_text')
                ], ['settings_key' => 'spoticon2-text']);
                $oSettingsTbl->update([
                    'settings_value' => $oRequest->getPost('spoticon2_url')
                ], ['settings_key' => 'spoticon2-url']);
            }
            /**
             * Icon 3
             */
            $sSpotIcon3 = $oRequest->getPost('spoticon3_icon');
            if($sSpotIcon3 != '') {
                $oSettingsTbl->update([
                    'settings_value' => $sSpotIcon3
                ], ['settings_key' => 'spoticon3-icon']);
                $oSettingsTbl->update([
                    'settings_value' => $oRequest->getPost('spoticon3_title')
                ], ['settings_key' => 'spoticon3-title']);
                $oSettingsTbl->update([
                    'settings_value' => $oRequest->getPost('spoticon3_text')
                ], ['settings_key' => 'spoticon3-text']);
                $oSettingsTbl->update([
                    'settings_value' => $oRequest->getPost('spoticon3_url')
                ], ['settings_key' => 'spoticon3-url']);
            }
            /**
             * Telegram Link
             */
            $sTelegramUrl = $oRequest->getPost('telegram_link');
            if($sTelegramUrl != '') {
                $oSettingsTbl->update([
                    'settings_value' => $sTelegramUrl
                ], ['settings_key' => 'telegram-link']);
                $oSettingsTbl->update([
                    'settings_value' => $oRequest->getPost('telegram_label')
                ], ['settings_key' => 'telegram-label']);
            }

            $this->flashMessenger()->addSuccessMessage('Einstellungen gespeichert');
            return $this->redirect()->toRoute('websettings');
        } else {
            return new ViewModel([]);
        }
    }
}
