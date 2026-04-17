<x-app-layout>
    <x-admin.page-shell
        :title="__('Barcode Management')"
        :description="__('Manage attendance barcode checkpoints and download QR codes for each location.')"
    >
        @livewire('admin.barcode-component')
    </x-admin.page-shell>
</x-app-layout>
