<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <x-heroicon-o-computer-desktop class="w-6 h-6 text-primary-600" />
                {{ __('My Assets') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Company properties currently assigned to you.') }}
            </p>
        </div>

        @if($assets->isEmpty())
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-computer-desktop class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3 mx-auto" />
                    <p class="font-medium">{{ __('No assets assigned to you') }}</p>
                    <p class="text-xs mt-1">{{ __('Contact your administrator if you believe this is an error.') }}</p>
                </div>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($assets as $asset)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 hover:shadow-md transition-shadow">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $asset->name }}</h3>
                                @if($asset->serial_number)
                                    <p class="text-xs text-gray-500 font-mono mt-0.5">{{ $asset->serial_number }}</p>
                                @endif
                            </div>
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                {{ $asset->status === 'assigned' ? 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                {{ $asset->status === 'maintenance' ? 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                {{ $asset->status === 'available' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400' : '' }}">
                                {{ __(ucfirst($asset->status)) }}
                            </span>
                        </div>

                        <!-- Details -->
                        <div class="space-y-1.5 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <x-heroicon-m-tag class="w-4 h-4 text-gray-400" />
                                <span>{{ __(ucfirst($asset->type)) }}</span>
                            </div>
                            @if($asset->date_assigned)
                                <div class="flex items-center gap-2">
                                    <x-heroicon-m-calendar class="w-4 h-4 text-gray-400" />
                                    <span>{{ __('Assigned') }}: {{ \Carbon\Carbon::parse($asset->date_assigned)->format('d M Y') }}</span>
                                </div>
                            @endif
                            @if($asset->return_date)
                                <div class="flex items-center gap-2">
                                    <x-heroicon-m-arrow-uturn-left class="w-4 h-4 text-gray-400" />
                                    <span>{{ __('Return') }}: {{ \Carbon\Carbon::parse($asset->return_date)->format('d M Y') }}</span>
                                </div>
                            @endif
                            @if($asset->notes)
                                <div class="flex items-start gap-2 mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                                    <x-heroicon-m-document-text class="w-4 h-4 text-gray-400 mt-0.5" />
                                    <span class="text-xs">{{ $asset->notes }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
