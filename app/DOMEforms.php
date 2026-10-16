<?php

namespace App;

use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Log;

/**
 * Class DOMEforms
 *
 * Parses EU eForms notices (UBL ContractNotice, ContractAwardNotice, PriorInformationNotice) into the same data
 * structure as DOMKerndaten::getData(), so that App\Jobs\Process can store both formats in the same tables.
 *
 * Fields of the app without eForms equivalent stay null (domain, revocation urls, standstill deadline, LEFTI).
 * Design contest results are not mapped yet.
 *
 * @see https://docs.ted.europa.eu/eforms/latest/ eForms SDK documentation (business terms BT-*)
 *
 * @package App
 */
class DOMEforms
{
    use TracksUnparsedNodes;

    const NAMESPACES = [
        'cac'   => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
        'cbc'   => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
        'ext'   => 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2',
        'efac'  => 'http://data.europa.eu/p27/eforms-ubl-extension-aggregate-components/1',
        'efbc'  => 'http://data.europa.eu/p27/eforms-ubl-extension-basic-components/1',
        'efext' => 'http://data.europa.eu/p27/eforms-ubl-extensions/1',
    ];

    // root element namespaces of the notice documents start with this
    const ROOT_NAMESPACE_PREFIX = 'urn:oasis:names:specification:ubl:schema:xsd:';

    // lots, without lot groups (which use the same element), as in the XPaths of the eForms SDK (fields.json)
    const LOT = "cac:ProcurementProjectLot[cbc:ID/@schemeName='Lot']";

    // the eForms extension of the notice root (and of other elements)
    const EXT = 'ext:UBLExtensions/ext:UBLExtension/ext:ExtensionContent/efext:EformsExtension';

    // directive 2014/23/EU (concessions), as given in cbc:RegulatoryDomain
    const CONCESSIONS_DIRECTIVE = '32014L0023';

    /**
     * Notice type (cbc:NoticeTypeCode) => dataset type code, as [ procurement, concession ].
     * Types missing here are not supported (e.g. 'subco', 'compl').
     */
    const TYPES = [
        'pin-only'         => ['KD_8_1_Z1', 'KD_8_1_Z1'],
        'pin-buyer'        => ['KD_8_1_Z1', 'KD_8_1_Z1'],
        'pin-rtl'          => ['KD_8_1_Z1', 'KD_8_1_Z1'],
        'pin-tran'         => ['KD_8_1_Z1', 'KD_8_1_Z1'],
        'pin-cfc-standard' => ['KD_8_1_Z1', 'KD_8_1_Z1'],
        'pin-cfc-social'   => ['KD_8_1_Z1', 'KD_8_1_Z1'],
        'qu-sy'            => ['KD_8_1_Z1', 'KD_8_1_Z1'],
        'pmc'              => ['KD_8_1_Z1', 'KD_8_1_Z1'],
        'cn-standard'      => ['KD_8_1_Z2', 'KD_7_1_Z1'],
        'cn-social'        => ['KD_8_1_Z2', 'KD_7_1_Z1'],
        'cn-desg'          => ['KD_8_1_Z3', 'KD_8_1_Z3'],
        'veat'             => ['KD_8_1_Z4', 'KD_7_1_Z2'],
        'can-standard'     => ['KD_8_2_Z1', 'KD_7_2_Z1'],
        'can-social'       => ['KD_8_2_Z1', 'KD_7_2_Z1'],
        'can-tran'         => ['KD_8_2_Z1', 'KD_7_2_Z1'],
        'can-desg'         => ['KD_8_2_Z2', 'KD_8_2_Z2'],
        'can-modif'        => ['KD_8_2_Z3', 'KD_7_2_Z2'],
    ];

