<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header title="Add Employee" description="Create the core employee profile first. Contacts, documents, and service book entries will come next.">
        <x-ui.button :href="route('hr.employees.index')" variant="outline">Back</x-ui.button>
    </x-ui.page-header>

    @include('livewire.hr.employees.partials.form', [
        'submitLabel' => 'Create Employee',
    ])
</section>
