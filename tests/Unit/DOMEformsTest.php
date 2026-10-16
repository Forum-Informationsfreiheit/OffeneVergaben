<?php

namespace Tests\Unit;

use App\DOMEforms;
use DOMDocument;
use Mockery;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tests\Fixtures\Eforms;
use Tests\Fixtures\Kerndaten;
use Tests\TestCase;

class DOMEformsTest extends TestCase
{
    public function testParsesContractingBodies()
    {
        $data = $this->parse(Eforms::xml('can-standard'));

        $this->assertSame('eforms', $data->sourceFormat);
        $this->assertSame('E4', $data->sourceSubtype);
        $this->assertSame('KD_8_2_Z1', $data->type);

        $address = $data->contractingBody->address;
        $this->assertSame('Testgemeinde Musterdorf', $address->officialName);
        $this->assertSame('99999', $address->nationalId);
        $this->assertSame('+43 1 2345678', $address->phone);
        $this->assertSame('vergabe@musterdorf.example', $address->email);
        $this->assertSame('Maria Muster', $address->contact);
        $this->assertNull($address->domain);

        $this->assertCount(1, $data->contractingBody->addressAdditional);
        $this->assertSame('Testverband Musterregion', $data->contractingBody->addressAdditional[0]->officialName);
        $this->assertSame('999998z', $data->contractingBody->addressAdditional[0]->nationalId);
    }

    /**
     * Without identifier, the company register number given as endpoint is used (EVN).
     */
    public function testRegisterNumberInEndpointIsUsedWithoutIdentifier()
    {
        $replacements = ['<cac:PartyLegalEntity><cbc:CompanyID>99999</cbc:CompanyID></cac:PartyLegalEntity>' => '<cbc:EndpointID>FN 999999 z</cbc:EndpointID>'];
        $data = $this->parse(Eforms::xml('can-standard', $replacements));

        $this->assertSame('FN 999999 z', $data->contractingBody->address->nationalId);
        // mapped now, so no longer in the further information
        $this->assertNotContains('BT-509-Organization-Company', array_merge(...array_map(function ($group) {
            return array_column($group['entries'], 'field');
        }, $data->furtherInformation['groups'])));
    }

    /**
     * @dataProvider endpointProvider
     */
    public function testEndpointIsNoIdentifierOtherwise($companyId, $endpoint, $expected)
    {
        $data = $this->parse(Eforms::xml('can-standard', [
            '<cac:PartyLegalEntity><cbc:CompanyID>99999</cbc:CompanyID></cac:PartyLegalEntity>' =>
                ($companyId ? "<cac:PartyLegalEntity><cbc:CompanyID>$companyId</cbc:CompanyID></cac:PartyLegalEntity>" : '')
                ."<cbc:EndpointID>$endpoint</cbc:EndpointID>",
        ]));

        $this->assertSame($expected, $data->contractingBody->address->nationalId);
    }

    public function endpointProvider()
    {
        return [
            'identifier given'       => ['99999', 'FN 999999 z', '99999'],
            'endpoint is an url'     => [null, 'https://vergabe.musterdorf.example', null],
            'not at the beginning'   => [null, 'Firmenbuch FN 999999 z', null],
        ];
    }

    public function testParsesObjectContract()
    {
        $contract = $this->parse(Eforms::xml('can-standard'))->objectContract;

        $this->assertSame('Sanierung der Ortsdurchfahrt Musterdorf', $contract->title);
        $this->assertSame('Erneuerung der Fahrbahn und der Gehsteige', $contract->description);
        $this->assertSame('MD-2026-42', $contract->refNumber);
        $this->assertSame('99100000', $contract->cpv);
        // additional codes of the procedure and the codes of the lots, without the main code
        $this->assertSame(['99110000', '99120000'], $contract->additionalCpvs);
        $this->assertSame('WORKS', $contract->type);
        $this->assertTrue($contract->lot);
        $this->assertFalse($contract->noLot);
        $this->assertSame('ZZ999', $contract->nuts);
        // planned period of the first lot that has one
        $this->assertSame('2026-04-01 00:00 Europe/Vienna', $contract->dateStart->format('Y-m-d H:i e'));
        $this->assertSame('2026-10-31', $contract->dateEnd->format('Y-m-d'));
        $this->assertNull($contract->duration);
    }

