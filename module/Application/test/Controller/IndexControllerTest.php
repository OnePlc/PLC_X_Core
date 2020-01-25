<?php

/**
 * Module.php - Module Class
 *
 * Module Class File for Application Module
 *
 * @category Test
 * @package Application
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\IndexController;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Laminas\Session\Container;
use Laminas\Db\Adapter\AdapterInterface;
use Application\Model\TestUser;

class IndexControllerTest extends AbstractHttpControllerTestCase {
    public function setUp() : void {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../../config/application.config.php',
            $configOverrides
        ));

        parent::setUp();
    }

    public function initTestSession() {

        //$this->assertQuery('login');
        /**
        $oSm = $this->getApplicationServiceLocator();
        $oDbAdapter = $oSm->get(AdapterInterface::class);
        $oSession = new Container('plcauth');
        $oTestUser = new TestUser($oDbAdapter);
        $oTestUser->exchangeArray(['full_name'=>'Test','email'=>'travis@1plc.ch','pass','User_ID'=>1]);
        $oSession->oUser = $oTestUser;
         **/
    }

    public function testIndexActionCanBeAccessed() {
        $this->dispatch('/', 'GET');
        $this->assertResponseStatusCode(302);
        $this->assertModuleName('application');
        $this->assertControllerName(IndexController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IndexController');
        $this->assertMatchedRouteName('home');
    }

    public function testIndexActionCanBeAccessedWithSession() {

       // $this->initTestSession();
        $this->dispatch('/login', 'POST', ['plc_login_user'=>'travis@1plc.ch','plc_login_pass'=>'1234']);
        $this->assertResponseStatusCode(302);
        /**
         * another day...

        $this->dispatch('/', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(IndexController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IndexController');
        $this->assertMatchedRouteName('home');
        $this->assertRedirectTo('login');
         *  */
    }

    public function testInvalidRouteDoesNotCrash() {
        $this->initTestSession();
        
        $this->dispatch('/invalid/route', 'GET');
        $this->assertResponseStatusCode(404);
    }
}
