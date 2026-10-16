<?php

namespace App\Http\Controllers;

use App\Jobs\MakeDatasetsCsvDumpJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Support\PublicMessages as Msg;

class DownloadController extends Controller
{
    /**
     * The path inside the <root>/public/       (web accessible) directory
     * that holds the temporary system links.
     */
    const PATH_PUBLIC_TMP = 'tmp';

    protected $supportedFormat = [
        'csv'
    ];

    public function index() {

        $dailyDumpfilePath = MakeDatasetsCsvDumpJob::getCurrentFilePath('absolute');
        $dailyDumpInfo = new \stdClass();
        $dailyDumpInfo->url = route('public::download-static-file',['fileName' => 'kerndaten_dump_daily', 'format' => 'csv']);
        $dailyDumpInfo->timestamp = Carbon::parse(MakeDatasetsCsvDumpJob::getCurrentFileTimestamp());
        $dailyDumpInfo->filesize = filesize($dailyDumpfilePath);

        $files = [
            'kerndaten_dailydump' => $dailyDumpInfo,
        ];

        return view('public.downloads.index',compact('files'));
    }

    public function downloadStaticFile($fileName) {

        // Nur ein einziger Download aktuell erlaubt: Bulk Download Kerndaten
        if ($fileName != 'kerndaten_dump_daily') {
            return redirect(route('public::downloads'));
        }

        // Format Angabe in der URL required
        if (!request('format')) {
            return $this->errorView(Msg::DOWNLOAD_FORMAT_MISSING, 422);
        }

        // Aber derzeit nur CSV unterstüzt
        if (!in_array(request('format'),$this->supportedFormat)) {
            return $this->errorView(Msg::DOWNLOAD_FORMAT_UNKNOWN, 422);
        }

        // prepare file, symlink etc.
        $filePath = MakeDatasetsCsvDumpJob::getCurrentFilePath('absolute');
        $timestamp = Carbon::parse(MakeDatasetsCsvDumpJob::getCurrentFileTimestamp());

        $rand = strtolower(Str::random(8));
        $tmpName = 'kerndaten_dailydump_' . $timestamp->format('YmdHi') . '_' . $rand . '.zip';
        $linkPath = public_path('tmp/'.$tmpName);

        if (!file_exists(public_path('tmp'))) {
            mkdir(public_path('tmp'));
        }

        $res = symlink($filePath,$linkPath);

        if (!$res) {
            return $this->errorView(Msg::DOWNLOAD_FAILED, 500);
        }

        $size     = filesize($filePath);
        $title    = 'BULK Kerndaten';
        $format   = request('format');
        $link     = url('tmp/'.$tmpName);

        return view('public.downloads.show',compact('title','format','link','timestamp','size'));
    }

    /**
     * Public pages have no session, so errors are rendered directly instead of flashed on a redirect.
     */
    protected function errorView($messageKey, $status) {
        return response()->view('public.downloads.error', ['message' => Msg::text($messageKey)], $status);
    }
}
