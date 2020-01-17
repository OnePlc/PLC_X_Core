<?php
/**
 * UserControllerTest.php - Main Controller Test Class
 *
 * Test Class for Main Controller of User Module
 *
 * @category Test
 * @package User
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace UserTest\Controller;

use User\Controller\UserController;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Laminas\Session\Container;
use User\Model\TestUser;
use Laminas\Db\Adapter\AdapterInterface;

class UserControllerTest extends AbstractHttpControllerTestCase
{
    private function initFakeTestSession() {
        /**
         * Init Test Session to Fake Login
         */
        $oSm = $this->getApplicationServiceLocator();
        $oDbAdapter = $oSm->get(AdapterInterface::class);
        $oSession = new Container('plcauth');
        $oTestUser = new TestUser($oDbAdapter);
        $oTestUser->exchangeArray(['full_name'=>'Test','email'=>'admin@1plc.ch','User_ID'=>1]);
        $oSession->oUser = $oTestUser;
    }

    public function setUp() : void
    {
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

    public function testIndexActionCanBeAccessed()
    {
        $this->initFakeTestSession();

        $this->dispatch('/user', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('user');
        $this->assertControllerName(UserController::class); // as specified in router's controller name alias
        $this->assertControllerClass('UserController');
        $this->assertMatchedRouteName('user');
    }

    public function testIndexActionViewModelTemplateRenderedWithinLayout()
    {
        $this->initFakeTestSession();

        $this->dispatch('/user', 'GET');
        $this->assertQuery('.container h1');
    }

    public function testLoginActionViewModelTemplateRenderedWithinLayout()
    {
        $this->dispatch('/login', 'GET');
        $this->assertQuery('.container .plc-login-form');
    }

    public function testLoginSuccessActionViewModelTemplateRenderedWithinLayout()
    {
        $this->dispatch('/login', 'POST');
        $this->assertResponseStatusCode(302);
        $oSession = new Container('plcauth');
        if(!isset($oSession->oUser)) {
            throw new \Exception(
                'Session not found'
            );
        }
    }

    public function testLogoutActionViewModelTemplateRenderedWithinLayout()
    {
        $this->initFakeTestSession();

        $this->dispatch('/logout', 'GET');
        $this->assertResponseStatusCode(302);
    }
}
