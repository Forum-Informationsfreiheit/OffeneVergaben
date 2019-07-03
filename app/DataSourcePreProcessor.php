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

                    $cb->additional[] = $add;
                }
            } else {
                $cb->additional = null;
            }
        }

        // TODO missing document, url document etc.

        $this->data->contractingBody = $cb;
    }

    protected function processObjectContract() {
        $data = $this->getObjectContract();

        $oc = new \stdClass();

        $oc->cpv = $this->hasCpvMain() ? $this->getCpvMain() : null;
        $oc->nuts = $this->hasNutsCode() ? $this->getNutsCode() : null;

        // TODO title, etc.

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

        if (is_array($value)) {
            // missing values are usually interpreted as an empty array by the xml/json parse sequence
            if (!count($value)) {
                return null;
            } else {
                // should never happen
                // but if it does: handle error
                dump("Found multiple values while only expecting 0 or 1",$value);
                dd('Exit PreProcessor');
            }
        }

        return $value;
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