    /**
     * Procedure type (cbc:ProcedureCode) => procedure codes as used by Kerndaten (combinations, see procedure_label()).
     * 'oth-single' / 'oth-mult' don't tell which national procedure was used, they get codes of their own, as do the
     * transport procedures without Kerndaten equivalent.
     */
    const PROCEDURES = [
        'open'        => ['PT_OPEN'],
        'restricted'  => ['PT_RESTRICTED', 'PT_WITH_PRIOR_NOTICE'],
        'neg-w-call'  => ['PT_COMPETITIVE_NEGOTIATION', 'PT_WITH_PRIOR_NOTICE'],
        'neg-wo-call' => ['PT_COMPETITIVE_NEGOTIATION', 'PT_WITHOUT_PRIOR_NOTICE'],
        'comp-dial'   => ['PT_COMPETITIVE_DIALOGUE'],
        'innovation'  => ['PT_INNOVATION_PARTNERSHIP'],
        'oth-single'  => ['OTH_SINGLE'],
        'oth-mult'    => ['OTH_MULT'],
        // public passenger transport services, art. 5(3) / 5(3b) of regulation 1370/2007
        'comp-tend'    => ['COMP_TEND'],
        'exp-int-rail' => ['EXP_INT_RAIL'],
    ];

    // company sizes (efbc:CompanySizeCode, code list economic-operator-size) that count as SME
    const SME_SIZES = ['micro', 'small', 'medium', 'sme'];

    /**
     * @var DOMDocument $document
     */
    protected $document;

    /**
     * @var DOMXPath $xpath
     */
    protected $xpath;

    /**
     * @var DOMElement $root
     */
    protected $root;

    protected $log;

    protected $info;

    protected $data;

    /**
     * @param DOMDocument|string $xml
     * @param \Psr\Log\LoggerInterface|null $log
     * @param array|null $info  log context, e.g. [ 'record_id' => 42 ]
     *
     * @throws \UnexpectedValueException if the document is no eForms notice or of an unsupported notice type
     */
    public function __construct($xml, $log = null, $info = null) {
        if ($xml instanceof DOMDocument) {
            $this->document = $xml;
        } else if (is_string($xml)) {
            $this->document = new DOMDocument();
            $this->document->loadXML($xml);
        } else {
            throw new \InvalidArgumentException('Parameter $xml must be a loaded DOMDocument or a xml string.');
        }

        if (!self::matches($this->document)) {
            throw new \UnexpectedValueException('Document is no eForms notice.');
        }

        $this->log = $log ? $log : Log::channel('processor_daily');
        $this->info = $info;

        $this->root = $this->document->documentElement;
        $this->xpath = new DOMXPath($this->document);
        foreach (self::NAMESPACES as $prefix => $uri) {
            $this->xpath->registerNamespace($prefix, $uri);
        }

        $this->data = $this->load();

        // everything the parser has not read, human readable (the SDK version of the notice decides about the fields)
        $this->data->furtherInformation = (new EformsFurtherInformation($this->text('cbc:CustomizationID')))
            ->extract($this->xpath, $this->consumed);

        $this->logUnparsed('eForms');
    }

    /**
     * Whether the document is an eForms notice (UBL root element).
     *
     * @param DOMDocument $document
     * @return bool
     */
    public static function matches(DOMDocument $document) {
        return $document->documentElement
            && strpos((string)$document->documentElement->namespaceURI, self::ROOT_NAMESPACE_PREFIX) === 0;
    }

    public function getDocument() {
        return $this->document;
    }

    /**
     * @return \stdClass  same structure as DOMKerndaten::getData()
     */
    public function getData() {
        return $this->data;
    }

    protected function load() {
        $data = new \stdClass();

        $data->sourceFormat = 'eforms';
        // e.g. '29' (award, directive 2014/24/EU) or 'E4' (national award); more specific than the type
        $data->sourceSubtype = $this->text(self::EXT . '/efac:NoticeSubType/cbc:SubTypeCode');
        $data->type = $this->loadType($data->sourceSubtype);
        $data->contractingBody = $this->loadContractingBody();
        $data->objectContract = $this->loadObjectContract();
        $data->procedure = $this->loadProcedure();

        // contract modification notices carry the (modified) contract like an award, Kerndaten keep it in
        // MODIFICATIONS_CONTRACT only
        $isModification = $data->type === 'KD_8_2_Z3' || $data->type === 'KD_7_2_Z2';

        $data->awardContract = $isModification ? null : $this->loadAwardContract();
        $data->modificationsContract = $isModification ? $this->loadModificationsContract($data->objectContract) : null;
        $data->results = null;
        $data->additionalCoreData = $this->loadAdditionalCoreData();
        $data->lefti = null;

        return $data;
    }

