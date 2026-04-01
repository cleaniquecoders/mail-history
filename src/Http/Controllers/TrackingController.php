<?php

namespace CleaniqueCoders\MailHistory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;

class TrackingController extends Controller
{
    public function open(Request $request, string $hash): Response
    {
        $model = config('mailhistory.model');
        $mailHistory = $model::where('hash', $hash)->first();

        if ($mailHistory) {
            $mailHistory->recordEvent('opened', [], [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // 1x1 transparent GIF
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen($gif),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function click(Request $request, string $hash)
    {
        $encryptedUrl = $request->query('url');

        if (! $encryptedUrl) {
            abort(400, 'Missing URL parameter.');
        }

        try {
            $url = Crypt::decryptString($encryptedUrl);
        } catch (\Throwable) {
            abort(400, 'Invalid URL parameter.');
        }

        $model = config('mailhistory.model');
        $mailHistory = $model::where('hash', $hash)->first();

        if ($mailHistory) {
            $mailHistory->recordEvent('clicked', [], [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $url,
            ]);
        }

        return redirect($url);
    }
}
