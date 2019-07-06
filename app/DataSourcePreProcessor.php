<?php
/**
 * Project: fif_offenevergaben
 * User: ives_markus
 * Date: 30/06/19
 */

namespace App;


use Carbon\Carbon;

class DataSourcePreProcessor
{
    /**
     * Type is stored as the name attribute of the wrapping xml
     * Use this FIF_TYPE as key for accessing it on the output data
     */
    const FIF_TYPE = 'FIF_TYPE';

    protected $xmlString;
    protected $simpleXmlArrayData;
    protected $simpleXmlParsedData;
    protected $data;

    public function __construct() { }

    public function getData() {
        return $this->data;
    }

    public function getSimpleXmlArray() {
        return $this->simpleXmlArrayData;
    }

    public function preProcess($xmlString) {
        $this->xmlString = $xmlString;
        $this->simpleXmlParsedData = simplexml_load_string($xmlString);
        $this->simpleXmlArrayData = $this->xmlToArray($this->simpleXmlParsedData);

        $this->data = new \stdClass();

        $this->data->type = $this->simpleXmlArrayData[self::FIF_TYPE];
        $this->data->contractingBody = null;
        $this->data->objectContract = null;
        $this->data->awardContract = null;
        $this->data->modificationsContract = null;
        $this->data->awardedPrize = null;

        if ($this->hasContractingBody()) {
            $this->processContractingBody();
        }

        if ($this->hasObjectContract()) {
            $this->processObjectContract();
        }

        if ($this->hasAwardContract()) {
            $this->processAwardContract();
        }

        if ($this->hasModificationsContract()) {
            $this->processModificationsContract();
        }

        if ($this->hasResults()) {
            $this->processResults();
        }

        if ($this->hasAdditionalCoreData()) {
            $this->processAdditionalCoreData();
        }
    }

    /**
     * Process CONTRACTING_BODY
     */
    protected function processContractingBody() {
        $data = $this->getContractingBody();

        $cb = new \stdClass();

        $adr = isset($data['ADDRESS_CONTRACTING_BODY']) ? $data['ADDRESS_CONTRACTING_BODY'] : null;

        if ($adr) {
            $cb->officialName = $this->getField($adr,'OFFICIALNAME');
            $cb->nationalId   = $this->getField($adr,'NATIONALID');
            $cb->phone        = $this->getField($adr,'PHONE');
            $cb->email        = $this->getField($adr,'E_MAIL');
            $cb->contact      = $this->getField($adr,'CONTACT');
            $cb->domain       = $this->getField($adr,'DOMAIN');

            if ($this->hasAnyAdditionalContractingBodies()) {
                $cb->additional = [];

                $additionals = $this->hasMultipleAdditionalContractingBodies() ?
                    $data['ADDRESS_CONTRACTING_BODY_ADDITIONAL'] : [ $data['ADDRESS_CONTRACTING_BODY_ADDITIONAL'] ];

                foreach($additionals as $additional) {
                    $add = new \stdClass();

                    $add->officialName = $this->getField($additional,'OFFICIALNAME');
                    $add->nationalId   = $this->getField($additional,'NATIONALID');
                    $add->phone        = $this->getField($additional,'PHONE');
                    $add->email        = $this->getField($additional,'E_MAIL');
                    $add->contact      = $this->getField($additional,'CONTACT');
                    $add->domain       = $this->getField($additional,'DOMAIN');
                    $add->refNumber    = $this->getField($additional,'REFERENCE_NUMBER');

                    $cb->additional[] = $add;
                }
            } else {
                $cb->additional = null;
            }
        }

        // URL_DOCUMENT, URL_PARTICIPATION, Flags [DOCUMENT_FULL, DOCUMENT_RESTRICTED]
        $cb->urlDocument             = $this->getField($data,'URL_DOCUMENT');
        $cb->urlParticipation        = $this->getField($data,'URL_PARTICIPATION');
        $cb->urlDocumentIsRestricted = isset($data['DOCUMENT_RESTRICTED']);
        $cb->urlDocumentIsFull       = isset($data['DOCUMENT_FULL']);

        $this->data->contractingBody = $cb;
    }

