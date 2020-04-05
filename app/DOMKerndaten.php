<?php
/**
 * Project: fif_offenevergaben
 * User: ives_markus
 * Date: 30/03/20
 */

namespace App;

use Carbon\Carbon;
use DOMDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class DOMKerndaten
 *
 * Kerndaten representation
 *
 * @package App
 */
class DOMKerndaten
{
    /**
     * @var DOMDocument $document
     */
    protected $document;

    protected $log;

    protected $info;

    protected $type;
    protected $contractingBody;
    protected $objectContract;
    protected $procedure;
    protected $awardContract;
    protected $modificationsContract;
    protected $results;
    protected $additionalCoreData;
    protected $lefti;    // this is so rare at the time of writing we will completely ignore it for now

    public function __construct($xml, $log = null, $info = null) {
        if ($xml instanceof DOMDocument) {
            $this->document = $xml;
        } else if (is_string($xml)) {
            $this->document = $this->createDocument($xml);
        } else {
            throw new \Exception('Parameter $xml must be a loaded DOMDocument, a xml string or an abs. file path.');
        }

        $this->log = $log ? $log : Log::channel('processor_daily');

        $this->info = $info;

        $this->loadAll();

        if (false) {        // DEBUG ONE RECORD
            dump('type --------------------------');
            dump($this->type);
            dump('contracting body --------------');
            dump($this->contractingBody);
            dump('object contract----------------');
            dump($this->objectContract);
            dump('procedure ---------------------');
            dump($this->procedure);
            dump('award contract ----------------');
            dump($this->awardContract);
            dump('modifications contract --------');
            dump($this->modificationsContract);
            dump('results  ----------------------');
            dump($this->results);
            dump('additional core data ----------');
            dump($this->additionalCoreData);
            dump('lefti -------------------------');
            dump($this->lefti);
            dd('end');
        }
    }

    protected function createDocument($xmlString) {

        $doc = new DOMDocument();

        try {
            // if $xmlString starts with a slash we assume its a file path
            if (Str::startsWith($xmlString,'/')) {
                $doc->load($xmlString);
            } else {
                $doc->loadXML($xmlString);
            }
        } catch(\Exception $ex) {
            throw new \Exception('Unable to create DOMDocument from string argument',null,$ex);
        }

        return $doc;
    }

    public function getDocument() {
        return $this->document;
    }

    public function loadAll() {
        $this->type = $this->document->firstChild->tagName;

        $this->loadContractingBody();
        $this->loadObjectContract();
        $this->loadProcedure();
        $this->loadAwardContract();
        $this->loadModificationsContract();
        $this->loadResults();
        $this->loadAdditionalCoreData();
        $this->loadLefti();
    }

    public function getData() {
        $data = new \stdClass();

        $data->type = $this->type;
        $data->contractingBody = $this->contractingBody;
        $data->objectContract = $this->objectContract;
        $data->procedure = $this->procedure;
        $data->awardContract = $this->awardContract;
        $data->modificationsContract = $this->modificationsContract;
        $data->results = $this->results;
        $data->additionalCoreData = $this->additionalCoreData;
        $data->lefti = $this->lefti;

        return $data;
    }

    /**
     *
     */
    protected function loadContractingBody() {
        $cb = new \stdClass();
        $domCb = $this->getFirstElementByTagName($this->document->documentElement, 'CONTRACTING_BODY');

        $cb->address                 = $this->loadAddressContractingBody($domCb, 'ADDRESS_CONTRACTING_BODY');
        $cb->addressAdditional       = [];

        foreach($domCb->getElementsByTagName('ADDRESS_CONTRACTING_BODY_ADDITIONAL') as $additional) {
            $cb->addressAdditional[] = $this->loadAddressContractingBody($additional);
        }

        $cb->urlDocument             = $this->getStringByTagName($domCb,'URL_DOCUMENT');
        $cb->urlParticipation        = $this->getStringByTagName($domCb,'URL_PARTICIPATION');
        $cb->urlDocumentIsFull       = $this->getBooleanByTagName($domCb,'DOCUMENT_FULL');
        $cb->urlDocumentIsRestricted = $this->getBooleanByTagName($domCb,'DOCUMENT_RESTRICTED');

        $this->contractingBody = $cb;
    }

