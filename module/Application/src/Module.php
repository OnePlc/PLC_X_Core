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
use Laminas\ModuleManager\ModuleManager;

class Module {
    /**
     * Module Version
     *
     * @since 1.0.3
     */
    const VERSION = '1.0.3';

    public function getConfig() : array {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Load Controllers
     */
    public function getControllerConfig() : array
    {
        return [
            'factories' => [
                Controller\IndexController::class => function($container) {
                    $oDbAdapter = $container->get(AdapterInterface::class);
                    return new Controller\IndexController(
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
            ],
        ];
    }
}