    public function testParsesAward()
    {
        $data = $this->parse(Eforms::xml('can-standard'));

        $this->assertSame(['OTH_SINGLE'], $data->procedure->procedures);
        $this->assertFalse($data->procedure->isFramework);
        $this->assertNull($data->modificationsContract);
        $this->assertNull($data->results);

        $award = $data->awardContract;
        // the earliest contract conclusion date
        $this->assertSame('2026-03-01', $award->dateConclusionContract->format('Y-m-d'));
        // summed over the lots
        $this->assertSame(4, $award->nbTendersReceived);
        $this->assertSame(2, $award->nbSmeTender);
        $this->assertSame('1234567.89', $award->valTotal);
        $this->assertSame('EUR', $award->valTotalCurrency);
        $this->assertSame(['Muster Bau GmbH', 'Beispiel Asphalt KG'], array_column($award->contractors, 'officialName'));
        $this->assertSame(['999997y', '9999999999999'], array_column($award->contractors, 'nationalId'));
        // one of the winners is small
        $this->assertTrue($award->nbSmeContractor);

        $acd = $data->additionalCoreData;
        $this->assertTrue($acd->belowThreshold);
        $this->assertFalse($acd->aboveThreshold);
        $this->assertSame('Einstufiges Verfahren im Unterschwellenbereich', $acd->procedureDescription);
        $this->assertSame('2026-03-10', $acd->dateFirstPublication->format('Y-m-d'));
        $this->assertSame('2026-03-10 10:30 Europe/Vienna', $acd->dateTimeLastChange->format('Y-m-d H:i e'));
        $this->assertNull($acd->nbSmeContractor);
    }

    /**
     * @dataProvider companySizeProvider
     */
    public function testSmeContractorDependsOnCompanySize($size, $expected)
    {
        // both winners get the same size
        $xml = str_replace(['>large</efbc:CompanySizeCode>', '>small</efbc:CompanySizeCode>'], ">$size</efbc:CompanySizeCode>", Eforms::xml('can-standard'));

        $this->assertSame($expected, $this->parse($xml)->awardContract->nbSmeContractor);
    }

    public function companySizeProvider()
    {
        return [
            'micro'  => ['micro', true],
            'small'  => ['small', true],
            'medium' => ['medium', true],
            'sme'    => ['sme', true],
            'large'  => ['large', false],
        ];
    }

    public function testJustificationIsOnlyTheDirectAwardJustification()
    {
        $justifications = '<cac:ProcessJustification>'
            .'<cbc:ProcessReasonCode listName="accelerated-procedure">true</cbc:ProcessReasonCode>'
            .'<cbc:ProcessReason languageID="DEU">Dringlichkeit</cbc:ProcessReason>'
            .'</cac:ProcessJustification>'
            .'<cac:ProcessJustification>'
            .'<cbc:ProcessReasonCode listName="direct-award-justification">in-house</cbc:ProcessReasonCode>'
            .'<cbc:ProcessReason languageID="DEU">Inhouse-Vergabe</cbc:ProcessReason>'
            .'</cac:ProcessJustification>';

        $data = $this->parse(Eforms::xml('can-standard', [
            '<cbc:ProcedureCode listName="procurement-procedure-type">oth-single</cbc:ProcedureCode>' =>
                '<cbc:ProcedureCode listName="procurement-procedure-type">oth-single</cbc:ProcedureCode>'.$justifications,
        ]));

        $this->assertSame('Inhouse-Vergabe', $data->additionalCoreData->justification);
    }

    public function testLotGroupsAreNoLots()
    {
        $lotGroup = '<cac:ProcurementProjectLot><cbc:ID schemeName="LotsGroup">GLO-0001</cbc:ID></cac:ProcurementProjectLot>';
        $data = $this->parse(Eforms::xml('can-modif', ['</ContractAwardNotice>' => $lotGroup.'</ContractAwardNotice>']));

        // one lot and a group: still not divided into lots
        $this->assertFalse($data->objectContract->lot);
        $this->assertTrue($data->objectContract->noLot);
    }

