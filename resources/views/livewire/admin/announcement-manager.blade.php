<x-admin.page-shell
    :title="__('Announcements')"
    :description="__('Broadcast news and updates to all employees.')"
>
    <x-slot name="actions">
        <x-actions.button wire:click="create" title="{{ __('Add Announcement') }}" aria-label="{{ __('Add Announcement') }}" class="h-10 w-10 justify-center !px-0 !py-0 !bg-primary-600 hover:!bg-primary-700">
            <x-heroicon-m-plus class="h-4 w-4" />
        </x-actions.button>
    </x-slot>

        <!-- Content -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Title') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Priority') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Publish Date') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Expires') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($announcements as $announcement)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                     <div class="font-medium text-gray-900 dark:text-white">{{ $announcement->title }}</div>
                                     <div class="text-xs text-gray-500">{{ __('By') }} {{ $announcement->creator?->name ?? 'System' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                     <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                        {{ $announcement->priority === 'high' ? 'bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-400/20' : '' }}
                                        {{ $announcement->priority === 'normal' ? 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-400/20' : '' }}
                                        {{ $announcement->priority === 'low' ? 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-700/30 dark:text-gray-400 dark:ring-gray-400/20' : '' }}">
                                        {{ __(ucfirst($announcement->priority)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ $announcement->publish_date->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ $announcement->expire_date?->translatedFormat('d M Y') ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <button wire:click="toggleActive({{ $announcement->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 {{ $announcement->is_active ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}">
                                        <span class="sr-only">{{ __('Use setting') }}</span>
                                        <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $announcement->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="edit({{ $announcement->id }})" class="text-gray-400 hover:text-blue-600 transition-colors" title="{{ __('Edit') }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </button>
                                        <button wire:click="delete({{ $announcement->id }})" wire:confirm="{{ __('Are you sure?') }}" class="text-gray-400 hover:text-red-600 transition-colors" title="{{ __('Delete') }}">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-heroicon-o-megaphone class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                                        <p class="font-medium">{{ __('No announcements yet') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

             <!-- Mobile List -->
            <div class="grid grid-cols-1 sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($announcements as $announcement)
                    <div class="p-4 space-y-2">
                        <div class="flex justify-between items-start">
                             <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ $announcement->title }}</h4>
                                <span class="text-xs text-gray-500">{{ $announcement->publish_date->format('d M') }}</span>
                             </div>
                             <span class="text-xs font-bold uppercase {{ $announcement->priority === 'high' ? 'text-red-600' : 'text-blue-600' }}">{{ $announcement->priority }}</span>
                        </div>
                        <div class="flex items-center justify-between pt-2">
                             <button wire:click="toggleActive({{ $announcement->id }})" class="text-xs font-medium {{ $announcement->is_active ? 'text-green-600' : 'text-gray-400' }}">
                                 {{ $announcement->is_active ? __('Active') : __('Inactive') }}
                             </button>
                             <div class="flex gap-3">
                                 <button wire:click="edit({{ $announcement->id }})" class="text-blue-600 dark:text-blue-400 text-sm font-medium">{{ __('Edit') }}</button>
                                 <button wire:click="delete({{ $announcement->id }})" wire:confirm="{{ __('Are you sure?') }}" class="text-red-600 dark:text-red-400 text-sm font-medium">{{ __('Delete') }}</button>
                             </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-800">
                {{ $announcements->links() }}
            </div>
        </div>
    </div>

    <!-- Modal -->
    <x-overlays.dialog-modal wire:model="showModal">
        <x-slot name="title">
            {{ $editMode ? __('Edit Announcement') : __('New Announcement') }}
        </x-slot>

        <x-slot name="content">
            <form wire:submit="save">
                <div class="space-y-4">
                    <div>
                        <x-forms.label for="title" value="{{ __('Title') }}" />
                        <x-forms.input id="title" type="text" class="mt-1 block w-full" wire:model="title" required />
                        <x-forms.input-error for="title" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.label for="content" value="{{ __('Content') }}" />
                        <textarea wire:model="content" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-primary-600" required></textarea>
                        <x-forms.input-error for="content" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                             <x-forms.label for="priority" value="{{ __('Priority') }}" />
                            <select wire:model="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-primary-600">
                                <option value="low">{{ __('Low') }}</option>
                                <option value="normal">{{ __('Normal') }}</option>
                                <option value="high">{{ __('High') }}</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <x-forms.checkbox id="is_active" wire:model="is_active" />
                            <x-forms.label for="is_active" value="{{ __('Active') }}" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-forms.label for="publish_date" value="{{ __('Publish Date') }}" />
                            <x-forms.input id="publish_date" type="date" class="mt-1 block w-full" wire:model="publish_date" required />
                        </div>
                        <div>
                            <x-forms.label for="expire_date" value="{{ __('Expire Date') }} (Optional)" />
                            <x-forms.input id="expire_date" type="date" class="mt-1 block w-full" wire:model="expire_date" />
                        </div>
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
</x-admin.page-shell>
