<?php
/**
 * modules.config.php - Modules Config
 *
 * Config File for Modules of this Application
 * Enable or disable modules here
 *
 * @category Config
 * @package Application
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

/**
 * List of enabled modules for this application.
 *
 * This should be an array of module namespaces used in the application.
 */
return [
    'Laminas\Paginator',
    'Laminas\Mvc\Plugin\FilePrg',
    'Laminas\Mvc\Plugin\FlashMessenger',
    'Laminas\Mvc\Plugin\Identity',
    'Laminas\Mvc\Plugin\Prg',
    'Laminas\Session',
    'Laminas\Mvc\I18n',
    'Laminas\I18n',
    'Laminas\Hydrator',
    'Laminas\Filter',
    'Laminas\Db',
    'Laminas\Router',
    'Laminas\Validator',
    'Application',
    'OnePlace\User',
    'OnePlace\Skeleton',
    'OnePlace\Contact',
];
