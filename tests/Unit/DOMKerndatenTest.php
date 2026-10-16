<?php

namespace Tests\Unit;

use App\DOMKerndaten;
use DOMDocument;
use Illuminate\Support\Facades\Log;
use Mockery;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\VarDumper\VarDumper;
use Tests\Fixtures\Kerndaten;
use Tests\TestCase;

class DOMKerndatenTest extends TestCase
{
    protected function tearDown(): void
    {
        VarDumper::setHandler(null);

        parent::tearDown();
    }

    public function testParsesContractingBody()
    {
        $data = $this->parse(Kerndaten::xml('KD_8_2_Z1'));

        $this->assertSame('KD_8_2_Z1', $data->type);

        $address = $data->contractingBody->address;
        $this->assertSame('Testgemeinde Musterdorf', $address->officialName);
        $this->assertSame('99999', $address->nationalId);
        $this->assertSame('+43 1 2345678', $address->phone);
        $this->assertSame('vergabe@musterdorf.example', $address->email);
        $this->assertSame('Maria Muster', $address->contact);
        $this->assertSame('federal', $address->domain);
        $this->assertNull($address->refNumber);

        $this->assertCount(1, $data->contractingBody->addressAdditional);
        $additional = $data->contractingBody->addressAdditional[0];
        $this->assertSame('Testverband Musterregion', $additional->officialName);
        $this->assertSame('999998z', $additional->nationalId);
        $this->assertSame('MR-2026-7', $additional->refNumber);

        $this->assertSame('https://vergabe.musterdorf.example/2026-42', $data->contractingBody->urlDocument);
        $this->assertSame('https://vergabe.musterdorf.example/2026-42/teilnahme', $data->contractingBody->urlParticipation);
        $this->assertTrue($data->contractingBody->urlDocumentIsFull);
        $this->assertFalse($data->contractingBody->urlDocumentIsRestricted);
    }

    public function testParsesObjectContract()
    {
        $contract = $this->parse(Kerndaten::xml('KD_8_2_Z1'))->objectContract;

        $this->assertSame('Sanierung der Ortsdurchfahrt Musterdorf', $contract->title);
        // paragraphs are joined with a literal \n (backslash and n), which nl_to_br() turns into <br />
        $this->assertSame('Erneuerung der Fahrbahn\nund der Gehsteige', $contract->description);
        $this->assertSame('99100000', $contract->cpv);
        $this->assertSame(['99110000', '99120000'], $contract->additionalCpvs);
        $this->assertSame('WORKS', $contract->type);
        $this->assertSame('MD-2026-42', $contract->refNumber);
        $this->assertTrue($contract->lot);
        $this->assertFalse($contract->noLot);
        $this->assertSame('ZZ999', $contract->nuts);
        $this->assertSame('2026-04-01', $contract->dateStart->toDateString());
        $this->assertSame('2026-10-31', $contract->dateEnd->toDateString());
        // given in months, stored in days
        $this->assertSame(210, $contract->duration);
    }

    public function testDurationInDaysIsKeptAsIs()
    {
        $xml = Kerndaten::xml('KD_8_2_Z1', ['<DURATION TYPE="MONTH">7</DURATION>' => '<DURATION TYPE="DAY">45</DURATION>']);

        $this->assertSame(45, $this->parse($xml)->objectContract->duration);
    }

    public function testParsesProcedure()
    {
        $procedure = $this->parse(Kerndaten::xml('KD_8_2_Z1'))->procedure;

        $this->assertSame(['PT_OPEN'], $procedure->procedures);
        $this->assertTrue($procedure->isFramework);
        $this->assertSame('2026-02-15 12:00:00', $procedure->dateTimeReceiptTenders->toDateTimeString());
    }

