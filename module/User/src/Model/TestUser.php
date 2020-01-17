<?php
/**
 * User.php - User Entity
 *
 * Entity Model for User
 *
 * @category Model
 * @package User
 * @author Verein onePlace
 * @copyright (C) 2020 Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

namespace User\Model;

use Application\Model\CoreEntityModel;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;

class TestUser extends CoreEntityModel {
    public $id;
    public $email;
    public $full_name;
    public $username;
    public $password;
    private $aMyPermissions;

    public function __construct($oDbAdapter) {
        parent::__construct($oDbAdapter);
        if(!isset(CoreEntityModel::$aEntityTables['user-permission'])) {
            CoreEntityModel::$aEntityTables['user-permission'] = new TableGateway('user_permission', CoreEntityModel::$oDbAdapter);
        }
        $this->aMyPermissions = $this->getMyPermissions();
    }

    public function setAdapter($oDbAdapter) {
        CoreEntityModel::$oDbAdapter = $oDbAdapter;
        if(!isset(CoreEntityModel::$aEntityTables['user-permission'])) {
            CoreEntityModel::$aEntityTables['user-permission'] = new TableGateway('user_permission', CoreEntityModel::$oDbAdapter);
        }
    }

    /**
     * Get Object Data from Array
     *
     * @param array $data
     * @since 1.0.0
     */
    public function exchangeArray(array $data) {
        $this->id = !empty($data['User_ID']) ? $data['User_ID'] : 0;
        $this->email = !empty($data['email']) ? $data['email'] : '';
        $this->username = !empty($data['username']) ? $data['username'] : '';
        $this->full_name = !empty($data['full_name']) ? $data['full_name'] : '';
        $this->password = !empty($data['password']) ? $data['password'] : '';
    }

    /**
     * Return Name
     *
     * @return string
     * @since 1.0.0
     */
    public function getLabel() {
        return $this->full_name;
    }

    /**
     * Check if user has permission
     *
     * @return boolean
     * @since 1.0.0
     */
    public function hasPermission($sPermission,$sModule) {
        return true;
    }

    /**
     * Gets User's permissions
     *
     * @return array
     * @since 1.0.0
     */
    public function getMyPermissions() {
        return [];
    }

    /**
     * Get users theme
     *
     * @return string
     * @since 1.0.0
     */
    public function getTheme() {
        return 'default';
    }
}