    /**
     * @param string|null $subtype  only for the error message, the type follows from notice type and directive
     * @return string  dataset type code
     */
    protected function loadType($subtype) {
        $noticeType = $this->text('cbc:NoticeTypeCode');

        if (!isset(self::TYPES[$noticeType])) {
            throw new \UnexpectedValueException("Unsupported eForms notice type '$noticeType' (subtype '$subtype').");
        }

        $isConcession = $this->text('cbc:RegulatoryDomain') === self::CONCESSIONS_DIRECTIVE;

        return self::TYPES[$noticeType][$isConcession ? 1 : 0];
    }

    protected function loadContractingBody() {
        $cb = new \stdClass();

        $buyers = [];
        foreach ($this->nodes('cac:ContractingParty/cac:Party/cac:PartyIdentification/cbc:ID') as $id) {
            $buyers[] = $this->loadOrganization($this->value($id), true);
        }

        $cb->address = array_shift($buyers) ?: $this->emptyAddress(true);
        $cb->addressAdditional = $buyers;

        // procurement documents and tender submission are given per lot, use the first lot that has them
        $cb->urlDocument = null;
        $cb->urlDocumentIsFull = false;
        $cb->urlDocumentIsRestricted = false;

        $document = $this->node(self::LOT . '/cac:TenderingTerms/cac:CallForTendersDocumentReference[cac:Attachment/cac:ExternalReference/cbc:URI]');
        if ($document) {
            $cb->urlDocument = $this->text('cac:Attachment/cac:ExternalReference/cbc:URI', $document);
            $documentType = $this->text('cbc:DocumentType', $document);
            $cb->urlDocumentIsFull = $documentType === 'non-restricted-document';
            $cb->urlDocumentIsRestricted = $documentType === 'restricted-document';
        }

        $cb->urlParticipation = $this->text(self::LOT . '/cac:TenderingTerms/cac:TenderRecipientParty/cbc:EndpointID')
            ?: $this->text(self::LOT . '/cac:TenderingProcess/cbc:AccessToolsURI');

        return $cb;
    }

    /**
     * Organization of the notice (efac:Organizations) by its id, e.g. 'ORG-0001'.
     *
     * @param string $id
     * @param bool $asContractingBody  contracting bodies have contact data, contractors only name and national id
     * @return \stdClass
     */
    protected function loadOrganization($id, $asContractingBody) {
        $adr = $this->emptyAddress($asContractingBody);

        $company = $this->node(self::EXT . '/efac:Organizations/efac:Organization/efac:Company[cac:PartyIdentification/cbc:ID = ' . $this->literal($id) . ']');
        if (!$company) {
            $this->log->warning('eForms organization not found', array_merge((array)$this->info, ['organization' => $id]));
            return $adr;
        }

        $this->text('cac:PartyIdentification/cbc:ID', $company);

        $adr->officialName = $this->text('cac:PartyName/cbc:Name', $company);
        $adr->nationalId   = $this->text('cac:PartyLegalEntity/cbc:CompanyID', $company);

        if ($asContractingBody) {
            // some platforms (e.g. EVN) leave the identifier empty and give the company register number (FN) as
            // endpoint (BT-509) instead
            if (!$adr->nationalId) {
                $endpoint = $this->xpath->query('cbc:EndpointID', $company)->item(0);
                if ($endpoint && preg_match('/^FN/', trim($endpoint->textContent))) {
                    $adr->nationalId = $this->value($endpoint);
                }
            }

            $adr->phone   = $this->text('cac:Contact/cbc:Telephone', $company);
            $adr->email   = $this->text('cac:Contact/cbc:ElectronicMail', $company);
            $adr->contact = $this->text('cac:Contact/cbc:Name', $company);
        } else {
            $adr->isSme = null;
            $size = $this->text('efbc:CompanySizeCode', $company);
            if ($size) {
                $adr->isSme = in_array($size, self::SME_SIZES);
            }
        }

        return $adr;
    }

