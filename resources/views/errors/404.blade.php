@extends('errors::layout')

@section('title', __('Not Found'))

@section('content')
    <div class="mb-8">
        <svg class="w-48 h-48 md:w-64 md:h-64 mx-auto text-gray-300 animate-float" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.5">
             <!-- Astronaut Body -->
             <path d="M12 2C8.13 2 5 5.13 5 9C5 11.38 6.19 13.47 8 14.74V17C8 17.55 8.45 18 9 18H15C15.55 18 16 17.55 16 17V14.74C17.81 13.47 19 11.38 19 9C19 5.13 15.87 2 12 2Z" fill="#1f2937" stroke="#4b5563" stroke-width="1"/>
             <circle cx="12" cy="9" r="5" fill="#111827" stroke="#60a5fa" stroke-width="0.5"/>
             <path d="M9 18V21C9 21.55 9.45 22 10 22H14C14.55 22 15 21.55 15 21V18" stroke="#4b5563" stroke-width="1"/>
             <!-- Cord -->
             <path d="M16 10C19 10 22 8 23 5" stroke="#60a5fa" stroke-width="0.5" stroke-dasharray="2 2" class="opacity-50"/>
        </svg>
        <div class="mt-4 text-6xl sm:text-7xl md:text-[120px] font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-600 leading-none drop-shadow-[0_0_10px_rgba(139,92,246,0.3)]">
            404
        </div>
    </div>

    <h1 class="text-3xl md:text-4xl font-bold mb-4">Houston, We Have a Problem!</h1>
    <p class="text-gray-400 text-lg md:text-xl">
        The page you are looking for has drifted into deep space.<br>
        We can't seem to find it in this galaxy.
    </p>
@endsection
