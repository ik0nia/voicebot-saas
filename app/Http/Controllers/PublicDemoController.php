<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Illuminate\Http\Request;

class PublicDemoController extends Controller
{
    public function show(string $slug)
    {
        $bot = Bot::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()
            ->view('public.demo', compact('bot'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function testById(int $bot)
    {
        $bot = Bot::withoutGlobalScopes()->findOrFail($bot);

        return response()
            ->view('public.demo', compact('bot'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }
}