    protected function emptyAddress($asContractingBody) {
        $adr = new \stdClass();
        $adr->officialName = null;
        $adr->nationalId = null;

        if ($asContractingBody) {
            $adr->phone = null;
            $adr->email = null;
            $adr->contact = null;
            $adr->domain = null;        // no eForms equivalent
            $adr->refNumber = null;
        }

        return $adr;
    }

    protected function loadObjectContract() {
        $oc = new \stdClass();

        $oc->title       = $this->text('cac:ProcurementProject/cbc:Name');
        $oc->description = $this->text('cac:ProcurementProject/cbc:Description');
        $oc->refNumber   = $this->text('cac:ProcurementProject/cbc:ID');

        $oc->cpv = $this->text("cac:ProcurementProject/cac:MainCommodityClassification/cbc:ItemClassificationCode[@listName='cpv']")
            ?: $this->text(self::LOT . "/cac:ProcurementProject/cac:MainCommodityClassification/cbc:ItemClassificationCode[@listName='cpv']");

        // additional codes of the procedure and the codes of the lots (Kerndaten: OBJECT_DESCR/CPV_ADDITIONAL)
        $oc->additionalCpvs = array_values(array_unique(array_diff($this->texts(
            "cac:ProcurementProject/cac:AdditionalCommodityClassification/cbc:ItemClassificationCode[@listName='cpv']"
            . " | " . self::LOT . "/cac:ProcurementProject/cac:MainCommodityClassification/cbc:ItemClassificationCode[@listName='cpv']"
            . " | " . self::LOT . "/cac:ProcurementProject/cac:AdditionalCommodityClassification/cbc:ItemClassificationCode[@listName='cpv']"
        ), [$oc->cpv])));

        $oc->type = null;
        $contractNature = $this->text("cac:ProcurementProject/cbc:ProcurementTypeCode[@listName='contract-nature']");
        if (in_array($contractNature, ['works', 'supplies', 'services'])) {
            $oc->type = strtoupper($contractNature);
        }

        $lots = count($this->nodes(self::LOT));
        $oc->lot   = $lots > 1;
        $oc->noLot = $lots === 1;

        $oc->nuts = $this->loadNuts();

        // planned period of the procedure, or else of the first lot that has one
        $period = $this->node('cac:ProcurementProject/cac:PlannedPeriod')
            ?: $this->node(self::LOT . '/cac:ProcurementProject/cac:PlannedPeriod[cbc:StartDate or cbc:EndDate or cbc:DurationMeasure]');

        $oc->dateStart = $period ? $this->date($this->text('cbc:StartDate', $period)) : null;
        $oc->dateEnd   = $period ? $this->date($this->text('cbc:EndDate', $period)) : null;
        $oc->duration  = $period ? $this->durationInDays($this->node('cbc:DurationMeasure', $period)) : null;

        return $oc;
    }

    /**
     * Place of performance: NUTS code of the procedure or the first lot, else the country (only Austria, 'AT').
     */
    protected function loadNuts() {
        $locations = 'cac:ProcurementProject/cac:RealizedLocation/cac:Address'
            . ' | ' . self::LOT . '/cac:ProcurementProject/cac:RealizedLocation/cac:Address';

        foreach ($this->nodes($locations) as $address) {
            $nuts = $this->text('cbc:CountrySubentityCode', $address);
            if ($nuts) {
                return $nuts;
            }
        }

        foreach ($this->nodes($locations) as $address) {
            if ($this->text('cac:Country/cbc:IdentificationCode', $address) === 'AUT') {
                return 'AT';
            }
        }

        return null;
    }

