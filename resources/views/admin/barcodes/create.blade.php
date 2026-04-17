<x-app-layout>
    <x-admin.page-shell
        :title="__('New Barcode')"
        :description="__('Create a new attendance checkpoint and map its valid scan radius.')"
    >
        <div class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
            <div class="p-4 lg:p-6">
                <form action="{{ route('admin.barcodes.store') }}" method="post">
                        @csrf

                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:gap-3">
                            <div class="w-full">
                                <x-forms.label for="name">Nama Barcode</x-forms.label>
                                <x-forms.input name="name" id="name" class="mt-1 block w-full" type="text"
                                    :value="old('name')" placeholder="Barcode Baru" />
                                @error('name')
                                    <x-forms.input-error for="name" class="mt-2" message="{{ $message }}" />
                                @enderror
                            </div>
                            <div class="w-full">
                                <x-forms.label for="value">Value Barcode</x-forms.label>
                                @livewire('admin.barcode-value-input-component')
                            </div>
                        </div>

                        <div class="mt-4 flex gap-3">
                            <div class="w-full">
                                <x-forms.label for="radius">Radius Valid Absen</x-forms.label>
                                <x-forms.input name="radius" id="radius" class="mt-1 block w-full" type="number"
                                    :value="old('radius')" placeholder="50 (meter)" />
                                @error('radius')
                                    <x-forms.input-error for="radius" class="mt-2" message="{{ $message }}" />
                                @enderror
                            </div>
                            <div class="w-full">
                            </div>
                        </div>

                        <div class="mt-5">
                            <h3 class="text-lg font-semibold dark:text-gray-400">{{ __('Coordinate') }}</h3>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div class="w-full">
                                    <x-forms.label for="lat">Latitude</x-forms.label>
                                    <x-forms.input name="lat" id="lat" class="mt-1 block w-full" type="text"
                                        :value="old('lat')" placeholder="cth: -6.12345" />
                                    @error('lat')
                                        <x-forms.input-error for="lat" class="mt-2" message="{{ $message }}" />
                                    @enderror
                                </div>
                                <div class="w-full">
                                    <x-forms.label for="lng">Longitude</x-forms.label>
                                    <x-forms.input name="lng" id="lng" class="mt-1 block w-full" type="text"
                                        :value="old('lng')" placeholder="cth: 6.12345" />
                                    @error('lng')
                                        <x-forms.input-error for="lng" class="mt-2" message="{{ $message }}" />
                                    @enderror
                                </div>
                            </div>

                            <div class="flex flex-col items-start gap-3 md:flex-row">
                                <x-actions.button type="button" onclick="toggleMap()" class="text-nowrap mt-4">
                                    <x-heroicon-s-map-pin class="mr-2 h-5 w-5" /> Tampilkan/Sembunyikan Peta
                                </x-actions.button>

                                <div id="map" class="my-6 h-72 w-full md:h-96"></div>
                            </div>

                            <div class="mb-3 mt-4 flex items-center justify-end">
                                <x-actions.button class="ms-4">
                                    {{ __('Save') }}
                                </x-actions.button>
                            </div>
                        </div>
                </form>
            </div>
        </div>
    </x-admin.page-shell>

    @pushOnce('scripts')
        <script>
            window.addEventListener("load", function() {
                window.initializeMap({
                    onUpdate: (lat, lng) => {
                        document.getElementById('lat').value = lat;
                        document.getElementById('lng').value = lng;
                    },
                    location: @if (old('lat') && old('lng'))
                        [Number({{ old('lat') }}), Number({{ old('lng') }})]
                    @else
                        undefined
                    @endif
                });
            });

            let map = document.getElementById('map');

            function toggleMap() {
                map.style.display = map.style.display === "none" ? "block" : "none";
            }
        </script>
    @endPushOnce
</x-app-layout>