    /**
     * @param $element
     * @param $tagName
     * @return \stdClass
     */
    protected function loadAddressContractingBody($element, $tagName = null) {

        if ($tagName) {
            $element = $this->getFirstElementByTagName($element,$tagName);
        }

        $adr = new \stdClass();
        $adr->officialName = $this->getStringByTagName($element, 'OFFICIALNAME');
        $adr->nationalId   = $this->getStringByTagName($element, 'NATIONALID');
        $adr->phone        = $this->getStringByTagName($element, 'PHONE');
        $adr->email        = $this->getStringByTagName($element, 'E_MAIL');
        $adr->contact      = $this->getStringByTagName($element, 'CONTACT');
        $adr->domain       = $this->getStringByTagName($element, 'DOMAIN');
        $adr->refNumber    = $this->getStringByTagName($element, 'REFERENCE_NUMBER');

        return $adr;
    }

    protected function loadAddressContractor($element, $tagName = null) {

        if ($tagName) {
            $element = $this->getFirstElementByTagName($element,$tagName);
        }

        $adr = new \stdClass();
        $adr->officialName = $this->getStringByTagName($element, 'OFFICIALNAME');
        $adr->nationalId   = $this->getStringByTagName($element, 'NATIONALID');

        return $adr;
    }

    /**
     *
     */
    protected function loadObjectContract() {
        // object contract
        //    object description
        $domOc     = $this->getFirstElementByTagName($this->document->documentElement, 'OBJECT_CONTRACT');
        $domOdescr = $this->getFirstElementByTagName($domOc, 'OBJECT_DESCR');

        $oc = new \stdClass();

        // OC --> TITLE
        $oc->title = null;
        if ($this->elementExists($domOc, 'TITLE')) {
            $oc->title = $this->getMultiLineTextByTagName($domOc, 'TITLE');
        }

        // OC --> SHORT_DESCR
        $oc->description = null;
        if ($this->elementExists($domOc, 'SHORT_DESCR')) {
            $oc->description = $this->getMultiLineTextByTagName($domOc, 'SHORT_DESCR');
        }

        // OC --> CPV MAIN
        $oc->cpv = null;
        if ($this->elementExists($domOc, 'CPV_MAIN')) {
            $oc->cpv = $this->getFirstElementByTagName($this->getFirstElementByTagName($domOc, 'CPV_MAIN'), 'CPV_CODE')->getAttribute('CODE');
        }

        // OC --> TYPE_CONTRACT
        $oc->type = null;
        if ($this->elementExists($domOc, 'TYPE_CONTRACT')) {
            $oc->type = $this->getFirstElementByTagName($domOc, 'TYPE_CONTRACT')->getAttribute('CTYPE');
        }

        // OC --> REFERENCE_NUMBER
        $oc->refNumber = null;
        if ($this->elementExists($domOc, 'REFERENCE_NUMBER')) {
            $oc->refNumber = $this->getStringByTagName($domOc, 'REFERENCE_NUMBER');
        }

        // OC --> LOT_DIVISION && OC --> NO_LOT_DIVISION
        $oc->lot   = $this->getBooleanByTagName($domOc, 'LOT_DIVISION');
        $oc->noLot = $this->getBooleanByTagName($domOc, 'NO_LOT_DIVISION');

        // OC --> OBJECT DESCRIPTION --> NUTS
        $oc->nuts = null;
        if ($domOdescr && $this->elementExists($domOdescr, 'NUTS')) {
            $oc->nuts = $this->getFirstElementByTagName($domOdescr, 'NUTS')->getAttribute('CODE');
        }

        // OC --> OBJECT DESCRIPTION --> CPV_ADDITIONAL[]
        $oc->additionalCpvs = [];
        if ($domOdescr && $this->elementExists($domOdescr, 'CPV_ADDITIONAL')) {
            foreach($domOdescr->getElementsByTagName('CPV_ADDITIONAL') as $cpvAdd) {
                $oc->additionalCpvs[] = $this->getFirstElementByTagName($cpvAdd, 'CPV_CODE')->getAttribute('CODE');
            }
        }

        // OC --> OBJECT DESCRIPTION --> DATE_START
        $oc->dateStart = null;
        if ($domOdescr && $this->elementExists($domOdescr, 'DATE_START')) {
            $oc->dateStart = $this->getDateByTagName($domOdescr, 'DATE_START');
        }

        // OC --> OBJECT DESCRIPTION --> DATE_END
        $oc->dateEnd = null;
        if ($domOdescr && $this->elementExists($domOdescr, 'DATE_END')) {
            $oc->dateEnd = $this->getDateByTagName($domOdescr, 'DATE_END');
        }

        // OC --> OBJECT_DESCRIPTION --> DURATION
        $oc->duration = null;

        if ($domOdescr && $this->elementExists($domOdescr, 'DURATION')) {
            $domDuration = $this->getFirstElementByTagName($domOdescr, 'DURATION');

            // store duration as DAYS, in case source data is specified in months automatically calculate day value
            if ($domDuration->hasAttribute('TYPE') && $domDuration->getAttribute('TYPE') == 'MONTH') {
                $oc->duration = intval($this->getString($domDuration)) * 30;
            } else {
                $oc->duration = intval($this->getString($domDuration));
            }
        }

        $this->objectContract = $oc;
    }

