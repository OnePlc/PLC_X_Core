<?php
/**
 * CoreExportController.php - Core Search Controller
 *
 * Main Controller for Skeleton based Searches
 *
 * @category Controller
 * @package Application
 * @author Verein onePlace
 * @copyright (C) 2020  Verein onePlace <admin@1plc.ch>
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @version 1.0.0
 * @since 1.0.5
 */

namespace Application\Controller;

use Application\Controller\CoreEntityController;
use OnePlace\Skeleton\Model\SkeletonTable;
use Laminas\Db\Sql\Where;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\AdapterInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;


class CoreSearchController extends CoreEntityController
{
    private $aFileNameSearch;
    private $aFileNameReplace;

    /**
     * ApiController constructor.
     *
     * @param AdapterInterface $oDbAdapter
     * @param SkeletonTable $oTableGateway
     * @since 1.0.0
     */
    public function __construct(AdapterInterface $oDbAdapter,$oTableGateway,$oServiceManager) {
        parent::__construct($oDbAdapter,$oTableGateway,$oServiceManager);
        $this->oTableGateway = $oTableGateway;
        $this->sSingleForm = 'skeleton-single';

        $this->aFileNameSearch = ['_a','_b','_c','_d','_e','_f','_g','_h','_i','_j','_k','_l','_n','_m','_o','_p','_q','_r','_s','_t','_u','_v','_w','_x','_y','_z'];
        $this->aFileNameReplace = ['A','B','C','D','E','F','G','H','I','J','K','L','N','M','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
    }

    /**
     * Dump Skeleton data to desired format
     *
     * @param string $sKey name of view
     * @return bool
     * @since 1.0.5
     */
    public function generateSearchView($sKey) {
        $this->sSingleForm = $sKey.'-single';

        # Set Layout based on users theme
        $this->setThemeBasedLayout($sKey);

        $oRequest = $this->getRequest();

        if(!$oRequest->isPost()) {
            # Load Tabs for View Form
            $this->setViewTabs($this->sSingleForm);

            # Load Fields for View Form
            $this->setFormFields($this->sSingleForm);

            return new ViewModel([
                'sFormName'=>$sKey.'-single',
            ]);
        } else {
            # Add Buttons for breadcrumb
            $this->setViewButtons($sKey.'-search');

            # Load Tabs for View Form
            $this->setViewTabs($this->sSingleForm);

            # Load Fields for View Form
            $this->setFormFields($this->sSingleForm);

            # Parse Raw Form Data
            $aData = $this->parseFormData($_REQUEST);

            $aAllFields = $this->getFormFields($sKey.'-single');
            $aFieldsByKey = [];
            foreach($aAllFields as $oField) {
                $aFieldsByKey[$oField->fieldkey] = $oField;
            }

            # Start building where query
            $aWhere = [];

            # loop data
            foreach(array_keys($aData) as $sFieldKey) {
                if($aData[$sFieldKey] != '') {
                    # add field to query based on type
                    switch($aFieldsByKey[$sFieldKey]->type) {
                        case 'select':
                            $aWhere[$sFieldKey] = $aData[$sFieldKey];
                            break;
                        case 'text':
                        case 'currency':
                        case 'textarea':
                        case 'date':
                        case 'time':
                        case 'datetime':
                        case 'email':
                        case 'number':
                            $aWhere[$sFieldKey.'-like'] = $aData[$sFieldKey];
                            break;
                        default:
                            break;
                    }
                }
            }

            $aItems = $this->oTableGateway->fetchAll(true,$aWhere);

            return new ViewModel([
                'sFormName'=>$sKey.'-single',
                'aResults' => $aItems,
            ]);
        }
    }
}