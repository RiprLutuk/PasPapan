<?php

namespace App\Http\Controllers\Admin\Barcode;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Barcode;
use App\Support\BarcodeGenerator;
use App\Support\DynamicBarcodeTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BarcodeController extends Controller
{
    protected $rules = [
        'name' => ['required', 'string', 'max:255'],
        'value' => ['nullable', 'string', 'max:255', 'unique:barcodes'],
        'lat' => ['required', 'numeric', 'between:-90,90'],
        'lng' => ['required', 'numeric', 'between:-180,180'],
        'radius' => ['required', 'numeric', 'min:1'],
        'dynamic_enabled' => ['nullable', 'boolean'],
        'dynamic_ttl_seconds' => ['nullable', 'integer', 'min:30', 'max:300'],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.barcodes.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    public function create()
    {
        return view('admin.barcodes.create');
    }

    public function store(Request $request)
    {
        $dynamicEnabled = $request->boolean('dynamic_enabled');
        $request->validate($this->validationRules($dynamicEnabled));

        try {
            Barcode::create([
                'name' => $request->name,
                'value' => $this->resolveBarcodeValue($request),
                'latitude' => doubleval($request->lat),
                'longitude' => doubleval($request->lng),
                'radius' => $request->radius,
                'secret_key' => Str::random(64),
                'dynamic_enabled' => $dynamicEnabled,
                'dynamic_ttl_seconds' => (int) ($request->dynamic_ttl_seconds ?: 60),
            ]);
            return redirect()->route('admin.barcodes')->with('flash.banner', __('Created successfully.'));
        } catch (\Throwable $th) {
            Log::error('Failed to create barcode.', [
                'user_id' => $request->user()?->id,
                'exception' => $th->getMessage(),
            ]);

            return redirect()->back()
                ->with('flash.banner', __('Failed to create barcode. Please check the input and try again.'))
                ->with('flash.bannerStyle', 'danger');
        }
    }

    public function edit(Barcode $barcode)
    {
        return view('admin.barcodes.edit', ['barcode' => $barcode]);
    }

    public function update(Request $request, Barcode $barcode)
    {
        $dynamicEnabled = $request->boolean('dynamic_enabled');
        $request->validate($this->validationRules($dynamicEnabled, $barcode));

        try {
            $barcode->update([
                'name' => $request->name,
                'value' => $this->resolveBarcodeValue($request, $barcode),
                'latitude' => doubleval($request->lat),
                'longitude' => doubleval($request->lng),
                'radius' => $request->radius,
                'secret_key' => $barcode->secret_key ?: Str::random(64),
                'dynamic_enabled' => $dynamicEnabled,
                'dynamic_ttl_seconds' => (int) ($request->dynamic_ttl_seconds ?: 60),
            ]);
            return redirect()->route('admin.barcodes')->with('flash.banner', __('Updated successfully.'));
        } catch (\Throwable $th) {
            Log::error('Failed to update barcode.', [
                'user_id' => $request->user()?->id,
                'barcode_id' => $barcode->id,
                'exception' => $th->getMessage(),
            ]);

            return redirect()->back()
                ->with('flash.banner', __('Failed to update barcode. Please check the input and try again.'))
                ->with('flash.bannerStyle', 'danger');
        }
    }


    public function download($barcodeId)
    {
        $barcode = Barcode::findOrFail($barcodeId);

        if ($barcode->dynamic_enabled) {
            return redirect()
                ->route('admin.barcodes.edit', $barcode)
                ->with('flash.banner', __('Dynamic barcodes must be shown from the live display page instead of downloaded as static QR.'))
                ->with('flash.bannerStyle', 'danger');
        }

        $barcodeFile = (new BarcodeGenerator(width: 1280, height: 1280))->generateQrCode($barcode->value);
        $filename = (new BarcodeGenerator())->safeFilename($barcode->name ?? $barcode->value);

        return response($barcodeFile)->withHeaders([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.png"',
        ]);
    }

    public function downloadAll()
    {
        $barcodes = Barcode::query()->where('dynamic_enabled', false)->get();
        if ($barcodes->isEmpty()) {
            return redirect()->back()
                ->with('flash.banner', 'Barcode ' . __('Not Found'))
                ->with('flash.bannerStyle', 'danger');
        }
        $zipFile = (new BarcodeGenerator(width: 1280, height: 1280))->generateQrCodesZip(
            $barcodes->mapWithKeys(fn ($barcode) => [$barcode->name => $barcode->value])->toArray()
        );

        return response(file_get_contents($zipFile))->withHeaders([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename=barcodes.zip',
        ]);
    }

    public function dynamicDisplay(Barcode $barcode, DynamicBarcodeTokenService $dynamicBarcodeTokenService)
    {
        if (!$barcode->dynamic_enabled) {
            return redirect()
                ->route('admin.barcodes.edit', $barcode)
                ->with('flash.banner', __('Enable dynamic barcode mode first.'))
                ->with('flash.bannerStyle', 'danger');
        }

        return view('admin.barcodes.dynamic-display', [
            'barcode' => $barcode,
            'tokenPayload' => $dynamicBarcodeTokenService->generateTokenPayload($barcode),
        ]);
    }

    public function dynamicToken(Barcode $barcode, DynamicBarcodeTokenService $dynamicBarcodeTokenService)
    {
        if (!$barcode->dynamic_enabled) {
            return response()->json([
                'success' => false,
                'message' => __('Dynamic barcode mode is disabled for this checkpoint.'),
            ], 422)->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
        }

        return response()->json([
            'success' => true,
            'barcode' => [
                'id' => $barcode->id,
                'name' => $barcode->name,
                'radius' => $barcode->radius,
            ],
            'data' => $dynamicBarcodeTokenService->generateTokenPayload($barcode),
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function regenerateSecret(Barcode $barcode)
    {
        $barcode->update([
            'secret_key' => Str::random(64),
        ]);

        ActivityLog::record(
            'Barcode Secret Regenerated',
            'Regenerated dynamic barcode secret for checkpoint: ' . $barcode->name
        );

        $targetRoute = $barcode->dynamic_enabled
            ? route('admin.barcodes.dynamic-display', $barcode)
            : route('admin.barcodes.edit', $barcode);

        return redirect($targetRoute)
            ->with('flash.banner', __('Barcode secret regenerated successfully. Any previously displayed dynamic QR is now invalid.'))
            ->with('flash.bannerStyle', 'success');
    }

    protected function validationRules(bool $dynamicEnabled, ?Barcode $barcode = null): array
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

    protected function resolveBarcodeValue(Request $request, ?Barcode $barcode = null): string
    {
        if (!$request->boolean('dynamic_enabled')) {
            return (string) $request->value;
        }

        if ($request->filled('value')) {
            return (string) $request->value;
        }

        if ($barcode?->value) {
            return $barcode->value;
        }

        return $this->generateSecureBarcodeValue();
    }

    protected function generateSecureBarcodeValue(): string
    {
        do {
            $value = 'BC-' . strtoupper(bin2hex(random_bytes(16)));
        } while (Barcode::query()->where('value', $value)->exists());

        return $value;
    }
}
