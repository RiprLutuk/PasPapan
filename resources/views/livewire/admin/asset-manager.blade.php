<div class="px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10 2xl:px-10">
    <div class="w-full">
        <!-- Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 flex items-center gap-2">
                    {{ __('Asset Management') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Track company properties, electronics, and vehicles assigned to employees.') }}
                </p>
            </div>
            <x-actions.button wire:click="create" title="{{ __('Add Asset') }}" aria-label="{{ __('Add Asset') }}" class="h-10 w-10 justify-center !px-0 !py-0 !bg-primary-600 hover:!bg-primary-700">
                <x-heroicon-m-plus class="h-4 w-4" />
            </x-actions.button>
        </div>

        <!-- Pending OTP Return Banners -->
        @php
            $otpNotifications = auth()
                ->user()
                ->unreadNotifications->where('type', 'App\Notifications\AssetReturnOtpRequested');
        @endphp

        @if ($otpNotifications->isNotEmpty())
            <div class="mb-6 space-y-3">
                @foreach ($otpNotifications as $notif)
                    <div
                        class="flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-900/50 dark:bg-amber-900/20">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-amber-600 dark:text-amber-500" />
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-amber-800 dark:text-amber-400">
                                    {{ __('Asset Return Request') }}
                                </h3>
                                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                    <span class="font-semibold">{{ $notif->data['user_name'] ?? 'Unknown User' }}</span>
                                    {{ __('is requesting to return') }} <span
                                        class="font-semibold">{{ $notif->data['asset_name'] ?? 'an asset' }}</span>.
                                    {{ __('Provide them with this OTP to finalize the return:') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div
                                class="rounded-md bg-white px-4 py-2 text-lg font-mono font-bold tracking-widest text-amber-700 shadow-sm border border-amber-200 dark:bg-gray-800 dark:border-amber-700 dark:text-amber-400">
                                {{ $notif->data['otp'] ?? '000000' }}
                            </div>
                            <!-- Button to simply dismiss notification if wanted -->
                            <button wire:click="markNotificationAsRead('{{ $notif->id }}')"
                                class="rounded bg-amber-100 px-2 py-1.5 text-xs font-semibold text-amber-800 shadow-sm hover:bg-amber-200 dark:bg-amber-900/50 dark:text-amber-300 dark:hover:bg-amber-800/80">
                                {{ __('Dismiss') }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Filters -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row">
            <x-forms.input type="text" wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search asset name or user...') }}" class="w-full sm:max-w-md" />
            <x-forms.tom-select id="typeFilter" wire:model.live="typeFilter" placeholder="{{ __('All Types') }}"
                class="w-full sm:w-48">
                <option value="">{{ __('All Types') }}</option>
                <option value="electronics">{{ __('Electronics') }}</option>
                <option value="vehicle">{{ __('Vehicle') }}</option>
                <option value="furniture">{{ __('Furniture') }}</option>
                <option value="uniform">{{ __('Uniform / Gear') }}</option>
            </x-forms.tom-select>
        </div>

        <!-- Content -->
        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Asset Info') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Type') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Purchase & Expiry') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Assigned To') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($assets as $asset)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $asset->name }}</div>
                                    <div class="text-xs text-gray-500 font-mono">
                                        {{ $asset->serial_number ?: __('No Serial') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-700/30 dark:text-gray-400 dark:ring-gray-400/20">
                                        {{ __(ucfirst($asset->type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        @if ($asset->purchase_cost)
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">Rp
                                                {{ number_format($asset->purchase_cost, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-xs text-gray-400 italic">{{ __('Unknown value') }}</span>
                                        @endif

                                        @if ($asset->expiration_date)
                                            @if ($asset->isExpired())
                                                <span
                                                    class="inline-flex max-w-fit items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400">
                                                    {{ __('Expired') }}:
                                                    {{ \Carbon\Carbon::parse($asset->expiration_date)->format('d M Y') }}
                                                </span>
                                            @elseif($asset->isExpiringSoon())
                                                <span
                                                    class="inline-flex max-w-fit items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                    {{ __('Expiring') }}:
                                                    {{ \Carbon\Carbon::parse($asset->expiration_date)->format('d M Y') }}
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex max-w-fit items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('Valid till') }}:
                                                    {{ \Carbon\Carbon::parse($asset->expiration_date)->format('d M Y') }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($asset->user)
                                        <div class="flex items-center gap-3">
                                            <img class="h-8 w-8 rounded-full object-cover"
                                                src="{{ $asset->user->profile_photo_url }}"
                                                alt="{{ $asset->user->name }}" />
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ $asset->user->name }}</div>
                                                <div class="text-xs text-gray-500">
                                                    {{ \Carbon\Carbon::parse($asset->date_assigned)->format('d M Y') }}
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 italic">{{ __('Unassigned') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                        {{ in_array($asset->status, ['available']) ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                        {{ in_array($asset->status, ['assigned', 'sold', 'auctioned']) ? 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                        {{ in_array($asset->status, ['lost', 'disposed']) ? 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                        {{ in_array($asset->status, ['maintenance', 'retired']) ? 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}">
                                        {{ __(ucfirst($asset->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="viewHistory({{ $asset->id }})"
                                            class="text-gray-400 hover:text-indigo-600 transition-colors"
                                            title="{{ __('View History') }}">
                                            <x-heroicon-m-clock class="h-5 w-5" />
                                        </button>
                                        <button wire:click="edit({{ $asset->id }})"
                                            class="text-gray-400 hover:text-blue-600 transition-colors"
                                            title="{{ __('Edit') }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </button>
                                        <button wire:click="delete({{ $asset->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this asset?') }}"
                                            class="text-gray-400 hover:text-red-600 transition-colors"
                                            title="{{ __('Delete') }}">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-heroicon-o-computer-desktop
                                            class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                                        <p class="font-medium">{{ __('No assets found in inventory') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-800">
                {{ $assets->links() }}
            </div>
        </div>
    </div>

    <!-- Modal -->
    <x-overlays.dialog-modal wire:model="showModal">
        <x-slot name="title">
            {{ $editMode ? __('Edit Asset') : __('Register New Asset') }}
        </x-slot>

        <x-slot name="content">
            <form wire:submit="save">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-forms.label for="name" value="{{ __('Asset Name') }}" />
                            <x-forms.input id="name" type="text" class="mt-1 block w-full" wire:model="name"
                                required placeholder="e.g. Macbook Pro M2" />
                            <x-forms.input-error for="name" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="serial_number" value="{{ __('Serial Number') }}" />
                            <x-forms.input id="serial_number" type="text" class="mt-1 block w-full font-mono text-sm"
                                wire:model="serial_number" placeholder="SN-12345" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-forms.label for="type" value="{{ __('Asset Type') }}" />
                            <x-forms.tom-select id="asset_type" wire:model="type" placeholder="{{ __('Select Type') }}"
                                class="mt-1">
                                <option value="electronics">{{ __('Electronics') }}</option>
                                <option value="vehicle">{{ __('Vehicle') }}</option>
                                <option value="furniture">{{ __('Furniture') }}</option>
                                <option value="uniform">{{ __('Uniform / Gear') }}</option>
                            </x-forms.tom-select>
                        </div>
                        <div>
                            <x-forms.label for="status" value="{{ __('Condition / Status') }}" />
                            <x-forms.tom-select id="asset_status" wire:model="status"
                                placeholder="{{ __('Select Status') }}" class="mt-1">
                                <option value="available">{{ __('Available') }}</option>
                                <option value="assigned">{{ __('Assigned') }}</option>
                                <option value="maintenance">{{ __('In Maintenance') }}</option>
                                <option value="lost">{{ __('Lost / Missing') }}</option>
                                <option value="retired">{{ __('Retired') }}</option>
                                <option value="sold">{{ __('Sold') }}</option>
                                <option value="auctioned">{{ __('Auctioned') }}</option>
                                <option value="disposed">{{ __('Disposed / Scrapped') }}</option>
                            </x-forms.tom-select>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                            {{ __('Financials & Validity') }}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <x-forms.label for="purchase_date" value="{{ __('Purchase Date') }}" />
                                <x-forms.input id="purchase_date" type="date" class="mt-1 block w-full"
                                    wire:model="purchase_date" />
                            </div>
                            <div>
                                <x-forms.label for="purchase_cost" value="{{ __('Purchase Cost') }}" />
                                <x-forms.input id="purchase_cost" type="number" step="0.01"
                                    class="mt-1 block w-full font-mono text-sm" wire:model="purchase_cost"
                                    placeholder="5000000" />
                            </div>
                            <div>
                                <x-forms.label for="expiration_date" value="{{ __('Expiration / Warranty') }}" />
                                <x-forms.input id="expiration_date" type="date" class="mt-1 block w-full"
                                    wire:model="expiration_date" />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                            {{ __('Assignment Checkout') }}</h4>
                        <div>
                            <x-forms.label for="user_id" value="{{ __('Assign To Employee') }}" />
                            <x-forms.tom-select id="asset_user" wire:model="user_id"
                                placeholder="-- {{ __('Unassigned (Keep in Storage)') }} --" class="mt-1">
                                <option value="">-- {{ __('Unassigned (Keep in Storage)') }} --</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->nip }})
                                    </option>
                                @endforeach
                            </x-forms.tom-select>
                        </div>

                        @if ($user_id)
                            <div class="grid grid-cols-2 gap-4 mt-4" x-data x-transition>
                                <div>
                                    <x-forms.label for="date_assigned" value="{{ __('Date Assigned') }}" />
                                    <x-forms.input id="date_assigned" type="date" class="mt-1 block w-full"
                                        wire:model="date_assigned" />
                                </div>
                                <div>
                                    <x-forms.label for="return_date" value="{{ __('Expected Return Date') }}" />
                                    <x-forms.input id="return_date" type="date" class="mt-1 block w-full"
                                        wire:model="return_date" />
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <x-forms.label for="notes" value="{{ __('Notes / Specs') }}" />
                        <textarea wire:model="notes" rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            placeholder="Intel i7, 16GB RAM..."></textarea>
                    </div>
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="save" wire:loading.attr="disabled">
                {{ $editMode ? __('Update') : __('Save') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <!-- Asset History Modal -->
    <x-overlays.dialog-modal wire:model.live="showHistoryModal">
        <x-slot name="title">
            {{ __('Asset Lifecycle History') }}
        </x-slot>

        <x-slot name="content">
            @if (isset($assetHistories) && $assetHistories->isNotEmpty())
                <div class="flow-root mt-4">
                    <ul role="list" class="-mb-8">
                        @foreach ($assetHistories as $index => $history)
                            <li>
                                <div class="relative pb-8">
                                    @if (!$loop->last)
                                        <span
                                            class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700"
                                            aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3 text-sm">
                                        <div>
                                            <span
                                                class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800
                                                {{ $history->action === 'created' ? 'bg-green-100 dark:bg-green-900/50' : '' }}
                                                {{ $history->action === 'assigned' ? 'bg-blue-100 dark:bg-blue-900/50' : '' }}
                                                {{ $history->action === 'returned' ? 'bg-indigo-100 dark:bg-indigo-900/50' : '' }}
                                                {{ in_array($history->action, ['maintenance', 'lost', 'retired']) ? 'bg-red-100 dark:bg-red-900/50' : '' }}">
                                                @if ($history->action === 'created')
                                                    <x-heroicon-m-plus
                                                        class="h-4 w-4 text-green-600 dark:text-green-400" />
                                                @elseif($history->action === 'assigned')
                                                    <x-heroicon-m-user-plus
                                                        class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                                @elseif($history->action === 'returned')
                                                    <x-heroicon-m-arrow-uturn-left
                                                        class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                                                @else
                                                    <x-heroicon-m-wrench
                                                        class="h-4 w-4 text-red-600 dark:text-red-400" />
                                                @endif
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-gray-900 dark:text-gray-100">
                                                    <span
                                                        class="font-semibold">{{ __(ucfirst($history->action)) }}</span>
                                                    @if ($history->user)
                                                        {{ __('to / by') }} <span
                                                            class="font-medium text-gray-900 dark:text-gray-100">{{ $history->user->name }}</span>
                                                    @endif
                                                </p>
                                                @if ($history->notes)
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $history->notes }}</p>
                                                @endif
                                            </div>
                                            <div
                                                class="text-right text-xs whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                <time
                                                    datetime="{{ $history->date->toIso8601String() }}">{{ $history->date->format('d M Y, H:i') }}</time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="py-6 text-center text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-clock class="h-10 w-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                    <p>{{ __('No history recorded for this asset.') }}</p>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showHistoryModal', false)">
                {{ __('Close') }}
            </x-actions.secondary-button>
        </x-slot>
    </x-overlays.dialog-modal>
</div>