    public function testValueIsSumOfWinningTendersWithoutTotalAmount()
    {
        $award = $this->parse(Eforms::xml('can-standard', [
            '<cbc:TotalAmount currencyID="EUR">1234567.89</cbc:TotalAmount>' => '',
        ]))->awardContract;

        $this->assertEquals(1234567.89, (float)$award->valTotal);
        $this->assertSame('EUR', $award->valTotalCurrency);
    }

    public function testElectronicSubmissionsAreUsedWithoutTotalNumberOfTenders()
    {
        // both lots report electronic submissions (3 + 1) instead of the number of tenders
        $xml = str_replace('>tenders</efbc:StatisticsCode>', '>t-esubm</efbc:StatisticsCode>', Eforms::xml('can-standard'));

        $this->assertSame(4, $this->parse($xml)->awardContract->nbTendersReceived);
    }

    public function testNoAwardIfNoWinnerWasSelected()
    {
        $xml = str_replace('>selec-w</cbc:TenderResultCode>', '>clos-nw</cbc:TenderResultCode>', Eforms::xml('can-standard'));
        $xml = preg_replace('#<efac:SettledContract>.*?</efac:SettledContract>#s', '', $xml);

        $this->assertNull($this->parse($xml)->awardContract);
    }

    public function testParsesContractNotice()
    {
        $data = $this->parse(Eforms::xml('cn-standard'));

        $this->assertSame('KD_8_1_Z2', $data->type);
        $this->assertNull($data->awardContract);

        $this->assertSame('https://vergabe.musterdorf.example/2026-43', $data->contractingBody->urlDocument);
        $this->assertTrue($data->contractingBody->urlDocumentIsFull);
        $this->assertFalse($data->contractingBody->urlDocumentIsRestricted);
        $this->assertSame('https://vergabe.musterdorf.example/2026-43/teilnahme', $data->contractingBody->urlParticipation);

        $this->assertSame('SUPPLIES', $data->objectContract->type);
        // only the country given
        $this->assertSame('AT', $data->objectContract->nuts);
        // 48 months of the first lot
        $this->assertSame(1440, $data->objectContract->duration);

        $this->assertSame(['PT_OPEN'], $data->procedure->procedures);
        $this->assertTrue($data->procedure->isFramework);
        // the earliest deadline of the lots
        $this->assertSame('2026-02-15 12:00 Europe/Vienna', $data->procedure->dateTimeReceiptTenders->format('Y-m-d H:i e'));

        $this->assertTrue($data->additionalCoreData->aboveThreshold);
        $this->assertFalse($data->additionalCoreData->belowThreshold);
    }

    public function testRestrictedDocumentsAreFlagged()
    {
        $cb = $this->parse(Eforms::xml('cn-standard', [
            'non-restricted-document' => 'restricted-document',
        ]))->contractingBody;

        $this->assertTrue($cb->urlDocumentIsRestricted);
        $this->assertFalse($cb->urlDocumentIsFull);
    }

    /**
     * @dataProvider procedureProvider
     */
    public function testMapsProcedureTypesToKerndatenCombinations($procedureCode, $expected)
    {
        $data = $this->parse(Eforms::xml('cn-standard', ['>open</cbc:ProcedureCode>' => ">$procedureCode</cbc:ProcedureCode>"]));

        $this->assertSame($expected, $data->procedure->procedures);
    }

