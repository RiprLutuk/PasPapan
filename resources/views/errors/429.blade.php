@extends('errors::layout')

@section('title', __('Too Many Requests'))

@section('content')
    <div class="mb-8">
        <svg class="w-48 h-48 md:w-64 md:h-64 mx-auto text-gray-300 animate-float" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.5">
             <!-- Clock/Speedometer -->
             <circle cx="12" cy="12" r="9" fill="#1f2937" stroke="#f59e0b" stroke-width="1"/>
             <path d="M12 12L16 8" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round"/>
             <!-- Steam -->
             <path d="M12 2V4M8 3V5M16 3V5" stroke="#9ca3af" stroke-width="1" class="animate-pulse"/>
        </svg>
        <div class="mt-4 text-6xl sm:text-7xl md:text-[120px] font-black text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-red-600 leading-none drop-shadow-[0_0_10px_rgba(249,115,22,0.3)]">
            429
        </div>
    </div>

    <h1 class="text-3xl md:text-4xl font-bold mb-4">Slow Down, Cowboy!</h1>
    <p class="text-gray-400 text-lg md:text-xl">
        Engine overheating due to too many requests.<br>
        Please wait a moment while we cool down.
    </p>
@endsection
