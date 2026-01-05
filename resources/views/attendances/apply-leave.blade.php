<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Apply Leave') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card">
                <div class="p-6">

                    <div class="mb-4">
                        <x-secondary-button href="{{ url()->previous() }}">
                            <x-heroicon-o-chevron-left class="mr-2 h-5 w-5" />
                            Kembali
                        </x-secondary-button>
                    </div>
                    @if ($attendance && ($attendance->time_in || $attendance->time_out))
                        {{-- Warning jika sudah clock in/out hari ini --}}
                        <div
                            class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-600">
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 mr-3 flex-shrink-0"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                                <div>
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                                        Peringatan: Anda sudah melakukan absensi hari ini
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                                        <p>
                                            @if ($attendance->time_in)
                                                Check In:
                                                {{ \App\Helpers::format_time($attendance->time_in) }}
                                            @endif
                                            @if ($attendance->time_out)
                                                | Check Out:
                                                {{ \App\Helpers::format_time($attendance->time_out) }}
                                            @endif
                                        </p>
                                        <p class="mt-1">Anda tidak dapat mengajukan izin untuk tanggal yang sudah
                                            diabsen.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    {{-- Form pengajuan izin --}}
                    <form method="POST" action="{{ route('store-leave-request') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Form fields... --}}
                        <div class="mb-4">
                            <x-label for="status" value="Jenis Izin" />
                            <select name="status" id="status" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-lg shadow-sm" required>
                                <option value="" disabled {{ old('status') ? '' : 'selected' }}>Pilih Jenis Izin</option>
                                <option value="excused" {{ old('status') == 'excused' ? 'selected' : '' }}>Izin</option>
                                <option value="sick" {{ old('status') == 'sick' ? 'selected' : '' }}>Sakit</option>
                            </select>
                            <x-input-error for="status" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-label for="from" value="Dari Tanggal" />
                                <input type="date" name="from" id="from" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-lg shadow-sm"
                                    value="{{ old('from', date('Y-m-d')) }}" required />
                                <x-input-error for="from" class="mt-2" />
                            </div>
                            <div>
                                <x-label for="to" value="Sampai Tanggal (Opsional)" />
                                <input type="date" name="to" id="to" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-lg shadow-sm"
                                    value="{{ old('to') }}" />
                                <x-input-error for="to" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika hanya 1 hari</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <x-label for="note" value="Keterangan" />
                            <x-textarea name="note" id="note" class="mt-1 block w-full" rows="3"
                                required>{{ old('note') }}</x-textarea>
                            <x-input-error for="note" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-label for="attachment" value="Lampiran (Opsional)" />
                            <input type="file" name="attachment" id="attachment"
                                class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-md file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-indigo-50 file:text-indigo-700
                                    hover:file:bg-indigo-100
                                    dark:file:bg-indigo-900 dark:file:text-indigo-300"
                                accept="image/*,application/pdf" />
                            <x-input-error for="attachment" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Max 3MB (Gambar atau PDF)</p>
                        </div>

                        <input type="hidden" name="lat" id="lat" />
                        <input type="hidden" name="lng" id="lng" />

                        <div class="flex items-center justify-end mt-6">
                            <x-secondary-button href="{{ route('home') }}" class="mr-4">
                                Batal
                            </x-secondary-button>
                            <x-button>
                                Ajukan Izin
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
// Validate date range
            const fromInput = document.getElementById('from');
            if (fromInput) {
                fromInput.addEventListener('change', function() {
                    const fromDate = new Date(this.value);
                    const toInput = document.getElementById('to');
                    if (toInput) {
                        toInput.min = this.value;
                        if (toInput.value && new Date(toInput.value) < fromDate) {
                            toInput.value = this.value;
                        }
                    }
                });
            }

            /*
            // Get user location (Disabled to prevent focus shift on mobile)
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const latEl = document.getElementById('lat');
                    const lngEl = document.getElementById('lng');
                    if(latEl) latEl.value = position.coords.latitude;
                    if(lngEl) lngEl.value = position.coords.longitude;
                });
            }
            */
        </script>
    @endpush
</x-app-layout>
