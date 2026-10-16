<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Process;
use App\Models\Organization;
use App\Models\ScraperKerndaten;
use App\Models\ScraperQuelle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Fixtures\Eforms;
use Tests\Fixtures\Kerndaten;

/**
 * Tests of the processing of kerndaten records into datasets (fif:process).
 *
 * The scraper database is not used: the job gets the records in memory (see processor()), and the write-back
 * into the kerndaten record is recorded instead of saved (see makeRecord()).
 */
class ProcessTest extends JobTestCase
{
    // quellen.id of the test records, stored in metasets.quellen_id
    const QUELLE_ID = 999;

    // kerndaten ids of the test records, far above the ids of real records
    private $nextRecordId = 900000001;

    protected function setUp(): void
    {
        parent::setUp();

        // the codes used in tests/Fixtures/kerndaten
        $this->createCpv('99100000');
        $this->createCpv('99110000');
        $this->createCpv('99120000');
        DB::table('nuts')->insertOrIgnore(['code' => 'ZZ999', 'name' => 'Testregion', 'level' => 3, 'country_code' => 'ZZ']);
        DB::table('procedures')->insertOrIgnore(['code' => 'PT_OPEN', 'name' => 'offenes Verfahren']);
        DB::table('dataset_types')->insertOrIgnore([
            ['code' => 'KD_8_2_Z1', 'name' => 'KD_8_2_Z1', 'description' => 'Auftrag', 'end' => 1],
            ['code' => 'KD_8_2_Z2', 'name' => 'KD_8_2_Z2', 'description' => 'Wettbewerb', 'end' => 1],
            ['code' => 'KD_8_2_Z3', 'name' => 'KD_8_2_Z3', 'description' => 'Änderung', 'end' => 1],
        ]);
    }

    public function testCreatesDatasetFromAward()
    {
        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'));

        $this->processor([$record])->handle();

        // write-back into the kerndaten record
        $this->assertTrue($record->saved);
        $this->assertNotNull($record->app_processed_at);
        $this->assertNotNull($record->app_dataset_id);

        $metasetId = DB::table('datasets')->where('id', $record->app_dataset_id)->value('metaset_id');
        $this->assertDatabaseHas('metasets', ['id' => $metasetId, 'quellen_id' => self::QUELLE_ID, 'item_id' => $record->item_id]);
        $this->assertDatabaseHas('datasets', [
            'id'                       => $record->app_dataset_id,
            'scraper_kerndaten_id'     => $record->id,
            'version'                  => 1,
            'is_current_version'       => 1,
            'item_lastmod'             => '2026-03-01 10:15:00.000000',
            'type_code'                => 'KD_8_2_Z1',
            'cpv_code'                 => '99100000',
            'nuts_code'                => 'ZZ999',
            'url_document'             => 'https://vergabe.musterdorf.example/2026-42',
            'url_participation'        => 'https://vergabe.musterdorf.example/2026-42/teilnahme',
            'url_is_restricted'        => 0,
            'contract_type'            => 'WORKS',
            'title'                    => 'Sanierung der Ortsdurchfahrt Musterdorf',
            'description'              => 'Erneuerung der Fahrbahn\nund der Gehsteige',
            'date_start'               => '2026-04-01',
            'date_end'                 => '2026-10-31',
            'duration'                 => 210,
            'is_lot'                   => 1,
            'datetime_receipt_tenders' => '2026-02-15 12:00:00',
            'is_framework'             => 1,
            'date_conclusion_contract' => '2026-03-01',
            'nb_tenders_received'      => 4,
            'nb_sme_tender'            => 3,
            'val_total'                => 123456789,
            'justification'            => 'Keine Begründung erforderlich',
            'date_first_publication'   => '2026-01-10',
            'datetime_last_change'     => '2026-03-02 09:30:00',
            'deadline_standstill'      => '2026-03-12',
            'procedure_description'    => 'Offenes Verfahren im Oberschwellenbereich',
            'threshold'                => 1,
            'url_revocation'           => 'https://vergabe.musterdorf.example/2026-42/widerruf',
            'url_revocation_statement' => 'https://vergabe.musterdorf.example/2026-42/widerrufserklaerung',
            'rd_notification'          => 1,
        ]);
    }

