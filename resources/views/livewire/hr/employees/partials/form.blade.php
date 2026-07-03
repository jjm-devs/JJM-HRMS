<form wire:submit="save" class="space-y-5">
    <x-ui.card title="Service Details" description="Where this employee belongs in the HRMS hierarchy.">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-ui.input
                wire:model="form.employee_code"
                label="Employee Code"
                :error="$errors->first('form.employee_code')"
                required
            />

            <x-ui.select
                wire:model.live="form.org_unit_id"
                label="Office / Unit"
                :options="$orgUnitOptions"
                :error="$errors->first('form.org_unit_id')"
                placeholder="Select office / unit"
                required
            />

            <x-ui.select
                wire:model="form.department_stream_id"
                label="Stream"
                :options="$departmentStreamOptions"
                :error="$errors->first('form.department_stream_id')"
                :placeholder="$departmentStreamPlaceholder"
                required
            />

            <x-ui.select
                wire:model="form.staff_category_id"
                label="Staff Category"
                :options="$staffCategoryOptions"
                :error="$errors->first('form.staff_category_id')"
                placeholder="None — not a district staff"
                hint="Only district (DMMU) staff have a category. Pick Support or WQ for them; leave as None for all other staff (SMMU, KRC, SRL, UNICEF, Grade IV)."
            />

            <x-ui.select
                wire:model="form.employment_type_id"
                label="Employment Type"
                :options="$employmentTypeOptions"
                :error="$errors->first('form.employment_type_id')"
                placeholder="Select type"
            />

            <x-ui.select
                wire:model="form.cadre_id"
                label="Cadre"
                :options="$cadreOptions"
                :error="$errors->first('form.cadre_id')"
                placeholder="Select cadre"
            />

            <x-ui.select
                wire:model="form.designation_id"
                label="Designation"
                :options="$designationOptions"
                :error="$errors->first('form.designation_id')"
                placeholder="Select designation"
            />

            <x-ui.input
                wire:model="form.joining_date"
                type="date"
                label="Joining Date"
                :error="$errors->first('form.joining_date')"
            />

            <x-ui.input
                wire:model="form.retirement_date"
                type="date"
                label="Retirement Date"
                :error="$errors->first('form.retirement_date')"
            />

            <x-ui.select
                wire:model="form.service_status"
                label="Service Status"
                :options="$statusOptions"
                :error="$errors->first('form.service_status')"
                required
            />
        </div>
    </x-ui.card>

    <x-ui.card title="Personal Details" description="Basic identity fields for the employee profile.">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-ui.input
                wire:model="form.full_name"
                label="Full Name"
                :error="$errors->first('form.full_name')"
                required
            />

            <x-ui.input
                wire:model="form.father_name"
                label="Father Name"
                :error="$errors->first('form.father_name')"
            />

            <x-ui.input
                wire:model="form.mother_name"
                label="Mother Name"
                :error="$errors->first('form.mother_name')"
            />

            <x-ui.input
                wire:model="form.date_of_birth"
                type="date"
                label="Date of Birth"
                :error="$errors->first('form.date_of_birth')"
            />

            <x-ui.select
                wire:model="form.gender"
                label="Gender"
                :options="[
                    'male' => 'Male',
                    'female' => 'Female',
                    'other' => 'Other',
                ]"
                :error="$errors->first('form.gender')"
                placeholder="Select gender"
            />

            <x-ui.select
                wire:model="form.blood_group"
                label="Blood Group"
                :options="[
                    'A+' => 'A+',
                    'A-' => 'A-',
                    'B+' => 'B+',
                    'B-' => 'B-',
                    'AB+' => 'AB+',
                    'AB-' => 'AB-',
                    'O+' => 'O+',
                    'O-' => 'O-',
                ]"
                :error="$errors->first('form.blood_group')"
                placeholder="Select blood group"
            />

            <x-ui.input
                wire:model="form.aadhaar_number"
                label="Aadhaar Number"
                :error="$errors->first('form.aadhaar_number')"
            />

            <x-ui.input
                wire:model="form.pan_number"
                label="PAN Number"
                :error="$errors->first('form.pan_number')"
            />
        </div>

        <div class="mt-4">
            <x-ui.textarea
                wire:model="form.remarks"
                label="Remarks"
                :error="$errors->first('form.remarks')"
            />
        </div>
    </x-ui.card>

    <div class="flex items-center justify-end gap-2">
        <x-ui.button :href="route('hr.employees.index')" variant="outline">Cancel</x-ui.button>

        <x-ui.button type="submit" variant="primary">
            {{ $submitLabel }}
        </x-ui.button>
    </div>
</form>
