<x-app-layout>
    <x-admin.page-shell
        :title="__('Edit Barcode')"
        :description="__('Update the checkpoint, QR mode, and scan location without the extra noise.')"
    >
        <form id="regenerate-secret-form" action="{{ route('admin.barcodes.regenerate-secret', $barcode) }}" method="post" class="hidden">
            @csrf
        </form>

        @include('admin.barcodes._form', [
            'mode' => 'edit',
            'barcode' => $barcode,
            'action' => route('admin.barcodes.update', $barcode->id),
            'heading' => $barcode->name,
            'subheading' => $barcode->dynamic_enabled
                ? __('Dynamic QR is active for this checkpoint.')
                : __('Static QR is active for this checkpoint.'),
            'submitLabel' => __('Save Changes'),
        ])
    </x-admin.page-shell>

    @include('admin.barcodes._location-script', [
        'initialLat' => old('lat', $barcode->latLng['lat'] ?? null),
        'initialLng' => old('lng', $barcode->latLng['lng'] ?? null),
    ])
</x-app-layout>
