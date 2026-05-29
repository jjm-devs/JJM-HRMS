<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header title="Edit Employee" :description="$employee->full_name">
        <x-ui.button :href="route('hr.employees.show', $employee)" variant="outline">Back</x-ui.button>
    </x-ui.page-header>

    @include('livewire.hr.employees.partials.form', [
        'submitLabel' => 'Update Employee',
    ])
</section>