    /**
     *
     */
    protected function loadProcedure() {
        if (!$this->elementExists($this->document->documentElement, 'PROCEDURE')) {
            return;
        }

        $domPr     = $this->getFirstElementByTagName($this->document->documentElement, 'PROCEDURE');

        $p = new \stdClass();

        $p->dateTimeReceiptTenders = null;
        if ($this->elementExists($domPr, 'DATETIME_RECEIPT_TENDERS')) {
            $p->dateTimeReceiptTenders = $this->getDateByTagName($domPr,'DATETIME_RECEIPT_TENDERS');
        }

        $p->procedures = [];
        if ($this->elementExists($domPr,'PT_OPEN')) { $p->procedures[] = 'PT_OPEN'; }
        if ($this->elementExists($domPr,'PT_RESTRICTED')) { $p->procedures[] = 'PT_RESTRICTED'; }
        if ($this->elementExists($domPr,'PT_INVITED')) { $p->procedures[] = 'PT_INVITED'; }
        if ($this->elementExists($domPr,'PT_WITH_PRIOR_NOTICE')) { $p->procedures[] = 'PT_WITH_PRIOR_NOTICE'; }
        if ($this->elementExists($domPr,'PT_WITHOUT_PRIOR_NOTICE')) { $p->procedures[] = 'PT_WITHOUT_PRIOR_NOTICE'; }
        if ($this->elementExists($domPr,'PT_COMPETITIVE_NEGOTIATION')) { $p->procedures[] = 'PT_COMPETITIVE_NEGOTIATION'; }
        if ($this->elementExists($domPr,'PT_COMPETITIVE_DIALOGUE')) { $p->procedures[] = 'PT_COMPETITIVE_DIALOGUE'; }
        if ($this->elementExists($domPr,'PT_INNOVATION_PARTNERSHIP')) { $p->procedures[] = 'PT_INNOVATION_PARTNERSHIP'; }
        if ($this->elementExists($domPr,'PT_SPECIAL_SERVICE')) { $p->procedures[] = 'PT_SPECIAL_SERVICE'; }
        if ($this->elementExists($domPr,'PT_DIRECT')) { $p->procedures[] = 'PT_DIRECT'; }
        if ($this->elementExists($domPr,'PT_IMPLEMENTATION')) { $p->procedures[] = 'PT_IMPLEMENTATION'; }
        if ($this->elementExists($domPr,'DPS')) { $p->procedures[] = 'DPS'; }

        $p->procedures = count($p->procedures) > 0 ? $p->procedures : null;
        $p->isFramework = $this->elementExists($domPr, 'FRAMEWORK');

        $this->procedure = $p;
    }

