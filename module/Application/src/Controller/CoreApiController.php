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
use Laminas\Db\Sql\Select;
use Zend\I18n\Translator\Translator;

class CoreApiController extends CoreController {
    /**
     * Article Table Object
     *
     * @since 1.0.0
     */
    protected $oTableGateway;

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

    /**
     * API Home - Main Index
     *
     * @return bool - no View File
     * @since 1.0.0
     */
    public function indexAction() {
        $this->layout('layout/json');

        $aReturn = ['state'=>'success','message'=>'Welcome to onePlace '.$this->sApiName.' API'];
        echo json_encode($aReturn);

        return false;
    }

    /**
     * List all Entities of Articles
     *
     * @return bool - no View File
     * @since 1.0.0
     */
    public function listAction() {
        $this->layout('layout/json');

        $sEntityType = explode('-',$this->sSingleForm)[0];

        # Check license
        if(!$this->checkLicense($sEntityType)) {
            $aReturn = ['state'=>'error','message'=>'no valid license for '.$sEntityType.' found'];
            echo json_encode($aReturn);
            return false;
        }

        $iPage = $this->params()->fromRoute('id', 0);

        # Set default values
        $bSelect2 = true;
        $sListLabel = 'label';

        # Get list mode from query
        if(isset($_REQUEST['listmode'])) {
            if($_REQUEST['listmode'] == 'entity') {
                $bSelect2 = false;
            }
        }

        # get list label from query
        if(isset($_REQUEST['listlabel'])) {
            $sListLabel = $_REQUEST['listlabel'];
        }

        # get list label from query
        $sLang = 'en_US';
        if(isset($_REQUEST['lang'])) {
            $sLang = $_REQUEST['lang'];
        }

        // translating system
        $translator = new Translator();
        $aLangs = ['en_US','de_DE'];
        foreach($aLangs as $sLoadLang) {
            if(file_exists('vendor/oneplace/oneplace-translation/language/'.$sLoadLang.'.mo')) {
                $translator->addTranslationFile('gettext', 'vendor/oneplace/oneplace-translation/language/'.$sLang.'.mo', $sEntityType, $sLoadLang);
            }
        }

        $translator->setLocale($sLang);

        # Init Item List for Response
        $aItems = [];

        $aFields = $this->getFormFields($this->sSingleForm);
        $aFieldsByKey = [];
        # fields are sorted by tab , we need an index with all fields
        foreach($aFields as $oField) {
            $aFieldsByKey[$oField->fieldkey] = $oField;
        }

        # only allow form fields as list labels
        if(!array_key_exists($sListLabel,$aFieldsByKey)) {
            $aReturn = [
                'state'=>'error',
                'results' => [],
                'message' => 'invalid list label',
            ];

            # Print List with all Entities
            echo json_encode($aReturn);
            return false;
        }

        $bPaginated = false;
        $aWhere = [];
        if(isset($_REQUEST['filter'])) {
            switch($_REQUEST['filter']) {
                case 'highlights':
                    $aWhere['web_highlight_idfs'] = 2;
                    break;
                case 'category':
                    $aWhere['multi_tag'] = $_REQUEST['filtervalue'];
                    break;
                default:
                    break;
            }
        }

        if(isset($_REQUEST['listmodefilter'])) {
            if($_REQUEST['listmodefilter'] == 'webonly') {
                $aWhere['show_on_web_idfs'] = 2;
            }
        }

        if($iPage > 0) {
            $bPaginated = true;
        }

        # Get All Article Entities from Database
        $oItemsDB = $this->oTableGateway->fetchAll($bPaginated,$aWhere);
        if($bPaginated) {
            $oItemsDB->setItemCountPerPage(25);
            $oItemsDB->setCurrentPageNumber($iPage);
        }
        if(count($oItemsDB) > 0) {
            # Loop all items
            foreach($oItemsDB as $oItem) {

                # Output depending on list mode
                if($bSelect2) {
                    $sVal = null;
                    # get value for list label field
                    switch($aFieldsByKey[$sListLabel]->type) {
                        case 'select':
                            $oTag = $oItem->getSelectField($aFieldsByKey[$sListLabel]->fieldkey);
                            if($oTag) {
                                $sVal = $oTag->getLabel();
                            }
                            break;
                        case 'text':
                        case 'date':
                        case 'textarea':
                            $sVal = $oItem->getTextField($aFieldsByKey[$sListLabel]->fieldkey);
                            break;
                        default:
                            break;
                    }
                    $aItems[] = ['id'=>$oItem->getID(),'text'=>$sVal];
                } else {
                    # Init public item
                    $aPublicItem = ['id'=>$oItem->getID()];

                    # add all fields to item
                    foreach($aFields as $oField) {
                        switch($oField->type) {
                            case 'multiselect':
                                # get selected
                                $oTags = $oItem->getMultiSelectField($oField->fieldkey);
                                $aTags = [];
                                foreach($oTags as $oTag) {
                                    $aTags[] = ['id'=>$oTag->id,'label'=>$translator->translate($oTag->text,$sEntityType,$sLang)];
                                }
                                $aPublicItem[$oField->fieldkey] = $aTags;
                                break;
                            case 'select':
                                # get selected
                                try {
                                    $oTag = $oItem->getSelectField($oField->fieldkey);
                                } catch(\RuntimeException $e) {

                                }
                                if(isset($oTag)) {
                                    if (is_object($oTag)) {
                                        if (property_exists($oTag, 'tag_value')) {
                                            $aPublicItem[$oField->fieldkey] = ['id' => $oTag->id, 'label' => $translator->translate($oTag->tag_value, $sEntityType, $sLang)];
                                        } else {
                                            $aPublicItem[$oField->fieldkey] = ['id' => $oTag->getID(), 'label' => $translator->translate($oTag->getLabel(), $sEntityType, $sLang)];
                                        }
                                    }
                                }
                                break;
                            case 'text':
                            case 'date':
                            case 'textarea':
                                $aPublicItem[$oField->fieldkey] = $translator->translate($oItem->getTextField($oField->fieldkey),$sEntityType,$sLang);
                                break;
                            case 'featuredimage':
                                $sImg = $oItem->getTextField('featured_image');
                                if($sImg != '') {
                                    $aPublicItem['featured_image'] = '/data/'.$sEntityType.'/' . $oItem->getID() . '/' . $sImg;
                                }
                                break;
                            case 'gallery':
                                $oMediaSel = new Select(CoreApiController::$aCoreTables['core-gallery-media']->getTable());
                                $oMediaSel->where(['entity_idfs'=>$oItem->getID(),'entity_type'=>$sEntityType,'is_public'=>1]);
                                $oMediaSel->order('sort_id ASC');
                                $oMediaDB = CoreApiController::$aCoreTables['core-gallery-media']->selectWith($oMediaSel);
                                if(count($oMediaDB) > 0) {
                                    $aImages = [];
                                    foreach($oMediaDB as $oMedia) {
                                        $aImages[] = $oMedia->filename;
                                    }
                                    $aPublicItem['gallery'] = $aImages;
                                }
                                break;
                            case 'url':
                                $aPublicItem[$oField->fieldkey] = $oItem->getTextField($oField->fieldkey);
                                break;
                            default:
                                break;
                        }
                    }

                    # add item to list
                    $aItems[] = $aPublicItem;
                }

            }
        }

        /**
         * Build Select2 JSON Response
         */
        $aReturn = [
            'state'=>'success',
            'results' => $aItems,
            'pagination' => (object)['more'=>false],
        ];

        if($bPaginated) {
            $aReturn['pages'] = $oItemsDB->getPages()->pageCount;
        }

        # Print List with all Entities
        echo json_encode($aReturn);

        return false;
    }

