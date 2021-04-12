<?php
/**
 * Module.php - Module Class
 *
 * Module Class File for Application Module
 *
 * @category Config
 * @package Application
 * @author Verein onePlace
 * @copyright (C) 2020 Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Application;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\EventManager\EventInterface;
use Laminas\Mvc\MvcEvent;

class Module {
    /**
     * Module Version
     *
     * @since 1.0.0
     */
    const VERSION = '1.0.36';

    public function getConfig() : array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public static function getModuleDir() : string
    {
        return __DIR__.'/../';
    }

    function onBootstrap(EventInterface $e)
    {
        $application = $e->getApplication();
        $eventManager = $application->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this,'onDispatchError'), 100);
    }

    function onDispatchError(MvcEvent $e)
    {
        $viewModel = $e->getViewModel();
        $viewModel->setTemplate('layout/error');
    }

    /**
     * Load Controllers
     */
    public function getControllerConfig() : array
    {
        return [
            'factories' => [
                Controller\SetupController::class => function($container) {
                    return new Controller\SetupController(
                        $container
                    );
                },
                Controller\IndexController::class => function($container) {
                    $oDbAdapter = $container->get(AdapterInterface::class);
                    return new Controller\IndexController(
                        $oDbAdapter,
                        false,
                        $container
                    );
                },
                Controller\WebController::class => function($container) {
                    $oDbAdapter = $container->get(AdapterInterface::class);
                    return new Controller\WebController(
                        $oDbAdapter,
                        false,
                        $container
                    );
                },
                Controller\ApiController::class => function($container) {
                    $oDbAdapter = $container->get(AdapterInterface::class);
                    return new Controller\ApiController(
                        $oDbAdapter,
                        false,
                        $container
                    );
                },
                Controller\UploadController::class => function($container) {
                    $oDbAdapter = $container->get(AdapterInterface::class);
                    return new Controller\UploadController(
                        $oDbAdapter,
                        false,
                        $container
                    );
                },
            ],
        ];
    }
}
