<?php

namespace App\Http\Middleware;

use App\Helpers\Editions;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class RedirectLockedEnterpriseFeature
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $feature, string $permission, string $fallbackRoute = 'home'): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $this->isLocked($feature) || ! $this->hasUnderlyingAccess($user, $permission)) {
            return $next($request);
        }

        $payload = $this->lockPayload($feature);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $payload['message'],
                'feature_locked' => true,
                'feature' => $feature,
            ], 423);
        }

        $fallbackRoute = $this->resolveFallbackRoute($fallbackRoute, $user);

        return redirect()
            ->route($fallbackRoute)
            ->with('show-feature-lock', $payload)
            ->with('flash.banner', $payload['message'])
            ->with('flash.bannerStyle', 'warning');
    }

    private function isLocked(string $feature): bool
    {
        return match ($feature) {
            'analytics' => Editions::analyticsLocked(),
            'appraisal', 'appraisals' => Editions::appraisalLocked(),
            'asset', 'assets' => Editions::assetLocked(),
            'audit' => Editions::auditLocked(),
            'cash_advance', 'cash-advance', 'kasbon' => Editions::cashAdvanceLocked(),
            'payroll' => Editions::payrollLocked(),
            'reporting' => Editions::reportingLocked(),
            'system_backup', 'system-backup' => Editions::systemBackupLocked(),
            default => false,
        };
    }

    private function hasUnderlyingAccess(User $user, string $permission): bool
    {
        if ($permission === 'user') {
            return $user->isUser;
        }

        if ($permission === 'admin') {
            return $user->canAccessAdminPanel();
        }

        if (str_starts_with($permission, 'gate:')) {
            return Gate::forUser($user)->allows(substr($permission, 5));
        }

        $permissions = array_filter(explode('|', $permission));

        return $user->allowsAdminPermission($permissions);
    }

    /**
     * @return array{title: string, message: string}
     */
    private function lockPayload(string $feature): array
    {
        $title = match ($feature) {
            'analytics' => __('Analytics Locked'),
            'appraisal', 'appraisals' => __('Appraisals Locked'),
            'asset', 'assets' => __('Asset Management Locked'),
            'audit' => __('Audit Export Locked'),
            'cash_advance', 'cash-advance', 'kasbon' => __('Kasbon Locked'),
            'payroll' => __('Payroll Locked'),
            'reporting' => __('Import/Export Locked'),
            'system_backup', 'system-backup' => __('System Backup Locked'),
            default => __('Enterprise Feature'),
        };

        return [
            'title' => $title,
            'message' => __('This feature is available in the Enterprise Edition. Please upgrade.'),
        ];
    }

    private function resolveFallbackRoute(string $fallbackRoute, User $user): string
    {
        if ($fallbackRoute !== 'auto') {
            return $fallbackRoute;
        }

        return $user->preferredAdminRouteName() ?? 'home';
    }
}