    public function procedureProvider()
    {
        return [
            'open'        => ['open', ['PT_OPEN']],
            'restricted'  => ['restricted', ['PT_RESTRICTED', 'PT_WITH_PRIOR_NOTICE']],
            'neg-w-call'  => ['neg-w-call', ['PT_COMPETITIVE_NEGOTIATION', 'PT_WITH_PRIOR_NOTICE']],
            'neg-wo-call' => ['neg-wo-call', ['PT_COMPETITIVE_NEGOTIATION', 'PT_WITHOUT_PRIOR_NOTICE']],
            'comp-dial'   => ['comp-dial', ['PT_COMPETITIVE_DIALOGUE']],
            'innovation'  => ['innovation', ['PT_INNOVATION_PARTNERSHIP']],
            'oth-single'  => ['oth-single', ['OTH_SINGLE']],
            'oth-mult'    => ['oth-mult', ['OTH_MULT']],
            'comp-tend'   => ['comp-tend', ['COMP_TEND']],
            'exp-int-rail' => ['exp-int-rail', ['EXP_INT_RAIL']],
        ];
    }

    public function testDynamicPurchasingSystemIsAdded()
    {
        $xml = str_replace('listName="dps-usage">none<', 'listName="dps-usage">dps-list<', Eforms::xml('cn-standard'));

        $this->assertSame(['PT_OPEN', 'DPS'], $this->parse($xml)->procedure->procedures);
    }

    public function testParsesModification()
    {
        $data = $this->parse(Eforms::xml('can-modif'));

        $this->assertSame('KD_8_2_Z3', $data->type);
        // the modified contract is kept in the modifications contract, like in Kerndaten
        $this->assertNull($data->awardContract);

        $mc = $data->modificationsContract;
        $this->assertSame('99100000', $mc->cpv);
        $this->assertSame('Zusätzliche Lärmschutzwand', $mc->description);
        $this->assertSame('Auflage der Behörde', $mc->additionalNeed);
        $this->assertNull($mc->unforeseenCircumstance);
        $this->assertSame('150000.00', $mc->valTotalAfter);
        $this->assertNull($mc->valTotalBefore);
        $this->assertSame(['Muster Bau GmbH'], array_column($mc->contractors, 'officialName'));
    }

    public function testUnforeseenCircumstancesAreSeparated()
    {
        $mc = $this->parse(Eforms::xml('can-modif', ['>add-wss<' => '>mod-cir<']))->modificationsContract;

        $this->assertNull($mc->additionalNeed);
        $this->assertSame('Auflage der Behörde', $mc->unforeseenCircumstance);
    }

    public function testConcessionsGetConcessionTypes()
    {
        $data = $this->parse(Eforms::xml('can-standard', [
            '<cbc:RegulatoryDomain>other</cbc:RegulatoryDomain>' => '<cbc:RegulatoryDomain>32014L0023</cbc:RegulatoryDomain>',
        ]));

        $this->assertSame('KD_7_2_Z1', $data->type);
    }

    public function testUnsupportedNoticeTypeThrows()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Unsupported eForms notice type 'subco'");

        $this->parse(Eforms::xml('cn-standard', ['>cn-standard</cbc:NoticeTypeCode>' => '>subco</cbc:NoticeTypeCode>']));
    }

    public function testMatchesOnlyEformsNotices()
    {
        $eforms = new DOMDocument();
        $eforms->loadXML(Eforms::xml('can-standard'));
        $kerndaten = new DOMDocument();
        $kerndaten->loadXML(Kerndaten::xml('KD_8_2_Z1'));

        $this->assertTrue(DOMEforms::matches($eforms));
        $this->assertFalse(DOMEforms::matches($kerndaten));

        $this->expectException(\UnexpectedValueException::class);
        new DOMEforms($kerndaten, new NullLogger());
    }

    public function testUnparsedElementsAreLogged()
    {
        $log = Mockery::mock(LoggerInterface::class);
        $log->shouldReceive('warning')->once()->with('eForms record contains unparsed elements', Mockery::on(function ($context) {
            // e.g. the notice id, which the app has no column for; attributes like listName are not reported
            return $context['record_id'] === 7
                && in_array('/ContractAwardNotice/ID', $context['unparsed'])
                && !preg_grep('#/@(listName|schemeName|languageID)$#', $context['unparsed']);
        }));

        new DOMEforms(Eforms::xml('can-standard'), $log, ['record_id' => 7]);
    }

    private function parse($xml)
    {
        return (new DOMEforms($xml, new NullLogger()))->getData();
    }
}
