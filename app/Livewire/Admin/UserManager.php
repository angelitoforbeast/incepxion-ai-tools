<?php

namespace App\Livewire\Admin;

use App\Models\Generation;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class UserManager extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'pending';

    #[Url]
    public string $search = '';

    // Reject-with-remarks modal state
    public ?int $rejectingId = null;
    public string $rejectRemarks = '';

    public function updating($name): void
    {
        if (in_array($name, ['filter', 'search'])) {
            $this->resetPage();
        }
    }

    public function approve(int $id): void
    {
        $user = User::findOrFail($id);
        $user->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'remarks'     => null,
        ]);
        session()->flash('msg', "{$user->name} has been approved.");
    }

    public function startReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->rejectRemarks = '';
        $this->resetErrorBag();
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->rejectRemarks = '';
    }

    public function confirmReject(): void
    {
        $this->validate(
            ['rejectRemarks' => ['required', 'string', 'max:1000']],
            ['rejectRemarks.required' => 'Please provide a reason for rejection.'],
        );

        if ($this->rejectingId) {
            User::whereKey($this->rejectingId)->update([
                'status'  => 'rejected',
                'remarks' => trim($this->rejectRemarks),
            ]);
            session()->flash('msg', 'User rejected with remarks.');
        }

        $this->cancelReject();
    }

    public function suspend(int $id): void
    {
        User::whereKey($id)->update(['status' => 'suspended']);
        session()->flash('msg', 'User suspended.');
    }

    public function reinstate(int $id): void
    {
        User::whereKey($id)->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id(), 'remarks' => null]);
        session()->flash('msg', 'User reinstated.');
    }

    public function makeAdmin(int $id): void
    {
        User::whereKey($id)->update(['role' => 'admin', 'status' => 'approved']);
        session()->flash('msg', 'User is now an admin.');
    }

    public function removeAdmin(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('msg', "You can't remove your own admin role.");

            return;
        }

        User::whereKey($id)->update(['role' => 'user']);
        session()->flash('msg', 'Admin role removed.');
    }

    public function deleteUser(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('msg', "You can't delete your own account.");

            return;
        }

        $user = User::find($id);
        if ($user) {
            $name = $user->name;
            $user->delete(); // cascades: social accounts, API keys, subscriptions, generations, usage
            session()->flash('msg', "{$name}'s account and all its data have been deleted.");
        }
    }

    public function render()
    {
        $users = User::query()
            ->when($this->filter !== 'all', fn ($q) => $q->where('status', $this->filter))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->with('plan')
            ->latest()
            ->paginate(12);

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'stats' => [
                'total'    => User::count(),
                'pending'  => User::where('status', 'pending')->count(),
                'approved' => User::where('status', 'approved')->count(),
                'gensToday' => Generation::whereDate('created_at', today())->count(),
            ],
        ]);
    }
}
