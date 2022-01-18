<?php

namespace App\Jobs;

use App\Http\Controllers\DownloadController;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanUpStaleTemporaryDownloadLinksJob
{
    use Dispatchable;

    const LINKS_EXPIRE_IN_MINUTES = 90;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * Delete everything thats older than self::LINKS_EXPIRE_IN_MINUTES minutes
     * from the tmp folder.

     * @return void
     */
    public function handle()
    {
        // create the threshold timestamp, delete anything thats older
        $threshold = Carbon::now()->subMinutes(self::LINKS_EXPIRE_IN_MINUTES);

        dump('Temporary system links clean up, delete anything older than '.$threshold);
        Log::info('Temporary system links clean up, delete anything older than '.$threshold);

        $tmpDir = public_path(DownloadController::PATH_PUBLIC_TMP);

        $files = scandir($tmpDir);
        $deleted = 0;

        foreach($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            // $file contains the filename inside the given directory and not a path!
            $linkPath = $tmpDir . '/' . $file;

            // ATTENTION, we are operating here on SYMLINKS not actual FILES
            // the php function filemtime will follow the link and return the datetime of the link target
            // --> not wanted
            // $lastMod = Carbon::createfromtimestamp(filemtime($tmpDir . '/' . $file));

            // instead use lstat
            // @see https://stackoverflow.com/a/34512584/718980 for solution
            // also see https://www.php.net/manual/de/function.stat.php for an explanation of the returned arry
            $linkInfo = lstat($linkPath);
            $lastMod = Carbon::createfromtimestamp($linkInfo['mtime']);

            if ($lastMod < $threshold) {
                unlink($linkPath);
                $deleted++;
            }
        }

        dump('Deleted '.$deleted.' system links');
        Log::info('Deleted '.$deleted.' system links');
    }
}
