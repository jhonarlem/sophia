@php
$appName = config('app.name');
$nameParts = explode(' ', $appName);
@endphp

<div class="filament-brand flex items-center gap-x-2">
  <img src="{{ asset('logo.svg') }}" alt="Logo" class="h-7">
  <span class="text-2xl font-bold">{{ $nameParts[0] }}</span>
  <x-filament::badge size="sm">
    {{ $nameParts[1] }}
  </x-filament::badge>
</div>