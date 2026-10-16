<?php

namespace Tests\Unit;

use App\DOMEforms;
use App\EformsFurtherInformation;
use Psr\Log\NullLogger;
use Tests\Fixtures\Eforms;
use Tests\TestCase;

class EformsFurtherInformationTest extends TestCase
{
    public function testUnmappedFieldsAreGroupedByEntityWithGermanNames()
    {
        $info = $this->furtherInformation(Eforms::xml('can-standard'));

        $this->assertSame('1.13', $info['sdk']);
        // the tender values are not mapped (the notice has a total value)
        $this->assertSame([
            ['field' => 'BT-720-Tender', 'label' => 'Wert der Ausschreibung', 'value' => '1.000.000,00 EUR'],
        ], $this->group($info, 'Angebot: TEN-0001'));
        $this->assertSame('234.567,89 EUR', $this->value($info, 'Angebot: TEN-0002', 'BT-720-Tender'));
        // dates in German format
        $this->assertSame('20.02.2026', $this->value($info, 'Auftrag: CON-0001', 'BT-1451-Contract'));
    }

    public function testMappedFieldsAndIdentifiersAreLeftOut()
    {
        $fields = $this->fields($this->furtherInformation(Eforms::xml('can-standard')));

        // mapped: title, main CPV, procedure type, total value, organization names
        foreach (['BT-21-Procedure', 'BT-262-Procedure', 'BT-105-Procedure', 'BT-161-NoticeResult', 'BT-500-Organization-Company'] as $mapped) {
            $this->assertNotContains($mapped, $fields);
        }
        // technical identifiers (ORG-0001, TEN-0001, ...)
        $this->assertNotContains('OPT-200-Organization-Company', $fields);
        $this->assertNotContains('OPT-321-Tender', $fields);
    }

    public function testGroupTitlesUseNamesAndTheNameIsNoEntry()
    {
        // an unmapped field of the lot (contract nature of the lot), so that the lot has a group
        $info = $this->furtherInformation(Eforms::xml('can-standard', [
            '<cbc:Name languageID="DEU">Fahrbahn</cbc:Name>' =>
                '<cbc:Name languageID="DEU">Fahrbahn</cbc:Name><cbc:ProcurementTypeCode listName="contract-nature">works</cbc:ProcurementTypeCode>',
        ]));
        $titles = array_column($info['groups'], 'title');

        // lots by their title, which is not repeated as an entry
        $this->assertContains('Los: Fahrbahn', $titles);
        $this->assertNotContains('BT-21-Lot', array_column($this->group($info, 'Los: Fahrbahn'), 'field'));
        // without name by their identifier
        $this->assertContains('Auftrag: CON-0001', $titles);
        // procedure first, then lots, ...
        $this->assertSame('Verfahren', $titles[0]);
        $this->assertSame('Los: Fahrbahn', $titles[1]);
    }

    public function testValuesAreFormatted()
    {
        $info = $this->furtherInformation(Eforms::xml('cn-standard', [
            '<cbc:ID schemeName="InternalID">MD-2026-43</cbc:ID>' => '<cbc:ID schemeName="InternalID">MD-2026-43</cbc:ID>'
                .'<cac:RequestedTenderTotal><cbc:EstimatedOverallContractAmount currencyID="EUR">500000</cbc:EstimatedOverallContractAmount></cac:RequestedTenderTotal>',
            '<cbc:ProcedureCode listName="procurement-procedure-type">open</cbc:ProcedureCode>' =>
                '<cbc:ProcedureCode listName="procurement-procedure-type">open</cbc:ProcedureCode>'
                .'<cac:ProcessJustification><cbc:ProcessReasonCode listName="accelerated-procedure">false</cbc:ProcessReasonCode></cac:ProcessJustification>',
            '<cbc:Name languageID="DEU">Monitore</cbc:Name>' =>
                '<cbc:Name languageID="DEU">Monitore</cbc:Name><cbc:ProcurementTypeCode listName="contract-nature">supplies</cbc:ProcurementTypeCode>',
        ]));

        // amounts with currency, indicators as ja/nein, codes by their German label
        $this->assertSame('500.000 EUR', $this->value($info, 'Verfahren', 'BT-27-Procedure'));
        $this->assertSame('nein', $this->value($info, 'Verfahren', 'BT-106-Procedure'));
        $this->assertSame('Lieferleistungen', $this->value($info, 'Los: Monitore', 'BT-23-Lot'));
    }

    /**
     * BT-98-Lot (duration with unit) and OPA-98-Lot-Number (only the number) describe the same element.
     */
    public function testEachElementIsShownOnceWithTheBusinessTerm()
    {
        $info = $this->furtherInformation(Eforms::xml('cn-standard', [
            '<cac:TenderRecipientParty>' => '<cac:TenderValidityPeriod><cbc:DurationMeasure unitCode="MONTH">5</cbc:DurationMeasure></cac:TenderValidityPeriod><cac:TenderRecipientParty>',
        ]));

        $this->assertSame([['field' => 'BT-98-Lot', 'label' => 'Dauer, während der das Angebot gültig bleiben muss', 'value' => '5 Monat']], array_values(array_filter(
            $this->group($info, 'Los: Notebooks'),
            function ($entry) {
                return strpos($entry['field'], '98') !== false;
            }
        )));
    }

    public function testGermanIsShownOfMultilingualTexts()
    {
        // the lot description is not mapped
        $xml = Eforms::xml('cn-standard', [
            '<cbc:Name languageID="DEU">Notebooks</cbc:Name>' => '<cbc:Name languageID="DEU">Notebooks</cbc:Name>'
                .'<cbc:Description languageID="ENG">Laptops</cbc:Description><cbc:Description languageID="DEU">Tragbare Rechner</cbc:Description>',
        ]);

        $entries = $this->group($this->furtherInformation($xml), 'Los: Notebooks');

        $this->assertSame(['Tragbare Rechner'], array_column(array_filter($entries, function ($entry) {
            return $entry['field'] === 'BT-24-Lot';
        }), 'value'));
    }

    public function testFallsBackToNewestBundledVersion()
    {
        $this->assertSame('1.13', EformsFurtherInformation::resolveVersion('eforms-sdk-1.13'));
        $this->assertSame('1.13', EformsFurtherInformation::resolveVersion('eforms-sdk-9.99'));
        $this->assertSame('1.13', EformsFurtherInformation::resolveVersion(null));
    }

    private function furtherInformation($xml)
    {
        return (new DOMEforms($xml, new NullLogger()))->getData()->furtherInformation;
    }

    /**
     * @return array|null  entries of the group with the title
     */
    private function group(array $info, $title)
    {
        foreach ($info['groups'] as $group) {
            if ($group['title'] === $title) {
                return $group['entries'];
            }
        }

        return null;
    }

    private function value(array $info, $title, $field)
    {
        foreach ($this->group($info, $title) ?: [] as $entry) {
            if ($entry['field'] === $field) {
                return $entry['value'];
            }
        }

        return null;
    }

    private function fields(array $info)
    {
        $fields = [];
        foreach ($info['groups'] as $group) {
            $fields = array_merge($fields, array_column($group['entries'], 'field'));
        }

        return $fields;
    }
}
