<?php

namespace App\Livewire\Employee\Policy;

use Livewire\Component;
use App\Models\HrPolicy;
use Illuminate\Support\Facades\Storage;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public string $category = '';
    public $file = null;
    public bool $isActive = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function download(int $id): mixed
    {
        $policy = HrPolicy::findOrFail($id);
        return Storage::disk('local')->download($policy->file_path, $policy->title . '.pdf');
    }

    public function render()
    {
        $policies = HrPolicy::query()
            ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->when($this->category, fn($q) => $q->where('category', $this->category))
            ->latest()
            ->paginate(15);

        return view('livewire.employee.policy.index', [
            'policies' => $policies,
            'categoryOptions'  => HrPolicy::CATEGORIES,
        ]);
    }
}