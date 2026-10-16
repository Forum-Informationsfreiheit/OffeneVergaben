<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{

    public function testContainsDecimal() {
        $this->assertSame(false, contains_decimal("100"));
        $this->assertSame(".", contains_decimal("1.00"));
        $this->assertSame(",", contains_decimal("1,00"));
        $this->assertSame(".", contains_decimal("1,0.0"));
    }

    /**
     * @dataProvider convertNumberToCentsProvider
     */
    public function testConvertNumberToCents($value, $expected) {
        $this->assertSame($expected, convert_number_to_cents($value));
    }

    public function convertNumberToCentsProvider() {
        return [
            'integer'                 => ['100', 10000],
            'one decimal (comma)'     => ['100,5', 10050],
            'one decimal (dot)'       => ['100.5', 10050],
            'two decimals'            => ['100.12', 10012],
            'excess decimals are cut' => ['100.129', 10012],
            'leading dot'             => ['.5', 50],
            'negative'                => ['-0.5', -50],
            'not numeric'             => ['abc', null],
            // TODO: needs to be fixed in the code
            //'trailing dot'            => ['100.', 10000],   // currently throws
        ];
    }

    /**
     * @dataProvider uiShortProvider
     */
    public function testUiShorten($text, $length, $expected) {
        $this->assertSame($expected, ui_shorten($text, $length));
    }

    public function uiShortProvider() {
        return [
            'null is returned unchanged'           => [null, 45, null],
            'empty string is returned unchanged'   => ['', 45, ''],
            'short text is returned unchanged'     => ['Stadt Wien', 45, 'Stadt Wien'],
            'text of exact length is not cut'      => [str_repeat('a', 45), 45, str_repeat('a', 45)],
            'text over length is cut with dots'    => [str_repeat('a', 46), 45, str_repeat('a', 45) . '...'],
            'custom length'                        => ['Hello World', 5, 'Hello...'],
            'multibyte text is cut by characters'  => [str_repeat('ä', 50), 45, str_repeat('ä', 45) . '...'],
            // TODO: needs to be fixed in the code
            //'short multibyte text is not cut'      => [str_repeat('ä', 30), 45, str_repeat('ä', 30)],
        ];
    }

    public function testDefaultLengthIs45() {
        $this->assertSame(str_repeat('a', 45) . '...', ui_shorten(str_repeat('a', 50)));
    }

    public function testUiFormatMoney() {
        $this->assertSame("0,01",  ui_format_money("1"));
        $this->assertSame("0,10",  ui_format_money("10"));
        $this->assertSame("1,00",  ui_format_money("100"));
        $this->assertSame("10,00",  ui_format_money("1000"));
        $this->assertSame("100,00", ui_format_money("10000"));
        $this->assertSame("1.000,00", ui_format_money("100000"));        
    }

    /**
     * @dataProvider uiHighlightTokensProvider
     */
    public function testUiHighlightTokens($text, $tokens, $expected) {
        $this->assertSame($expected, ui_highlight_tokens($text, $tokens, 'strong'));
    }

    public function uiHighlightTokensProvider()
    {
        return [
            'no tokens'                    => ['Stadt Wien', [], 'Stadt Wien'],
            'token not found'              => ['Stadt Wien', ['Graz'], 'Stadt Wien'],
            'single token'                 => ['Stadt Wien', ['Wien'], 'Stadt <strong>Wien</strong>'],
            'case insensitive, keeps case' => ['Stadt Wien', ['wien'], 'Stadt <strong>Wien</strong>'],
            'every occurrence'             => ['Wien, Wiener Neustadt', ['wien'], '<strong>Wien</strong>, <strong>Wien</strong>er Neustadt'],
            'multiple tokens'              => ['Stadt Wien', ['stadt', 'wien'], '<strong>Stadt</strong> <strong>Wien</strong>'],
            // regex special characters (see commit 3b402b2)
            'plus'                         => ['C++ Entwicklung', ['c++'], '<strong>C++</strong> Entwicklung'],
            'parentheses'                  => ['Preis (netto)', ['(netto)'], 'Preis <strong>(netto)</strong>'],
            'dot is no wildcard'           => ['a.b', ['.'], 'a<strong>.</strong>b'],
            'regex delimiter'              => ['Wien/Graz', ['/'], 'Wien<strong>/</strong>Graz'],
            'no backreference injection'   => ['Kosten $1 Mio', ['$1'], 'Kosten <strong>$1</strong> Mio'],
        ];
    }

    public function testUiHighlightTokensUsesBTagByDefault()
    {
        $this->assertSame('Stadt <b>Wien</b>', ui_highlight_tokens('Stadt Wien', ['wien']));
    }


    /**
     * @dataProvider nlToBrProvider
     */
    public function testNlToBr($text, $expected) {
        $this->assertSame($expected, nl_to_br($text));
    }

    public function nlToBrProvider() {
        return [
            "contains \\n"    => ["test\nhello", "test<br />hello"],
            "contains \\r"    => ["test\rhello", "test<br />hello"],
            "contains \\r\\n" => ["test\r\nhello", "test<br />hello"],
            "contains none"   => ["test hello", "test hello"],
            "no text"         => [null, null],
        ];
    }

    /**
     * @dataProvider producerLabelProvider
     */
    public function testProducerLabel($procedures, $expected) {
        $this->assertSame($expected, procedure_label($procedures));
    }

    public function producerLabelProvider() {
        return [
            "nothing matches"            => ["test", "test"],
            "nothing matches 2"          => [["test", "hello"], "test, hello"],
            "is no array"                => ["PT_OPEN", "offenes Verfahren"],
            "PT_OPEN"                    => [["PT_OPEN"], "offenes Verfahren"],
            "PT_COMPETITIVE_DIALOG"      => [["PT_COMPETITIVE_DIALOG"], "wettbewerblicher Dialog"],
            "PT_COMPETITIVE_NEGOTIATION" => [["PT_COMPETITIVE_NEGOTIATION"], "Verhandlungsverfahren"],
            "PT_INNOVATION_PARTNERSHIP"  => [["PT_INNOVATION_PARTNERSHIP"], "Innovationspartnerschaft"],
            "PT_SPECIAL_SERVICE"         => [["PT_SPECIAL_SERVICE"], "Besonderer Dienstleistungsauftrag"],
            "DPS"                        => [["DPS"], "dynamisches Beschaffungssystem"],
            "PT_DIRECT"                  => [["PT_DIRECT"], "Direktvergabe"],
            "PT_RESTRICTED + PT_WITH_PRIOR_NOTICE" => [
                ["PT_RESTRICTED","PT_WITH_PRIOR_NOTICE"], 
                "nicht offenes Verfahren mit vorheriger Bekanntmachung"
            ],
            "PT_RESTRICTED + PT_WITHOUT_PRIOR_NOTICE" => [
                ["PT_RESTRICTED","PT_WITHOUT_PRIOR_NOTICE"], 
                "nicht offenes Verfahren ohne vorheriger Bekanntmachung"
            ],
            "PT_COMPETITIVE_NEGOTIATION + PT_WITH_PRIOR_NOTICE" => [
                ["PT_COMPETITIVE_NEGOTIATION","PT_WITH_PRIOR_NOTICE"], 
                "Verhandlungsverfahren mit vorheriger Bekanntmachung"
            ],
            "PT_COMPETITIVE_NEGOTIATION + PT_WITHOUT_PRIOR_NOTICE" => [
                ["PT_COMPETITIVE_NEGOTIATION","PT_WITHOUT_PRIOR_NOTICE"], 
                "Verhandlungsverfahren ohne vorheriger Bekanntmachung"
            ],
            "PT_SPECIAL_SERVICE + PT_WITH_PRIOR_NOTICE" => [
                ["PT_SPECIAL_SERVICE","PT_WITH_PRIOR_NOTICE"], 
                "besonderer Dienstleistungsauftrag mit vorheriger Bekanntmachung"
            ],
            "PT_SPECIAL_SERVICE + PT_WITHOUT_PRIOR_NOTICE" => [
                ["PT_SPECIAL_SERVICE","PT_WITHOUT_PRIOR_NOTICE"], 
                "besonderer Dienstleistungsauftrag ohne vorheriger Bekanntmachung"
            ],
            "PT_DIRECT + PT_WITH_PRIOR_NOTICE" => [
                ["PT_DIRECT","PT_WITH_PRIOR_NOTICE"], 
                "Direktvergabe mit vorheriger Bekanntmachung"
            ],
            "PT_OPEN + PT_IDEA" => [
                ["PT_OPEN","PT_IDEA"], 
                "offener Ideenwettbewerb"
            ],
            "PT_OPEN + PT_IMPLEMENTATION" => [
                ["PT_OPEN","PT_IMPLEMENTATION"], 
                "offener Realisierungswettbewerb"
            ],
            "PT_RESTRICTED + PT_IDEA" => [
                ["PT_RESTRICTED","PT_IDEA"], 
                "nicht offener Ideenwettbewerb"
            ],
            "PT_RESTRICTED + PT_IMPLEMENTATION" => [
                ["PT_RESTRICTED","PT_IMPLEMENTATION"], 
                "nicht offener Realisierungswettbewerb"
            ],
            "PT_INVITED + PT_IDEA" => [
                ["PT_INVITED","PT_IDEA"], 
                "geladener Ideenwettbewerb"
            ],
            "PT_INVITED + PT_IMPLEMENTATION" => [
                ["PT_INVITED","PT_IMPLEMENTATION"], 
                "geladener Realisierungswettbewerb"
            ],
        ];
    }
}