    /**
     *
     */
    protected function loadAwardContract() {
        if (!$this->elementExists($this->document->documentElement, 'AWARD_CONTRACT')) {
            return;
        }

        $domAcRoot = $this->getFirstElementByTagName($this->document->documentElement, 'AWARD_CONTRACT');
        $domAc     = $this->getFirstElementByTagName($domAcRoot, 'AWARDED_CONTRACT');

        if (!$domAc) {
            // This is possible on error OR if no contract has been awarded, as indicated by
            // AWARD_CONTRACT --> NO_AWARDED_CONTRACT
            // @see quelle=ausschreibungat item_id=CEB8FDA48287BACB
            return;
        }

        $ac = new \stdClass();

        $ac->dateConclusionContract = null;
        if ($this->elementExists($domAc, 'DATE_CONCLUSION_CONTRACT')) {
            $ac->dateConclusionContract = $this->getDateByTagName($domAc, 'DATE_CONCLUSION_CONTRACT');
        }

        $ac->nbTendersReceived = null;
        if ($this->elementExists($domAc, 'NB_TENDERS_RECEIVED')) {
            $ac->nbTendersReceived = intval($this->getStringByTagName($domAc, 'NB_TENDERS_RECEIVED'));
        }

        $ac->nbSmeTender = null;
        if ($this->elementExists($domAc, 'NB_SME_TENDER')) {
            $ac->nbSmeTender = intval($this->getStringByTagName($domAc, 'NB_SME_TENDER'));
        }

        $ac->nbSmeContractor  = $this->getBooleanByTagName($domAc, 'NB_SME_CONTRACTOR');

        $ac->valTotal = null;
        $ac->valTotalCurrency = null;
        if ($this->elementExists($domAc, 'VAL_TOTAL')) {
            $valTotal = $this->getFirstElementByTagName($domAc, 'VAL_TOTAL');

            $ac->valTotal = $this->getString($valTotal);
            $ac->valTotalCurrency = $valTotal->getattribute('CURRENCY');
        }

        // AWARDED_CONTRACT --> AWARD_CONTRACT --> CONTRACTOR --> ADDRESS_CONTRACTOR
        $ac->contractors       = [];
        if ($this->elementExists($domAc, 'CONTRACTOR')) {
            foreach($domAc->getElementsByTagName('CONTRACTOR') as $domContractor) {
                if ($this->elementExists($domContractor, 'ADDRESS_CONTRACTOR')) {
                    $ac->contractors[] = $this->loadAddressContractor($domContractor, 'ADDRESS_CONTRACTOR');
                }
            }
        }

        $this->awardContract = $ac;
    }

