<?php
/**
 * Project: fif_offenevergaben
 * User: ives_markus
 * Date: 30/06/19
 */

namespace App;


class DataSourcePreProcessor
{
    protected $xmlString;

    protected $simpleXmlArrayData;

    // data
    protected $data;

    public function __construct() {
    }

    public function preProcess($xmlString) {
        $this->xmlString = $xmlString;
        $this->simpleXmlArrayData = $this->xmlToArray($this->xmlString);

        $this->data = new \stdClass();

        $this->data->type = $this->simpleXmlArrayData['FIF_TYPE'];
        $this->data->contractingBody = null;
        $this->data->objectContract = null;
        $this->data->awardContract = null;
        $this->data->modificationsContract = null;

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

        $oc->cpv       = $this->hasCpvMain() ? $this->getCpvMain() : null;
        $oc->nuts      = $this->hasNutsCode() ? $this->getNutsCode() : null;
        $oc->type      = $this->hasContractType() ? $this->getContractType() : null;
        $oc->refNumber = $this->getField($data,'REFERENCE_NUMBER');
        $oc->additionalCpvs = null;

        // Handle Object Description child
        if ($this->hasObjectDescription()) {
            if ($this->hasAnyAdditionalCpvs()) {
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
        }

        $oc->title = isset($data['TITLE']) ? $this->getMultiLineText($data,'TITLE') : null;
        $oc->description = isset($data['SHORT_DESCR']) ? $this->getMultiLineText($data,'SHORT_DESCR') : null;

        $this->data->objectContract = $oc;
    }

    protected function processAwardContract() {
        $data = $this->getAwardContract();

        $ac = new \stdClass();

        if ($this->hasAnyContractors('AWARD')) {
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
        } else {
            $ac->contractors = null;
        }

        $this->data->awardContract = $ac;
    }

    protected function processModificationsContract() {
        $data = $this->getModificationsContract();

        $mc = new \stdClass();

        if ($this->hasAnyContractors('MODIFICATIONS')) {
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

    /**
     * Retrieve a value (string) for a given $needle from the provided $hayStack
     *
     * Do NOT use this if you need to check for the existence of a given key.
     * - in this case better just use isset()
     *
     * @param array $hayStack
     * @param String $needle
     * @return null|string
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

        return $value;
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

    protected function xmlToArray($xmlString) {
        // use simplexml for parsing xml document
        $xml = simplexml_load_string($xmlString);

        // use json encode to transform to json
        $json = json_encode($xml);

        // use json decode to get an associative array
        $array = json_decode($json,TRUE);

        $type = $xml->getName(); // e.g. "KD_8_1_Z2"
        // add type to result, use prefix to prevent name collision
        $array['FIF_TYPE'] = $type;

        return $array;
    }


    // GETTERS / UTILITIES

    public function getData() {
        return $this->data;
    }

    protected function getContractingBody() {
        return $this->simpleXmlArrayData['CONTRACTING_BODY'];
    }
    protected function hasContractingBody() {
        return isset($this->simpleXmlArrayData['CONTRACTING_BODY']);
    }

    protected function getObjectContract() {
        return $this->simpleXmlArrayData['OBJECT_CONTRACT'];
    }
    protected function hasObjectContract() {
        return isset($this->simpleXmlArrayData['OBJECT_CONTRACT']);
    }

    protected function getObjectDescription() {
        return $this->simpleXmlArrayData['OBJECT_CONTRACT']['OBJECT_DESCR'];
    }
    protected function hasObjectDescription() {
        return $this->hasObjectContract() && isset($this->simpleXmlArrayData['OBJECT_CONTRACT']['OBJECT_DESCR']);
    }

    protected function getAwardContract() {
        return $this->simpleXmlArrayData['AWARD_CONTRACT'];
    }
    protected function hasAwardContract() {
        return isset($this->simpleXmlArrayData['AWARD_CONTRACT']);
    }

    protected function getModificationsContract() {
        return $this->simpleXmlArrayData['MODIFICATIONS_CONTRACT'];
    }
    protected function hasModificationsContract() {
        return isset($this->simpleXmlArrayData['MODIFICATIONS_CONTRACT']);
    }

    protected function getCpvMain() {
        $value = $this->simpleXmlArrayData['OBJECT_CONTRACT']['CPV_MAIN']['CPV_CODE']['@attributes']['CODE'];
        $value = trim($value);

        return strlen($value) === 0 ? null : $value;
    }

    protected function getCpvAdditional($hayStack) {
        if(isset($hayStack['CPV_CODE']) && isset($hayStack['CPV_CODE']['@attributes'])
              && isset($hayStack['CPV_CODE']['@attributes']['CODE'])) {

            $value = $hayStack['CPV_CODE']['@attributes']['CODE'];
            $value = trim($value);

            return strlen($value) === 0 ? null : $value;
        }

        return null;
    }

    protected function hasCpvMain() {
        if (!$this->hasObjectContract()) {
            return false;
        }

        $OC = $this->simpleXmlArrayData['OBJECT_CONTRACT'];

        return isset($OC['CPV_MAIN'])
            && isset($OC['CPV_MAIN']['CPV_CODE'])
            && isset($OC['CPV_MAIN']['CPV_CODE']['@attributes'])
            && isset($OC['CPV_MAIN']['CPV_CODE']['@attributes']['CODE']);
    }

    protected function getContractType() {
        $value = $this->simpleXmlArrayData['OBJECT_CONTRACT']['TYPE_CONTRACT']['@attributes']['CTYPE'];
        $value = trim($value);
        return strlen($value) === 0 ? null : $value;
    }

    protected function hasContractType() {
        if (!$this->hasObjectContract()) {
            return false;
        }

        $OC = $this->simpleXmlArrayData['OBJECT_CONTRACT'];

        return isset($OC['TYPE_CONTRACT'])
            && isset($OC['TYPE_CONTRACT']['@attributes'])
            && isset($OC['TYPE_CONTRACT']['@attributes']['CTYPE']);
    }

    protected function getNutsCode() {
        $value = $this->simpleXmlArrayData['OBJECT_CONTRACT']['OBJECT_DESCR']['NUTS']['@attributes']['CODE'];
        $value = trim($value);

        return strlen($value) === 0 ? null : $value;
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

    protected function hasAnyAdditionalCpvs() {
        return $this->hasObjectDescription() && isset($this->simpleXmlArrayData['OBJECT_CONTRACT']['OBJECT_DESCR']['CPV_ADDITIONAL']);
    }

    public function hasAnyAdditionalContractingBodies() {
        return $this->hasContractingBody() &&
        isset($this->simpleXmlArrayData['CONTRACTING_BODY']['ADDRESS_CONTRACTING_BODY_ADDITIONAL']);
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
        if (!$this->hasAnyAdditionalCpvs()) {
            return false;
        }

        $array = $this->simpleXmlArrayData['OBJECT_CONTRACT']['OBJECT_DESCR']['CPV_ADDITIONAL'];

        return count(array_filter(array_keys($array), 'is_string')) === 0;
    }

    public function hasAnyContractors($contract) {
        if ($contract == 'AWARD') {
            return $this->hasAwardContract()
                && isset($this->simpleXmlArrayData['AWARD_CONTRACT'])
                && isset($this->simpleXmlArrayData['AWARD_CONTRACT']['AWARDED_CONTRACT'])
                && isset($this->simpleXmlArrayData['AWARD_CONTRACT']['AWARDED_CONTRACT']['CONTRACTOR'])
                && isset($this->simpleXmlArrayData['AWARD_CONTRACT']['AWARDED_CONTRACT']['CONTRACTOR']['ADDRESS_CONTRACTOR']);
        }
        if ($contract == 'MODIFICATIONS') {
            return $this->hasModificationsContract()
                && isset($this->simpleXmlArrayData['MODIFICATIONS_CONTRACT'])
                && isset($this->simpleXmlArrayData['MODIFICATIONS_CONTRACT']['DESCRIPTION_PROCUREMENT'])
                && isset($this->simpleXmlArrayData['MODIFICATIONS_CONTRACT']['DESCRIPTION_PROCUREMENT']['CONTRACTOR'])
                && isset($this->simpleXmlArrayData['MODIFICATIONS_CONTRACT']['DESCRIPTION_PROCUREMENT']['CONTRACTOR']['ADDRESS_CONTRACTOR']);
        }

        return false;
    }

    protected function hasMultipleContractors($contract) {
        if (!$this->hasAnyContractors($contract)) {
            return false;
        }

        if ($contract == 'AWARD') {
            $array = $this->simpleXmlArrayData['AWARD_CONTRACT']['AWARDED_CONTRACT']['CONTRACTOR']['ADDRESS_CONTRACTOR'];
        } else if ($contract == 'MODIFICATIONS') {
            $array = $this->simpleXmlArrayData['MODIFICATIONS_CONTRACT']['DESCRIPTION_PROCUREMENT']['CONTRACTOR']['ADDRESS_CONTRACTOR'];
        }

        // this is actually a test if the provided array contains numerical or string keys
        // or in other words if the provided array is (not) an 'associative' array
        return count(array_filter(array_keys($array), 'is_string')) === 0;
    }
}