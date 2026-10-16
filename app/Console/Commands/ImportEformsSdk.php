<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Extracts the parts of the eForms SDK needed for the "further information" of eForms datasets
 * (see App\EformsFurtherInformation) into resources/eforms-sdk/<version>/:
 *
 * - fields.json: the fields (business terms) with XPath, type, business entity, code list and attribute fields,
 *   and the repeating business entities (lot, organization, ...) with the XPath of their node
 * - labels-de.json: German names of fields, business entities and the codes of the code lists used by fields
 *
 * The SDK is published by the Publications Office of the EU under CC BY 4.0: https://github.com/OP-TED/eForms-SDK
 */
class ImportEformsSdk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eforms:import-sdk {path : directory of an eForms SDK checkout or release}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract fields and German labels of an eForms SDK into resources/eforms-sdk/<version>.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = rtrim($this->argument('path'), '/');

        $sdk = json_decode(file_get_contents($path . '/fields/fields.json'), true);
        if (!$sdk || !isset($sdk['fields'], $sdk['xmlStructure'], $sdk['businessEntities'])) {
            $this->error("No eForms SDK found in $path");
            return 1;
        }

        // eforms-sdk-1.13.3 -> 1.13, as notices name the minor version only (cbc:CustomizationID eforms-sdk-1.13)
        $version = implode('.', array_slice(explode('.', preg_replace('/^eforms-sdk-/', '', $sdk['sdkVersion'])), 0, 2));

        $nodes = [];
        foreach ($sdk['xmlStructure'] as $node) {
            $nodes[$node['id']] = $node['xpathAbsolute'];
        }

        $entities = [];
        foreach ($sdk['businessEntities'] as $entity) {
            $entities[$entity['id']] = array_filter([
                'repeatsWith' => isset($entity['repeatsWithNodeId']) ? $nodes[$entity['repeatsWithNodeId']] : null,
                'identifier'  => $entity['instanceIdentifier']['identifierFieldId'] ?? null,
            ]);
        }

        $fields = [];
        $codeLists = [];
        foreach ($sdk['fields'] as $field) {
            $codeList = $field['codeList']['value']['id'] ?? null;
            if ($codeList) {
                $codeLists[$codeList] = true;
            }

            $fields[$field['id']] = array_filter([
                'xpath'       => $field['xpathAbsolute'],
                'type'        => $field['type'],
                'entity'      => $field['businessEntityId'] ?? null,
                'codeList'    => $codeList,
                'attributes'  => $field['attributes'] ?? null,
                'attributeOf' => $field['attributeOf'] ?? null,
                'attribute'   => $field['attributeName'] ?? null,
            ]);
        }

        $labels = [
            'fields'   => $this->translations($path . '/translations/field_de.xml', 'field|name|'),
            'entities' => $this->translations($path . '/translations/business-entity_de.xml', 'business-entity|name|'),
            'codes'    => array_filter(
                $this->translations($path . '/translations/code_de.xml', 'code|name|'),
                function ($key) use ($codeLists) {
                    return isset($codeLists[strtok($key, '.')]);
                },
                ARRAY_FILTER_USE_KEY
            ),
        ];

        $target = resource_path('eforms-sdk/' . $version);
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        file_put_contents($target . '/fields.json', json_encode([
            'sdkVersion' => $sdk['sdkVersion'],
            'entities'   => $entities,
            'fields'     => $fields,
        ], $flags));
        file_put_contents($target . '/labels-de.json', json_encode($labels, $flags));

        $this->info(sprintf('Imported eForms SDK %s into %s: %d fields, %d codes.',
            $sdk['sdkVersion'], $target, count($fields), count($labels['codes'])));

        return 0;
    }

    /**
     * @param string $file  translation file of the SDK (java properties as xml)
     * @param string $prefix  key prefix to select and strip
     * @return array  key without prefix => text
     */
    protected function translations($file, $prefix) {
        $result = [];

        foreach (simplexml_load_file($file)->entry as $entry) {
            $key = (string)$entry['key'];
            if (strpos($key, $prefix) === 0) {
                $result[substr($key, strlen($prefix))] = trim((string)$entry);
            }
        }

        return $result;
    }
}