    /**
     *
     */
    protected function loadModificationsContract() {
        if (!$this->elementExists($this->document->documentElement, 'MODIFICATIONS_CONTRACT')) {
            return;
        }

        $domMcRoot = $this->getFirstElementByTagName($this->document->documentElement, 'MODIFICATIONS_CONTRACT');
        $domDp     = $this->getFirstElementByTagName($domMcRoot, 'DESCRIPTION_PROCUREMENT');

        $mc = new \stdClass();

        // MODIFICATIONS_CONTRACT --> DESCRIPTION_PROCUREMENT --> CPV_MAIN
        $mc->cpv = null;
        if ($domDp && $this->elementExists($domDp,'CPV_MAIN')) {
            $mc->cpv = $this->getFirstElementByTagName($this->getFirstElementByTagName($domDp, 'CPV_MAIN'), 'CPV_CODE')->getAttribute('CODE');
        }

        //                                                        CPV_ADDITIONAL
        $mc->additionalCpvs = [];
        if ($domDp && $this->elementExists($domDp, 'CPV_ADDITIONAL')) {
            foreach($domDp->getElementsByTagName('CPV_ADDITIONAL') as $cpvAdd) {
                $mc->additionalCpvs[] = $this->getFirstElementByTagName($cpvAdd, 'CPV_CODE')->getAttribute('CODE');
            }
        }

        //                                                        SHORT_DESCR
        $mc->description = null;
        if ($domDp && $this->elementExists($domDp, 'SHORT_DESCR')) {
            $mc->description = $this->getMultiLineTextByTagName($domDp, 'SHORT_DESCR');
        }

        //                                                        CONTRACTOR --> ADDRESS_CONTRACTOR
        $mc->contractors       = [];
        if ($domDp && $this->elementExists($domDp, 'CONTRACTOR')) {
            foreach($domDp->getElementsByTagName('CONTRACTOR') as $domContractor) {
                if ($domDp && $this->elementExists($domContractor, 'ADDRESS_CONTRACTOR')) {
                    $mc->contractors[] = $this->loadAddressContractor($domContractor, 'ADDRESS_CONTRACTOR');
                }
            }
        }

        $domIm     = $this->getFirstElementByTagName($domMcRoot, 'INFO_MODIFICATIONS');

        // MODIFICATIONS_CONTRACT --> INFO_MODIFICATIONS --> ADDITIONAL_NEED
        $mc->additionalNeed = null;
        if ($domIm && $this->elementExists($domIm, 'ADDITIONAL_NEED')) {
            $mc->additionalNeed = $this->getMultiLineTextByTagName($domIm, 'ADDITIONAL_NEED');
        }

        //                                                   UNFORESEEN_CIRCUMSTANCE
        $mc->unforeseenCircumstance = null;
        if ($domIm && $this->elementExists($domIm, 'UNFORESEEN_CIRCUMSTANCE')) {
            $mc->unforeseenCircumstance = $this->getMultiLineTextByTagName($domIm, 'UNFORESEEN_CIRCUMSTANCE');
        }

        //                                                   VAL_TOTAL_BEFORE
        $mc->valTotalBefore = null;
        $mc->valTotalBeforeCurrency = null;
        if ($domIm && $this->elementExists($domIm, 'VAL_TOTAL_BEFORE')) {
            $valTotalBefore = $this->getFirstElementByTagName($domIm, 'VAL_TOTAL_BEFORE');

            $mc->valTotalBefore = $this->getString($valTotalBefore);
            $mc->valTotalBeforeCurrency = $valTotalBefore->getattribute('CURRENCY');
        }

        //                                                   VAL_TOTAL_AFTER
        $mc->valTotalAfter = null;
        $mc->valTotalAfterCurrency = null;
        if ($domIm && $this->elementExists($domIm, 'VAL_TOTAL_AFTER')) {
            $valTotalAfter = $this->getFirstElementByTagName($domIm, 'VAL_TOTAL_AFTER');

            $mc->valTotalAfter = $this->getString($valTotalAfter);
            $mc->valTotalAfterCurrency = $valTotalAfter->getattribute('CURRENCY');
        }

        $this->modificationsContract = $mc;
    }

    /**
     *
     */
    protected function loadResults() {
        if (!$this->elementExists($this->document->documentElement, 'RESULTS')) {
            return;
        }

        $domReRoot = $this->getFirstElementByTagName($this->document->documentElement, 'RESULTS');
        $domAp     = $this->getFirstElementByTagName($domReRoot, 'AWARDED_PRIZE');

        if (!$domAp) {
            return;
        }

        $re = new \stdClass();

        $re->nbParticipants = null;
        if ($this->elementExists($domAp, 'NB_PARTICIPANTS')) {
            $re->nbParticipants = intval($this->getStringByTagName($domAp,'NB_PARTICIPANTS'));
        }

        $re->nbParticipantsSme = null;
        if ($this->elementExists($domAp, 'NB_PARTICIPANTS_SME')) {
            $re->nbParticipantsSme = intval($this->getStringByTagName($domAp,'NB_PARTICIPANTS_SME'));
        }

        $re->valPrize = null;
        $re->valPrizeCurrency = null;
        if ($this->elementExists($domAp, 'VAL_PRIZE')) {
            $valPrize = $this->getFirstElementByTagName($domAp, 'VAL_PRIZE');

            $re->valPrize = $this->getString($valPrize);
            $re->valPrizeCurrency = $valPrize->getattribute('CURRENCY');
        }

        $re->winners       = [];
        if ($this->elementExists($domAp, 'WINNER')) {
            foreach($domAp->getElementsByTagName('WINNER') as $domWinner) {
                if ($this->elementExists($domWinner, 'ADDRESS_WINNER')) {
                    $re->winners[] = $this->loadAddressContractor($domWinner, 'ADDRESS_WINNER');
                }
            }
        }

        $this->results = $re;
    }

