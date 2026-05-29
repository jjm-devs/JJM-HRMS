<section class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header
        title="Change Password"
        description="Your account is using a temporary password. Set a new password before continuing."
    />

    @if (session('status'))
        <div class="mb-4">
            <x-ui.alert variant="warning">{{ session('status') }}</x-ui.alert>
        </div>
    @endif

    <x-ui.card title="Set New Password">
        <form wire:submit="save" class="space-y-4">
            <x-ui.input
                wire:model="current_password"
                type="password"
                label="Current Password"
                autocomplete="current-password"
                :error="$errors->first('current_password')"
                required
            />

            <x-ui.input
                wire:model="password"
                type="password"
                label="New Password"
                hint="Use at least 8 characters."
                autocomplete="new-password"
                :error="$errors->first('password')"
                required
            />

            <x-ui.input
                wire:model="password_confirmation"
                type="password"
                label="Confirm New Password"
                autocomplete="new-password"
                required
            />

            <div class="flex justify-end">
                <x-ui.button type="submit" variant="primary">
                    Change Password
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</section>
