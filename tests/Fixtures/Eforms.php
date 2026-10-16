<?php

namespace Tests\Fixtures;

/**
 * eForms notices in tests/Fixtures/eforms, named after their notice type (cbc:NoticeTypeCode):
 * can-standard contract award (national, below threshold, 2 lots), cn-standard contract notice (2 lots),
 * can-modif contract modification.
 *
 * Same test data as the Kerndaten fixtures: CPV codes 99100000, 99110000, 99120000, NUTS code ZZ999,
 * national IDs starting with 9999.
 */
class Eforms extends Kerndaten
{
    const DIR = 'eforms';
}
