<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ShareTargetController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $payload = [
            'title' => $request->input('title'),
            'text'  => $request->input('text'),
            'url'   => $request->input('url'),
        ];

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            if ($file->isValid()) {
                $path = $file->store('share-target-temp', 'public');
                $payload['media_path'] = $path;
            }
        }

        session()->put('share_target_payload', $payload);

        return redirect('/?share-intent=1');
    }
}
