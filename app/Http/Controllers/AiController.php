<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use App\Models\User;

class AiController extends Controller
{
    public function index()
    {
        return view('ai.index');
    }

    public function chat(Request $request, \App\Services\AiAgentService $aiService)
    {
        $request->validate([
            'message' => 'required|string|max:500'
        ]);

        $userMessage = trim($request->message);
        $response = $aiService->chat($userMessage, Auth::user());

        return response()->json([
            'success' => true,
            'response' => $response,
            'timestamp' => now()->format('h:i a')
        ]);
    }

    private function showMainMenu()
    {
        return __('ai.welcome');
    }
}
