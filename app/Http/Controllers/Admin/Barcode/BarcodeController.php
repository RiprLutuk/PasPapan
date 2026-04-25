<?php

namespace App\Http\Controllers\Admin\Barcode;

use App\Http\Controllers\Controller;
use App\Models\Barcode;
use App\Support\AdminBarcodeService;
use App\Support\DynamicBarcodeTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BarcodeController extends Controller
{
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

    public function store(Request $request, AdminBarcodeService $barcodeService)
    {
        $dynamicEnabled = $request->boolean('dynamic_enabled');
        $validated = $request->validate($barcodeService->validationRules($dynamicEnabled));

        try {
            $barcodeService->create($validated);

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

    public function update(Request $request, Barcode $barcode, AdminBarcodeService $barcodeService)
    {
        $dynamicEnabled = $request->boolean('dynamic_enabled');
        $validated = $request->validate($barcodeService->validationRules($dynamicEnabled, $barcode));

        try {
            $barcodeService->update($barcode, $validated);

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

    public function download($barcodeId, AdminBarcodeService $barcodeService)
    {
        $barcode = Barcode::findOrFail($barcodeId);

        if ($barcode->dynamic_enabled) {
            return redirect()
                ->route('admin.barcodes.edit', $barcode)
                ->with('flash.banner', __('Dynamic barcodes must be shown from the live display page instead of downloaded as static QR.'))
                ->with('flash.bannerStyle', 'danger');
        }

        $download = $barcodeService->generateDownload($barcode);

        return response($download['content'])->withHeaders([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$download['filename'].'"',
        ]);
    }

    public function downloadAll(AdminBarcodeService $barcodeService)
    {
        $download = $barcodeService->generateBulkDownload();

        if ($download === null) {
            return redirect()->back()
                ->with('flash.banner', 'Barcode '.__('Not Found'))
                ->with('flash.bannerStyle', 'danger');
        }

        return response($download['content'])->withHeaders([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename='.$download['filename'],
        ]);
    }

    public function dynamicDisplay(Barcode $barcode, DynamicBarcodeTokenService $dynamicBarcodeTokenService)
    {
        if (! $barcode->dynamic_enabled) {
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
        if (! $barcode->dynamic_enabled) {
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

    public function regenerateSecret(Barcode $barcode, AdminBarcodeService $barcodeService)
    {
        $barcode = $barcodeService->regenerateSecret($barcode);

        $targetRoute = $barcode->dynamic_enabled
            ? route('admin.barcodes.dynamic-display', $barcode)
            : route('admin.barcodes.edit', $barcode);

        return redirect($targetRoute)
            ->with('flash.banner', __('Barcode secret regenerated successfully. Any previously displayed dynamic QR is now invalid.'))
            ->with('flash.bannerStyle', 'success');
    }
}
