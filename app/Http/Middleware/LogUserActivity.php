<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log for authenticated users
        if (Auth::check()) {
            $method = $request->method();
            $path = $request->path();

            // Skip logging for debugbar, horizon, telescope if any, or assets
            if ($request->is('livewire/*') || $request->is('filament/*')) {
                // For Livewire, we might want to log specific actions if possible,
                // but usually it's too noisy. User asked for "doing changes".
                // Let's log 'livewire/update' only if we can extract component name, otherwise skip to avoid noise?
                // Or just log it. "semua aktifitas" means ALL.
                // Let's log it but keep description short.
            }

            // Avoid logging simple asset requests (though middleware usually doesn't run on them)

            $action = match ($method) {
                'GET' => 'Visited Page',
                'POST' => 'Form Submission',
                'PUT', 'PATCH' => 'Updated Data',
                'DELETE' => 'Deleted Data',
                default => 'Action'
            };

            // Enhance description for distinctiveness
            $description = "$method /".$path;

            // Optional: Identify Livewire component updates
            if ($request->is('livewire/update')) {
                $components = $request->input('components', []);
                if (! empty($components)) {
                    $names = collect($components)->pluck('name')->join(', ');
                    $description .= " ($names)";
                    $action = 'Livewire Action';
                }
            }

            ActivityLog::record($action, $description);
        }

        return $response;
    }
}
