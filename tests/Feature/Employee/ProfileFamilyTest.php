<?php

namespace Tests\Feature\Employee;

use App\Livewire\Employee\Profile\Index;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileFamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_manage_own_family_members(): void
    {
        $user = User::query()->create([
            'name' => 'Employee User',
            'email' => 'employee-family@example.test',
            'password' => 'password',
            'is_hr' => false,
            'is_admin' => false,
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-SELF-FAMILY-00001',
            'full_name' => 'Self Family Employee',
            'service_status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('activeTab', 'family')
            ->set('familyForm.name', 'Test Father')
            ->set('familyForm.relationship', 'father')
            ->set('familyForm.gender', 'male')
            ->set('familyForm.is_dependent', true)
            ->call('saveFamilyMember')
            ->assertHasNoErrors();

        $familyMember = $employee->familyMembers()->firstOrFail();

        $this->assertDatabaseHas('employee_family_members', [
            'id' => $familyMember->id,
            'employee_id' => $employee->id,
            'name' => 'Test Father',
            'relationship' => 'father',
            'is_dependent' => true,
        ]);

        Livewire::test(Index::class)
            ->call('editFamilyMember', $familyMember->id)
            ->set('familyForm.occupation', 'Retired')
            ->call('saveFamilyMember')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employee_family_members', [
            'id' => $familyMember->id,
            'occupation' => 'Retired',
        ]);

        Livewire::test(Index::class)
            ->call('deleteFamilyMember', $familyMember->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('employee_family_members', [
            'id' => $familyMember->id,
        ]);
    }
}