    /**
     * Get a single Entity of Article
     *
     * @return bool - no View File
     * @since 1.0.0
     */
    public function getAction() {
        $this->layout('layout/json');

        $sEntityType = explode('-',$this->sSingleForm)[0];

        # Check license
        if(!$this->checkLicense($sEntityType)) {
            $aReturn = ['state'=>'error','message'=>'no valid license for '.$sEntityType.' found'];
            echo json_encode($aReturn);
            return false;
        }

        # Get entity ID from route
        $iItemID = $this->params()->fromRoute('id', 0);

        # Try to get Article
        try {
            $oItem = $this->oTableGateway->getSingle($iItemID);
        } catch (\RuntimeException $e) {
            # Display error message
            $aReturn = ['state'=>'error','message'=>'item not found','oItem'=>[]];
            echo json_encode($aReturn);
            return false;
        }

        # get list label from query
        $sLang = 'en_US';
        if(isset($_REQUEST['lang'])) {
            $sLang = $_REQUEST['lang'];
        }

        $translator = new Translator();
        $aLangs = ['en_US','de_DE'];
        foreach($aLangs as $sLoadLang) {
            if(file_exists('vendor/oneplace/oneplace-translation/language/'.$sLoadLang.'.mo')) {
                $translator->addTranslationFile('gettext', 'vendor/oneplace/oneplace-translation/language/'.$sLang.'.mo', $sEntityType, $sLoadLang);
            }
        }

        $translator->setLocale($sLang);

        $aFields = $this->getFormFields($this->sSingleForm);
        $aFieldsByKey = [];
        # fields are sorted by tab , we need an index with all fields
        foreach($aFields as $oField) {
            $aFieldsByKey[$oField->fieldkey] = $oField;
        }

        # Init public item
        $aPublicItem = ['id'=>$oItem->getID()];

        # add all fields to item
        foreach($aFields as $oField) {
            switch($oField->type) {
                case 'multiselect':
                    # get selected
                    $oTags = $oItem->getMultiSelectField($oField->fieldkey);
                    $aTags = [];
                    foreach($oTags as $oTag) {
                        $aTags[] = ['id'=>$oTag->id,'label'=>$translator->translate($oTag->text,$sEntityType,$sLang)];
                    }
                    $aPublicItem[$oField->fieldkey] = $aTags;
                    break;
                case 'select':
                    # get selected
                    try {
                        $oTag = $oItem->getSelectField($oField->fieldkey);
                    } catch(\RuntimeException $e) {

                    }
                    if(isset($oTag)) {
                        if (is_object($oTag)) {
                            if (property_exists($oTag, 'tag_value')) {
                                $aPublicItem[$oField->fieldkey] = ['id' => $oTag->id, 'label' => $translator->translate($oTag->tag_value, $sEntityType, $sLang)];
                            } else {
                                $aPublicItem[$oField->fieldkey] = ['id' => $oTag->getID(), 'label' => $translator->translate($oTag->getLabel(), $sEntityType, $sLang)];
                            }
                        }
                    }
                    break;
                case 'text':
                case 'date':
                case 'textarea':
                    $aPublicItem[$oField->fieldkey] = $translator->translate($oItem->getTextField($oField->fieldkey),$sEntityType,$sLang);
                    break;
                case 'featuredimage':
                    $aPublicItem['featured_image'] = '/data/'.$sEntityType.'/'.$oItem->getID().'/'.$oItem->getTextField('featured_image');
                    break;
                case 'gallery':
                    $oMediaSel = new Select(CoreApiController::$aCoreTables['core-gallery-media']->getTable());
                    $oMediaSel->where(['entity_idfs'=>$oItem->getID(),'entity_type'=>$sEntityType,'is_public'=>1]);
                    $oMediaSel->order('sort_id ASC');
                    $oMediaDB = CoreApiController::$aCoreTables['core-gallery-media']->selectWith($oMediaSel);
                    if(count($oMediaDB) > 0) {
                        $aImages = [];
                        foreach($oMediaDB as $oMedia) {
                            $aImages[] = $oMedia->filename;
                        }
                        $aPublicItem['gallery'] = $aImages;
                    }
                    break;
                case 'url':
                    $aPublicItem[$oField->fieldkey] = $oItem->getTextField($oField->fieldkey);
                    break;
                default:
                    break;
            }
        }

        # Print Entity
        $aReturn = ['state'=>'success','message'=>'Item found','oItem'=>$aPublicItem];
        echo json_encode($aReturn);

        return false;
    }

    public function getfieldsAction() {
        $this->layout('layout/json');

        $aData = $this->getPublicFormFields($this->sSingleForm);
        $sEntityType = explode('-',$this->sSingleForm)[0];

        # get list label from query
        $sLang = 'en_US';
        if(isset($_REQUEST['lang'])) {
            $sLang = $_REQUEST['lang'];
        }

        // translating system
        $translator = new Translator();
        $aLangs = ['en_US','de_DE'];
        foreach($aLangs as $sLoadLang) {
            if(file_exists('vendor/oneplace/oneplace-translation/language/'.$sLoadLang.'.mo')) {
                $translator->addTranslationFile('gettext', 'vendor/oneplace/oneplace-translation/language/'.$sLang.'.mo', $sEntityType, $sLoadLang);
            }
        }

        $translator->setLocale($sLang);

        return new ViewModel([
            'aFields' => $aData,
            'translator' => $translator,
            'sEntityType' => $sEntityType,
        ]);
    }
}
