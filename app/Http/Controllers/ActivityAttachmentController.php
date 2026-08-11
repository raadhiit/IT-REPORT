<?php

namespace App\Http\Controllers;

use App\Models\ActivityAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityAttachmentController extends Controller
{
    /**
     * Download an activity attachment.
     */
    public function show(Request $request, ActivityAttachment $attachment): StreamedResponse|Response
    {
        $user = $request->user();

        abort_unless($attachment->activity->user_id === $user->id || $user->isAdmin(), 403);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }
}
