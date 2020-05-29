<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NationalIdParser
{
    const TYPE_FN  = 'FN';
    const TYPE_GLN = 'GLN';
    const TYPE_GKZ = 'GKZ';

    protected $originalId;

    protected $blackList;

    protected $valid;

    protected $blackListed;

    protected $type;

    protected $log;

    public function __construct($nationalId, $blackList = null) {
        if (!is_string($nationalId) && !is_integer($nationalId)) {
            throw new \InvalidArgumentException("No valid national ID provided. Must be either String or Integer");
        }

        $this->originalId = $nationalId;

        $this->type = null;

        if (!is_array($blackList)) {
            $this->useDefaultBlackList();
        }

        $this->log = Log::channel('processor_daily');
    }

    public function parse() {
        $nationalId = self::cleanId($this->originalId);

        if (!$this->validateId($nationalId)) {
            return;
        }

        $this->parseType($nationalId);
    }

    protected function useDefaultBlackList() {
        $this->blackList = [
            'unbekannt',
            'na',
            'va',
        ];
    }

    /**
     * Use a one-for-all cleaning approach.
     * (This could fail for future, not yet implemented, types)
     *
     * @param $id
     * @param null $specialChars
     * @return mixed|string
     */
    public static function cleanId($id, $specialChars = null) {
        if (!is_string($id) && !is_integer($id)) {
            throw new \InvalidArgumentException("Id argument invalid, must be either String or Integer");
        }
        if ($specialChars != null && !is_array($specialChars)) {
            throw new \InvalidArgumentException("SpecialChars argument invalid, use an array of One-Character Strings or null to use the default characters");
        }

        $defaultSpecialChars = ['.',',','-','/'];

        $id = trim((String) $id);

        // clear whitespace
        $id = str_replace(' ','',$id);
        // clear special characters
        $id = str_replace($specialChars ? $specialChars : $defaultSpecialChars,'',$id);
        // convert to lower case
        $id = strtolower($id);

        return $id;
    }

    /**
     * Very Basic level validation.
     *
     * @param $id
     *
     * @return boolean
     */
    protected function validateId($id) {
        // check blacklist
        if (in_array($id,$this->blackList)) {
            $this->isBlackListed = true;
            $this->valid = false;

            return false;
        }

        // check string length
        // GLN Numbers are 14 characters long
        // FN  Numbers (with or without FN prefix) are at least 5 characters long
        // GKZ Numbers are 5 characters long
        $this->valid = strlen($id) > 4;

        return $this->valid;
    }

    /**
     * Try to parse the type
     *
     * @param $id String
     */
    protected function parseType($id) {
        $isFn  = $this->checkFN($id);
        $isGLN = $this->checkGLN($id);
        $isGKZ = $this->checkGKZ($id);

        $positives = 0;
        $positives += $isFn  ? 1 : 0;
        $positives += $isGLN ? 1 : 0;
        $positives += $isGKZ ? 1 : 0;

        if ($positives > 1) {
            $this->log->error('NationalIdParser: Multiple valid types found for id:'.$id);
            return;
        }

        if ($isFn) {
            $this->type = self::TYPE_FN;
        }
        if ($isGLN) {
            $this->type = self::TYPE_GLN;
        }
        if ($isGKZ) {
            $this->type = self::TYPE_GKZ;
        }
    }

    /**
     * This is check is based on
     * http://www.pruefziffernberechnung.de/F/Firmenbuchnummer.shtml
     *
     * The checking number is only checked for 'plausibility' but not actually validated.
     *
     * @param $id String a no-white space all-lower case string
     *
     * FN starts with: FN or number
     *    prefix:      (FN) optional
     *    length:      7, e.g. 123456Z (can be shorter, like 5+Z need to fill up with 0 on the left side)
     *    ends with:   a character (one of: A, B, D, F, G, H, I, K, M, P, S, T, V, W, X, Y, Z)
     *
     * @return boolean
     */
    public static function checkFN($id) {
        $checkingNumberAlphabet = [
            'a', 'b', 'd', 'f', 'g', 'h', 'i', 'k', 'm', 'p', 's', 't', 'v', 'w', 'x', 'y', 'z'
        ];

        if (!Str::endsWith($id,$checkingNumberAlphabet)) {
            return false;
        }

        $prefixedWithFn = Str::startsWith($id,['fn']);

        // cut off first two and last character
        $justTheNumber = substr($id,0,-1);
        $justTheNumber = $prefixedWithFn ? substr($justTheNumber,2) : $justTheNumber;

        if (strlen($justTheNumber) > 6) {
            return false;
        }

        return true;
    }

    /**
     * @param $id
     *
     * @return boolean
     */
    public static function checkGLN($id) {
        // TODO warum manchmal 13 und manchmal 14 stellig???
        if (strlen($id) != 13 && strlen($id) != 14) {
            return false;
        }

        if (preg_match('#[^0-9]#',$id)) {
            return false;
        }

        // this should hold true for our use-case:
        // we expect that the number starts with 9.............. (Austria)
        if ($id[0] !== '9') {
            return false;
        }

        return true;
    }

    /**
     * @param $id
     * @return bool
     */
    public static function checkGKZ($id) {
        if (strlen($id) != 5) {
            return false;
        }

        if (preg_match('#[^0-9]#',$id)) {
            return false;
        }

        if ($id[0] === '0') {
            return false;
        }

        return true;
    }

    /**
     * @return mixed|string
     */
    protected function formattedGLN() {
        // could do zero-fill to 14 places, but for now just return the number
        return self::cleanId($this->originalId);
    }

    /**
     * @return mixed|string
     */
    protected function formattedGKZ() {
        // this type is a simple 5 digit integer anyway, just return it
        return self::cleanId($this->originalId);
    }

    /**
     * @return string FN<number><control character>
     */
    protected function formattedFN() {
        $cleaned = self::cleanId($this->originalId);

        // Prefixed
        if (Str::startsWith($cleaned,'fn')) {
            $cleaned[0] = 'F';
            $cleaned[1] = 'N';

            return $cleaned;
        }

        return 'FN'.$cleaned;
    }

    // PUBLIC API ------------------------------------------------------------------------------------------------------

    /**
     * @return bool
     */
    public function isValid() {
        return $this->valid;
    }

    /**
     * @return bool
     */
    public function isFN() {
        return $this->type === self::TYPE_FN;
    }

    /**
     * @return bool
     */
    public function isGLN() {
        return $this->type === self::TYPE_GLN;
    }

    /**
     * @return bool
     */
    public function isGKZ() {
        return $this->type === self::TYPE_GKZ;
    }

    /**
     * @return bool
     */
    public function isUnknown() {
        return $this->type === null;
    }

    /**
     * @return bool
     */
    public function isBlackListed() {
        return $this->blackListed;
    }

    /**
     * @return null|string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return null|string
     */
    public function getFormattedId() {
        if ($this->isGLN()) {
            return $this->formattedGLN();
        }

        if ($this->isFN()) {
            return $this->formattedFN();
        }

        if ($this->isGKZ()) {
            return $this->formattedGKZ();
        }

        return null;
    }
}