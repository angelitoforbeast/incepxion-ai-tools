<?php

namespace App\Livewire\Admin;

use App\Models\FromJnt;
use App\Models\RtsUpload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Admin · RTS Data')]
class RtsData extends Component
{
    use WithPagination;

    public string $search = '';

    // Delete-confirmation modal state
    public ?int $deletingUserId = null;
    public string $deleteConfirm = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $userId): void
    {
        $this->deletingUserId = $userId;
        $this->deleteConfirm = '';
        $this->resetErrorBag();
    }

    public function cancelDelete(): void
    {
        $this->reset('deletingUserId', 'deleteConfirm');
        $this->resetErrorBag();
    }

    public function deleteData(): void
    {
        if (! $this->deletingUserId) {
            return;
        }

        // Hard gate: the admin must type DELETE exactly.
        if (trim($this->deleteConfirm) !== 'DELETE') {
            $this->addError('deleteConfirm', 'Type DELETE (all caps) to confirm.');

            return;
        }

        $user = User::findOrFail($this->deletingUserId);

        // Best-effort cleanup of any stored upload files.
        RtsUpload::where('user_id', $user->id)->get()->each(function (RtsUpload $u) {
            if ($u->path) {
                try {
                    Storage::disk($u->disk ?: 'local')->delete($u->path);
                } catch (\Throwable $e) {
                    // ignore — file may already be gone
                }
            }
        });

        $records = FromJnt::where('user_id', $user->id)->delete();
        $uploads = RtsUpload::where('user_id', $user->id)->delete();

        $this->cancelDelete();
        $this->dispatch('notify', message: "🗑️ Deleted {$records} RTS records + {$uploads} uploads for {$user->name}.", type: 'success');
    }

    public function render()
    {
        $rows = User::query()
            ->where(fn ($q) => $q->has('fromJnts')->orHas('rtsUploads'))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->withCount(['fromJnts', 'rtsUploads'])
            ->withMax('rtsUploads', 'batch_at')
            ->orderByDesc('from_jnts_count')
            ->paginate(15);

        $deletingUser = $this->deletingUserId ? User::withCount(['fromJnts', 'rtsUploads'])->find($this->deletingUserId) : null;

        return view('livewire.admin.rts-data', [
            'activeTab'     => 'admin.rts',
            'rows'          => $rows,
            'deletingUser'  => $deletingUser,
        ]);
    }
}