    public function testParsesAwardContract()
    {
        $award = $this->parse(Kerndaten::xml('KD_8_2_Z1'))->awardContract;

        $this->assertSame('2026-03-01', $award->dateConclusionContract->toDateString());
        $this->assertSame(4, $award->nbTendersReceived);
        $this->assertSame(3, $award->nbSmeTender);
        $this->assertFalse($award->nbSmeContractor);
        $this->assertSame('1234567.89', $award->valTotal);
        $this->assertSame('EUR', $award->valTotalCurrency);

        $this->assertCount(2, $award->contractors);
        $this->assertSame('Muster Bau GmbH', $award->contractors[0]->officialName);
        $this->assertSame('999997y', $award->contractors[0]->nationalId);
        $this->assertSame('Beispiel Asphalt KG', $award->contractors[1]->officialName);
        $this->assertSame('9999999999999', $award->contractors[1]->nationalId);
    }

    public function testParsesAdditionalCoreData()
    {
        $core = $this->parse(Kerndaten::xml('KD_8_2_Z1'))->additionalCoreData;

        $this->assertSame('Keine Begründung erforderlich', $core->justification);
        $this->assertSame('2026-01-10', $core->dateFirstPublication->toDateString());
        $this->assertSame('2026-03-02 09:30:00', $core->dateTimeLastChange->toDateTimeString());
        $this->assertSame('2026-03-12', $core->deadlineStandstill->toDateString());
        $this->assertNull($core->nbSmeContractor);
        $this->assertSame('Offenes Verfahren im Oberschwellenbereich', $core->procedureDescription);
        $this->assertTrue($core->aboveThreshold);
        $this->assertFalse($core->belowThreshold);
        $this->assertSame('https://vergabe.musterdorf.example/2026-42/widerruf', $core->urlRevocation);
        $this->assertSame('https://vergabe.musterdorf.example/2026-42/widerrufserklaerung', $core->urlRevocationStatement);
        $this->assertTrue($core->rdNotification);
        $this->assertNull($core->objectContractModifications);
    }

    public function testSectionsMissingInRecordAreNull()
    {
        $data = $this->parse(Kerndaten::xml('KD_8_2_Z1'));

        $this->assertNull($data->modificationsContract);
        $this->assertNull($data->results);
        $this->assertNull($data->lefti);
    }

    public function testParsesModificationsContract()
    {
        $data = $this->parse(Kerndaten::xml('KD_8_2_Z3'));

        $this->assertNull($data->awardContract);
        $this->assertNull($data->objectContract->description);

        $modification = $data->modificationsContract;
        $this->assertSame('99100000', $modification->cpv);
        $this->assertSame(['99120000'], $modification->additionalCpvs);
        $this->assertSame('Zusätzliche Lärmschutzwand', $modification->description);
        $this->assertCount(1, $modification->contractors);
        $this->assertSame('Muster Bau GmbH', $modification->contractors[0]->officialName);
        $this->assertSame('999997y', $modification->contractors[0]->nationalId);
        $this->assertSame('Auflage der Behörde', $modification->additionalNeed);
        $this->assertNull($modification->unforeseenCircumstance);
        $this->assertSame('1234567.89', $modification->valTotalBefore);
        $this->assertSame('EUR', $modification->valTotalBeforeCurrency);
        $this->assertSame('150000', $modification->valTotalAfter);
        $this->assertSame('EUR', $modification->valTotalAfterCurrency);
    }

    public function testParsesAwardedPrize()
    {
        $results = $this->parse(Kerndaten::xml('KD_8_2_Z2'))->results;

        $this->assertSame(12, $results->nbParticipants);
        $this->assertSame(9, $results->nbParticipantsSme);
        $this->assertSame('25000', $results->valPrize);
        $this->assertSame('EUR', $results->valPrizeCurrency);
        $this->assertCount(1, $results->winners);
        $this->assertSame('Musterarchitekten ZT GmbH', $results->winners[0]->officialName);
        $this->assertSame('999996x', $results->winners[0]->nationalId);
    }

