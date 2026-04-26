<div class="auth-shell">
  <div class="auth-shell__backdrop" aria-hidden="true"></div>

  <div class="auth-shell__container">
    <section class="auth-card" aria-label="{{ __('Authentication') }}">
      @isset($logo)
        <div class="mb-5 flex justify-center">
          {{ $logo }}
        </div>
      @endisset

      {{ $slot }}
    </section>
  </div>
</div>
