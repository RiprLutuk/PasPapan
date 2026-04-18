<x-admin.page-shell
    :title="__('Announcements')"
    :description="__('Broadcast news and updates to all employees.')"
>
    <x-slot name="actions">
        <x-actions.button wire:click="create" size="icon" label="{{ __('Add Announcement') }}">
            <x-heroicon-m-plus class="h-5 w-5" />
        </x-actions.button>
    </x-slot>

        <!-- Content -->
        <x-admin.panel>
            @if ($announcements->isEmpty())
                <div class="px-6 py-16">
                    <div class="flex flex-col items-center justify-center text-center">
                        <x-heroicon-o-megaphone class="h-12 w-12 text-gray-300 dark:text-gray-600" />
                        <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">{{ __('No announcements yet') }}</h3>
                        <p class="mt-2 max-w-2xl text-gray-500 dark:text-gray-400">
                            {{ __('Create your first announcement to broadcast updates to all employees.') }}
                        </p>
                    </div>
                </div>
            @else
                <!-- Desktop Table -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full whitespace-nowrap text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-medium">{{ __('Title') }}</th>
                                <th scope="col" class="px-6 py-4 font-medium">{{ __('Priority') }}</th>
                                <th scope="col" class="px-6 py-4 font-medium">{{ __('High Priority Mode') }}</th>
                                <th scope="col" class="px-6 py-4 font-medium">{{ __('Publish Date') }}</th>
                                <th scope="col" class="px-6 py-4 font-medium">{{ __('Expires') }}</th>
                                <th scope="col" class="px-6 py-4 font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($announcements as $announcement)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                     <div class="font-medium text-gray-900 dark:text-white">{{ $announcement->title }}</div>
                                     <div class="text-xs text-gray-500">{{ __('By') }} {{ $announcement->creator?->name ?? 'System' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                     <x-admin.status-badge :tone="$announcement->priority === 'high' ? 'danger' : ($announcement->priority === 'normal' ? 'info' : 'neutral')">
                                        {{ __(ucfirst($announcement->priority)) }}
                                    </x-admin.status-badge>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($announcement->priority === 'high')
                                        <x-admin.status-badge :tone="$announcement->modal_behavior === 'once' ? 'info' : 'warning'">
                                            {{ $announcement->modal_behavior === 'once' ? __('Show Once') : __('Require Confirmation') }}
                                        </x-admin.status-badge>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('Standard') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ $announcement->publish_date->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ $announcement->expire_date?->translatedFormat('d M Y') ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <x-forms.switch
                                        wire:click="toggleActive({{ $announcement->id }})"
                                        :checked="$announcement->is_active"
                                        size="sm"
                                        :label="__('Toggle announcement status') . ': ' . $announcement->title" />
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <x-actions.icon-button wire:click="edit({{ $announcement->id }})" variant="primary" label="{{ __('Edit announcement') }}: {{ $announcement->title }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </x-actions.icon-button>
                                        <x-actions.icon-button wire:click="delete({{ $announcement->id }})" wire:confirm="{{ __('Are you sure?') }}" variant="danger" label="{{ __('Delete announcement') }}: {{ $announcement->title }}">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </x-actions.icon-button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
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
                                 <div class="flex flex-col items-end gap-1">
                                     <x-admin.status-badge :tone="$announcement->priority === 'high' ? 'danger' : ($announcement->priority === 'normal' ? 'info' : 'neutral')">
                                         {{ __(ucfirst($announcement->priority)) }}
                                     </x-admin.status-badge>
                                     @if ($announcement->priority === 'high')
                                         <x-admin.status-badge :tone="$announcement->modal_behavior === 'once' ? 'info' : 'warning'">
                                             {{ $announcement->modal_behavior === 'once' ? __('Show Once') : __('Require Confirmation') }}
                                         </x-admin.status-badge>
                                     @endif
                                 </div>
                            </div>
                            <div class="flex items-center justify-between pt-2">
                                 <button type="button" wire:click="toggleActive({{ $announcement->id }})" role="switch" aria-checked="{{ $announcement->is_active ? 'true' : 'false' }}" aria-label="{{ __('Toggle announcement status') }}: {{ $announcement->title }}" class="wcag-touch-target rounded-lg px-3 py-2 text-xs font-medium {{ $announcement->is_active ? 'text-green-700 hover:bg-green-50 dark:text-green-300 dark:hover:bg-green-900/20' : 'text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                                     {{ $announcement->is_active ? __('Active') : __('Inactive') }}
                                 </button>
                                 <div class="flex gap-3">
                                     <x-actions.button type="button" wire:click="edit({{ $announcement->id }})" variant="soft-primary" size="sm" label="{{ __('Edit announcement') }}: {{ $announcement->title }}">{{ __('Edit') }}</x-actions.button>
                                     <x-actions.button type="button" wire:click="delete({{ $announcement->id }})" wire:confirm="{{ __('Are you sure?') }}" variant="soft-danger" size="sm" label="{{ __('Delete announcement') }}: {{ $announcement->title }}">{{ __('Delete') }}</x-actions.button>
                                 </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-200/60 bg-gray-50/70 px-6 py-3 dark:border-gray-700/60 dark:bg-gray-900/40">
                    {{ $announcements->links() }}
                </div>
            @endif
        </x-admin.panel>

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
                        <x-forms.textarea wire:model="content" rows="4" class="mt-1 block w-full" required />
                        <x-forms.input-error for="content" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                             <x-forms.label for="priority" value="{{ __('Priority') }}" />
                            <x-forms.select id="priority" wire:model.live="priority" class="mt-1 block w-full">
                                <option value="low">{{ __('Low') }}</option>
                                <option value="normal">{{ __('Normal') }}</option>
                                <option value="high">{{ __('High') }}</option>
                            </x-forms.select>
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <x-forms.checkbox id="is_active" wire:model="is_active" />
                            <x-forms.label for="is_active" value="{{ __('Active') }}" />
                        </div>
                    </div>
                    @if ($priority === 'high')
                        <div>
                            <x-forms.label for="modal_behavior" value="{{ __('High Priority Modal Behavior') }}" />
                            <x-forms.select id="modal_behavior" wire:model="modal_behavior" class="mt-1 block w-full">
                                <option value="once">{{ __('Show Once') }}</option>
                                <option value="acknowledge">{{ __('Require Confirmation') }}</option>
                            </x-forms.select>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Show Once: the modal appears one time per user. Require Confirmation: it keeps appearing on user pages until they press the confirmation button or the announcement expires.') }}
                            </p>
                        </div>
                    @endif
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
