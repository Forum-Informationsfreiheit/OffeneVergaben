<?php

namespace App\Console\Commands;

use App\Organization;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateOrganizationStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fif:update-organization-stats {--organization=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates (dataset) count and val_total organization aggregates.';

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
        $start = Carbon::now();
        $this->info('Starting update organization stats Job');
        Log::info('Starting update organization stats Job');

        $singleOrganization = $this->option('organization') ? $this->option('organization') : null;

        // check option before starting job
        if ($singleOrganization && !Organization::find($singleOrganization)) {
            $this->error("Unknown organization id $singleOrganization. Exit.");
            return;
        }

        $params = [ 'organization_id' => $singleOrganization ];

        $job = app()->make('App\Jobs\UpdateOrganizationStatsJob',$params);
        dispatch($job);
        $runtime = $start->diffInSeconds(Carbon::now());

        $this->info('Finished update organization stats Job in '.$runtime.' seconds');
        Log::info('Finished update organization stats Job in '.$runtime.' seconds');
    }
}
