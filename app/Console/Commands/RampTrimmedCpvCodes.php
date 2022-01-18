<?php

namespace App\Console\Commands;

use App\CPV;
use Illuminate\Console\Command;

class RampTrimmedCpvCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:ramp-trimmed-cpv-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill the trimmed_code column in cpvs table.';

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
        $cpvs = CPV::where('trimmed_code',null)->get();
        $counter = 0;

        foreach($cpvs as $cpv) {


            $cpv->trimmed_code = rtrim($cpv->code,'0');

            if (strlen($cpv->trimmed_code) === 1) {
                // root level is level 2, not going lower than that
                $cpv->trimmed_code = $cpv->trimmed_code . '0';
            }

            $cpv->save();

            $counter++;
        }

        $this->info('Finished job for '.$counter.' cpv codes.');
    }
}
