<?php

namespace Tests\Feature\Employee;

use App\Livewire\Employee\Documents\Index as EmployeeDocumentsIndex;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_submit_and_download_document(): void
    {
        Storage::fake('local');

        [$user, $employee] = $this->employeeUser();

        $type = DocumentType::query()->create([
            'name' => 'PAN Card',
            'code' => 'PAN-DOC',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(EmployeeDocumentsIndex::class)
            ->set('documentForm.document_type_id', (string) $type->id)
            ->set('documentForm.title', 'PAN Card')
            ->set('documentFile', UploadedFile::fake()->create('pan-card.pdf', 200, 'application/pdf'))
            ->call('submitDocument')
            ->assertHasNoErrors();

        $document = Document::query()->firstOrFail();

        $this->assertSame($employee->id, $document->documentable_id);
        $this->assertSame($employee->getMorphClass(), $document->documentable_type);
        $this->assertSame('submitted', $document->status);
        Storage::disk('local')->assertExists($document->file_path);

        Livewire::test(EmployeeDocumentsIndex::class)
            ->call('downloadDocument', $document->id)
            ->assertFileDownloaded('pan-card.pdf');

        $this->assertSame(1, DocumentAccessLog::query()->where('document_id', $document->id)->count());
    }

    private function employeeUser(): array
    {
        $user = User::query()->create([
            'name' => 'Document Employee',
            'email' => 'document-employee@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-DOC-00001',
            'full_name' => 'Document Employee',
            'service_status' => 'active',
        ]);

        return [$user, $employee];
    }
}
