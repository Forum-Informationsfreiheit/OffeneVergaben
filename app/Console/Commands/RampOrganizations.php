<?php

namespace App\Console\Commands;

use App\Contractor;
use App\NationalIdParser;
use App\Offeror;
use App\Organization;
use Illuminate\Console\Command;

class RampOrganizations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:ramp-organizations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initially create the organizations table and link it with offerors.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Starting ramp job for creating organizations');

        $offerors = Offeror::all();
        $this->info('Found '.count($offerors).' offerors to process');

        foreach($offerors as $offeror) {
            $this->processOfferor($offeror);
        }

        $contractors = Contractor::all();
        $this->info('Found '.count($contractors).' contractors to process');

        foreach($contractors as $contractor) {
            $this->processContractor($contractor);
        }

        $this->info('Finished organizations ramp job.');
    }

    protected function processOfferor($offeror) {
        $parser = null;

        if ($offeror->national_id != null) {
            $parser = new NationalIdParser($offeror->national_id);
            $parser->parse();

            if ($parser->isValid() && !$parser->isUnknown()) {
                $this->processWithIdentifiedId($offeror, $parser);
                return;
            }
        }

        $this->processOther($offeror, $parser);
    }

    protected function processContractor($contractor) {
        $parser = null;

        if ($contractor->national_id != null) {
            $parser = new NationalIdParser($contractor->national_id);
            $parser->parse();

            if ($parser->isValid() && !$parser->isUnknown()) {
                $this->processContractorWithIdentifiedId($contractor, $parser);
                return;
            }
        }

        $this->processOtherContractor($contractor, $parser);
    }

    /**
     * @param \App\Offeror $offeror
     * @param \App\NationalIdParser $parser
     */
    protected function processWithIdentifiedId($offeror, $parser) {
        $formatted = $parser->getFormattedId();
        $type = strtolower($parser->getType());     // transform to lower case so it can be used in where clause

        $existing = Organization::where($type,$formatted)->first();

        if ($existing) {
            // alright, just update the offeror with organization id
            $offeror->organization_id = $existing->id;
            $offeror->save();
        } else {
            $org = Organization::createFromType($formatted, $type, $offeror->name);

            // and store the reference in the offeror record
            $offeror->organization_id = $org->id;
            $offeror->save();
        }
    }

    /**
     * @param $contractor \App\Contractor
     * @param $parser \App\NationalIdParser
     */
    protected function processContractorWithIdentifiedId($contractor, $parser) {
        $formatted = $parser->getFormattedId();
        $type = strtolower($parser->getType());     // transform to lower case so it can be used in where clause

        $existing = Organization::where($type,$formatted)->first();

        if ($existing) {
            // alright, just update the offeror with organization id
            $contractor->organization_id = $existing->id;
            $contractor->save();
        } else {
            $org = Organization::createFromType($formatted, $type, $contractor->name);

            // and store the reference in the offeror record
            $contractor->organization_id = $org->id;
            $contractor->save();
        }
    }

    /**
     * @param $offeror
     * @param $parser \App\NationalIdParser
     */
    protected function processOther($offeror, $parser) {
        if ($parser && $parser->isValid() && $parser->isUnknown()) {
            $existing = Organization::where('ukn',$offeror->national_id)->first();

            if ($existing) {
                $offeror->organization_id = $existing->id;
                $offeror->save();
            } else {
                // new organization!
                $org = Organization::createFromUnknownType($offeror->national_id, $offeror->name);

                // and store the reference in the offeror record
                $offeror->organization_id = $org->id;
                $offeror->save();
            }

            return;
        }

        // reaching this point means one of two things
        // 1. no national_id was provided (results in null in database)
        // 2. the provided national_id is deemed invalid, (probably because it is shorter than 5 chars)

        // lets do a name lookup just because we might get lucky
        $existing = Organization::where('name',$offeror->name)->get();

        if ($existing->count() >= 1) {
            if ($existing->count() === 1) {
                $existing = $existing->first();
            } else {
                // try to match it to the most generic one
                $existing = Organization::where('name',$offeror->name)
                    ->whereNull('fn')
                    ->whereNull('gln')
                    ->whereNull('gkz')
                    ->whereNull('ukn')
                    ->where('is_identified',0)
                    ->get();

                if ($existing->count() === 1) {
                    $existing = $existing->first();
                } else {
                    $existing = null;
                }
            }
        } else {
            $existing = null;
        }

        if ($existing) {
            $offeror->organization_id = $existing->id;
            $offeror->save();
        } else {
            // new organization!
            $org = Organization::createGeneric($offeror->name);

            // and store the reference in the offeror record
            $offeror->organization_id = $org->id;
            $offeror->save();
        }
    }

    protected function processOtherContractor($contractor, $parser) {
        if ($parser && $parser->isValid() && $parser->isUnknown()) {
            $existing = Organization::where('ukn',$contractor->national_id)->first();

            if ($existing) {
                $contractor->organization_id = $existing->id;
                $contractor->save();
            } else {
                // new organization!
                $org = Organization::createFromUnknownType($contractor->id, $contractor->name);

                // and store the reference in the offeror record
                $contractor->organization_id = $org->id;
                $contractor->save();
            }

            return;
        }

        // reaching this point means one of two things
        // 1. no national_id was provided (results in null in database)
        // 2. the provided national_id is deemed invalid, (probably because it is shorter than 5 chars)

        // lets do a name lookup just because we might get lucky
        $existing = Organization::where('name',$contractor->name)->get();

        if ($existing->count() >= 1) {
            if ($existing->count() === 1) {
                $existing = $existing->first();
            } else {
                // try to match it to the most generic one
                $existing = Organization::where('name',$contractor->name)
                    ->whereNull('fn')
                    ->whereNull('gln')
                    ->whereNull('gkz')
                    ->whereNull('ukn')
                    ->where('is_identified',0)
                    ->get();

                if ($existing->count() === 1) {
                    $existing = $existing->first();
                } else {
                    $existing = null;
                }
            }
        } else {
            $existing = null;
        }

        if ($existing) {
            $contractor->organization_id = $existing->id;
            $contractor->save();
        } else {
            // new organization!
            $org = Organization::createGeneric($contractor->name);

            // and store the reference in the offeror record
            $contractor->organization_id = $org->id;
            $contractor->save();
        }
    }
}
