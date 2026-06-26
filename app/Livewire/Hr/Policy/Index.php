<?php

namespace App\Livewire\Hr\Policy;

use App\Models\HrPolicy;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';
    public string $category = '';

    public bool $showUploadModal = false;
    public ?int $deletingId = null;

    public string $title = '';
    public string $uploadCategory = '';
    public $file = null;
    public bool $isActive = true;

    protected function rules(): array
    {
        return [
            'title'          => 'required|string|max:255',
            'uploadCategory' => 'required|string',
            'file'           => 'required|file|mimes:pdf|max:10240',
            'isActive'       => 'boolean',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $this->validate();

        $path = $this->file->store('hr-policies', 'local');

        HrPolicy::create([
            'title'       => $this->title,
            'category'    => $this->uploadCategory,
            'file_path'   => $path,
            'is_active'   => $this->isActive,
            'uploaded_by' => auth()->id(),
        ]);

        $this->reset(['title', 'uploadCategory', 'file', 'isActive', 'showUploadModal']);
        session()->flash('status', 'Policy uploaded successfully.');
    }

    public function toggleActive(int $id): void
    {
        $policy = HrPolicy::findOrFail($id);
        $policy->update(['is_active' => ! $policy->is_active]);
    }

    public function download(int $id): mixed
    {
        $policy = HrPolicy::findOrFail($id);
        return Storage::disk('local')->download($policy->file_path, $policy->title . '.pdf');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'confirm-delete');
    }

    public function delete(): void
    {
        $policy = HrPolicy::findOrFail($this->deletingId);
        Storage::disk('local')->delete($policy->file_path);
        $policy->delete();

        $this->deletingId = null;
        $this->dispatch('close-modal', name: 'confirm-delete');
        session()->flash('status', 'Policy deleted.');
    }

    public function render()
    {
        $policies = HrPolicy::query()
            ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->category, fn($q) => $q->where('category', $this->category))
            ->with('uploader')
            ->latest()
            ->paginate(15);

        return view('livewire.hr.policy.index', [
            'policies'         => $policies,
            'categoryOptions'  => HrPolicy::CATEGORIES,
        ]);
    }
}