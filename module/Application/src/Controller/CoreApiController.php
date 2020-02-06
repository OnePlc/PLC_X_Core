<?php
/**
 * ApiController.php - Api Controller
 *
 * Main Controller API for all Modules
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

class CoreApiController extends CoreController {
    public function __construct(AdapterInterface $oDbAdapter,$oTableGateway = false,$oServiceManager) {
        parent::__construct($oDbAdapter,$oTableGateway,$oServiceManager);
    }

    public function getPublicFormFields($sForm) {
        $aFields = [];
        $oFieldsDB = $this->getFormFields($sForm);
        if(count($oFieldsDB) > 0) {
            foreach($oFieldsDB as $oField) {
                $aFields[] = $oField;
            }
        }
        return $aFields;
    }
}
