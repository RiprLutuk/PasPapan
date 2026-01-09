@component('emails.layouts.modern')

# {{ __('New Leave Request') }}

{{ __('Hello Admin,') }}

{{ __('There is a new leave request that requires your attention.') }}

<div class="info-table">
    <div class="info-row">
        <span class="info-label">{{ __('Employee') }}:</span>
        <span class="info-value">{{ $userName }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">{{ __('Type') }}:</span>
        <span class="info-value">{{ __('' . $leaveType) }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">{{ __('Date') }}:</span>
        <span class="info-value">{{ $dateDisplay }} <span style="font-size: 12px; color: #6b7280;">({{ $daysInfo }})</span></span>
    </div>
    <div class="info-row">
        <span class="info-label">{{ __('Reason') }}:</span>
        <span class="info-value">{{ $reason }}</span>
    </div>
</div>

<div style="text-align: center;">
    <a href="{{ $url }}" class="btn">{{ __('View Request') }}</a>
</div>

{{ __('Please login to approve or reject this request.') }}

{{-- Bilingual Footer / Subtitles could be added here if strictly required --}}
{{-- <div class="divider"></div>
<p style="color: #9ca3af; font-size: 14px; font-style: italic;">
    Halo Admin, ada pengajuan cuti baru dari {{ $userName }}. Silakan klik tombol di atas untuk melihat detail.
</p> --}}

@endcomponent
