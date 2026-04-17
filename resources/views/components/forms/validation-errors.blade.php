@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-900 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-100']) }} role="alert" aria-live="assertive">
        <div class="font-semibold text-red-900 dark:text-red-100">{{ __('Whoops! Something went wrong.') }}</div>

        <ul class="mt-3 list-disc list-inside space-y-1 text-sm text-red-800 dark:text-red-100">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
