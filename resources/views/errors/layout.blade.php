<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .stars {
            background-image: 
                radial-gradient(2px 2px at 20px 30px, #eee, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 40px 70px, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 50px 160px, #ddd, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 90px 40px, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 130px 80px, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 160px 120px, #ddd, rgba(0,0,0,0));
            background-repeat: repeat;
            background-size: 200px 200px;
            animation: stars 40s linear infinite;
        }
        
        @keyframes stars {
            0% { background-position: 0 0; }
            100% { background-position: 0 1000px; }
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .planet {
            background: radial-gradient(circle at 30% 30%, #4c1d95, #000);
            box-shadow: 0 0 20px #8b5cf6;
        }
    </style>
</head>
<body class="bg-[#0B1120] text-white h-screen overflow-hidden flex items-center justify-center relative font-sans antialiased">
    
    <!-- Background Elements -->
    <div class="stars absolute inset-0 opacity-50 pointer-events-none"></div>
    <div class="absolute bottom-[-100px] right-[-100px] w-64 h-64 rounded-full planet opacity-80 animate-float pointer-events-none" style="animation-delay: -2s;"></div>
    <div class="absolute top-[10%] left-[10%] w-4 h-4 rounded-full bg-blue-400 blur-[2px] animate-pulse pointer-events-none"></div>
    <div class="absolute top-[20%] right-[20%] w-2 h-2 rounded-full bg-purple-400 blur-[1px] animate-pulse pointer-events-none" style="animation-delay: 1s;"></div>

    <!-- Content -->
    <div class="relative z-10 text-center w-full max-w-2xl px-4 md:px-6">
        @yield('content')
        
        <div class="mt-12">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-8 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-full font-bold transition-all duration-300 hover:scale-105 hover:shadow-[0_0_20px_rgba(79,70,229,0.5)] group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform group-hover:-translate-x-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Return to Earth
            </a>
        </div>
    </div>

</body>
</html>
