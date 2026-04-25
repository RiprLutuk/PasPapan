@php($user = $user ?? auth()->user())

<div>
    <x-sections.action-section class="profile-card profile-card--danger">
        <x-slot name="icon">
            <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-500" />
        </x-slot>

        <x-slot name="title">
            {{ __('Danger') }}
        </x-slot>

        <x-slot name="description">
            {{ __('Request permanent account deletion with admin approval.') }}
        </x-slot>

        <x-slot name="content">
            @if ($user?->hasPendingAccountDeletionRequest())
                <div class="space-y-4">
                    <x-admin.status-badge tone="warning" pill>
                        {{ __('Pending admin approval') }}
                    </x-admin.status-badge>

                    <div class="max-w-xl text-sm text-gray-700 dark:text-gray-300">
                        {{ __('Your deletion request has been recorded and is waiting for an administrator review.') }}
                    </div>

                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-gray-900 dark:text-white">{{ __('Requested at') }}</dt>
                            <dd class="mt-1 text-gray-600 dark:text-gray-400">{{ $user->account_deletion_requested_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-900 dark:text-white">{{ __('Reason') }}</dt>
                            <dd class="mt-1 whitespace-pre-line text-gray-600 dark:text-gray-400">{{ $user->account_deletion_reason ?: '-' }}</dd>
                        </div>
                    </dl>
                </div>
            @else
                <div class="max-w-xl space-y-4 text-sm text-gray-600 dark:text-gray-400">
                    <p>{{ __('Your account will not be deleted immediately. An administrator must review and approve your request first.') }}</p>
                    <p>{{ __('Before continuing, make sure you have saved any information you still need.') }}</p>
                </div>
            @endif
        </x-slot>

        <x-slot name="actions">
            <x-actions.danger-button wire:click="confirmUserDeletion" wire:loading.attr="disabled" :disabled="$user?->hasPendingAccountDeletionRequest()">
                {{ $user?->hasPendingAccountDeletionRequest() ? __('Awaiting Review') : __('Request Deletion') }}
            </x-actions.danger-button>
        </x-slot>
    </x-sections.action-section>

    <x-overlays.dialog-modal wire:model.live="confirmingUserDeletion">
        <x-slot name="title">
            {{ __('Request Account Deletion') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Your request will be sent to an administrator for review. Please enter your password and explain why you want this account to be removed.') }}

            <div class="mt-4" x-data="{}" x-on:confirming-delete-user.window="setTimeout(() => $refs.password.focus(), 250)">
                <x-forms.input type="password" class="mt-1 block w-full"
                            autocomplete="current-password"
                            placeholder="{{ __('Password') }}"
                            x-ref="password"
                            wire:model="password"
                            wire:keydown.enter="deleteUser" />

                <x-forms.input-error for="password" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-forms.label for="delete-account-reason" value="{{ __('Reason') }}" />
                <x-forms.textarea
                    id="delete-account-reason"
                    class="mt-1 block w-full"
                    rows="4"
                    wire:model="reason"
                    placeholder="{{ __('Example: I no longer work here and want my account to be removed.') }}"
                />
                <x-forms.input-error for="reason" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingUserDeletion')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.danger-button class="ms-3" wire:click="deleteUser" wire:loading.attr="disabled">
                {{ __('Submit Request') }}
            </x-actions.danger-button>
        </x-slot>
    </x-overlays.dialog-modal>
</div>
