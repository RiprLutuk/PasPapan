@props([
    'title',
    'description' => null,
    'backHref' => null,
    'titleId' => null,
    'plain' => false,
    'backLabel' => null,
])

<header {{ $attributes->merge(['class' => 'user-page-header' . ($plain ? ' user-page-header--plain' : '')]) }}>
    <div class="user-page-header__row">
        <div class="user-page-header__main">
            @if ($backHref)
                <x-actions.secondary-button href="{{ $backHref }}" class="user-page-header__back !rounded-2xl !px-3 !py-2 border-gray-200 dark:border-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600" aria-label="{{ $backLabel ?? __('Go back') }}">
                    <x-heroicon-o-arrow-left class="h-5 w-5 text-gray-500 dark:text-gray-300" />
                </x-actions.secondary-button>
            @endif

            @isset($icon)
                <div class="user-page-header__icon" aria-hidden="true">
                    {{ $icon }}
                </div>
            @endisset

            <div class="user-page-header__copy">
                <h1 @if ($titleId) id="{{ $titleId }}" @endif class="user-page-header__title">{{ $title }}</h1>

                @if ($description)
                    <p class="user-page-header__description">{{ $description }}</p>
                @endif
            </div>
        </div>

        @isset($actions)
            <div class="user-page-header__actions">
                {{ $actions }}
            </div>
        @endisset
    </div>
</header>
