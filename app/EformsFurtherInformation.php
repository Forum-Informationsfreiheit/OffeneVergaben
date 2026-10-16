<?php

namespace App;

use Carbon\Carbon;
use DOMAttr;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * "Further information" of an eForms notice: the fields the app has no column for, human readable with the official
 * German names of the eForms SDK, to be shown as they are.
 *
 * Uses the SDK extracts in resources/eforms-sdk (see php artisan eforms:import-sdk). Fields are grouped by their
 * business entity, repeating entities (lots, organizations, tenders, ...) by instance:
 *
 * [
 *   'sdk'    => '1.13',
 *   'groups' => [
 *     [ 'title' => 'Los: Fahrbahn', 'entries' => [ [ 'field' => 'BT-27-Lot', 'label' => 'Geschätzter Wert ohne MwSt.', 'value' => '78.412,00 EUR' ], ... ] ],
 *     ...
 *   ],
 * ]
 *
 * @package App
 */
class EformsFurtherInformation
{
    // technical identifiers and references between parts of the notice, no information for readers
    const SKIPPED_TYPES = ['id', 'id-ref'];

    // OPT-999 is a placeholder date ("fictitious date" in the SDK), required by the schema but without meaning
    const SKIPPED_FIELDS = ['OPT-999'];

    // order of the groups; entities missing here follow in document order
    const ENTITY_ORDER = ['procedure', 'lot', 'groupOfLots', 'part', 'noticeResult', 'resultForLot', 'tender',
        'contract', 'tenderingParty', 'contractModification', 'organization', 'touchpoint', 'ultimateBeneficialOwner',
        'buyer', 'reviewInformation', 'noticeInformation', 'changes'];

    // field naming an instance of a repeating entity in group titles; else its identifier (e.g. TEN-0001)
    const TITLE_FIELDS = [
        'lot'          => 'BT-21-Lot',
        'groupOfLots'  => 'BT-21-LotsGroup',
        'part'         => 'BT-21-Part',
        'organization' => 'BT-500-Organization-Company',
        'touchpoint'   => 'BT-500-Organization-TouchPoint',
        'contract'     => 'BT-721-Contract',
    ];

    // multilingual texts: the language shown if a text is given in several languages
    const LANGUAGE = 'DEU';

    /**
     * @var array[] loaded SDK extracts by version
     */
    protected static $sdks = [];

    protected $version;

    protected $fields;

    protected $entities;

    protected $labels;

    /**
     * @param string $version  SDK minor version, e.g. '1.13'; falls back to the newest bundled version
     */
    public function __construct($version) {
        $this->version = self::resolveVersion($version);

        if (!isset(self::$sdks[$this->version])) {
            $directory = resource_path('eforms-sdk/' . $this->version);
            $fields = json_decode(file_get_contents($directory . '/fields.json'), true);
            self::$sdks[$this->version] = [
                'fields'   => $fields['fields'],
                'entities' => $fields['entities'],
                'labels'   => json_decode(file_get_contents($directory . '/labels-de.json'), true),
            ];
        }

        $this->fields   = self::$sdks[$this->version]['fields'];
        $this->entities = self::$sdks[$this->version]['entities'];
        $this->labels   = self::$sdks[$this->version]['labels'];
    }

    /**
     * @param string|null $version  e.g. '1.13' or 'eforms-sdk-1.13' (cbc:CustomizationID)
     * @return string  the bundled version to use
     */
    public static function resolveVersion($version) {
        $version = preg_replace('/^eforms-sdk-/', '', (string)$version);

        if ($version && is_dir(resource_path('eforms-sdk/' . $version))) {
            return $version;
        }

        $versions = array_map('basename', glob(resource_path('eforms-sdk/*'), GLOB_ONLYDIR));
        usort($versions, 'version_compare');

        return end($versions);
    }

    public function getVersion() {
        return $this->version;
    }

