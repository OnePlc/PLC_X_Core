<?php
/**
 * CoreUpdateController.php - Update DB Controller
 *
 * Main Controller DB Update for all modules
 *
 * @category Controller
 * @package Application
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Application\Controller;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Sql\Select;
use Zend\I18n\Translator\Translator;

class CoreUpdateController extends CoreController {
    /**
     * Article Table Object
     *
     * @since 1.0.0
     */
    protected $oTableGateway;

    public function __construct(AdapterInterface $oDbAdapter,$oTableGateway = false,$oServiceManager) {
        parent::__construct($oDbAdapter,$oTableGateway,$oServiceManager);
    }

    /**
     * Parse SQL File from Installer and save to database
     *
     * @param string $sFile location of sql file
     * @param AdapterInterface $oAdapter database connection
     * @since 1.0.2.1
     */
    protected function parseSQLInstallFile($sFile,$oAdapter) {
        $templine = '';
        $lines = file($sFile);
        // Loop through each line
        foreach ($lines as $line)  {
            if (substr($line, 0, 2) == '--' || $line == '')
                continue;
            // Add this line to the current segment
            $templine .= $line;
            // If it has a semicolon at the end, it's the end of the query
            if (substr(trim($line), -1, 1) == ';')
            {
                $results = $oAdapter->query($templine, $oAdapter::QUERY_MODE_EXECUTE);
                $templine = '';
            }
        }
    }
}
