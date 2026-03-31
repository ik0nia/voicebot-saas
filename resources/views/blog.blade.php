@extends('layouts.app')

@section('title', 'Blog - Sambla')
@section('meta_description', 'Articole și ghiduri despre AI conversațional, automatizare, și comunicare inteligentă cu clienții.')

@section('content')

<section class="relative overflow-hidden bg-slate-950 pt-28 pb-20 lg:pt-36 lg:pb-24">
    <div class="absolute inset-0 opacity-[0.04]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="blog-motif" x="0" y="0" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M40 12 L52 24 L40 36 L28 24 Z" fill="#991b1b"/><rect x="38" y="2" width="4" height="8" fill="#991b1b"/><rect x="38" y="38" width="4" height="8" fill="#991b1b"/></pattern></defs><rect width="100%" height="100%" fill="url(#blog-motif)"/></svg>
    </div>
    <div class="absolute top-20 right-0 w-[300px] h-[300px] bg-red-900/15 rounded-full blur-[100px]"></div>
    <div class="container-custom text-center relative z-10">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-white mb-6 animate-fade-in">
            <span class="bg-gradient-to-r from-red-400 via-red-300 to-amber-300 bg-clip-text text-transparent">Blog</span>
        </h1>
        <p class="text-lg md:text-xl text-slate-400 max-w-2xl mx-auto leading-relaxed animate-fade-in">
            Articole, ghiduri și noutăți despre AI conversațional
        </p>
    </div>
</section>

<x-motif-border />

<section class="section-padding bg-white">
    <div class="container-custom">
        <div class="max-w-2xl mx-auto text-center py-16">
            <div class="w-20 h-20 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mb-3">Pregătim conținut nou</h2>
            <p class="text-slate-500 mb-8 leading-relaxed">Lucrăm la articole și ghiduri practice despre AI conversațional, automatizare, și cum să îți crești afacerea cu inteligență artificială.</p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/register" class="px-6 py-3 bg-red-700 text-white font-semibold rounded-lg hover:bg-red-600 transition-colors shadow-sm">Începe gratuit cu Sambla</a>
                <a href="/contact" class="px-6 py-3 text-slate-600 font-semibold hover:text-slate-900 transition-colors">Contactează-ne →</a>
            </div>
        </div>
    </div>
</section>

@endsection
