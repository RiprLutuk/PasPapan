@props(['name'])

@php
    $component = [
        'history' => 'heroicon-o-clock',
        'correction' => 'heroicon-o-clipboard-document-list',
        'leave' => 'heroicon-o-paper-airplane',
        'clock' => 'heroicon-o-clock',
        'calendar' => 'heroicon-o-calendar-days',
        'tasks' => 'heroicon-o-clipboard-document-check',
        'swap' => 'heroicon-o-arrows-right-left',
        'document' => 'heroicon-o-document-text',
        'face' => 'heroicon-o-face-smile',
        'approvals' => 'heroicon-o-check-circle',
        'reimbursement' => 'heroicon-o-banknotes',
        'payslip' => 'heroicon-o-document-text',
        'kasbon' => 'heroicon-o-banknotes',
        'assets' => 'heroicon-o-computer-desktop',
        'performance' => 'heroicon-o-chart-bar',
        'team' => 'heroicon-o-users',
        'profile' => 'heroicon-o-user',
        'logout' => 'heroicon-o-arrow-right-on-rectangle',
        'more' => 'heroicon-o-ellipsis-vertical',
    ][$name] ?? 'heroicon-o-squares-2x2';
@endphp

<x-dynamic-component
    :component="$component"
    {{ $attributes->merge(['class' => 'h-6 w-6', 'aria-hidden' => 'true']) }}
/>
