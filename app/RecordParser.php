<?php

namespace App;

/**
 * Parses the xml of a kerndaten record (scraper database) with the parser for its format: Kerndaten (root element
 * KD_*) or eForms (UBL notice). Used by App\Jobs\Process and fif:update-from-xml.
 *
 * @package App
 */
class RecordParser
{
    /**
     * @param string $xml
     * @param \Psr\Log\LoggerInterface $log
     * @param int $recordId  kerndaten id, for log and error messages
     *
     * @return \stdClass|null  data as returned by DOMKerndaten::getData(), null if the format is unknown
     *
     * @throws \Exception if the xml can't be loaded or parsed
     */
    public static function parse($xml, $log, $recordId) {
        $document = new \DOMDocument();

        if (!@$document->loadXML((string)$xml)) {
            throw new \Exception("Unable to load xml of record $recordId");
        }

        $info = [ 'record_id' => $recordId ];

        if (DOMEforms::matches($document)) {
            return (new DOMEforms($document, $log, $info))->getData();
        }

        if (strpos($document->documentElement->localName, 'KD_') === 0) {
            return (new DOMKerndaten($document, $log, $info))->getData();
        }

        $log->warning("Skipped record $recordId: unknown format", [
            'record_id' => $recordId,
            'root'      => $document->documentElement->nodeName,
            'namespace' => $document->documentElement->namespaceURI,
        ]);

        return null;
    }
}