    /**
     * @param DOMXPath $xpath  of the notice, with the eForms namespace prefixes registered
     * @param array $consumed  node paths read by the parser (see TracksUnparsedNodes), these are left out
     * @return array  see class description
     */
    public function extract(DOMXPath $xpath, array $consumed) {
        $instances = $this->findInstances($xpath);

        $groups = [];
        $shown = [];

        foreach ($this->fieldsInOrderOfPreference() as $id => $field) {
            if (isset($field['attributeOf']) || in_array($field['type'], self::SKIPPED_TYPES) || in_array($id, self::SKIPPED_FIELDS)) {
                continue;
            }

            $nodes = @$xpath->query($field['xpath']);
            if ($nodes === false) {
                continue;
            }

            foreach ($nodes as $node) {
                // each element once, also if several fields describe it (e.g. BT-98-Lot and OPA-98-Lot-Number)
                if (isset($consumed[$node->getNodePath()]) || isset($shown[$node->getNodePath()]) || !$this->isShownLanguage($node)) {
                    continue;
                }

                $value = $this->format($field, $node);
                if ($value === null || $value === '') {
                    continue;
                }

                $entity = $field['entity'] ?? 'procedure';
                $instance = $this->instanceOf($node, $entity, $instances);

                // e.g. the service provider (entity organization) is given within the buyer: group with the buyer
                if (!$instance && !empty($this->entities[$entity]['repeatsWith'])) {
                    $instance = $this->instanceOf($node, null, $instances);
                    $entity = $instance ? $instances[$instance->getNodePath()][0] : $entity;
                }

                // the name of the instance is the title of its group
                if ($instance && (self::TITLE_FIELDS[$entity] ?? null) === $id) {
                    continue;
                }
                $key = $instance ? $instance->getNodePath() : $entity;

                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'entity'  => $entity,
                        'line'    => $instance ? $instance->getLineNo() : 0,
                        'node'    => $instance,
                        'entries' => [],
                    ];
                }

                $shown[$node->getNodePath()] = true;
                $groups[$key]['entries'][] = [
                    'line'  => $node->getLineNo(),
                    'field' => $id,
                    'label' => $this->labels['fields'][$id] ?? $id,
                    'value' => $value,
                ];
            }
        }

        return [
            'sdk'    => $this->version,
            'groups' => $this->sortAndTitle($groups, $xpath, $instances),
        ];
    }

    /**
     * Business terms (BT-*) before the auxiliary fields (OPA-*, OPT-*, ...), which partly describe the same
     * elements in a technical way.
     *
     * @return array  field id => definition
     */
    protected function fieldsInOrderOfPreference() {
        $fields = $this->fields;

        uksort($fields, function ($a, $b) {
            return (strpos($a, 'BT-') === 0 ? 0 : 1) <=> (strpos($b, 'BT-') === 0 ? 0 : 1) ?: strnatcmp($a, $b);
        });

        return $fields;
    }

    /**
     * Nodes of the repeating entities (lots, organizations, ...).
     *
     * @return array  node path => [ entity, node ]
     */
    protected function findInstances(DOMXPath $xpath) {
        $instances = [];

        foreach ($this->entities as $entity => $definition) {
            if (empty($definition['repeatsWith'])) {
                continue;
            }

            $nodes = @$xpath->query($definition['repeatsWith']);
            foreach ($nodes ?: [] as $node) {
                $instances[$node->getNodePath()] = [$entity, $node];
            }
        }

        return $instances;
    }

    /**
     * @param string|null $entity  null: the nearest instance of any entity
     * @return DOMElement|null  the node of the instance of $entity that $node belongs to
     */
    protected function instanceOf(DOMNode $node, $entity, array $instances) {
        for ($n = $node instanceof DOMAttr ? $node->ownerElement : $node; $n instanceof DOMElement; $n = $n->parentNode) {
            $path = $n->getNodePath();
            if (isset($instances[$path]) && ($entity === null || $instances[$path][0] === $entity)) {
                return $n;
            }
        }

        return null;
    }

    /**
     * A text given in several languages is shown in German only.
     */
    protected function isShownLanguage(DOMNode $node) {
        if (!$node instanceof DOMElement || !$node->hasAttribute('languageID') || $node->getAttribute('languageID') === self::LANGUAGE) {
            return true;
        }

        foreach ($node->parentNode->childNodes as $sibling) {
            if ($sibling instanceof DOMElement && $sibling->localName === $node->localName
                && $sibling->getAttribute('languageID') === self::LANGUAGE) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string|null  the value as shown to readers
     */
    protected function format(array $field, DOMNode $node) {
        $value = trim(preg_replace('/\s+/u', ' ', $node->textContent));
        if ($value === '') {
            return null;
        }

        switch ($field['type']) {
            case 'code':
                return $this->codeLabel($field['codeList'] ?? null, $value);

            case 'indicator':
                return $value === 'true' ? 'ja' : ($value === 'false' ? 'nein' : $value);

            case 'amount':
                return trim($this->number($value) . ' ' . $this->attributeValue($field, $node, 'currencyID'));

            case 'number':
            case 'integer':
                return $this->number($value);

            case 'measure':
                return trim($value . ' ' . $this->attributeValue($field, $node, 'unitCode'));

            case 'date':
                return $this->dateValue($value);

            case 'time':
                return substr($value, 0, 5);

            default:
                return $value;
        }
    }

    protected function codeLabel($codeList, $code) {
        return $this->labels['codes'][$codeList . '.' . $code] ?? $code;
    }

    /**
     * Value of an attribute field (e.g. currency, unit), with the label of its code.
     */
    protected function attributeValue(array $field, DOMNode $node, $attribute) {
        if (!$node instanceof DOMElement || !$node->hasAttribute($attribute)) {
            return '';
        }

        $code = $node->getAttribute($attribute);

        foreach ($field['attributes'] ?? [] as $attributeField) {
            $definition = $this->fields[$attributeField] ?? [];
            if (($definition['attribute'] ?? null) === $attribute && $attribute !== 'currencyID') {
                return $this->codeLabel($definition['codeList'] ?? null, $code);
            }
        }

        // currencies as code (EUR), as usual for amounts
        return $code;
    }

    protected function number($value) {
        if (!is_numeric($value)) {
            return $value;
        }

        $decimals = strpos($value, '.') !== false ? 2 : 0;

        return number_format((float)$value, $decimals, ',', '.');
    }

    protected function dateValue($value) {
        try {
            return Carbon::createFromFormat('!Y-m-d', substr($value, 0, 10))->format('d.m.Y');
        } catch (\Exception $ex) {
            return $value;
        }
    }

    /**
     * Orders the groups and gives them titles, e.g. "Los: Fahrbahn", "Organisation: Muster Bau GmbH".
     *
     * @return array[]  [ 'title' => ..., 'entries' => [ [ 'field', 'label', 'value' ], ... ] ]
     */
    protected function sortAndTitle(array $groups, DOMXPath $xpath, array $instances) {
        $titles = $this->instanceTitles($xpath, $instances);
        $order = array_flip(self::ENTITY_ORDER);

        uasort($groups, function ($a, $b) use ($order) {
            $entityA = $order[$a['entity']] ?? count($order);
            $entityB = $order[$b['entity']] ?? count($order);

            return $entityA <=> $entityB ?: $a['line'] <=> $b['line'];
        });

        $result = [];
        foreach ($groups as $group) {
            usort($group['entries'], function ($a, $b) {
                return $a['line'] <=> $b['line'];
            });

            $title = $this->labels['entities'][$group['entity']] ?? $group['entity'];
            if ($group['node'] && isset($titles[$group['node']->getNodePath()])) {
                $title .= ': ' . $titles[$group['node']->getNodePath()];
            }

            $result[] = [
                'title'   => $title,
                'entries' => array_map(function ($entry) {
                    unset($entry['line']);
                    return $entry;
                }, $group['entries']),
            ];
        }

        return $result;
    }

    /**
     * @return array  instance node path => name (or identifier) of the instance
     */
    protected function instanceTitles(DOMXPath $xpath, array $instances) {
        $titles = [];
        $organizationNames = $this->organizationNames($xpath);

        foreach ($this->entities as $entity => $definition) {
            // the name of an instance if it has one, else its identifier (e.g. CON-0001)
            $fieldIds = array_filter([self::TITLE_FIELDS[$entity] ?? null, $definition['identifier'] ?? null]);

            foreach ($fieldIds as $fieldId) {
                if (!isset($this->fields[$fieldId])) {
                    continue;
                }

                $this->addInstanceTitles($titles, $xpath->query($this->fields[$fieldId]['xpath']) ?: [], $entity, $instances, $organizationNames);
            }
        }

        return $titles;
    }

    /**
     * Adds the values of $nodes as titles of their instances, if these have no title yet.
     */
    protected function addInstanceTitles(array &$titles, $nodes, $entity, array $instances, array $organizationNames) {
        foreach ($nodes as $node) {
            if (!$this->isShownLanguage($node)) {
                continue;
            }
            $instance = $this->instanceOf($node, $entity, $instances);
            $value = trim($node->textContent);
            if ($instance && $value !== '' && !isset($titles[$instance->getNodePath()])) {
                // references to organizations (e.g. buyers: ORG-0001) by name
                $titles[$instance->getNodePath()] = $organizationNames[$value] ?? $value;
            }
        }
    }

    /**
     * @return array  organization id (ORG-0001) => name
     */
    protected function organizationNames(DOMXPath $xpath) {
        $names = [];

        foreach (@$xpath->query($this->fields['OPT-200-Organization-Company']['xpath'] ?? '') ?: [] as $id) {
            $company = $id->parentNode->parentNode;
            foreach ($company->getElementsByTagNameNS(DOMEforms::NAMESPACES['cac'], 'PartyName') as $partyName) {
                if ($partyName->parentNode === $company) {
                    $names[trim($id->textContent)] = trim($partyName->textContent);
                    break;
                }
            }
        }

        return $names;
    }
}