    public function testStoresCpvsAndProcedures()
    {
        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'));

        $this->processor([$record])->handle();

        $this->assertEquals(
            ['99100000' => 1, '99110000' => 0, '99120000' => 0],
            DB::table('cpv_dataset')->where('dataset_id', $record->app_dataset_id)->orderBy('cpv_code')->pluck('main', 'cpv_code')->all()
        );
        $this->assertEquals(
            ['PT_OPEN'],
            DB::table('dataset_procedure')->where('dataset_id', $record->app_dataset_id)->pluck('procedure_code')->all()
        );
    }

    public function testStoresOfferorsWithTheirOrganizations()
    {
        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'));

        $this->processor([$record])->handle();

        $this->assertDatabaseHas('offerors', [
            'dataset_id'       => $record->app_dataset_id,
            'is_extra'         => 0,
            'name'             => 'Testgemeinde Musterdorf',
            'national_id'      => '99999',
            'phone'            => '+43 1 2345678',
            'email'            => 'vergabe@musterdorf.example',
            'contact'          => 'Maria Muster',
            'domain'           => 'federal',
            // the reference number of the main offeror is part of OBJECT_CONTRACT
            'reference_number' => 'MD-2026-42',
            'organization_id'  => $this->organizationId('gkz', '99999'),
        ]);
        $this->assertDatabaseHas('offerors', [
            'dataset_id'       => $record->app_dataset_id,
            'is_extra'         => 1,
            'name'             => 'Testverband Musterregion',
            'national_id'      => '999998z',
            'reference_number' => 'MR-2026-7',
            'organization_id'  => $this->organizationId('fn', 'FN999998z'),
        ]);
        $this->assertDatabaseHas('organizations', ['gkz' => '99999', 'name' => 'Testgemeinde Musterdorf', 'is_identified' => 1]);
    }

    /**
     * The first contractor is the main contractor, the others are extra.
     */
    public function testStoresContractorsWithTheirOrganizations()
    {
        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'));

        $this->processor([$record])->handle();

        $this->assertDatabaseHas('contractors', [
            'dataset_id'      => $record->app_dataset_id,
            'is_extra'        => 0,
            'name'            => 'Muster Bau GmbH',
            'national_id'     => '999997y',
            'organization_id' => $this->organizationId('fn', 'FN999997y'),
        ]);
        $this->assertDatabaseHas('contractors', [
            'dataset_id'      => $record->app_dataset_id,
            'is_extra'        => 1,
            'name'            => 'Beispiel Asphalt KG',
            'national_id'     => '9999999999999',
            'organization_id' => $this->organizationId('gln', '9999999999999'),
        ]);
    }

    public function testCreatesDatasetFromContractModification()
    {
        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z3'));

        $this->processor([$record])->handle();

        $this->assertDatabaseHas('datasets', [
            'id'                 => $record->app_dataset_id,
            'type_code'          => 'KD_8_2_Z3',
            'cpv_code'           => '99100000',
            'title'              => 'Sanierung der Ortsdurchfahrt Musterdorf, Zusatzleistung',
            // OBJECT_CONTRACT has no SHORT_DESCR, so the one of the modification is used
            'description'        => 'Zusätzliche Lärmschutzwand',
            'val_total_before'   => 123456789,
            'val_total_after'    => 15000000,
            'info_modifications' => 'Auflage der Behörde',
        ]);
        $this->assertEquals(
            ['99100000' => 1, '99120000' => 0],
            DB::table('cpv_dataset')->where('dataset_id', $record->app_dataset_id)->orderBy('cpv_code')->pluck('main', 'cpv_code')->all()
        );
        $this->assertDatabaseHas('contractors', [
            'dataset_id'      => $record->app_dataset_id,
            'is_extra'        => 0,
            'name'            => 'Muster Bau GmbH',
            'organization_id' => $this->organizationId('fn', 'FN999997y'),
        ]);
    }

