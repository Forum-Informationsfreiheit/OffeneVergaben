<?php

namespace App;

use App\Models\Organization;

/**
 * Matches offerors and contractors to organizations: by national id (FN, GLN, GKZ, or an unknown type), else by
 * name. Used by App\Jobs\Process and fif:update-from-xml.
 *
 * @package App
 */
class OrganizationMatcher
{
    /**
     * The matching organization, a new one if there is none.
     *
     * @param string|null $id  national id as given in the record
     * @param string|null $name
     * @return Organization|null
     */
    public function matchOrCreate($id, $name) {
        $existing = $this->find($id, $name);
        if ($existing) {
            return $existing;
        }

        $identifier = $this->identifier($id);

        if ($identifier && $identifier['type'] !== 'ukn') {
            return Organization::createFromType($identifier['id'], $identifier['type'], $name);
        }

        if ($identifier) {
            return Organization::createFromUnknownType($identifier['id'], $name);
        }

        return Organization::createGeneric($name);
    }

    /**
     * The matching existing organization, without creating one.
     *
     * @param string|null $id
     * @param string|null $name
     * @return Organization|null
     */
    public function find($id, $name) {
        $identifier = $this->identifier($id);

        // a national id decides, the name is not used then
        if ($identifier) {
            return Organization::where($identifier['type'], $identifier['id'])->first();
        }

        // by name: if there's exactly one organization with that name
        $existing = Organization::where('name', $name)->get();

        if ($existing->count() > 1) {
            // multiple organizations with the same name: the generic one without identifiers, if there's exactly one
            $existing = Organization::where('name', $name)
                ->whereNull('fn')
                ->whereNull('gln')
                ->whereNull('gkz')
                ->whereNull('ukn')
                ->where('is_identified', 0)
                ->get();
        }

        return $existing->count() === 1 ? $existing->first() : null;
    }

    /**
     * @param string|null $id
     * @return array|null  [ 'type' => fn|gln|gkz|ukn, 'id' => formatted id ], null without valid id
     */
    public function identifier($id) {
        if ($id === null) {
            return null;
        }

        $parser = new NationalIdParser($id);
        $parser->parse();

        if (!$parser->isValid()) {
            return null;
        }

        if ($parser->isUnknown()) {
            return ['type' => 'ukn', 'id' => $id];
        }

        // lower case, as the column names
        return ['type' => strtolower($parser->getType()), 'id' => $parser->getFormattedId()];
    }
}
