<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    /**
     * The national id is the first of GLN, FN, GKZ and UKN that is set.
     */
    #[DataProvider('nationalIdProvider')]
    public function testNationalIdAndLabel(array $identifiers, $nationalId, $label)
    {
        $organization = (new Organization())->forceFill($identifiers);

        $this->assertSame($nationalId, $organization->national_id);
        $this->assertSame($label, $organization->national_id_label);
    }

    public static function nationalIdProvider()
    {
        return [
            'all identifiers' => [
                ['gln' => '9008390000009', 'fn' => 'FN123456a', 'gkz' => '90001', 'ukn' => 'ATU12345678'],
                '9008390000009',
                'GLN',
            ],
            'FN and GKZ'      => [['fn' => 'FN123456a', 'gkz' => '90001'], 'FN123456a', 'Firmenbuchnr.'],
            'GKZ'             => [['gkz' => '90001'], '90001', 'Gemeindekennzahl'],
            'unknown type'    => [['ukn' => 'ATU12345678'], 'ATU12345678', '?'],
            'no identifier'   => [[], '?', ''],
        ];
    }

    public function testIdentifiers()
    {
        $organization = (new Organization())->forceFill([
            'gln' => '9008390000009',
            'fn'  => 'FN123456a',
            'gkz' => '90001',
            'ukn' => 'ATU12345678',
        ]);

        $this->assertSame(
            ['gln' => '9008390000009', 'fn' => 'FN123456a', 'gkz' => '90001', 'ukn' => 'ATU12345678'],
            $organization->identifiers
        );
        $this->assertSame([], (new Organization())->identifiers);
    }
}
