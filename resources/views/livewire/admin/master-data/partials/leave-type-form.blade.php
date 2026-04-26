<form wire:submit.prevent="{{ $formId === 'create' ? 'create' : 'update' }}" class="space-y-4">
    <div>
        <x-forms.label for="{{ $formId }}_leave_type_name" value="{{ __('Name') }}" />
        <x-forms.input id="{{ $formId }}_leave_type_name" type="text" class="mt-1 block w-full" wire:model="name" />
        <x-forms.input-error for="name" class="mt-2" />
    </div>

    <div>
        <x-forms.label for="{{ $formId }}_leave_type_description" value="{{ __('Description') }}" />
        <x-forms.textarea id="{{ $formId }}_leave_type_description" class="mt-1 block w-full" rows="3" wire:model="description" />
        <x-forms.input-error for="description" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-forms.label for="{{ $formId }}_leave_type_category" value="{{ __('Category') }}" />
            <x-forms.select id="{{ $formId }}_leave_type_category" class="mt-1 block w-full" wire:model.live="category">
                @foreach ($categories as $categoryKey => $categoryLabel)
                    <option value="{{ $categoryKey }}">{{ $categoryLabel }}</option>
                @endforeach
            </x-forms.select>
            <x-forms.input-error for="category" class="mt-2" />
        </div>

        <div>
            <x-forms.label for="{{ $formId }}_leave_type_sort_order" value="{{ __('Sort Order') }}" />
            <x-forms.input id="{{ $formId }}_leave_type_sort_order" type="number" min="0" max="999" class="mt-1 block w-full" wire:model="sort_order" />
            <x-forms.input-error for="sort_order" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 text-sm dark:border-slate-700 dark:bg-slate-900">
            <x-forms.checkbox class="mt-1" wire:model="counts_against_quota" @disabled($category === \App\Models\LeaveType::CATEGORY_SICK) />
            <span>
                <span class="block font-semibold text-slate-900 dark:text-white">{{ __('Uses annual quota') }}</span>
                <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Sick leave always ignores quota.') }}</span>
            </span>
        </label>

        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 text-sm dark:border-slate-700 dark:bg-slate-900">
            <x-forms.checkbox class="mt-1" wire:model="requires_attachment" />
            <span>
                <span class="block font-semibold text-slate-900 dark:text-white">{{ __('Requires attachment') }}</span>
                <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Useful for sick, birth, umrah, or permit documents.') }}</span>
            </span>
        </label>

        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 text-sm dark:border-slate-700 dark:bg-slate-900">
            <x-forms.checkbox class="mt-1" wire:model="is_active" />
            <span>
                <span class="block font-semibold text-slate-900 dark:text-white">{{ __('Active') }}</span>
                <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Inactive types are hidden from employee requests.') }}</span>
            </span>
        </label>
    </div>
</form>