    /**
     * TODO: bug — Process::process() always copies the CPV of MODIFICATIONS_CONTRACT into the dataset. If the
     * modification has no CPV_MAIN, the CPV of OBJECT_CONTRACT is overwritten with null (validate() only catches
     * differing codes). Only use the CPV of the modification if it is set, then remove markTestIncomplete() here.
     */
    public function testModificationWithoutCpvKeepsCpvOfObjectContract()
    {
        $this->markTestIncomplete('Process::process() overwrites the CPV with null for a modification without CPV_MAIN.');

        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z3', ['<CPV_MAIN><CPV_CODE CODE="99100000"/></CPV_MAIN>' => '']));

        $this->processor([$record])->handle();

        $this->assertDatabaseHas('datasets', ['id' => $record->app_dataset_id, 'cpv_code' => '99100000']);
    }

    /**
     * Design contest: the prize is stored as val_total, the winners as contractors.
     */
    public function testCreatesDatasetFromDesignContestResult()
    {
        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z2'));

        $this->processor([$record])->handle();

        $this->assertDatabaseHas('datasets', [
            'id'                  => $record->app_dataset_id,
            'type_code'           => 'KD_8_2_Z2',
            'title'               => 'Architekturwettbewerb Gemeindezentrum Musterdorf',
            'nb_participants'     => 12,
            'nb_participants_sme' => 9,
            'val_total'           => 2500000,
        ]);
        $this->assertDatabaseHas('contractors', [
            'dataset_id'      => $record->app_dataset_id,
            'is_extra'        => 0,
            'name'            => 'Musterarchitekten ZT GmbH',
            'organization_id' => $this->organizationId('fn', 'FN999996x'),
        ]);
    }

    /**
     * A newer record of the same item (quelle and item_id) becomes the current version in the same metaset, even if
     * the previous version was disabled in the meantime.
     */
    public function testNewVersionReplacesCurrentVersion()
    {
        $version1 = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'));
        $version2 = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'), ['item_id' => $version1->item_id, 'version' => 2]);

        $this->processor([$version1])->handle();
        DB::table('datasets')->where('id', $version1->app_dataset_id)->update(['disabled_at' => now()]);
        $this->processor([$version2])->handle();

        $this->assertDatabaseHas('datasets', ['id' => $version1->app_dataset_id, 'is_current_version' => 0]);
        $this->assertDatabaseHas('datasets', ['id' => $version2->app_dataset_id, 'is_current_version' => 1, 'version' => 2]);
        $this->assertSame(
            1,
            DB::table('metasets')->where('quellen_id', self::QUELLE_ID)->where('item_id', $version1->item_id)->count()
        );
        $this->assertEquals(
            DB::table('datasets')->where('id', $version1->app_dataset_id)->value('metaset_id'),
            DB::table('datasets')->where('id', $version2->app_dataset_id)->value('metaset_id')
        );
    }

    /**
     * @dataProvider invalidRecordProvider
     */
    public function testInvalidRecordIsSkipped($type, array $replacements)
    {
        $record = $this->makeRecord(Kerndaten::xml($type, $replacements));

        $this->processor([$record])->handle();

        $this->assertFalse($record->saved);
        $this->assertNull($record->app_dataset_id);
        $this->assertDatabaseMissing('metasets', ['item_id' => $record->item_id]);
    }