    /**
     * Process OBJECT_CONTRACT
     */
    protected function processObjectContract() {
        $data = $this->getObjectContract();

        $oc = new \stdClass();

        $oc->cpv         = $this->hasCpvMain($data) ? $this->getCpvMain($data) : null;
        $oc->nuts        = $this->hasNutsCode() ? $this->getNutsCode() : null;
        $oc->type        = $this->hasTypeContract($data) ? $this->getTypeContract($data) : null;
        $oc->refNumber   = $this->getField($data,'REFERENCE_NUMBER');
        $oc->title       = $this->getMultiLineText($data,'TITLE');
        $oc->description = $this->getMultiLineText($data,'SHORT_DESCR');
        $oc->lot         = isset($data['LOT_DIVISION']);
        $oc->noLot       = isset($data['NO_LOT_DIVISION']);

        $oc->additionalCpvs = null;
        $oc->dateStart = null;
        $oc->dateEnd = null;
        $oc->duration = null;

        // Handle Object Description child
        if ($this->hasObjectDescription()) {

            // for KD_8_1_Z3, KD_8_2_Z2 and (even though missing in schema description) KD_8_2_Z3
            if (!$oc->description && isset($data['OBJECT_DESCR']['SHORT_DESCR'])) {
                $oc->description = $this->getMultiLineText($data['OBJECT_DESCR'],'SHORT_DESCR');
            }

            if ($this->hasAnyAdditionalCpv()) {
                $oc->additionalCpvs = [];

                $additionals = $this->hasMultipleAdditionalCpvs() ? $data['OBJECT_DESCR']['CPV_ADDITIONAL'] :
                    [ $data['OBJECT_DESCR']['CPV_ADDITIONAL'] ];

                foreach($additionals as $additional) {
                    $cpvCode = $this->getCpvAdditional($additional);

                    if ($cpvCode) {
                        $oc->additionalCpvs[] = $cpvCode;
                    }
                }
            }

            $oc->dateStart = $this->getDate($data['OBJECT_DESCR'],'DATE_START');
            $oc->dateEnd   = $this->getDate($data['OBJECT_DESCR'],'DATE_END');

            $oc->duration  = $this->getDuration();
        }

        $this->data->objectContract = $oc;
    }

    protected function processAwardContract() {
        $data = $this->getAwardContract();

        $ac = new \stdClass();
        $ac->dateConclusionContract = $this->getDate($data['AWARDED_CONTRACT'],'DATE_CONCLUSION_CONTRACT');
        $ac->nbSmeTender = $this->getNumber($data['AWARDED_CONTRACT'],'NB_SME_TENDER');
        $ac->nbSmeContractor = isset($data['AWARDED_CONTRACT']['NB_SME_CONTRACTOR']);
        $ac->nbTendersReceived = $this->getNumber($data['AWARDED_CONTRACT'],'NB_TENDERS_RECEIVED');
        $ac->valTotal = $this->getValueTotal();
        $ac->contractors = null;

        if ($this->hasAnyContractor('AWARD')) {
            $ac->contractors = [];

            $contractors = $this->hasMultipleContractors('AWARD') ?
                $data['AWARDED_CONTRACT']['CONTRACTOR']['ADDRESS_CONTRACTOR'] :
                [ $data['AWARDED_CONTRACT']['CONTRACTOR']['ADDRESS_CONTRACTOR'] ];

            foreach($contractors as $contractor) {
                $con = new \stdClass();

                $con->officialName = $this->getField($contractor,'OFFICIALNAME');
                $con->nationalId   = $this->getField($contractor,'NATIONALID');

                $ac->contractors[] = $con;
            }
        }

        $this->data->awardContract = $ac;
    }

