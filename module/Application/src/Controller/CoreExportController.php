<?php
/**
 * ExportController.php - Core Export Controller
 *
 * Main Controller for Export
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


class CoreExportController extends CoreController
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
     * @return array
     * @since 1.0.5
     */
    public function exportData($sTitle,$sKey) {
        $this->sSingleForm = $sKey.'-single';

        # set dump export mode
        $sMode = $this->params('id', 'csv');
        $spreadsheet = new Spreadsheet();

        # Set document properties
        $spreadsheet->getProperties()
            ->setCreator(CoreController::$oSession->oUser->getLabel())
            ->setLastModifiedBy(CoreController::$oSession->oUser->getLabel())
            ->setTitle($sTitle)
            ->setSubject('Export of all '.$sTitle.' Data')
            ->setDescription('This file contains all data of module '.$sTitle)
            ->setKeywords($sKey.' export')
            ->setCategory($sTitle.' Export');

        # Add some data
        $spreadsheet->setActiveSheetIndex(0);

        /**
         * Generate Header Row
         */
        $sCol = 'A';
        $aFields = $this->getFormFields($this->sSingleForm);
        foreach($aFields as $oField) {
            $spreadsheet->getActiveSheet()->getStyle($sCol.'1')->applyFromArray([
                'font' => [
                    'bold'=> true,
                    'size'=>14,
                ],
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],

            ]);
            $spreadsheet->getActiveSheet()->setCellValue($sCol.'1',$oField->label);
            $sCol++;
        }

        /**
         * Generate Data Rows
         */
        $aData = $this->oTableGateway->fetchAll(false,[],'label ASC');
        $iRowCounter = 2;
        $sMaxCol = 'A';
        foreach($aData as $oRow) {
            $sCol = 'A';
            foreach($aFields as $oField) {
                $sVal = '';
                # Output based on field type
                switch ($oField->type) {
                    case 'multiselect':
                        $aItems = $oRow->getMultiSelectField($oField->fieldkey,true);
                        foreach($aItems as $oItem) {
                            if(property_exists($oItem,'tag_value')) {
                                $sVal .= $oItem->tag_value.',';
                            } else {
                                $sVal .= $oItem->getLabel().',';
                            }
                        }
                        break;
                    case 'select':
                        $oItem = $oRow->getSelectField($oField->fieldkey);
                        if($oItem) {
                            $sVal .= $oItem->getLabel();
                        } else {
                            $sVal .= '-';
                        }
                        break;
                    case 'url':
                        if(!$oRow->getTextField($oField->fieldkey)) {
                            $sVal = '-';
                        } else {
                            $sVal = '<a href="#">'.$oRow->getTextField($oField->fieldkey).'</a>';
                        }
                        break;
                    case 'text':
                        if(!$oRow->getTextField($oField->fieldkey)) {
                            $sVal = '-';
                        } else {
                            $sVal = $oRow->getTextField($oField->fieldkey);
                        }
                        break;
                    case 'date':
                        if($oRow->getTextField($oField->fieldkey)) {
                            if($oRow->getTextField($oField->fieldkey) != '0000-00-00') {
                                $sVal = date('d.m.Y',strtotime($oRow->getTextField($oField->fieldkey)));;
                            } else {
                                $sVal .= '-';
                            }
                        } else {
                            $sVal .= '-';
                        }
                        break;
                    case 'datetime':
                    case 'time':
                        if($oRow->getTextField($oField->fieldkey)) {
                            if($oRow->getTextField($oField->fieldkey) != '0000-00-00 00:00:00') {
                                $sVal = date('d.m.Y',strtotime($oRow->getTextField($oField->fieldkey)));;
                            } else {
                                $sVal .= '-';
                            }
                        } else {
                            $sVal .= '-';
                        }
                        break;
                    case 'currency':
                    case 'readonly-currency':
                        if(!$oRow->getTextField($oField->fieldkey)) {
                            $sVal = '-';
                        } else {
                            $sVal = number_format($oRow->getTextField($oField->fieldkey),2,'.','\'');
                        }
                        break;
                    case 'partial':
                    default:
                        break;
                }
                $spreadsheet->getActiveSheet()->setCellValue($sCol.$iRowCounter,$sVal);
                $sCol++;
                $sMaxCol = $sCol;
            }
            $iRowCounter++;
        }
        for($sCol = 'A';$sCol != $sMaxCol;$sCol++) {
            $spreadsheet->getActiveSheet()->getColumnDimension($sCol)->setAutoSize(true);
        }

        // Rename worksheet
        $spreadsheet->getActiveSheet()
            ->setTitle($sTitle);

        $sPath = $_SERVER['DOCUMENT_ROOT'].'/data/'.$sKey.'/export/test-core.xlsx';
        // Save
        $writer = new Xlsx($spreadsheet);
        $writer->save($sPath);

        # Add XP for creating an export
        CoreController::$oSession->oUser->addXP($sKey.'-export');

        sleep(1);

        return [
            'href'=>'/data/'.$sKey.'/export/test-core.xlsx',
            'label'=>'Download Excel File',
            'icon'=>'fas fa-download',
            'class'=>'btn-primary',
        ];
    }
}