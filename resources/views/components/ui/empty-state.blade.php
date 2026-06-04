@props([
    'title'       => 'No records found',
    'description' => null,
])

<div class="flex flex-col items-center justify-center py-16 text-center">
    <div class="flex h-12 w-12 items-center justify-center border border-slate-200 bg-slate-50">
        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
    </div>
    <p class="mt-4 text-sm font-medium text-slate-700">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-slate-400">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
