@php
    $notifications = auth()->user()->notifications()->latest()->take(30)->get();
@endphp

<x-filament-panels::page>
    @include('partials.notification-list')
</x-filament-panels::page>
