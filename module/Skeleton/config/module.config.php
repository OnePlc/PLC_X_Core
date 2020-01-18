<?php
/**
 * module.config.php - Skeleton Config
 *
 * Main Config File for Skeleton Module
 *
 * @category Config
 * @package Skeleton
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

namespace Skeleton;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    # Skeleton Module - Routes
    'router' => [
        'routes' => [
            # Module Basic Route
            'skeleton' => [
                'type'    => Segment::class,
                'options' => [
                    'route' => '/skeleton[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\SkeletonController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'skeleton-api' => [
                'type'    => Segment::class,
                'options' => [
                    'route' => '/skeleton/api[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    # View Settings
    'view_manager' => [
        'template_path_stack' => [
            'skeleton' => __DIR__ . '/../view',
        ],
    ],
];