    /**
     * @param DOMElement|null $measure  cbc:DurationMeasure
     * @return int|null  days, a month counts 30 days (as in DOMKerndaten)
     */
    protected function durationInDays($measure) {
        if (!$measure) {
            return null;
        }

        $value = intval($this->value($measure));
        $factors = ['DAY' => 1, 'WEEK' => 7, 'MONTH' => 30, 'YEAR' => 365];
        $unit = $this->attribute($measure, 'unitCode');

        return isset($factors[$unit]) ? $value * $factors[$unit] : null;
    }

    protected function loadProcedure() {
        $p = new \stdClass();

        // the earliest deadline of all lots: for tenders (BT-131), else, in two-stage procedures, for requests to
        // participate (BT-1311); the Kerndaten deadline covers both
        $p->dateTimeReceiptTenders = $this->earliestDeadline('cac:TenderSubmissionDeadlinePeriod')
            ?: $this->earliestDeadline('cac:ParticipationRequestReceptionPeriod');

        $p->procedures = [];
        $procedureCode = $this->text('cac:TenderingProcess/cbc:ProcedureCode');
        if (isset(self::PROCEDURES[$procedureCode])) {
            $p->procedures = self::PROCEDURES[$procedureCode];
        } else if ($procedureCode) {
            $this->log->warning("Unknown eForms procedure type '$procedureCode'", (array)$this->info);
        }

        $usesDps = false;
        foreach ($this->texts(self::LOT . "/cac:TenderingProcess/cac:ContractingSystem/cbc:ContractingSystemTypeCode[@listName='dps-usage']") as $code) {
            $usesDps = $usesDps || $code !== 'none';
        }
        if ($usesDps) {
            $p->procedures[] = 'DPS';
        }

        $p->isFramework = false;
        foreach ($this->texts(self::LOT . "/cac:TenderingProcess/cac:ContractingSystem/cbc:ContractingSystemTypeCode[@listName='framework-agreement']") as $code) {
            $p->isFramework = $p->isFramework || $code !== 'none';
        }

        return $p;
    }

    /**
     * @param string $period  element of the lots' tendering process with cbc:EndDate and cbc:EndTime
     * @return Carbon|null  the earliest of the lots
     */
    protected function earliestDeadline($period) {
        $earliest = null;

        foreach ($this->nodes(self::LOT . '/cac:TenderingProcess/' . $period) as $node) {
            $deadline = $this->dateTime($this->text('cbc:EndDate', $node), $this->text('cbc:EndTime', $node));
            if ($deadline && (!$earliest || $deadline->lessThan($earliest))) {
                $earliest = $deadline;
            }
        }

        return $earliest;
    }

    /**
     * @return \stdClass|null  null if the notice has no results (e.g. contract notices) or no contract was awarded
     *                         (like NO_AWARDED_CONTRACT in Kerndaten)
     */
    protected function loadAwardContract() {
        $result = $this->node(self::EXT . '/efac:NoticeResult');
        if (!$result || !$this->winningTenderIds($result)) {
            return null;
        }

        $ac = new \stdClass();

        // BT-145 contract conclusion date; cac:TenderResult/cbc:AwardDate is not used, some platforms fill it
        // with a placeholder (2000-01-01)
        $ac->dateConclusionContract = null;
        foreach ($this->texts('efac:SettledContract/cbc:IssueDate', $result) as $issueDate) {
            $date = $this->date($issueDate);
            if ($date && (!$ac->dateConclusionContract || $date->lessThan($ac->dateConclusionContract))) {
                $ac->dateConclusionContract = $date;
            }
        }

        // number of tenders over all lots; some platforms only send electronic submissions (t-esubm),
        // which is the lower bound
        $ac->nbTendersReceived = $this->sumStatistics($result, 'tenders');
        if ($ac->nbTendersReceived === null) {
            $ac->nbTendersReceived = $this->sumStatistics($result, 't-esubm');
        }
        $ac->nbSmeTender = $this->sumStatistics($result, 't-sme');

        [$ac->valTotal, $ac->valTotalCurrency] = $this->loadTotalValue($result);

        $ac->contractors = $this->loadWinners($result);

        // true if a winner is an SME, false if no winner is, null without company sizes
        $ac->nbSmeContractor = null;
        foreach ($ac->contractors as $contractor) {
            if ($contractor->isSme !== null) {
                $ac->nbSmeContractor = $ac->nbSmeContractor || $contractor->isSme;
            }
        }

        return $ac;
    }

