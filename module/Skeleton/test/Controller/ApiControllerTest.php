<?php
/**
 * ApiControllerTest.php - Main Controller Test Class
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

use Skeleton\Controller\ApiController;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Laminas\Session\Container;
use User\Model\TestUser;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Stdlib\Parameters;

class ApiControllerTest extends AbstractHttpControllerTestCase {
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

    public function testIndexActionCannotBeAccessedWithoutKey() {
        $this->dispatch('/skeleton/api', 'GET');
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo('/login');
    }

    public function testIndexActionCanBeAccessed() {
        # todo: API works without session - somehow $_REQUEST is empty with testsuite find out why
        $this->initFakeTestSession();

        $this->dispatch('/skeleton/api?authkey=DEVRANDOMKEY', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('skeleton');
        $this->assertControllerName(ApiController::class); // as specified in router's controller name alias
        $this->assertControllerClass('ApiController');
        $this->assertMatchedRouteName('skeleton-api');
    }
}
