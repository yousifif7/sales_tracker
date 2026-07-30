<?php

namespace App\Http\Controllers;

use App\Models\EmailMessage;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    public function open(string $token): Response
    {
        $message = EmailMessage::query()
            ->where('tracking_token', $token)
            ->where('direction', 'outbound')
            ->first();

        if ($message) {
            $message->recordOpen();
        }

        // 1x1 transparent GIF
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => (string) strlen($gif ?: ''),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
