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

        return view('public.demo', compact('bot'));
    }

    public function testById(Bot $bot)
    {
        $bot = Bot::withoutGlobalScopes()->findOrFail($bot->id);

        return view('public.demo', compact('bot'));
    }
}
