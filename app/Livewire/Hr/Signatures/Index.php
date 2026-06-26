<?php

namespace App\Livewire\Hr\Signatures;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public $signatureFile = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('deputy_md'), 403);
    }

    public function save(): void
    {
        abort_unless(Auth::user()?->hasRole('deputy_md'), 403);

        $this->validate([
            'signatureFile' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $user = Auth::user();

        if ($user->signature_path && Storage::disk($user->signature_disk ?? 'local')->exists($user->signature_path)) {
            Storage::disk($user->signature_disk ?? 'local')->delete($user->signature_path);
        }

        $path = $this->signatureFile->store("signatures/{$user->id}", 'local');

        $user->update([
            'signature_path' => $path,
            'signature_disk' => 'local',
        ]);

        $this->signatureFile = null;
        $this->resetValidation();
        session()->flash('status', 'Signature saved successfully.');
    }

    public function remove(): void
    {
        abort_unless(Auth::user()?->hasRole('deputy_md'), 403);

        $user = Auth::user();

        if ($user->signature_path && Storage::disk($user->signature_disk ?? 'local')->exists($user->signature_path)) {
            Storage::disk($user->signature_disk ?? 'local')->delete($user->signature_path);
        }

        $user->update([
            'signature_path' => null,
            'signature_disk' => null,
        ]);

        session()->flash('status', 'Signature removed.');
    }

    public function render()
    {
        $user = Auth::user();
        $signatureUrl = null;

        if ($user->signature_path && Storage::disk($user->signature_disk ?? 'local')->exists($user->signature_path)) {
            $signatureUrl = Storage::disk($user->signature_disk ?? 'local')->url($user->signature_path);
        }

        return view('livewire.hr.signatures.index', [
            'signatureUrl' => $signatureUrl,
            'hasSignature' => $user->signature_path !== null,
        ]);
    }
}
