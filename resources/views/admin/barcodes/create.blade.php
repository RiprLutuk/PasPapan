<x-app-layout>
    <x-admin.page-shell
        :title="__('New Barcode')"
        :description="__('Add a checkpoint, choose the QR mode, then pin the location.')"
    >
        @include('admin.barcodes._form', [
            'mode' => 'create',
            'action' => route('admin.barcodes.store'),
            'heading' => __('Create Attendance Checkpoint'),
            'subheading' => __('Keep only the checkpoint name, radius, QR mode, and location.'),
            'submitLabel' => __('Save Checkpoint'),
        ])
    </x-admin.page-shell>

    @include('admin.barcodes._location-script', [
        'initialLat' => old('lat'),
        'initialLng' => old('lng'),
    ])
</x-app-layout>
