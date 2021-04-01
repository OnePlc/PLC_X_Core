<?php
/**
 * SetupController.php - Index Controller
 *
 * Main Controller Application Module
 *
 * @category Controller
 * @package Application
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.5
 */

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Adapter\Adapter;

class SetupController extends AbstractActionController {
    /**
     * Laminas Service Manager
     *
     * @var $oServiceManager
     * @since 1.0.5
     */
    private static $oServiceManager;
    /**
     * SetupController constructor.
     *
     * @param AdapterInterface $oDbAdapter
     * @since 1.0.5
     */
    public function __construct($oServiceManager) {
        SetupController::$oServiceManager = $oServiceManager;
    }
    /**
     * Initial Setup
     *
     * @since 1.0.2
     * @return ViewModel - View Object with Data from Controller
     */
    public function indexAction() {
        $this->layout('layout/json');

        # Set Layout based on users theme
        $this->layout('layout/setup');

        # Check if setup is already done
        if(file_exists(__DIR__.'/../../../../config/autoload/local.php')) {
            return $this->redirect()->toRoute('home');
        }

        $oRequest = $this->getRequest();

        if(!$oRequest->isPost()) {
            $aWarnings = [];
            if(!is_writable(__DIR__.'/../../../../config/autoload/global.php')) {
                $aWarnings['file-perm'] = 'Need write permissions on docroot for setup';
            }
            return new ViewModel([
                'aWarnings'=>$aWarnings,
            ]);
        } else {
            $aWarnings = [];
            /**
            if(!is_writable(__DIR__.'/../../../../config/autoload/global.php')) {
                $aWarnings['file-perm'] = 'Need write permissions on docroot for setup';
                echo 'Need write permissions on docroot for setup';
                echo __DIR__.'/../../../../config/autoload/global.php';
            }
            if(count($aWarnings) > 0) {
                return new ViewModel([
                    'aWarnings'=>$aWarnings,
                ]);
            } **/

            # get db user data
            $aDBUserInfo = [
                'username' => $oRequest->getPost('setup_dbuser'),
                'password' => $oRequest->getPost('setup_dbpass'),
            ];

            # get db host data
            $aDBHostInfo = [
                'driver' => 'Pdo',
                'dsn'    => 'mysql:dbname='.$oRequest->getPost('setup_dbname').';host='.$oRequest->getPost('setup_dbhost').';charset=utf8',
            ];

            # get admin user info
            $aAdminInfo = [
                'username' => $oRequest->getPost('setup_adminname'),
                'email' => $oRequest->getPost('setup_adminemail'),
                'password' => $oRequest->getPost('setup_adminpass'),
                'password_check' => $oRequest->getPost('setup_adminpassrep'),
            ];

            /**
             * Create local.php config
             */
            $config = new \Laminas\Config\Config([], true);
            $config->db = $aDBUserInfo;
            $writer = new \Laminas\Config\Writer\PhpArray();
            file_put_contents(__DIR__.'/../../../../config/autoload/local.php',$writer->toString($config));

            /**
             * Create global.php config
             */
            $config = new \Laminas\Config\Config([], true);
            $config->db = $aDBHostInfo;
            $writer = new \Laminas\Config\Writer\PhpArray();
            file_put_contents(__DIR__.'/../../../../config/autoload/global.php',$writer->toString($config));

            //$adapter = CoreController::$oServiceManager->get(AdapterInterface::class);
            $adapter = new Adapter([
                'driver'=>'Pdo_Mysql',
                'database'=>$oRequest->getPost('setup_dbname'),
                'username'=>$aDBUserInfo['username'],
                'password'=>$aDBUserInfo['password'],
                'hostname'=>$oRequest->getPost('setup_dbhost'),
                'charset'=>'utf8',
            ]);

            # Core DB Structure
            $filename = __DIR__.'/../../data/structure.sql';
            $this->parseSQLInstallFile($filename,$adapter);

            # User DB Structure
            # todo: move user related stuff to static function in User Module
            if(file_exists( __DIR__.'/../../../../vendor/oneplace/oneplace-user/data/structure.sql')) {
                $filename = __DIR__.'/../../../../vendor/oneplace/oneplace-user/data/structure.sql';
                $this->parseSQLInstallFile($filename,$adapter);
            } elseif(file_exists( '/home/travis/build/OnePlc/PLC_X_User/data/structure.sql')) {
                $this->parseSQLInstallFile('/home/travis/build/OnePlc/PLC_X_User/data/structure.sql',$adapter);
            }

            # Default settings and core data
            $filename = __DIR__.'/../../data/data.sql';
            $this->parseSQLInstallFile($filename,$adapter);

            # Default settings and data for users
            $filename = __DIR__.'/../../../../vendor/oneplace/oneplace-user/data/data.sql';
            if(file_exists($filename)) {
                $this->parseSQLInstallFile($filename,$adapter);
            } elseif(file_exists('/home/travis/build/OnePlc/PLC_X_User/data/data.sql')) {
                $this->parseSQLInstallFile('/home/travis/build/OnePlc/PLC_X_User/data/data.sql',$adapter);
            }

            $oUserTbl = new TableGateway('user',$adapter);
            /**
             * Add Admin User
             */
            $oUserTbl->insert([
                'username'=>$aAdminInfo['username'],
                'full_name'=>'Admin',
                'email'=>$aAdminInfo['email'],
                'password'=>password_hash($aAdminInfo['password'],PASSWORD_DEFAULT),
                'authkey'=>'',
                'xp_level'=>1,
                'xp_total'=>0,
                'xp_current'=>0,
                'is_backend_user'=>1,
                'is_globaladmin'=>1,
                'lang'=>'en_US',
                'mobile'=>'',
                'password_reset_token'=>'',
                'password_reset_date'=>'0000-00-00 00:00:00',
                'theme'=>'default',
                'featured_image'=>'',
                'created_by'=>1,
                'created_date'=>date('Y-m-d H:i:s',time()),
                'modified_by'=>1,
                'modified_date'=>date('Y-m-d H:i:s',time()),
            ]);

            $iAdminUserID = $oUserTbl->lastInsertValue;

            /**
             * Add Basic Permissions for Admin
             */
            $oUserPermTbl = new TableGateway('user_permission',$adapter);
            # Home
            $oUserPermTbl->insert([
                'user_idfs'=>$iAdminUserID,
                'permission'=>'index',
                'module'=>'Application\Controller\IndexController',
            ]);

            # Updates
            $oUserPermTbl->insert([
                'user_idfs'=>$iAdminUserID,
                'permission'=>'update',
                'module'=>'Application\Controller\IndexController',
            ]);

            # Update Index Column Sorting
            $oUserPermTbl->insert([
                'user_idfs'=>$iAdminUserID,
                'permission'=>'updateindexcolumnsort',
                'module'=>'OnePlace\User\Controller\UserController',
            ]);

            # User Index
            $oUserPermTbl->insert([
                'user_idfs'=>$iAdminUserID,
                'permission'=>'index',
                'module'=>'OnePlace\User\Controller\UserController',
            ]);
            # User Edit
            $oUserPermTbl->insert([
                'user_idfs'=>$iAdminUserID,
                'permission'=>'edit',
                'module'=>'OnePlace\User\Controller\UserController',
            ]);
            # User Add
            $oUserPermTbl->insert([
                'user_idfs'=>$iAdminUserID,
                'permission'=>'add',
                'module'=>'OnePlace\User\Controller\UserController',
            ]);
            # User View
            $oUserPermTbl->insert([
                'user_idfs'=>$iAdminUserID,
                'permission'=>'view',
                'module'=>'OnePlace\User\Controller\UserController',
            ]);
            # Themes Index
            $oUserPermTbl->insert([
                'user_idfs'=>$iAdminUserID,
                'permission'=>'themes',
                'module'=>'Application\Controller\IndexController',
            ]);
            # Add Theme
            $oUserPermTbl->insert([
                'user_idfs'=>$iAdminUserID,
                'permission'=>'addtheme',
                'module'=>'Application\Controller\IndexController',
            ]);
            # Set Theme
            $oUserPermTbl->insert([
                'user_idfs'=>$iAdminUserID,
                'permission'=>'settheme',
                'module'=>'OnePlace\User\Controller\UserController',
            ]);
            # Globaladmin
            $oUserPermTbl->insert([
                'user_idfs'=>$iAdminUserID,
                'permission'=>'globaladmin',
                'module'=>'OnePlace\Core',
            ]);

            /**
             * Add Basic Views / Columns / Fields
             */
            $oUserTabs = new TableGateway('user_form_tab',$adapter);
            $aDefaultTabs = ['user-base','user-columns','user-fields','user-tabs','user-permissions'];
            $iTabSortID = 0;
            foreach($aDefaultTabs as $sTab) {
                $oUserTabs->insert([
                    'tab_idfs'=>$sTab,
                    'user_idfs'=>$iAdminUserID,
                    'sort_id'=>$iTabSortID,
                ]);
                $iTabSortID++;
            }

            $oUserCols = new TableGateway('user_table_column',$adapter);
            # Username
            $oUserCols->insert([
                'tbl_name'=>'user-index',
                'user_idfs'=>$iAdminUserID,
                'field_idfs'=>1,
                'sortID'=>0,
                'width'=>'30%',
            ]);
            # User E-Mail
            $oUserCols->insert([
                'tbl_name'=>'user-index',
                'user_idfs'=>$iAdminUserID,
                'field_idfs'=>3,
                'sortID'=>1,
                'width'=>'30%',
            ]);

            $oUserFields = new TableGateway('user_form_field',$adapter);
            $iFieldSortID = 0;
            $aDefFields = [1,2,3,4,5,6,7,8];
            foreach($aDefFields as $iFieldID) {
                $oUserFields->insert([
                    'user_idfs'=>$iAdminUserID,
                    'field_idfs'=>$iFieldID,
                    'sort_id'=>$iFieldSortID,
                ]);
                $iFieldSortID++;
            }

            # Add core modules
            $oCoreModules = new TableGateway('core_module',$adapter);
            $oCoreModules->insert([
                'module_key'=>'oneplace-core',
                'version'=> \Application\Module::VERSION,
                'label'=>'onePlace Core',
                'type'=>'module',
                'vendor'=>'oneplace',
            ]);
            $oCoreModules->insert([
                'module_key'=>'oneplace-user',
                'version'=> \OnePlace\User\Module::VERSION,
                'label'=>'onePlace User',
                'type'=>'module',
                'vendor'=>'oneplace',
            ]);

            return $this->redirect()->toRoute('login');
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
}
