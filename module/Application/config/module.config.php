<?php
/**
 * module.config.php - Application Config
 *
 * Main Config File for Application Module
 *
 * @category Config
 * @package Application
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'filepond' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/filepond',
                    'defaults' => [
                        'controller' => Controller\UploadController::class,
                        'action'     => 'filepond',
                    ],
                ],
            ],
            'uppy' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/uppy',
                    'defaults' => [
                        'controller' => Controller\UploadController::class,
                        'action'     => 'uppy',
                    ],
                ],
            ],
            'uppy-togglepub' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/uppy/togglemediapub[/:id]',
                    'constraints' => [
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UploadController::class,
                        'action'     => 'togglemediapub',
                    ],
                ],
            ],
            'form-updatetabsort' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/application/updatetabsort',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'updatetabsort',
                    ],
                ],
            ],
            'uppy-updatesort' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/uppy/updatesort',
                    'defaults' => [
                        'controller' => Controller\UploadController::class,
                        'action'     => 'updateuppysort',
                    ],
                ],
            ],
            'add-theme' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/themes/add',
                    'defaults' => [
                        'controller' => Controller\UploadController::class,
                        'action'     => 'addtheme',
                    ],
                ],
            ],
            'setup' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/setup',
                    'defaults' => [
                        'controller' => Controller\SetupController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'update' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/update',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'update',
                    ],
                ],
            ],
            'quicksearch' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/quicksearch',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'quicksearch',
                    ],
                ],
            ],
            'form-updatesorting' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/form/updatefieldsort[/:formname]',
                    'constraints' => [
                        'formname' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'updatefieldsort',
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'api' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api[/:module[/:action[/:id]]]',
                    'constraints' => [
                        'module' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ApiController::class,
                        'module'     => 'core',
                        'action'    => 'index',
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout-default.phtml',
            'layout/modal'           => __DIR__ . '/../view/layout/modal.phtml',
            'layout/setup'           => __DIR__ . '/../view/layout/layout-setup.phtml',
            'layout/error' => __DIR__ . '/../view/layout/error.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    # Translator
    'translator' => [
        'locale' => 'de_DE',
        'translation_file_patterns' => [
            [
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ],
        ],
    ],
];