    /**
     * Unknown codes don't stop the processing, they are removed.
     */
    public function testUnknownCodesAreRemoved()
    {
        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1', [
            '<CPV_CODE CODE="99100000"/>' => '<CPV_CODE CODE="99999998"/>',
            '<CPV_CODE CODE="99120000"/>' => '<CPV_CODE CODE="99999997"/>',
            '<NUTS CODE="ZZ999"/>'        => '<NUTS CODE="ZZ000"/>',
        ]));

        $this->processor([$record])->handle();

        $this->assertDatabaseHas('datasets', ['id' => $record->app_dataset_id, 'cpv_code' => null, 'nuts_code' => null]);
        $this->assertEquals(
            ['99110000'],
            DB::table('cpv_dataset')->where('dataset_id', $record->app_dataset_id)->pluck('cpv_code')->all()
        );
    }

    public function testOfferorIsMatchedToExistingOrganizationByNationalId()
    {
        $organization = $this->createOrganization(['gkz' => '99999', 'is_identified' => 1]);
        $record       = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'));

        $this->processor([$record])->handle();

        $this->assertDatabaseHas('offerors', [
            'dataset_id'      => $record->app_dataset_id,
            'is_extra'        => 0,
            'organization_id' => $organization->id,
        ]);
        $this->assertSame(1, Organization::where('gkz', '99999')->count());
    }

    /**
     * A valid national ID of unknown type is kept as it is in organizations.ukn.
     */
    public function testNationalIdOfUnknownTypeIsStoredAsUkn()
    {
        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1', [
            '<NATIONALID>99999</NATIONALID>' => '<NATIONALID>ATU99999999</NATIONALID>',
        ]));

        $this->processor([$record])->handle();

        $this->assertDatabaseHas('offerors', [
            'dataset_id'      => $record->app_dataset_id,
            'is_extra'        => 0,
            'organization_id' => $this->organizationId('ukn', 'ATU99999999'),
        ]);
        $this->assertDatabaseHas('organizations', ['ukn' => 'ATU99999999', 'name' => 'Testgemeinde Musterdorf', 'is_identified' => 0]);
    }

    /**
     * Without national ID the organization is matched by name. If several organizations have that name, the one
     * without any ID is used.
     */
    public function testOfferorWithoutNationalIdIsMatchedByName()
    {
        $this->createOrganization(['name' => 'Testgemeinde Musterdorf', 'gkz' => '99998', 'is_identified' => 1]);
        $generic = $this->createOrganization(['name' => 'Testgemeinde Musterdorf']);
        $record  = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1', ['<NATIONALID>99999</NATIONALID>' => '']));

        $this->processor([$record])->handle();

        $this->assertDatabaseHas('offerors', [
            'dataset_id'      => $record->app_dataset_id,
            'is_extra'        => 0,
            'national_id'     => null,
            'organization_id' => $generic->id,
        ]);
    }

    /**
     * An organization is linked only once per dataset, even if it is listed as contractor several times.
     */
    public function testSameContractorIsStoredOnce()
    {
        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1', [
            '<NATIONALID>9999999999999</NATIONALID>' => '<NATIONALID>999997y</NATIONALID>',
        ]));

        $this->processor([$record])->handle();

        $this->assertSame(1, DB::table('contractors')->where('dataset_id', $record->app_dataset_id)->count());
    }

    public function testMalformedRecordIsSkipped()
    {
        $malformed = $this->makeRecord('<KD_8_2_Z1><CONTRACTING_BODY>');
        $record    = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'));

        $this->processor([$malformed, $record])->handle();

        $this->assertFalse($malformed->saved);
        $this->assertTrue($record->saved);
    }

    /**
     * If the write-back into the kerndaten record fails, nothing written for the record is kept.
     */
    public function testWritesAreRolledBackIfWriteBackFails()
    {
        $record            = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'));
        $record->saveFails = true;

        $this->processor([$record])->handle();

        $this->assertDatabaseMissing('metasets', ['item_id' => $record->item_id]);
        $this->assertSame(0, Organization::where('gkz', '99999')->count());
    }

    /**
     * Without national ID, a single organization with the same name is used, whatever identifiers it has.
     */
    public function testOfferorWithoutNationalIdIsMatchedToOnlyOrganizationWithThatName()
    {
        $organization = $this->createOrganization(['name' => 'Testgemeinde Musterdorf', 'gkz' => '99998', 'is_identified' => 1]);
        $record       = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1', ['<NATIONALID>99999</NATIONALID>' => '']));

        $this->processor([$record])->handle();

        $this->assertDatabaseHas('offerors', [
            'dataset_id'      => $record->app_dataset_id,
            'is_extra'        => 0,
            'organization_id' => $organization->id,
        ]);
    }

    /**
     * Without national ID and without organization of that name, a new organization without identifiers is created.
     */
    public function testOfferorWithoutNationalIdAndUnknownNameGetsNewOrganization()
    {
        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1', ['<NATIONALID>99999</NATIONALID>' => '']));

        $this->processor([$record])->handle();

        $this->assertDatabaseHas('offerors', [
            'dataset_id'      => $record->app_dataset_id,
            'is_extra'        => 0,
            'organization_id' => $this->organizationId('name', 'Testgemeinde Musterdorf'),
        ]);
        $this->assertDatabaseHas('organizations', [
            'name'          => 'Testgemeinde Musterdorf',
            'fn'            => null,
            'gln'           => null,
            'gkz'           => null,
            'ukn'           => null,
            'is_identified' => 0,
        ]);
    }

    public function testKerndatenDatasetsAreMarkedAsKerndaten()
    {
        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'));

        $this->processor([$record])->handle();

        $this->assertDatabaseHas('datasets', [
            'id'                  => $record->app_dataset_id,
            'source_format'       => 'kerndaten',
            'source_subtype'      => null,
            'further_information' => null,
        ]);
    }

    /**
     * eForms notices are stored in the same tables as Kerndaten, marked with their source format.
     */
    public function testCreatesDatasetFromEformsAward()
    {
        DB::table('procedures')->insertOrIgnore(['code' => 'OTH_SINGLE', 'name' => 'OTH_SINGLE']);
        $record = $this->makeRecord(Eforms::xml('can-standard'));

        $this->processor([$record])->handle();

        $this->assertTrue($record->saved);
        $this->assertDatabaseHas('datasets', [
            'id'                       => $record->app_dataset_id,
            'source_format'            => 'eforms',
            'source_subtype'           => 'E4',
            'type_code'                => 'KD_8_2_Z1',
            'title'                    => 'Sanierung der Ortsdurchfahrt Musterdorf',
            'cpv_code'                 => '99100000',
            'nuts_code'                => 'ZZ999',
            'contract_type'            => 'WORKS',
            'is_lot'                   => 1,
            'threshold'                => 0,
            'nb_tenders_received'      => 4,
            'val_total'                => 123456789,
            'date_conclusion_contract' => '2026-03-01 00:00:00',
        ]);
        $this->assertEquals(
            ['OTH_SINGLE'],
            DB::table('dataset_procedure')->where('dataset_id', $record->app_dataset_id)->pluck('procedure_code')->all()
        );
        $this->assertEquals(
            ['Testgemeinde Musterdorf' => 0, 'Testverband Musterregion' => 1],
            DB::table('offerors')->where('dataset_id', $record->app_dataset_id)->pluck('is_extra', 'name')->all()
        );
        $this->assertEquals(
            ['Muster Bau GmbH' => 0, 'Beispiel Asphalt KG' => 1],
            DB::table('contractors')->where('dataset_id', $record->app_dataset_id)->pluck('is_extra', 'name')->all()
        );

        // the unmapped fields, e.g. the values of the tenders
        $info = json_decode(DB::table('datasets')->where('id', $record->app_dataset_id)->value('further_information'), true);
        $this->assertSame('1.13', $info['sdk']);
        $this->assertContains('Angebot: TEN-0001', array_column($info['groups'], 'title'));
    }

    public function testRecordOfUnknownFormatIsSkipped()
    {
        $unknown = $this->makeRecord('<Bekanntmachung><Titel>Kein bekanntes Format</Titel></Bekanntmachung>');
        $record  = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'));

        $this->processor([$unknown, $record])->handle();

        $this->assertFalse($unknown->saved);
        $this->assertTrue($record->saved);
    }

    /**
     * For a record without CONTRACTING_BODY DOMKerndaten fails with a TypeError, which must not stop the run.
     */
    public function testRecordWithoutContractingBodyIsSkipped()
    {
        $incomplete = $this->makeRecord('<KD_8_2_Z1><OBJECT_CONTRACT><TITLE><P>Ohne Auftraggeber</P></TITLE></OBJECT_CONTRACT></KD_8_2_Z1>');
        $record     = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'));

        $this->processor([$incomplete, $record])->handle();

        $this->assertFalse($incomplete->saved);
        $this->assertTrue($record->saved);
    }

    /**
     * TODO: bug — Process::handle() moves on by the number of records found instead of the block size. If ids of a
     * block have no record (e.g. a mistyped --kerndaten_id), the next block overlaps the previous one, so records are
     * processed twice (and stored as a new version), and a block without any record ends the loop. Advance $index by
     * $blockSize, then remove markTestIncomplete() here.
     */
    public function testUnknownIdsDoNotStopProcessing()
    {
        $this->markTestIncomplete('Process::handle() advances by count($records) instead of $blockSize.');

        $record = $this->makeRecord(Kerndaten::xml('KD_8_2_Z1'));
        // a whole block (50 ids) without records before the existing one
        $ids = array_merge(range(800000001, 800000050), [$record->id]);

        $this->processor([$record], $ids)->handle();

        $this->assertTrue($record->saved);
    }

    public function invalidRecordProvider()
    {
        return [
            'both document flags' => ['KD_8_2_Z1', [
                '<DOCUMENT_FULL/>' => '<DOCUMENT_FULL/><DOCUMENT_RESTRICTED/>',
            ]],
            'both lot flags' => ['KD_8_2_Z1', [
                '<LOT_DIVISION/>' => '<LOT_DIVISION/><NO_LOT_DIVISION/>',
            ]],
            'both threshold flags' => ['KD_8_2_Z1', [
                '<ABOVETHRESHOLD/>' => '<ABOVETHRESHOLD/><BELOWTHRESHOLD/>',
            ]],
            'NB_SME_CONTRACTOR in award and in additional core data' => ['KD_8_2_Z1', [
                '<NB_SME_TENDER>3</NB_SME_TENDER>' => '<NB_SME_TENDER>3</NB_SME_TENDER><NB_SME_CONTRACTOR/>',
                '<RD_NOTIFICATION/>'               => '<RD_NOTIFICATION/><NB_SME_CONTRACTOR>2</NB_SME_CONTRACTOR>',
            ]],
            'modification with other cpv than object contract' => ['KD_8_2_Z3', [
                '<CPV_MAIN><CPV_CODE CODE="99100000"/></CPV_MAIN>' => '<CPV_MAIN><CPV_CODE CODE="99120000"/></CPV_MAIN>',
            ]],
            'modification with both reasons' => ['KD_8_2_Z3', [
                '</ADDITIONAL_NEED>' => '</ADDITIONAL_NEED><UNFORESEEN_CIRCUMSTANCE><P>Hochwasser</P></UNFORESEEN_CIRCUMSTANCE>',
            ]],
            'modification with negative value' => ['KD_8_2_Z3', [
                '<VAL_TOTAL_AFTER CURRENCY="EUR">150000</VAL_TOTAL_AFTER>' => '<VAL_TOTAL_AFTER CURRENCY="EUR">-150000</VAL_TOTAL_AFTER>',
            ]],
        ];
    }

    /**
     * The job with records given in memory instead of loaded from the scraper database.
     *
     * @param ScraperKerndaten[] $records
     * @param array|null $ids the ids passed to the job, by default the ids of the records
     */
    private function processor(array $records, array $ids = null)
    {
        $ids = $ids ?: array_map(function ($record) {
            return $record->id;
        }, $records);

        return new class($ids, $records) extends Process {
            private $records;

            public function __construct(array $ids, array $records)
            {
                parent::__construct($ids);

                $this->records = collect($records);
            }

            protected function getRawKerndatenRecords($offset = null, $limit = null)
            {
                // the same lookup as Process::getRawKerndatenRecords(), on the given records
                return $this->records->whereIn('id', array_slice($this->recordIds, $offset, $limit))->values();
            }
        };
    }

    /**
     * A kerndaten record as the scraper stores it. save() only records that it was called, or fails if $saveFails is set.
     */
    private function makeRecord($xml, array $attributes = [])
    {
        $record = new class extends ScraperKerndaten {
            public $saved = false;

            public $saveFails = false;

            public function save(array $options = [])
            {
                if ($this->saveFails) {
                    throw new \RuntimeException('Scraper-Datenbank nicht erreichbar');
                }

                return $this->saved = true;
            }
        };

        $record->forceFill(array_merge([
            'id'           => $this->nextRecordId++,
            'quelle'       => 'test',
            'item_id'      => 'process-test-'.Str::random(8),
            'item_lastmod' => '2026-03-01 10:15:00.000000',
            'version'      => 1,
            'xml'          => $xml,
        ], $attributes));
        $record->setRelation('rel_quelle', (new ScraperQuelle())->forceFill(['id' => self::QUELLE_ID, 'alias' => 'test']));

        return $record;
    }

    /**
     * The id of the one organization with the given identifier.
     */
    private function organizationId($column, $value)
    {
        $ids = Organization::where($column, $value)->pluck('id');
        $this->assertCount(1, $ids, "Expected exactly one organization with $column = $value.");

        return $ids->first();
    }
}
