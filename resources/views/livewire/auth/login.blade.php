<section class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">JJM Brain HRMS</p>
        <h1 class="mt-2 text-2xl font-semibold text-slate-950">Login</h1>
        <p class="mt-2 text-sm text-slate-600">Sign in to continue to your dashboard.</p>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="authenticate" class="space-y-5">
        <div>
            <label for="email" class="block text-sm font-medium text-slate-800">Email address</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autocomplete="email"
                class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
            >
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-800">Password</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                autocomplete="current-password"
                class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
            >
            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-3 text-sm text-slate-700">
            <input
                wire:model="remember"
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600"
            >
            Remember me
        </label>

        <button
            type="submit"
            class="inline-flex w-full items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"
        >
            Sign in
        </button>
    </form>
</section>