    /**
     * @param DOMElement $result  efac:NoticeResult
     * @param string $code  efbc:StatisticsCode
     * @return int|null
     */
    protected function sumStatistics($result, $code) {
        $values = $this->texts("efac:LotResult/efac:ReceivedSubmissionsStatistics[efbc:StatisticsCode = '$code']/efbc:StatisticsNumeric", $result);

        if (!$values) {
            return null;
        }

        // read the codes as well, for the unparsed report
        $this->texts("efac:LotResult/efac:ReceivedSubmissionsStatistics[efbc:StatisticsCode = '$code']/efbc:StatisticsCode", $result);

        return array_sum(array_map('intval', $values));
    }

    /**
     * Total value of the notice (BT-161), else the sum of the winning tenders.
     *
     * @param DOMElement $result  efac:NoticeResult
     * @return array  [ value as string, currency ]
     */
    protected function loadTotalValue($result) {
        $total = $this->node('cbc:TotalAmount', $result);
        if ($total && $this->value($total) !== null) {
            return [$this->value($total), $this->attribute($total, 'currencyID')];
        }

        $sum = null;
        $currency = null;
        foreach ($this->winningTenderIds($result) as $tenderId) {
            $amount = $this->node('efac:LotTender[cbc:ID = ' . $this->literal($tenderId) . ']/cac:LegalMonetaryTotal/cbc:PayableAmount', $result);
            if ($amount && $this->value($amount) !== null) {
                $sum = ($sum ?: 0) + floatval($this->value($amount));
                $currency = $this->attribute($amount, 'currencyID');
            }
        }

        return [$sum === null ? null : (string)$sum, $currency];
    }

    /**
     * Ids of the winning tenders: tenders of lot results with winner selected, else the tenders of the settled
     * contracts.
     *
     * @param DOMElement $result  efac:NoticeResult
     * @return string[]
     */
    protected function winningTenderIds($result) {
        $ids = $this->texts("efac:LotResult[cbc:TenderResultCode = 'selec-w']/efac:LotTender/cbc:ID", $result);
        $this->texts("efac:LotResult[cbc:TenderResultCode = 'selec-w']/cbc:TenderResultCode", $result);

        if (!$ids) {
            $ids = $this->texts('efac:SettledContract/efac:LotTender/cbc:ID', $result);
        }

        return array_values(array_unique($ids));
    }

    /**
     * Winning tenderers (tender -> tendering party -> tenderers -> organizations), in document order.
     *
     * @param DOMElement $result  efac:NoticeResult
     * @return \stdClass[]  [ officialName, nationalId, isSme ]
     */
    protected function loadWinners($result) {
        $organizationIds = [];

        foreach ($this->winningTenderIds($result) as $tenderId) {
            $tender = $this->node('efac:LotTender[cbc:ID = ' . $this->literal($tenderId) . ']', $result);
            if (!$tender) {
                continue;
            }
            $this->text('cbc:ID', $tender);

            foreach ($this->texts('efac:TenderingParty/cbc:ID', $tender) as $partyId) {
                $party = $this->node('efac:TenderingParty[cbc:ID = ' . $this->literal($partyId) . ']', $result);
                if (!$party) {
                    continue;
                }
                $this->text('cbc:ID', $party);

                foreach ($this->texts('efac:Tenderer/cbc:ID', $party) as $organizationId) {
                    $organizationIds[] = $organizationId;
                }
            }
        }

        $winners = [];
        foreach (array_unique($organizationIds) as $organizationId) {
            $winners[] = $this->loadOrganization($organizationId, false);
        }

        return $winners;
    }