    protected function processModificationsContract() {
        $data = $this->getModificationsContract();

        $mc = new \stdClass();
        $mc->cpv = $this->hasCpvMain($data['DESCRIPTION_PROCUREMENT']) ?
            $this->getCpvMain($data['DESCRIPTION_PROCUREMENT']) : null;

        if ($this->hasAnyContractor('MODIFICATIONS')) {
            $mc->contractors = [];

            $contractors = $this->hasMultipleContractors('MODIFICATIONS') ?
                $data['DESCRIPTION_PROCUREMENT']['CONTRACTOR']['ADDRESS_CONTRACTOR'] :
                [ $data['DESCRIPTION_PROCUREMENT']['CONTRACTOR']['ADDRESS_CONTRACTOR'] ];

            foreach($contractors as $contractor) {
                $con = new \stdClass();

                $con->officialName = $this->getField($contractor,'OFFICIALNAME');
                $con->nationalId   = $this->getField($contractor,'NATIONALID');

                $mc->contractors[] = $con;
            }
        } else {
            $mc->contractors = null;
        }

        $this->data->modificationsContract = $mc;
    }

    protected function processResults() {
        $results = $this->getResults();

        // all fields are within the enclosing <AWARDED_PRIZE> node
        if (!isset($results['AWARDED_PRIZE'])) {
            return;
        }

        $data = $results['AWARDED_PRIZE'];

        $ap = new \stdClass();
        $ap->nbParticipants = $this->getNumber($data,'NB_PARTICIPANTS');;
        $ap->nbParticipantsSme = $this->getNumber($data,'NB_PARTICIPANTS_SME');
        $ap->valPrize = $this->getPrize();
        $ap->winners = null;

        if ($this->hasAnyWinner()) {
            $ap->winners = [];

            $winners = $this->hasMultipleWinners() ? $data['WINNER']['ADDRESS_WINNER'] :
                [ $data['WINNER']['ADDRESS_WINNER'] ];

            foreach($winners as $winner) {
                $win = new \stdClass();

                $win->officialName = $this->getField($winner,'OFFICIALNAME');
                $win->nationalId   = $this->getField($winner,'NATIONALID');

                $ap->winners[] = $win;
            }
        }

        $this->data->awardedPrize = $ap;
    }

    protected function processAdditionalCoreData() {
        $data = $this->simpleXmlArrayData['ADDITIONAL_CORE_DATA'];

        $acd = new \stdClass();
        $acd->justification = $this->getMultiLineText($data,'D_JUSTIFICATION');
        $acd->dateFirstPublication = $this->getDate($data,'DATE_FIRST_PUBLICATION');
        $acd->dateTimeLastChange   = $this->getTimestamp($data,'DATETIME_LAST_CHANGE');
        $acd->deadlineStandstill   = $this->getDate($data,'DEADLINE_STANDSTILL');
        $acd->rdNotification = null;
        $acd->nbSmeContractor = $this->getNumber($data,'NB_SME_CONTRACTOR');
        $acd->objectContractModifications = null;
        $acd->procedureDescription = $this->getMultiLineText($data,'PROCEDURE_SHORT_DESCRIPTION');
        $acd->belowThreshold = isset($data['BELOWTHRESHOLD']);
        $acd->aboveThreshold = isset($data['ABOVETHRESHOLD']);
        $acd->urlRevocation = $this->getField($data,'URL_REVOCATION');
        $acd->urlRevocationStatement = $this->getField($data,'URL_REVOCATION_STATEMENT');

        if (isset($data['RD_NOTIFICATION'])) {
            // at the moment of writing no exemplary data for this attribute available
            // this is most likely a boolean FLAG, if its something else make notice
            $acd->rdNotification = true;

            if (is_array($data['RD_NOTIFICATION'])) {
                dump('Unexpected array value for RD_NOTIFICATION');
                dump($data['RD_NOTIFICATION']);
            }

            if (is_string($data['RD_NOTIFICATION'] && strlen(trim($data['RD_NOTIFICATION'])) > 0)) {
                dump('Unexpected string value for RD_NOTIFICATION:'.$data['RD_NOTIFICATION']);
            }
        }

        if (isset($data['OBJECT_CONTRACT_MODIFICATIONS'])) {
            $ocm = new \stdClass();

            $ocm->title = $this->getMultiLineText($data['OBJECT_CONTRACT_MODIFICATIONS'],'TITLE');
            $ocm->type  = $this->hasTypeContract($data['OBJECT_CONTRACT_MODIFICATIONS']) ?
                $this->getTypeContract($data['OBJECT_CONTRACT_MODIFICATIONS']) : null;

            $acd->objectContractModifications = $ocm;
        }

        $this->data->additionalCoreData = $acd;
    }