    /**
     *
     */
    protected function loadAdditionalCoreData() {
        if (!$this->elementExists($this->document->documentElement, 'ADDITIONAL_CORE_DATA')) {
            return;
        }

        $domAcdRoot = $this->getFirstElementByTagName($this->document->documentElement, 'ADDITIONAL_CORE_DATA');

        $acd = new \stdClass();

        $acd->justification = null;
        if ($this->elementExists($domAcdRoot, 'D_JUSTIFICATION')) {
            $acd->justification = $this->getMultiLineTextByTagName($domAcdRoot,'D_JUSTIFICATION');
        }

        $acd->dateFirstPublication = null;
        if ($this->elementExists($domAcdRoot, 'DATE_FIRST_PUBLICATION')) {
            $acd->dateFirstPublication = $this->getDateByTagName($domAcdRoot,'DATE_FIRST_PUBLICATION');
        }

        $acd->dateTimeLastChange = null;
        if ($this->elementExists($domAcdRoot, 'DATETIME_LAST_CHANGE')) {
            $acd->dateTimeLastChange = $this->getDateByTagName($domAcdRoot,'DATETIME_LAST_CHANGE');
        }

        $acd->deadlineStandstill = null;
        if ($this->elementExists($domAcdRoot, 'DEADLINE_STANDSTILL')) {
            $acd->deadlineStandstill = $this->getDateByTagName($domAcdRoot,'DEADLINE_STANDSTILL');
        }

        $acd->nbSmeContractor = null;
        if ($this->elementExists($domAcdRoot, 'NB_SME_CONTRACTOR')) {
            $acd->nbSmeContractor = intval($this->getStringByTagName($domAcdRoot,'NB_SME_CONTRACTOR'));
        }

        $acd->procedureDescription = null;
        if ($this->elementExists($domAcdRoot, 'PROCEDURE_SHORT_DESCRIPTION')) {
            $acd->procedureDescription = $this->getMultiLineTextByTagName($domAcdRoot,'PROCEDURE_SHORT_DESCRIPTION');
        }

        $acd->belowThreshold = $this->getBooleanByTagName($domAcdRoot,'BELOWTHRESHOLD');
        $acd->aboveThreshold = $this->getBooleanByTagName($domAcdRoot,'ABOVETHRESHOLD');

        $acd->urlRevocation = null;
        if ($this->elementExists($domAcdRoot, 'URL_REVOCATION')) {
            $acd->urlRevocation = $this->getStringByTagName($domAcdRoot,'URL_REVOCATION');
        }

        $acd->urlRevocationStatement = null;
        if ($this->elementExists($domAcdRoot, 'URL_REVOCATION_STATEMENT')) {
            $acd->urlRevocationStatement = $this->getStringByTagName($domAcdRoot,'URL_REVOCATION_STATEMENT');
        }

        $acd->rdNotification = $this->getBooleanByTagName($domAcdRoot,'RD_NOTIFICATION');

        $acd->objectContractModifications = null;
        if ($this->elementExists($domAcdRoot, 'OBJECT_CONTRACT_MODIFICATIONS')) {
            $domOcm = $this->getFirstElementByTagName($domAcdRoot, 'OBJECT_CONTRACT_MODIFICATIONS');

            $ocm = new \stdClass();

            $ocm->title = null;
            if ($this->elementExists($domOcm, 'TITLE')) {
                $ocm->title = $this->getMultiLineTextByTagName($domOcm, 'TITLE');
            }

            $ocm->type = null;
            if ($this->elementExists($domOcm, 'TYPE_CONTRACT')) {
                $ocm->type = $this->getFirstElementByTagName($domOcm, 'TYPE_CONTRACT')->getAttribute('CTYPE');
            }

            $acd->objectContractModifications = $ocm;
        }

        $this->additionalCoreData = $acd;
    }

    /**
     *
     */
    protected function loadLefti() {
        if (!$this->elementExists($this->document->documentElement, 'LEFTI')) {
            return;
        }

        $domLefti = $this->getFirstElementByTagName($this->document->documentElement, 'LEFTI');

        $lefti = new \stdClass();
        $lefti->restrictedShelteredWorkshop = $this->getBooleanByTagName($domLefti, 'RESTRICTED_SHELTERED_WORKSHOP');
        $lefti->restrictedShelteredProgram  = $this->getBooleanByTagName($domLefti, 'RESTRICTED_SHELTERED_PROGRAM');
        $lefti->reservedOrganisationsServiceMission = $this->getBooleanByTagName($domLefti, 'RESERVED_ORGANISATIONS_SERVICE_MISSION');
        $lefti->particularProfession = $this->getBooleanByTagName($domLefti, 'PARTICULAR_PROFESSION');

        $this->lefti = $lefti;
    }