    /**
     * @param \stdClass $objectContract
     * @return \stdClass
     */
    protected function loadModificationsContract($objectContract) {
        $mc = new \stdClass();

        $mc->cpv = $objectContract->cpv;
        $mc->additionalCpvs = [];       // Process uses those of the object contract

        $modification = self::EXT . '/efac:ContractModification';

        $mc->description = $this->joinedTexts($modification . '/efac:Change/efbc:ChangeDescription');

        // Process stores both texts in the same column (info_modifications), the distinction only follows Kerndaten:
        // additional works, services or supplies ('add-wss') vs. unforeseeable circumstances ('mod-cir'); other
        // reasons are kept as additional need
        $mc->additionalNeed = null;
        $mc->unforeseenCircumstance = null;
        foreach ($this->nodes($modification . '/efac:ChangeReason') as $reason) {
            $text = $this->text('efbc:ReasonDescription', $reason);
            if ($this->text('cbc:ReasonCode', $reason) === 'mod-cir') {
                $mc->unforeseenCircumstance = $mc->unforeseenCircumstance ?: $text;
            } else {
                $mc->additionalNeed = $mc->additionalNeed ?: $text;
            }
        }

        $mc->contractors = [];
        $mc->valTotalBefore = null;        // no eForms equivalent
        $mc->valTotalBeforeCurrency = null;
        $mc->valTotalAfter = null;
        $mc->valTotalAfterCurrency = null;

        $result = $this->node(self::EXT . '/efac:NoticeResult');
        if ($result) {
            $mc->contractors = $this->loadWinners($result);
            [$mc->valTotalAfter, $mc->valTotalAfterCurrency] = $this->loadTotalValue($result);
        }

        // the modified notice and contract, read for the unparsed report
        $this->text($modification . '/efbc:ChangedNoticeIdentifier');
        $this->text($modification . '/efbc:ModifiedContractIdentifier');

        return $mc;
    }

