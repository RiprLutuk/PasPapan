@extends('errors::layout')

@section('title', __('Maintenance Mode'))

@section('content')
    <div class="mb-8">
        <svg class="w-48 h-48 md:w-64 md:h-64 mx-auto text-gray-300 animate-float" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.5">
             <!-- Rocket Body -->
             <path d="M12 2C12 2 16 8 16 14C16 17 14 20 12 22C10 20 8 17 8 14C8 8 12 2 12 2Z" fill="#1f2937" stroke="#9ca3af" stroke-width="1"/>
             <circle cx="12" cy="12" r="2" fill="#374151" stroke="#60a5fa" stroke-width="0.5"/>
             
             <!-- Wrench -->
             <path d="M18 16L20 18M18 16L19 15M20 18L21 17M18 16C17 17 16 18 16 18C16 18 17 19 18 19C18 19 19 18 19 18L20 18" stroke="#f59e0b" stroke-width="1.5" class="animate-pulse"/>
             
             <!-- Sparks -->
             <circle cx="16" cy="18" r="0.5" fill="#fcd34d" class="animate-ping"/>
             <circle cx="15" cy="17" r="0.5" fill="#fcd34d" class="animate-ping" style="animation-delay: 0.5s"/>
        </svg>
        <div class="mt-4 text-4xl sm:text-5xl md:text-[80px] font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-600 leading-tight drop-shadow-[0_0_10px_rgba(245,158,11,0.3)]">
            Maintenance
        </div>
    </div>

    <h1 class="text-3xl md:text-4xl font-bold mb-4">Polishing the Boosters</h1>
    <p class="text-gray-400 text-lg md:text-xl">
        We're currently performing scheduled maintenance.<br>
        The system will be back online shortly.
    </p>
@endsection
