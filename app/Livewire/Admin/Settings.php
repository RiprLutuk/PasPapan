<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
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
        if (!auth()->check() || !auth()->user()->isAdmin) {
            abort(403, 'Unauthorized action.');
        }

        $this->syncEnterpriseLicenseState();
    }

    public function updateValue($id, $value)
    {
        if (!auth()->user()->isSuperadmin) {
            return; // Silently fail or abort
        }

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
        if (!auth()->user()->isSuperadmin) {
            return;
        }

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
