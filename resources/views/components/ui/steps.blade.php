@props([
    'steps'   => [],   // ['Basic Info', 'Employment', 'Contact', ...]
    'current' => 1,    // 1-based
])

<div class="flex items-center gap-0">
    @foreach ($steps as $index => $step)
        @php
            $number    = $index + 1;
            $isDone    = $number < $current;
            $isActive  = $number === $current;
            $isPending = $number > $current;
        @endphp

        <div class="flex items-center">
            {{-- Circle --}}
            <div class="flex flex-col items-center gap-1.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold transition
                    {{ $isDone   ? 'bg-blue-700 text-white' : '' }}
                    {{ $isActive ? 'border-2 border-blue-700 text-blue-700 bg-white' : '' }}
                    {{ $isPending ? 'border-2 border-slate-200 text-slate-400 bg-white' : '' }}
                ">
                    @if ($isDone)
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    @else
                        {{ $number }}
                    @endif
                </div>

                <span class="text-xs font-medium
                    {{ $isActive  ? 'text-blue-700' : '' }}
                    {{ $isDone    ? 'text-slate-600' : '' }}
                    {{ $isPending ? 'text-slate-400' : '' }}
                ">
                    {{ $step }}
                </span>
            </div>

            {{-- Connector line --}}
            @if (!$loop->last)
                <div class="mx-2 mb-4 h-px w-10 {{ $isDone ? 'bg-blue-700' : 'bg-slate-200' }}"></div>
            @endif
        </div>
    @endforeach
</div>
