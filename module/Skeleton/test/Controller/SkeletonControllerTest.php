<?php
/**
 * SkeletonControllerTest.php - Main Controller Test Class
 *
 * Test Class for Main Controller of Skeleton Module
 *
 * @category Test
 * @package Skeleton
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace SkeletonTest\Controller;

use Skeleton\Controller\SkeletonController;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Laminas\Session\Container;
use User\Model\TestUser;
use Laminas\Db\Adapter\AdapterInterface;

class SkeletonControllerTest extends AbstractHttpControllerTestCase {
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

    public function testIndexActionCanBeAccessed() {
        $this->initFakeTestSession();

        $this->dispatch('/skeleton', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('skeleton');
        $this->assertControllerName(SkeletonController::class); // as specified in router's controller name alias
        $this->assertControllerClass('SkeletonController');
        $this->assertMatchedRouteName('skeleton');
    }

    public function testIndexActionViewModelTemplateRenderedWithinLayout() {
        $this->initFakeTestSession();

        $this->dispatch('/skeleton', 'GET');
        $this->assertQuery('.container h1');
    }

    public function testAddActionCanBeAccessed() {
        $this->initFakeTestSession();

        $this->dispatch('/skeleton/add', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('skeleton');
        $this->assertControllerName(SkeletonController::class); // as specified in router's controller name alias
        $this->assertControllerClass('SkeletonController');
        $this->assertMatchedRouteName('skeleton');
    }

    public function testAddActionViewModelTemplateRenderedWithinLayout() {
        $this->initFakeTestSession();

        $this->dispatch('/skeleton/add', 'GET');

        # Check if view is loading
        $this->assertQuery('.container h1');

        # check if form partial is loading
        $this->assertQuery('.container form.plc-core-basic-form');
    }

    public function testEditActionCanBeAccessed() {
        $this->initFakeTestSession();

        $this->dispatch('/skeleton/edit/1', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('skeleton');
        $this->assertControllerName(SkeletonController::class); // as specified in router's controller name alias
        $this->assertControllerClass('SkeletonController');
        $this->assertMatchedRouteName('skeleton');
    }

    public function testEditActionViewModelTemplateRenderedWithinLayout() {
        $this->initFakeTestSession();

        $this->dispatch('/skeleton/edit/1', 'GET');
        $this->assertQuery('.container h2');

        # check if form partial is loading
        $this->assertQuery('.container form.plc-core-basic-form');
    }

    public function testViewActionCanBeAccessed() {
        $this->initFakeTestSession();

        $this->dispatch('/skeleton/view/1', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('skeleton');
        $this->assertControllerName(SkeletonController::class); // as specified in router's controller name alias
        $this->assertControllerClass('SkeletonController');
        $this->assertMatchedRouteName('skeleton');
    }

    public function testViewActionViewModelTemplateRenderedWithinLayout() {
        $this->initFakeTestSession();

        $this->dispatch('/skeleton/view/1', 'GET');
        # Check if view is loading
        $this->assertQuery('.container h2');

        # check if view partial is loading
        $this->assertQuery('.container div.plc-core-basic-view');
    }
}