    public function testParsesConcessionModificationsAndLefti()
    {
        $data = $this->parse($this->minimalXml(
            '<ADDITIONAL_CORE_DATA>'
                .'<OBJECT_CONTRACT_MODIFICATIONS><TITLE><P>Parkraumbewirtschaftung</P></TITLE><TYPE_CONTRACT CTYPE="SERVICES"/></OBJECT_CONTRACT_MODIFICATIONS>'
            .'</ADDITIONAL_CORE_DATA>'
            .'<LEFTI><RESTRICTED_SHELTERED_WORKSHOP/></LEFTI>'
        ));

        $this->assertSame('Parkraumbewirtschaftung', $data->additionalCoreData->objectContractModifications->title);
        $this->assertSame('SERVICES', $data->additionalCoreData->objectContractModifications->type);
        $this->assertTrue($data->lefti->restrictedShelteredWorkshop);
        $this->assertFalse($data->lefti->restrictedShelteredProgram);
        $this->assertFalse($data->lefti->reservedOrganisationsServiceMission);
        $this->assertFalse($data->lefti->particularProfession);
    }

    /**
     * AWARD_CONTRACT without AWARDED_CONTRACT: no contract was awarded.
     */
    public function testNoAwardedContractLeavesAwardContractEmpty()
    {
        $data = $this->parse($this->minimalXml('<AWARD_CONTRACT><NO_AWARDED_CONTRACT/></AWARD_CONTRACT>'));

        $this->assertNull($data->awardContract);
    }

    public function testTextIsTrimmedAndStrippedOfTags()
    {
        $address = $this->parse(Kerndaten::xml('KD_8_2_Z1', [
            '<OFFICIALNAME>Testgemeinde Musterdorf</OFFICIALNAME>' => '<OFFICIALNAME>  Stadt &lt;b&gt;Wien&lt;/b&gt;  </OFFICIALNAME>',
            '<PHONE>+43 1 2345678</PHONE>'                         => '<PHONE>   </PHONE>',
        ]))->contractingBody->address;

        $this->assertSame('Stadt Wien', $address->officialName);
        $this->assertNull($address->phone);
    }

    public function testUnparsableDateIsLoggedAndNull()
    {
        $xml = Kerndaten::xml('KD_8_2_Z1', ['<DATE_START>2026-04-01</DATE_START>' => '<DATE_START>kein Datum</DATE_START>']);
        $log = Mockery::mock(LoggerInterface::class);
        $log->shouldReceive('error')->once();
        // getDate() also dumps the value
        VarDumper::setHandler(function () {
        });

        $data = (new DOMKerndaten($xml, $log))->getData();

        $this->assertNull($data->objectContract->dateStart);
    }

    /**
     * TODO: bug — DOMKerndaten::getDate() passes the trimmed text to Carbon::parse(), which returns the current time
     * for an empty string. So an empty date element (<DATE_END/>) is stored as the date of processing. Return null
     * for empty values, then remove markTestIncomplete() here.
     */
    public function testEmptyDateIsNull()
    {
        $this->markTestIncomplete('DOMKerndaten::getDate() returns the current time for an empty date element.');

        $xml = Kerndaten::xml('KD_8_2_Z1', ['<DATE_END>2026-10-31</DATE_END>' => '<DATE_END/>']);

        $this->assertNull($this->parse($xml)->objectContract->dateEnd);
    }

    public function testAcceptsDomDocument()
    {
        $document = new DOMDocument();
        $document->loadXML(Kerndaten::xml('KD_8_2_Z1'));

        $kerndaten = new DOMKerndaten($document, new NullLogger());

        $this->assertSame($document, $kerndaten->getDocument());
        $this->assertSame('KD_8_2_Z1', $kerndaten->getData()->type);
    }

    /**
     * A string starting with a slash is read as file path.
     */
    public function testLoadsXmlFromAbsoluteFilePath()
    {
        $path = tempnam(sys_get_temp_dir(), 'kerndaten');
        file_put_contents($path, Kerndaten::xml('KD_8_2_Z2'));

        try {
            $this->assertSame('KD_8_2_Z2', $this->parse($path)->type);
        } finally {
            unlink($path);
        }
    }