    protected function loadAdditionalCoreData() {
        $acd = new \stdClass();

        // BT-135 direct award justification; other justifications (e.g. of an accelerated procedure) are not meant
        $acd->justification = $this->joinedTexts("cac:TenderingProcess/cac:ProcessJustification[cbc:ProcessReasonCode/@listName='direct-award-justification']/cbc:ProcessReason");

        // eForms has no separate date of first publication, the issue date is the date of dispatch
        $acd->dateFirstPublication = $this->date($this->text('cbc:IssueDate'));
        $acd->dateTimeLastChange   = $this->dateTime($this->text('cbc:IssueDate'), $this->text('cbc:IssueTime'));

        $acd->deadlineStandstill = null;        // no eForms equivalent
        $acd->nbSmeContractor = null;           // see awardContract->nbSmeContractor
        $acd->procedureDescription = $this->text('cac:TenderingProcess/cbc:Description');

        // EU directive as regulatory domain, or the Austrian legislation reference 'Oberschwelle' / 'Unterschwelle'
        $legislation = $this->text('cac:TenderingTerms/cac:ProcurementLegislationDocumentReference/cbc:ID');
        $regulatoryDomain = $this->text('cbc:RegulatoryDomain');
        $acd->belowThreshold = $legislation === 'Unterschwelle';
        $acd->aboveThreshold = !$acd->belowThreshold
            && ($legislation === 'Oberschwelle' || ($regulatoryDomain && $regulatoryDomain !== 'other'));

        $acd->urlRevocation = null;             // no eForms equivalent
        $acd->urlRevocationStatement = null;
        $acd->rdNotification = false;
        $acd->objectContractModifications = null;

        return $acd;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Utilities
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Elements matching the expression, marked as read (without their content).
     *
     * @param string $expression  XPath, relative to $context
     * @param DOMElement|null $context  defaults to the root element
     * @return DOMElement[]
     */
    protected function nodes($expression, $context = null) {
        $nodes = [];

        foreach ($this->xpath->query($expression, $context ?: $this->root) as $node) {
            $nodes[] = $this->consume($node);
        }

        return $nodes;
    }

    /**
     * @return DOMElement|null  the first element matching the expression
     */
    protected function node($expression, $context = null) {
        $node = $this->xpath->query($expression, $context ?: $this->root)->item(0);

        return $node ? $this->consume($node) : null;
    }

    /**
     * @return string|null  trimmed text of the first element matching the expression, null if empty
     */
    protected function text($expression, $context = null) {
        $node = $this->xpath->query($expression, $context ?: $this->root)->item(0);

        return $node ? $this->value($node) : null;
    }

    /**
     * @return string[]  trimmed, non empty texts of all elements matching the expression
     */
    protected function texts($expression, $context = null) {
        $texts = [];

        foreach ($this->xpath->query($expression, $context ?: $this->root) as $node) {
            $value = $this->value($node);
            if ($value !== null) {
                $texts[] = $value;
            }
        }

        return $texts;
    }

    /**
     * Texts of all elements matching the expression, joined like multi-line texts in DOMKerndaten.
     *
     * @return string|null
     */
    protected function joinedTexts($expression, $context = null) {
        $texts = $this->texts($expression, $context);

        return $texts ? join('\n', $texts) : null;
    }

    /**
     * @param DOMElement $element
     * @return string|null  trimmed text, null if empty; marks the element and its content as read
     */
    protected function value(DOMElement $element) {
        $this->consume($element, true);

        $value = trim($element->textContent);

        return $value === '' ? null : $value;
    }

    /**
     * @return string|null
     */
    protected function attribute(DOMElement $element, $name) {
        if (!$element->hasAttribute($name)) {
            return null;
        }

        $this->consume($element->getAttributeNode($name));

        $value = trim($element->getAttribute($name));

        return $value === '' ? null : $value;
    }

    /**
     * @param string $value
     * @return string  $value as XPath string literal
     */
    protected function literal($value) {
        if (strpos($value, "'") === false) {
            return "'$value'";
        }

        return 'concat(\'' . str_replace("'", "', \"'\", '", $value) . '\')';
    }

    /**
     * eForms dates carry a timezone offset (2026-10-15+02:00), which belongs to the date and is dropped.
     *
     * @param string|null $value
     * @return Carbon|null  start of the day in the app timezone, as dates from DOMKerndaten
     */
    protected function date($value) {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', substr($value, 0, 10), config('app.timezone'));
        } catch (\Exception $ex) {
            $this->log->error('Unable to parse eForms date', array_merge((array)$this->info, ['value' => $value]));
        }

        return null;
    }

    /**
     * @param string|null $date  e.g. 2026-11-05+01:00
     * @param string|null $time  e.g. 12:00:00+01:00
     * @return Carbon|null  in the app timezone; without time the start of the day
     */
    protected function dateTime($date, $time) {
        if (!$date) {
            return null;
        }
        if (!$time) {
            return $this->date($date);
        }

        try {
            return Carbon::parse(substr($date, 0, 10) . 'T' . $time)->setTimezone(config('app.timezone'));
        } catch (\Exception $ex) {
            $this->log->error('Unable to parse eForms date and time', array_merge((array)$this->info, ['date' => $date, 'time' => $time]));
        }

        return null;
    }

    /**
     * Code list and identifier scheme names, languages: metadata on almost every element, never content.
     */
    protected function ignoresUnparsedAttribute(\DOMAttr $attribute) {
        return in_array($attribute->name, ['listName', 'schemeName', 'languageID'])
            || $attribute->namespaceURI === 'http://www.w3.org/2001/XMLSchema-instance';
    }
}
