<?php

namespace App\Console\Commands;

use App\Dataset;
use App\NationalIdParser;
use App\Offeror;
use Illuminate\Console\Command;

class TestNationalIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:test-national-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A simple command for testing the NationalIdParser';

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
        // get all national ids currently in the system
        $nationalIds = Offeror::pluck('national_id');

        $this->getStats($nationalIds);
        //$this->checkGLN($nationalIds);
        //$this->getInvalidIds($nationalIds);
        //$this->getUnknownIds($nationalIds);
    }

    protected function checkGLN($nationalIds, $stopAfter = 500) {
        $this->info("Checking for GLNs");
        $idx = 0;

        foreach($nationalIds as $id) {
            $idx++;

            if (!$id) {
                continue;
            }

            if (strlen($id) >= 1) {
                if ($id[0] === '9') {
                    $cleaned = NationalIdParser::cleanId($id);

                    $isGLN = NationalIdParser::checkGLN($id);

                    $this->info('Original:Cleaned --> '.$id.':'.$cleaned.'    Is GLN? '.($isGLN ? 'Yes': 'No'));
                }
            }

            if ($idx >= $stopAfter) {
                $this->info('Stopped after '.$stopAfter);
                break;
            }
        }
    }

    protected function getInvalidIds($nationalIds) {
        $this->info("Checking invalid ids");

        foreach($nationalIds as $id) {
            if ($id === null) {
                continue;
            }

            $parser = new NationalIdParser($id);

            $parser->parse();

            if (!$parser->isValid()) {
                $this->info('Invalid ID:'.$id);
            }
        }
    }

    protected function getUnknownIds($nationalIds) {
        $this->info("Checking unknown ids");

        foreach($nationalIds as $id) {
            if ($id === null) {
                continue;
            }

            $parser = new NationalIdParser($id);

            $parser->parse();

            if ($parser->isValid() && $parser->isUnknown()) {
                $cleaned = NationalIdParser::cleanId($id);
                $this->info('Unknown ID:'.$id.'   Cleaned --> '.$cleaned);
            }
        }
    }

    protected function getStats($nationalIds) {
        $this->info("Getting stats for ".count($nationalIds)." national ids");

        $stats = [
            'GLN' => 0,
            'GKZ' => 0,
            'FN'  => 0,
            'null' => 0,
            'invalid' => 0,
            'unknown' => 0,
        ];

        // and let the parser run through it
        foreach($nationalIds as $id) {
            if ($id === null) {
                $stats['null']++;
                continue;
            }

            $parser = new NationalIdParser($id);

            $parser->parse();

            if ($parser->isValid()) {

                if ($parser->isUnknown()) {
                    $stats['unknown']++;
                } else {
                    if ($parser->isFN()) {
                        $stats['FN']++;
                    }

                    if ($parser->isGLN()) {
                        $stats['GLN']++;
                    }

                    if ($parser->isGKZ()) {
                        $stats['GKZ']++;
                    }
                }

            } else {
                $stats['invalid']++;
            }
        }

        $this->info("Ran through  ".count($nationalIds)." national ids");
        dump($stats);
    }
}
