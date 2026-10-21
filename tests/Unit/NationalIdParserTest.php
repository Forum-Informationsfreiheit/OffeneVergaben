<?php

namespace Tests\Unit;

use App\NationalIdParser;
use Tests\TestCase;

class NationalIdParserTest extends TestCase
{
    /**
     * @dataProvider typeProvider
     */
    public function testParsesType($id, $type, $formattedId)
    {
        $parser = $this->parse($id);

        $this->assertTrue($parser->isValid());
        $this->assertSame($type, $parser->getType());
        $this->assertSame($type === null, $parser->isUnknown());
        $this->assertSame($type === NationalIdParser::TYPE_FN, $parser->isFN());
        $this->assertSame($type === NationalIdParser::TYPE_GLN, $parser->isGLN());
        $this->assertSame($type === NationalIdParser::TYPE_GKZ, $parser->isGKZ());
        $this->assertSame($formattedId, $parser->getFormattedId());
    }

    public function typeProvider()
    {
        return [
            // Firmenbuchnummer: up to 6 digits and a check letter, optionally with prefix FN
            'FN'                          => ['123456a', 'FN', 'FN123456a'],
            'FN with prefix and spaces'   => ['FN 123456 a', 'FN', 'FN123456a'],
            'FN with fewer digits'        => ['1234z', 'FN', 'FN1234z'],
            'FN with dash'                => ['fn-123456-x', 'FN', 'FN123456x'],
            // GLN: 13 or 14 digits starting with 9
            'GLN with 13 digits'          => ['9008390000009', 'GLN', '9008390000009'],
            'GLN with 14 digits and dots' => ['9.008.390.000.009.1', 'GLN', '90083900000091'],
            // Gemeindekennzahl: 5 digits, not starting with 0
            'GKZ'                         => ['90001', 'GKZ', '90001'],
            'GKZ as integer'              => [30101, 'GKZ', '30101'],
            // valid, but of no known type
            'UID'                         => ['ATU12345678', null, null],
            'FN with 7 digits'            => ['1234567a', null, null],
            'FN with invalid check letter'=> ['123456c', null, null],
            'GLN not starting with 9'     => ['8008390000009', null, null],
            'GKZ starting with 0'         => ['01234', null, null],
        ];
    }

    /**
     * @dataProvider invalidIdProvider
     */
    public function testInvalidIds($id)
    {
        $parser = $this->parse($id);

        $this->assertFalse($parser->isValid());
        $this->assertNull($parser->getFormattedId());
    }

    public function invalidIdProvider()
    {
        return [
            'too short'           => ['1234'],
            'unbekannt'           => ['unbekannt'],
            'n.a.'                => ['n.a.'],
            'N/A'                 => ['N/A'],
            'v.a.'                => ['v.a.'],
        ];
    }

    public function testIsBlackListed()
    {
        $this->assertTrue($this->parse('unbekannt')->isBlackListed());
        $this->assertFalse($this->parse('90001')->isBlackListed());
    }

    public function testCleanId()
    {
        $this->assertSame('fn123456a', NationalIdParser::cleanId(' FN 123.456-a/ '));
        $this->assertSame('12345', NationalIdParser::cleanId(12345));
        $this->assertSame('12-34', NationalIdParser::cleanId('1_2-3_4', ['_']));
    }

    /**
     * @dataProvider invalidArgumentProvider
     */
    public function testRejectsInvalidArguments(callable $call)
    {
        $this->expectException(\InvalidArgumentException::class);

        $call();
    }

    public function invalidArgumentProvider()
    {
        return [
            'parser without id'            => [function () { new NationalIdParser(null); }],
            'parser with array'            => [function () { new NationalIdParser(['90001']); }],
            'cleanId without id'           => [function () { NationalIdParser::cleanId(null); }],
            'cleanId with invalid chars'   => [function () { NationalIdParser::cleanId('90001', '-'); }],
        ];
    }

    private function parse($id)
    {
        $parser = new NationalIdParser($id);
        $parser->parse();

        return $parser;
    }
}