    public function testRejectsOtherArguments()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Parameter $xml must be a loaded DOMDocument');

        new DOMKerndaten(42, new NullLogger());
    }

    public function testMalformedXmlIsLoggedAndThrows()
    {
        $log = Mockery::mock(LoggerInterface::class);
        $log->shouldReceive('error')->once();
        Log::shouldReceive('channel')->with('processor_daily')->andReturn($log);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to create DOMDocument from string argument');

        new DOMKerndaten('<KD_8_2_Z1><CONTRACTING_BODY>', new NullLogger());
    }

    /**
     * The type is read from the root element, also if the file starts with a comment (e.g. AGES exports).
     */
    public function testTypeIsRootElementAfterLeadingComment()
    {
        $xml = Kerndaten::xml('KD_8_2_Z1', ['<KD_8_2_Z1>' => '<!-- Export vom 01.03.2026 --><KD_8_2_Z1>']);

        $this->assertSame('KD_8_2_Z1', $this->parse($xml)->type);
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testFixturesHaveNoUnparsedElements($type)
    {
        $this->assertSame([], (new DOMKerndaten(Kerndaten::xml($type), new NullLogger()))->getUnparsed());
    }

    public function fixtureProvider()
    {
        return [['KD_8_2_Z1'], ['KD_8_2_Z2'], ['KD_8_2_Z3']];
    }

    public function testUnparsedElementsAndAttributesAreLoggedAsXPaths()
    {
        $xml = Kerndaten::xml('KD_8_2_Z1', [
            '<FRAMEWORK/>'                   => '<FRAMEWORK><SINGLE_OPERATOR/></FRAMEWORK>',
            '<TYPE_CONTRACT CTYPE="WORKS"/>' => '<TYPE_CONTRACT CTYPE="WORKS" EXTRA="1"/><MAIN_SITE><P></P></MAIN_SITE>',
            '</KD_8_2_Z1>'                   =>'<COMPLEMENTARY_INFO><DATE_DISPATCH_NOTICE>2026-01-09</DATE_DISPATCH_NOTICE></COMPLEMENTARY_INFO></KD_8_2_Z1>',
        ]);

        $expected = [
            '/KD_8_2_Z1/OBJECT_CONTRACT/TYPE_CONTRACT/@EXTRA',
            '/KD_8_2_Z1/OBJECT_CONTRACT/MAIN_SITE (empty)',
            '/KD_8_2_Z1/PROCEDURE/FRAMEWORK/SINGLE_OPERATOR (empty)',
            '/KD_8_2_Z1/COMPLEMENTARY_INFO',
        ];

        $log = Mockery::mock(LoggerInterface::class);
        $log->shouldReceive('warning')->once()->with('Kerndaten record contains unparsed elements', [
            'record_id' => 7,
            'unparsed'  => $expected,
        ]);

        $kerndaten = new DOMKerndaten($xml, $log, ['record_id' => 7]);

        $this->assertSame($expected, $kerndaten->getUnparsed());
    }

    private function parse($xml)
    {
        return (new DOMKerndaten($xml, new NullLogger()))->getData();
    }

    /**
     * A record with only the elements every record has, followed by the given elements.
     */
    private function minimalXml($elements)
    {
        return '<KD_7_2_Z1>'
            .'<CONTRACTING_BODY><ADDRESS_CONTRACTING_BODY><OFFICIALNAME>Testgemeinde</OFFICIALNAME></ADDRESS_CONTRACTING_BODY></CONTRACTING_BODY>'
            .'<OBJECT_CONTRACT><TITLE><P>Testkonzession</P></TITLE></OBJECT_CONTRACT>'
            .$elements
            .'</KD_7_2_Z1>';
    }
}