    // -----------------------------------------------------------------------------------------------------------------
    // Utilities
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param \DOMElement $element
     * @return null|string
     */
    public function getValue($element) {
        if ($element) {
            return $element->nodeValue;
        }

        return null;
    }

    /**
     * @param \DOMElement $element
     * @return null|string
     */
    public function getString($element) {
        if ($element) {
            $value = trim(strip_tags($element->nodeValue));

            return $value ? $value : null;
        }

        return null;
    }

    public function getDate($element) {
        if ($element) {
            try {
                $value = trim($element->nodeValue);

                $date = Carbon::parse($value);

                return $date;
            } catch (\Exception $ex) {
                $this->log->error('Unable to parse date from provided value.',['code' => $ex->getCode(), 'message' => $ex->getMessage(), 'value' => $element->nodeValue ]);
                dump('Unable to parse date from provided value ',$element->nodeValue);
            }
        }

        return null;
    }

    /**
     * @param \DOMElement $root
     * @param $tagName
     * @param string $lineSeparator
     * @return null|string
     */
    public function getMultiLineTextByTagName($root,$tagName,$lineSeparator = '\n') {
        $element = $this->getFirstElementByTagName($root, $tagName);

        $result = null;

        if ($element) {
            $ps = $element->getElementsByTagName('P');
            $texts = [];
            foreach($ps as $p) {
                $str = $this->getString($p);
                if ($str) {
                    $texts[] = $str;
                }
            }

            $result = join($lineSeparator, $texts);
        }

        return $result;
    }

    /**
     * @param \DOMElement $root
     * @param $tagName
     * @return null|string
     */
    public function getValueByTagName(\DOMElement $root, $tagName) {
        return $this->getValue($this->getFirstElementByTagName($root, $tagName));
    }

    /**
     * @param \DOMElement $root
     * @param $tagName
     * @return null|string
     */
    public function getStringByTagName(\DOMElement $root, $tagName) {
        return $this->getString($this->getFirstElementByTagName($root, $tagName));
    }

    /**
     * @param \DOMElement $root
     * @param $tagName
     * @return null|string
     */
    public function getBooleanByTagName(\DOMElement $root, $tagName) {
        return $this->elementExists($root, $tagName);
    }

    /**
     * @param \DOMElement $root
     * @param $tagName
     * @return null|string
     */
    public function getDateByTagName(\DOMElement $root, $tagName) {
        return $this->getDate($this->getFirstElementByTagName($root, $tagName));
    }

    /**
     * @param \DOMElement $element
     * @param $tagName
     * @return bool
     */
    protected function elementExists(\DOMElement $element, $tagName) {
        return $this->getFirstElementByTagName($element, $tagName) ? true : false;
    }

    /**
     * Copied from DOMHelper
     *
     * @param \DOMElement $element
     * @param $tagName
     * @return \DomElement|null - event though the ->item(..) method on a DOMNodeList returns a DOMNode
     *                            a DomElement is returned (subclass of DomNode), as the method getElementsByTagName
     *                            always returns a list of ELEMENTS, ergo a list of DOMElement.
     *                            for more or less confusion @see https://stackoverflow.com/a/994113/718980
     *
     */
    protected function getFirstElementByTagName(\DOMElement $element, $tagName) {
        /*
        if ($this->namespaceURI) {
            if ($element->hasChildNodes() && $element->getElementsByTagNameNS($this->namespaceURI, $tagName)->length) {
                return $element->getElementsByTagNameNS($this->namespaceURI, $tagName)->item(0);
            }
        } else {
            if ($element->hasChildNodes() && $element->getElementsByTagName($tagName)->length) {
                return $element->getElementsByTagNameNS($this->namespaceURI, $tagName)->item(0);
            }
        }
        */

        if ($element->hasChildNodes() && $element->getElementsByTagName($tagName)->length) {
            return $element->getElementsByTagName($tagName)->item(0);
        }

        return null;
    }
}