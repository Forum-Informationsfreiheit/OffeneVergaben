<?php

namespace App\Jobs;

use App\Datasource;
use App\DataSourcePreProcessor;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Process implements ShouldQueue
{
    use Dispatchable;

    protected $timestamp;

    protected $preProcessor;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        // use same timestamp for all requests
        $this->timestamp = Carbon::now();

        $this->preProcessor = new DataSourcePreProcessor();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Get only unprocessed data sources
        $sources = Datasource::unprocessed()->get();

        //$sources = Datasource::where('id',1809)->get();  --> with contracting bodies

        dump($sources->count());

        $stopAt = 10;
        $idx = 0;

        foreach($sources as $source) {
            $this->processDataSource($source);

            /*
            if (++$idx >= $stopAt) {
                break;
            }
            */
        }

        dd('Exit Processing');
    }

    protected function processDataSource($source) {

        $content = $source->content;

        $this->preProcessor->preProcess($content);

        $data = $this->preProcessor->getData();

        //dd($data);

        if ($data->awardContract && count($data->awardContract->contractors) > 1) {
            dd($data);
        }

        if ($data->modificationsContract && count($data->modificationsContract->contractors) > 1) {
            dd($data);
        }

        if ($data->contractingBody->additional && count($data->contractingBody->additional) == 1) {
            //dd($data);
        }
    }
}
