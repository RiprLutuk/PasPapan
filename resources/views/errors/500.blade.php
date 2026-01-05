@extends('errors::layout')

@section('title', __('Server Error'))

@section('content')
    <div class="mb-8">
        <svg class="w-48 h-48 md:w-64 md:h-64 mx-auto text-gray-300 animate-float" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.5">
             <!-- Satellite -->
             <rect x="8" y="6" width="8" height="12" rx="1" fill="#1f2937" stroke="#9ca3af" stroke-width="1"/>
             <path d="M4 8H8M16 8H20M4 16H8M16 16H20" stroke="#60a5fa" stroke-width="1"/>
             <!-- Broken Antenna -->
             <path d="M12 6V3L14 1" stroke="#ef4444" stroke-width="1" stroke-dasharray="2 2"/>
             <!-- Smoke -->
             <path d="M15 4C16 3 17 3 18 4" stroke="#9ca3af" stroke-width="0.5" class="animate-pulse"/>
        </svg>
        <div class="mt-4 text-6xl sm:text-7xl md:text-[120px] font-black text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-orange-500 leading-none drop-shadow-[0_0_10px_rgba(239,68,68,0.3)]">
            500
        </div>
    </div>

    <h1 class="text-3xl md:text-4xl font-bold mb-4">Engine Malfunction</h1>
    <p class="text-gray-400 text-lg md:text-xl">
        Internal system error detected.<br>
        Our engineers are fixing the warp drive.
    </p>
@endsection