    protected function xmlToArray($parsedXml) {

        // use json encode to transform to json
        $json = json_encode($parsedXml);

        // use json decode to get an associative array
        $array = json_decode($json,TRUE);

        $type = $parsedXml->getName(); // e.g. "KD_8_1_Z2"
        // add type to result, use prefix to prevent name collision
        $array[self::FIF_TYPE] = $type;

        return $array;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Getters
    // NOTE: check for existence first! ($this->hasXYZ()))
    // -----------------------------------------------------------------------------------------------------------------

    protected function getContractingBody() {
        return $this->simpleXmlArrayData['CONTRACTING_BODY'];
    }
    protected function getObjectContract() {
        return $this->simpleXmlArrayData['OBJECT_CONTRACT'];
    }
    protected function getObjectDescription() {
        return $this->simpleXmlArrayData['OBJECT_CONTRACT']['OBJECT_DESCR'];
    }
    protected function getAwardContract() {
        return $this->simpleXmlArrayData['AWARD_CONTRACT'];
    }
    protected function getModificationsContract() {
        return $this->simpleXmlArrayData['MODIFICATIONS_CONTRACT'];
    }
    protected function getResults() {
        return $this->simpleXmlArrayData['RESULTS'];
    }
    protected function getAdditionalCoreData() {
        return $this->simpleXmlArrayData['ADDITIONAL_CORE_DATA'];
    }

    /**
     * CPV_MAIN usually to be found in OBJECT_CONTRACT
     * but it's possible that it is in MODIFICATIONS_CONTRACT
     *
     * @param $hayStack
     * @return null|string
     */
    protected function getCpvMain($hayStack) {
        $value = $hayStack['CPV_MAIN']['CPV_CODE']['@attributes']['CODE'];
        $value = trim($value);

        return strlen($value) === 0 ? null : $value;
    }

    /**
     * TYPE_CONTRACT is an attribute from Object-Contract and Object-Contract-Modifications
     * Therefor the containing element is parameterized: $hayStack
     *
     * @param $hayStack
     * @return null|string
     */
    protected function getTypeContract($hayStack) {
        $value = $hayStack['TYPE_CONTRACT']['@attributes']['CTYPE'];
        $value = trim($value);
        return strlen($value) === 0 ? null : $value;
    }

    /**
     * CPV_ADDITIONAL can be present in OBJECT_CONTRACT or MODIFICATIONS_CONTRACT
     * Therefor the containing element is parameterized: $hayStack
     *
     * @param $hayStack
     * @return null|string
     */
    protected function getCpvAdditional($hayStack) {
        if(isset($hayStack['CPV_CODE']) && isset($hayStack['CPV_CODE']['@attributes'])
            && isset($hayStack['CPV_CODE']['@attributes']['CODE'])) {

            $value = $hayStack['CPV_CODE']['@attributes']['CODE'];
            $value = trim($value);

            return strlen($value) === 0 ? null : $value;
        }

        return null;
    }

    protected function getNutsCode() {
        $value = $this->simpleXmlArrayData['OBJECT_CONTRACT']['OBJECT_DESCR']['NUTS']['@attributes']['CODE'];
        $value = trim($value);

        return strlen($value) === 0 ? null : $value;
    }

    protected function getPrize() {
        if (!$this->hasResults()) {
            return null;
        }

        $results = $this->getResults();

        if (!isset($results['AWARDED_PRIZE']) || !isset($results['AWARDED_PRIZE']['VAL_PRIZE'])) {
            return null;
        }

        // need to make use of simpleXmlElements to check currency
        $element = $this->simpleXmlParsedData->RESULTS->AWARDED_PRIZE->VAL_PRIZE;
        $value = trim((String)$element);

        // currency == 'EUR' ???
        if ($element['CURRENCY']) {
            $curr = $element['CURRENCY'];

            if (trim($curr) !== 'EUR') {
                dump('Unexpected Currency attribute value received for VAL_PRIZE',$curr);
            }
        }

        return $value;
    }

    // TODO this is basically a duplicate of getPrize (refactor!!)
    protected function getValueTotal() {
        if (!$this->hasAwardContract()) {
            return null;
        }

        $data = $this->getAwardContract();

        if (!isset($data['AWARDED_CONTRACT']) || !isset($data['AWARDED_CONTRACT']['VAL_TOTAL'])) {
            return null;
        }

        // need to make use of simpleXmlElements to check currency
        $element = $this->simpleXmlParsedData->AWARD_CONTRACT->AWARDED_CONTRACT->VAL_TOTAL;
        $value = trim((String)$element);

        // currency == 'EUR' ???
        if ($element['CURRENCY']) {
            $curr = $element['CURRENCY'];

            if (trim($curr) !== 'EUR') {
                dump('Unexpected Currency attribute value received for VAL_PRIZE',$curr);
            }
        }

        return $value;
    }

    protected function getDuration() {
        if (!$this->hasObjectDescription()) {
            return null;
        }

        $OD = $this->getObjectDescription();

        if (!isset($OD['DURATION'])) {
            return null;
        }

        // awkward... Duration seems to be the first field where the parsed array data from simple xml
        // is insufficient. we actually need to revert back to the simple xml representation
        // to get the type attribute of the duration field
        $element = $this->simpleXmlParsedData->OBJECT_CONTRACT->OBJECT_DESCR->DURATION;

        $value = (String)$element;

        // Sanity check integer value
        if (!$this->validatesAsInt($value)) {
            dump('Possibly wrong integer value for DURATION provided',$value);
        }

        // Possibly convert value from MONTH duration type
        if($element['TYPE']) {
            if ($element['TYPE'] == 'MONTH') {
                $value = intval($value) * 30;
            } else if ($element['TYPE'] == 'DAY') {
                $value = intval($value);
            } else {
                dump('Unknown type attribute provided for DURATION');
                $value = null;
            }
        } else {
            dump('No type attribute provided for DURATION');
            $value = null;
        }

        return $value;
    }

    protected function getDate($hayStack,$needle) {
        if (!isset($hayStack[$needle])) {
            return null;
        }

        $date = null;

        try {
            $date = Carbon::parse($hayStack[$needle]);
        } catch (\Exception $e) {
            dump('Unable to parse date from provided value ',$hayStack[$needle]);
        }

        return $date;
    }

    protected function getTimestamp($hayStack,$needle) {
        // carbon parses both...
        return $this->getDate($hayStack,$needle);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Existence Checks
    // -----------------------------------------------------------------------------------------------------------------

    protected function hasContractingBody() {
        return isset($this->simpleXmlArrayData['CONTRACTING_BODY']);
    }
    protected function hasObjectContract() {
        return isset($this->simpleXmlArrayData['OBJECT_CONTRACT']);
    }
    protected function hasAwardContract() {
        return isset($this->simpleXmlArrayData['AWARD_CONTRACT']);
    }
    protected function hasModificationsContract() {
        return isset($this->simpleXmlArrayData['MODIFICATIONS_CONTRACT']);
    }
    protected function hasResults() {
        return isset($this->simpleXmlArrayData['RESULTS']);
    }
    protected function hasAdditionalCoreData() {
        return isset($this->simpleXmlArrayData['ADDITIONAL_CORE_DATA']);
    }

    protected function hasObjectDescription() {
        return $this->hasObjectContract()
        && isset($this->simpleXmlArrayData['OBJECT_CONTRACT']['OBJECT_DESCR']);
    }

    protected function hasCpvMain($hayStack) {
        if (!$hayStack) {
            return false;
        }

        return isset($hayStack['CPV_MAIN'])
            && isset($hayStack['CPV_MAIN']['CPV_CODE'])
            && isset($hayStack['CPV_MAIN']['CPV_CODE']['@attributes'])
            && isset($hayStack['CPV_MAIN']['CPV_CODE']['@attributes']['CODE']);
    }

    protected function hasTypeContract($hayStack) {
        if (!isset($hayStack['TYPE_CONTRACT'])) {
            return false;
        }

        return isset($hayStack['TYPE_CONTRACT']['@attributes'])
        && isset($hayStack['TYPE_CONTRACT']['@attributes']['CTYPE']);
    }

    protected function hasNutsCode() {
        if (!$this->hasObjectContract()) {
            return false;
        }

        $OC = $this->simpleXmlArrayData['OBJECT_CONTRACT'];

        return isset($OC['OBJECT_DESCR'])
        && isset($OC['OBJECT_DESCR']['NUTS'])
        && isset($OC['OBJECT_DESCR']['NUTS']['@attributes'])
        && isset($OC['OBJECT_DESCR']['NUTS']['@attributes']['CODE']);
    }

    protected function hasAnyAdditionalCpv() {
        return $this->hasObjectDescription() && isset($this->simpleXmlArrayData['OBJECT_CONTRACT']['OBJECT_DESCR']['CPV_ADDITIONAL']);
    }

    protected function hasAnyAdditionalContractingBodies() {
        return $this->hasContractingBody() &&
            isset($this->simpleXmlArrayData['CONTRACTING_BODY']['ADDRESS_CONTRACTING_BODY_ADDITIONAL']);
    }

    public function hasAnyContractor($contract) {
        if ($contract == 'AWARD') {
            return $this->hasAwardContract()
            && isset($this->simpleXmlArrayData['AWARD_CONTRACT']['AWARDED_CONTRACT'])
            && isset($this->simpleXmlArrayData['AWARD_CONTRACT']['AWARDED_CONTRACT']['CONTRACTOR'])
            && isset($this->simpleXmlArrayData['AWARD_CONTRACT']['AWARDED_CONTRACT']['CONTRACTOR']['ADDRESS_CONTRACTOR']);
        }
        if ($contract == 'MODIFICATIONS') {
            return $this->hasModificationsContract()
            && isset($this->simpleXmlArrayData['MODIFICATIONS_CONTRACT']['DESCRIPTION_PROCUREMENT'])
            && isset($this->simpleXmlArrayData['MODIFICATIONS_CONTRACT']['DESCRIPTION_PROCUREMENT']['CONTRACTOR'])
            && isset($this->simpleXmlArrayData['MODIFICATIONS_CONTRACT']['DESCRIPTION_PROCUREMENT']['CONTRACTOR']['ADDRESS_CONTRACTOR']);
        }

        return false;
    }

    public function hasAnyWinner() {
        return $this->hasResults()
            && isset($this->simpleXmlArrayData['RESULTS']['AWARDED_PRIZE'])
            && isset($this->simpleXmlArrayData['RESULTS']['AWARDED_PRIZE']['WINNER'])
            && isset($this->simpleXmlArrayData['RESULTS']['AWARDED_PRIZE']['WINNER']['ADDRESS_WINNER']);
    }

    protected function hasMultipleAdditionalContractingBodies() {
        if (!$this->hasAnyAdditionalContractingBodies()) {
            return false;
        }

        $array = $this->simpleXmlArrayData['CONTRACTING_BODY']['ADDRESS_CONTRACTING_BODY_ADDITIONAL'];

        // this is actually a test if the provided array contains numerical or string keys
        // or in other words if the provided array is (not) an 'associative' array
        return count(array_filter(array_keys($array), 'is_string')) === 0;
    }

    protected function hasMultipleAdditionalCpvs() {
        if (!$this->hasAnyAdditionalCpv()) {
            return false;
        }

        $array = $this->simpleXmlArrayData['OBJECT_CONTRACT']['OBJECT_DESCR']['CPV_ADDITIONAL'];

        return count(array_filter(array_keys($array), 'is_string')) === 0;
    }

    public function hasMultipleWinners() {
        if (!$this->hasAnyWinner()) {
            return false;
        }

        $array = $this->simpleXmlArrayData['RESULTS']['AWARDED_PRIZE']['WINNER']['ADDRESS_WINNER'];

        return count(array_filter(array_keys($array), 'is_string')) === 0;
    }

    protected function hasMultipleContractors($contract) {
        if (!$this->hasAnyContractor($contract)) {
            return false;
        }

        if ($contract == 'AWARD') {
            $array = $this->simpleXmlArrayData['AWARD_CONTRACT']['AWARDED_CONTRACT']['CONTRACTOR']['ADDRESS_CONTRACTOR'];
        } else if ($contract == 'MODIFICATIONS') {
            $array = $this->simpleXmlArrayData['MODIFICATIONS_CONTRACT']['DESCRIPTION_PROCUREMENT']['CONTRACTOR']['ADDRESS_CONTRACTOR'];
        } else {
            return false;
        }

        // this is actually a test if the provided array contains numerical or string keys
        // or in other words if the provided array is (not) an 'associative' array
        return count(array_filter(array_keys($array), 'is_string')) === 0;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Utilities
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Retrieve a value (string) for a given $needle from the provided $hayStack
     *
     * Do NOT use this if you need to check for the existence of a given key.
     * - in this case better just use isset()
     *
     * @param array $hayStack
     * @param String $needle
     * @return null|string NULL if given $needle not found in haystack
     *                     NULL if empty string
     *                     String otherwise
     *
     */
    protected function getField($hayStack, $needle) {
        if (!isset($hayStack[$needle])) {
            return null;
        }

        $value = $hayStack[$needle];

        if (is_string($value)) {
            $value = trim($value);

            if (strlen($value) === 0) {
                return null;
            }

            return $value;
        }

        // gets a bit tricky here
        if (is_array($value)) {
            // missing values are interpreted as an empty array by the xml/json parse sequence
            // in the source xml string it looks like this: <TAG_NAME></TAG_NAME>
            // or <TAG_NAME />
            if (!count($value)) {
                return null;

                // but if the source tag was something like <TAG_NAME>   </TAG_NAME>
                // then this results in an array with 3 spaces as a value: ['   ']
                // not useful --> return null
            } else if(count($value) === 1) {
                $val = trim($value[0]);
                if (strlen($val) === 0) {
                    // after trim no string left --> no value at all
                    return null;
                } else {
                    // ???? should never happen
                    dump("Field was interpreted as array with length 1. Why? Key: $needle",$value);
                    dd('Exit PreProcessor');
                }
            } else {
                // ???? should never happen
                dump("Found multiple values while only expecting 0 or 1 for key '$needle'",$value);
                dd('Exit PreProcessor');
            }
        }

        dump($hayStack,$value);
        dd('what are you?');

        return $value;
    }

    protected function getNumber($hayStack, $needle) {
        $value = $this->getField($hayStack,$needle);

        if (!$value) {
            return null;
        }

        if ($this->validatesAsInt($value)) {
            return intval($value);
        }

        dump('Unexpected value (not an int???) for '.$needle);
        dump($hayStack);

        return null;
    }

    /**
     * Get text from multi paragraph text field
     *
     * @param $hayStack
     * @param $needle
     * @param $lineSeparator
     * @return null
     */
    protected function getMultiLineText($hayStack,$needle,$lineSeparator = '\n') {
        if (!isset($hayStack[$needle])) {
            return null;
        }

        $text = $hayStack[$needle];

        if (!isset($text['P'])) {
            // should never happen ??? why ???
            dump("Expected Paragraphs, found something else '$needle'",$text);
            dd('Exit PreProcessor');
        }

        $ps = $text['P'];

        $result = null;

        if (is_array($ps)) {
            $ps = array_filter($ps,function($i) {
                // possible array problem, empty last line, filter out
                return !is_array($i);
            });
            $result = join($lineSeparator,$ps);
        } else if (is_string($ps)){
            // should never happen ???? why ???
            // dump("INFO: Text had P attribute but is not an array? key $needle",$text);

            $val = trim($ps);
            $result = strlen($val) > 0 ? $val : null;
        } else {
            // should never happen ????
            dump("Unable to get MultiLine Text",$hayStack,$needle);
            dd('Exit PreProcessor');
        }

        return $result;
    }

    /**
     * Check if a given String validates as an actual Integer
     *
     * @param $number
     * @return bool
     */
    protected function validatesAsInt($number) {
        $number = filter_var($number, FILTER_VALIDATE_INT);
        return ($number !== FALSE);
    }
}