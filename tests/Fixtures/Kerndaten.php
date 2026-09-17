<?php

namespace Tests\Fixtures;

/**
 * Kerndaten XML records in tests/Fixtures/kerndaten, named after their root element (the dataset type):
 * KD_8_2_Z1 contract award, KD_8_2_Z2 design contest result, KD_8_2_Z3 contract modification.
 *
 * The fixtures use CPV codes 99100000, 99110000, 99120000 and NUTS code ZZ999, which don't exist in real data,
 * and national IDs starting with 9999.
 */
class Kerndaten
{
    /**
     * @param string $type file name without .xml
     * @param array $replacements search => replace. Every search string has to occur exactly once in the file,
     *                            so a replacement can't silently do nothing or change the wrong element.
     * @return string
     */
    public static function xml($type, array $replacements = [])
    {
        $xml = file_get_contents(__DIR__.'/kerndaten/'.$type.'.xml');

        foreach (array_keys($replacements) as $search) {
            if (substr_count($xml, $search) !== 1) {
                throw new \InvalidArgumentException("'$search' does not occur exactly once in kerndaten/$type.xml.");
            }
        }

        return strtr($xml, $replacements);
    }
}
