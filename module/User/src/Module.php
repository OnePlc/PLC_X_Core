<?php
/**
 * Module.php - Module Class
 *
 * Module Class File for User Module
 *
 * @category Config
 * @package User
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

namespace User;

use Application\Controller\CoreController;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Laminas\EventManager\EventInterface as Event;
use Laminas\Mvc\MvcEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\SessionManager;
use Laminas\Session\Container;

class Module
{
    /**
     * Load module config file
     *
     * @since 1.0.0
     * @return array
     */
    public function getConfig() : array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Init module, add hooks
     *
     * @param ModuleManager $moduleManager
     * @since 1.0.0
     */
    public function init(ModuleManager $moduleManager)
    {
        // Remember to keep the init() method as lightweight as possible
        $events = $moduleManager->getEventManager();
        $events->attach('loadModules.post', [$this, 'modulesLoaded']);
    }

    /**
     * Final configuration after all modules are loaded
     *
     * @param Event $e
     * @since 1.0.0
     */
    public function modulesLoaded(Event $e)
    {
        // This method is called once all modules are loaded.
        $moduleManager = $e->getTarget();
        $loadedModules = $moduleManager->getLoadedModules();

        // To get the configuration from another module named 'FooModule'
        $config = $moduleManager->getModule('User')->getConfig();
    }

    /**
     * On Bootstrap - is executed on every page request
     *
     * checks if user is logged in and has sufficient
     * permissions, redirects to login otherwise
     * so this is our basic firewall
     *
     * @param Event $e
     * @since 1.0.0
     */
    public function onBootstrap(\Laminas\EventManager\EventInterface $e)
    {
        CoreController::$aPerfomanceLogStart = getrusage();

        $app = $e->getApplication();
        $sm = $app->getServiceManager();
        $app->getEventManager()->attach(
            'route',
            function($e) {
                # get basic info from application
                $app = $e->getApplication();
                $routeMatch = $e->getRouteMatch();
                $sm = $app->getServiceManager();

                $oDbAdapter = $sm->get(AdapterInterface::class);

                /**
                # set session manager
                $config = new StandardConfig();
                $config->setOptions([
                    'remember_me_seconds' => 1800,
                    'name'                => 'plcauth',
                ]);
                $manager = new SessionManager($config);
**/
                # get session
                $container = new Container('plcauth');
                $bLoggedIn = false;

                $sRouteName = $routeMatch->getMatchedRouteName();
                $aRouteInfo = $routeMatch->getParams();

                # check if user is logged in
                if(isset($container->oUser)) {
                    $bLoggedIn = true;
                    # check permissions

                    //echo 'check for '.$aRouteInfo['action'].'-'.$aRouteInfo['controller'];

                    $container->oUser->setAdapter($oDbAdapter);

                    if(!$container->oUser->hasPermission($aRouteInfo['action'],$aRouteInfo['controller']) && $sRouteName != 'denied') {
                        $response = $e->getResponse();
                        $response->getHeaders()->addHeaderLine(
                            'Location',
                            $e->getRouter()->assemble(
                                ['id'=>$aRouteInfo['action']],
                                ['name' => 'denied']));
                        $response->setStatusCode(302);
                        return $response;
                    }
                }

                /**
                 * Api Login
                 */
                $bIsApiController = stripos($aRouteInfo['controller'],'ApiController');
                if(isset($_REQUEST['authkey']) && $bIsApiController !== false) {
                    # todo: replace with database based authkey list so keys can be revoked
                    if($_REQUEST['authkey'] == 'DEVRANDOMKEY') {
                        $bLoggedIn = true;
                    }
                }

                /**
                 * Redirect to Login Page if not logged in
                 */
                if (!$bLoggedIn && $sRouteName != 'login') {
                    $response = $e->getResponse();
                    $response->getHeaders()->addHeaderLine(
                        'Location',
                        $e->getRouter()->assemble(
                            [],
                            ['name' => 'login'],
                        )
                    );
                    $response->setStatusCode(302);
                    return $response;
                }
            },
            -100
        );
    }

    /**
     * Load Models
     *
     * @since 1.0.0
     */
    public function getServiceConfig() : array
    {
        return [
            'factories' => [
                # User Module - Base Model
                Model\UserTable::class => function($container) {
                    $tableGateway = $container->get(Model\UserTableGateway::class);
                    return new Model\UserTable($tableGateway);
                },
                Model\UserTableGateway::class => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\User($dbAdapter));
                    return new TableGateway('user', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserPermissionGateway::class => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    return new TableGateway('user_permission', $dbAdapter);
                },
            ],
        ];
    }

    /**
     * Load Controllers
     */
    public function getControllerConfig() : array
    {
        return [
            'factories' => [
                Controller\UserController::class => function($container) {
                    $oDbAdapter = $container->get(AdapterInterface::class);
                    return new Controller\UserController(
                        $oDbAdapter,
                        $container->get(Model\UserTable::class)
                    );
                },
            ],
        ];
    }
}
