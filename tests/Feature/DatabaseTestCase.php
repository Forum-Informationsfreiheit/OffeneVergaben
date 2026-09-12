<?php

namespace Tests\Feature;

use App\CPV;
use App\Organization;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Base class for feature tests that use the app database: the public routes (PublicTestCase) and the jobs.
 *
 * Like the admin tests, every test runs inside a transaction against the configured MySQL database.
 * Locally that is the dev database, which may hold imported data: tests create their own records with
 * random names and don't assume that they are the only records.
 */
abstract class DatabaseTestCase extends TestCase
{
    use DatabaseTransactions;

    // dataset type with dataset_types.end = 1 ("Auftrag"), so test datasets count in the organization stats
    const DATASET_TYPE = 'TEST_AUFTRAG';

    // in the future, so test datasets are on the first page of lists sorted by item_lastmod desc.
    // item_lastmod is a MySQL TIMESTAMP, which ends at 2038-01-19 03:14:07 UTC.
    const ITEM_LASTMOD = '2037-01-01 00:00:00.000000';

    protected function setUp(): void
    {
        parent::setUp();

        // the reference tables have no seeders, but users.role_id and datasets.type_code reference them
        DB::table('roles')->insertOrIgnore([
            ['id' => Role::REGISTERED, 'name' => 'Registered'],
            ['id' => Role::SUBSCRIBER, 'name' => 'Subscriber'],
            ['id' => Role::EDITOR,     'name' => 'Editor'],
            ['id' => Role::ADMIN,      'name' => 'Admin'],
        ]);
        DB::table('dataset_types')->insertOrIgnore([
            'code'        => self::DATASET_TYPE,
            'name'        => 'Test',
            'description' => 'Testauftrag',
            'end'         => 1,
        ]);

        // The user with ID 1 passes every gate (Gate::before). Make sure it exists, so users created
        // by the tests never get this ID and are checked like regular users.
        if (!User::find(1)) {
            User::forceCreate([
                'id'       => 1,
                'name'     => 'Root',
                'email'    => 'root@example.test',
                'password' => 'root',
                'role_id'  => Role::ADMIN,
            ]);
        }
    }

    protected function createUser($roleId, array $attributes = [])
    {
        return factory(User::class)->create(array_merge(['role_id' => $roleId], $attributes));
    }

    protected function createOrganization(array $attributes = [])
    {
        return Organization::forceCreate(array_merge([
            'name' => 'Testorganisation '.Str::random(10),
        ], $attributes));
    }

    /**
     * Use codes starting with 99 for test CPVs: no real CPV code does, so they can't collide with imported ones.
     */
    protected function createCpv($code, $name = null)
    {
        $trimmedCode = rtrim($code, '0');

        DB::table('cpvs')->insert([
            'code'          => $code,
            'trimmed_code'  => strlen($trimmedCode) < 2 ? str_pad($trimmedCode, 2, '0') : $trimmedCode,
            'control_digit' => 0,
            'name'          => $name ?: 'Testbranche '.Str::random(10),
        ]);

        return CPV::find($code);
    }

    protected function createMetaset()
    {
        return DB::table('metasets')->insertGetId([
            'quellen_id' => 1,
            'item_id'    => 'public-test-'.Str::random(8),
        ]);
    }

    /**
     * Creates the current version of a dataset with its main offeror (a new organization if none is given).
     *
     * @return int the dataset id
     */
    protected function createDataset(array $attributes = [], Organization $offeror = null)
    {
        $id = DB::table('datasets')->insertGetId(array_merge([
            'metaset_id'         => $this->createMetaset(),
            'version'            => 1,
            'is_current_version' => 1,
            'type_code'          => self::DATASET_TYPE,
            'title'              => 'Testauftrag '.Str::random(10),
            'item_lastmod'       => self::ITEM_LASTMOD,
        ], $attributes));

        $this->addOfferor($id, $offeror ?: $this->createOrganization());

        return $id;
    }

    protected function addOfferor($datasetId, Organization $organization, $isExtra = false)
    {
        DB::table('offerors')->insert([
            'dataset_id'      => $datasetId,
            'organization_id' => $organization->id,
            'name'            => $organization->name,
            'is_extra'        => (int) $isExtra,
        ]);
    }

    protected function addContractor($datasetId, Organization $organization, $isExtra = false)
    {
        DB::table('contractors')->insert([
            'dataset_id'      => $datasetId,
            'organization_id' => $organization->id,
            'name'            => $organization->name,
            'is_extra'        => (int) $isExtra,
        ]);
    }
}
