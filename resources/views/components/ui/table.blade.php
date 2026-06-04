{{-- 
    Usage:
    <x-table :headers="['Name', 'Email', 'Status']">
        <tr>
            <x-table.td>John Doe</x-table.td>
            <x-table.td>john@example.com</x-table.td>
            <x-table.td><x-badge variant="success">Active</x-badge></x-table.td>
        </tr>
    </x-table>
--}}

@props([
    'headers' => [],
    'empty'   => 'No records found.',
])

<div class="overflow-hidden border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            @if (count($headers))
                <thead class="bg-slate-50">
                    <tr>
                        @foreach ($headers as $header)
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            <tbody class="divide-y divide-slate-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
