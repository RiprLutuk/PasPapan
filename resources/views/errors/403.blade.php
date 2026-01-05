@extends('errors::layout')

@section('title', __('Forbidden'))

@section('content')
    <div class="mb-8">
        <svg class="w-48 h-48 md:w-64 md:h-64 mx-auto text-gray-300 animate-float" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.5">
             <!-- Planet -->
             <circle cx="12" cy="12" r="8" fill="#1f2937" stroke="#ef4444" stroke-width="1"/>
             <path d="M4 12H20" stroke="#ef4444" stroke-width="0.5" stroke-dasharray="2 2"/>
             
             <!-- Sign -->
             <rect x="8" y="8" width="8" height="8" rx="1" fill="#7f1d1d" stroke="#ef4444"/>
             <path d="M10 10L14 14M14 10L10 14" stroke="#fecaca" stroke-width="1.5"/>
        </svg>
        <div class="mt-4 text-6xl sm:text-7xl md:text-[120px] font-black text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-pink-600 leading-none drop-shadow-[0_0_10px_rgba(239,68,68,0.3)]">
            403
        </div>
    </div>

    <h1 class="text-3xl md:text-4xl font-bold mb-4">Restricted Sector</h1>
    <p class="text-gray-400 text-lg md:text-xl">
        Access denied, Commander.<br>
        You don't have clearance for this planet.
    </p>
@endsection
