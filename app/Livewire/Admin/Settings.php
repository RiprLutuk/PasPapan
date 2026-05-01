<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Settings extends Component
{
    public string $enterpriseLicenseDraft = '';

    public array $licenseValidation = [];

    public ?int $enterpriseLicenseSettingId = null;

    public function mount()
    {
        Gate::authorize('viewAdminSettings');

        $this->syncEnterpriseLicenseState();
    }

    public function updateValue($id, $value)
    {
        Gate::authorize('manageSystemSettings');

        $setting = Setting::find($id);

        if ($setting) {
            // Handle boolean toggle where value might be sent as true/false string or 1/0
            if ($setting->type === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }

            $setting->update(['value' => $value]);
            Setting::flushCache($setting->key);

            // Vital: Clear Enterprise License Cache if Company Name, Email, or Key changes
            if (in_array($setting->key, ['app.company_name', 'app.support_contact', 'enterprise_license_key'])) {
                \App\Services\Enterprise\LicenseGuard::clearLicenseCache();
                $this->syncEnterpriseLicenseState($setting->key !== 'enterprise_license_key');
            }

            $this->dispatch('saved'); // For sweetalert or notification
        }
    }

    public function applyEnterpriseLicense()
    {
        Gate::authorize('manageEnterpriseLicense');

        $setting = Setting::query()->firstOrCreate(
            ['key' => 'enterprise_license_key'],
            [
                'value' => '',
                'group' => 'enterprise',
                'type' => 'textarea',
                'description' => 'Enterprise License Key',
            ],
        );

        $setting->update(['value' => trim($this->enterpriseLicenseDraft)]);
        Setting::flushCache($setting->key);
        \App\Services\Enterprise\LicenseGuard::clearLicenseCache();

        $this->enterpriseLicenseSettingId = $setting->id;
        $this->syncEnterpriseLicenseState();
        $this->dispatch('saved');
        $this->dispatch('enterprise-license-applied', reload: (bool) ($this->licenseValidation['valid'] ?? false));
    }

    private function syncEnterpriseLicenseState(bool $reloadDraft = true): void
    {
        $setting = Setting::query()->where('key', 'enterprise_license_key')->first();

        $this->enterpriseLicenseSettingId = $setting?->id;

        if ($reloadDraft) {
            $this->enterpriseLicenseDraft = (string) ($setting?->value ?? '');
        }

        $this->licenseValidation = \App\Services\Enterprise\LicenseGuard::validateDetailed($this->enterpriseLicenseDraft);

        if (blank($this->enterpriseLicenseDraft)) {
            \App\Services\Enterprise\LicenseGuard::clearLicenseCache();

            return;
        }

        $cacheUntil = $this->licenseValidationCacheExpiration();

        Cache::put('ent_lic_status', ($this->licenseValidation['valid'] ?? false) ? 'valid' : 'invalid', $cacheUntil);
        Cache::put('ent_lic_hash', hash('sha256', $this->enterpriseLicenseDraft), $cacheUntil);
        Cache::put('ent_lic_result', $this->licenseValidation, $cacheUntil);
    }

    private function licenseValidationCacheExpiration(): \Carbon\Carbon
    {
        if (! ($this->licenseValidation['valid'] ?? false)) {
            return now()->addMinutes(5);
        }

        $expiresAt = $this->licenseValidation['license']['expires_at'] ?? null;

        if (is_string($expiresAt) && trim($expiresAt) !== '') {
            try {
                $licenseExpiry = \Carbon\Carbon::parse($expiresAt)->endOfDay();

                if ($licenseExpiry->isBefore(now()->addHours(24))) {
                    return $licenseExpiry;
                }
            } catch (\Throwable $e) {
                // Keep the default cache TTL if the validated date cannot be parsed here.
            }
        }

        return now()->addHours(24);
    }

    public function render()
    {
        $groups = Setting::query()
            // Verification already implies enrollment when the user has no Face ID.
            // Keep the enrollment setting for backward compatibility, but avoid
            // exposing two near-identical admin toggles.
            ->where('key', '!=', 'attendance.require_face_enrollment')
            ->get()
            ->groupBy('group');
        $licenseInfo = $this->licenseValidation['valid'] ?? false ? ($this->licenseValidation['license'] ?? null) : null;
        $hwid = \App\Console\Commands\EnterpriseHwId::generate();

        return view('livewire.admin.settings', [
            'groups' => $groups,
            'licenseInfo' => $licenseInfo,
            'licenseValidation' => $this->licenseValidation,
            'hwid' => $hwid,
        ]);
    }
}
