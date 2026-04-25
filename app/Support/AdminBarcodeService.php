<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Barcode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminBarcodeService
{
    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'value' => ['nullable', 'string', 'max:255', 'unique:barcodes'],
        'lat' => ['required', 'numeric', 'between:-90,90'],
        'lng' => ['required', 'numeric', 'between:-180,180'],
        'radius' => ['required', 'numeric', 'min:1'],
        'dynamic_enabled' => ['nullable', 'boolean'],
        'dynamic_ttl_seconds' => ['nullable', 'integer', 'min:30', 'max:300'],
    ];

    public function validationRules(bool $dynamicEnabled, ?Barcode $barcode = null): array
    {
        $rules = $this->rules;
        $uniqueRule = Rule::unique('barcodes');

        if ($barcode) {
            $uniqueRule->ignore($barcode->id);
        }

        $rules['value'] = [
            $dynamicEnabled ? 'nullable' : 'required',
            'string',
            'max:255',
            $uniqueRule,
        ];

        return $rules;
    }

    public function create(array $validated): Barcode
    {
        return Barcode::create($this->payload($validated));
    }

    public function update(Barcode $barcode, array $validated): Barcode
    {
        $barcode->update($this->payload($validated, $barcode));

        return $barcode->refresh();
    }

    public function generateDownload(Barcode $barcode): array
    {
        $file = (new BarcodeGenerator(width: 1280, height: 1280))
            ->generateQrCode($barcode->value)
            ->toString();

        $filename = (new BarcodeGenerator)->safeFilename($barcode->name ?? $barcode->value).'.png';

        return [
            'content' => $file,
            'filename' => $filename,
        ];
    }

    public function generateBulkDownload(): ?array
    {
        $barcodes = Barcode::query()
            ->where('dynamic_enabled', false)
            ->get();

        if ($barcodes->isEmpty()) {
            return null;
        }

        $zipFile = (new BarcodeGenerator(width: 1280, height: 1280))->generateQrCodesZip(
            $this->downloadableBarcodeValues($barcodes)->toArray()
        );

        return [
            'content' => file_get_contents($zipFile),
            'filename' => 'barcodes.zip',
        ];
    }

    public function regenerateSecret(Barcode $barcode): Barcode
    {
        $barcode->update([
            'secret_key' => Str::random(64),
        ]);

        ActivityLog::record(
            'Barcode Secret Regenerated',
            'Regenerated dynamic barcode secret for checkpoint: '.$barcode->name
        );

        return $barcode->refresh();
    }

    protected function payload(array $validated, ?Barcode $barcode = null): array
    {
        $dynamicEnabled = filter_var($validated['dynamic_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'name' => $validated['name'],
            'value' => $this->resolveBarcodeValue($validated, $barcode),
            'latitude' => (float) $validated['lat'],
            'longitude' => (float) $validated['lng'],
            'radius' => $validated['radius'],
            'secret_key' => $barcode?->secret_key ?: Str::random(64),
            'dynamic_enabled' => $dynamicEnabled,
            'dynamic_ttl_seconds' => (int) ($validated['dynamic_ttl_seconds'] ?? 60),
        ];
    }

    protected function resolveBarcodeValue(array $validated, ?Barcode $barcode = null): string
    {
        $dynamicEnabled = filter_var($validated['dynamic_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $dynamicEnabled) {
            return (string) $validated['value'];
        }

        if (filled($validated['value'] ?? null)) {
            return (string) $validated['value'];
        }

        if ($barcode?->value) {
            return $barcode->value;
        }

        return $this->generateSecureBarcodeValue();
    }

    protected function generateSecureBarcodeValue(): string
    {
        do {
            $value = 'BC-'.strtoupper(bin2hex(random_bytes(16)));
        } while (Barcode::query()->where('value', $value)->exists());

        return $value;
    }

    /**
     * @return Collection<string, string>
     */
    protected function downloadableBarcodeValues(Collection $barcodes): Collection
    {
        return $barcodes->mapWithKeys(fn (Barcode $barcode) => [$barcode->name => $barcode->value]);
    }
}
