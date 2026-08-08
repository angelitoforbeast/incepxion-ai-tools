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

    #[Url]
    public string $sort = 'newest'; // newest | active

    // Reject-with-remarks modal state
    public ?int $rejectingId = null;
    public string $rejectRemarks = '';

    // Pre-approve / invite modal state
    public bool $showInvite = false;
    public string $inviteEmail = '';
    public bool $inviteAdmin = false;

    public function updating($name): void
    {
        if (in_array($name, ['filter', 'search', 'sort'])) {
            $this->resetPage();
        }
    }

    public function openInvite(): void
    {
        $this->reset('inviteEmail', 'inviteAdmin');
        $this->resetErrorBag();
        $this->showInvite = true;
    }

    public function invite(): void
    {
        $this->validate([
            'inviteEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        User::create([
            'name'              => \Illuminate\Support\Str::before($this->inviteEmail, '@'),
            'email'             => $this->inviteEmail,
            'password'          => null,
            'status'            => 'approved',
            'role'              => $this->inviteAdmin ? 'admin' : 'user',
            'email_verified_at' => now(),
            'approved_at'       => now(),
            'approved_by'       => auth()->id(),
        ]);

        session()->flash('msg', "Pre-approved {$this->inviteEmail}. They get access the moment they sign in with Google.");
        $this->reset('inviteEmail', 'inviteAdmin', 'showInvite');
    }

    public function approve(int $id): void
    {
        $user = User::findOrFail($id);
        $user->update([
            'status'            => 'approved',
            'approved_at'       => now(),
            'approved_by'       => auth()->id(),
            'remarks'           => null,
            'access_expires_at' => $user->access_expires_at ?? now()->addMonths(User::DEFAULT_VALIDITY_MONTHS),
        ]);
        session()->flash('msg', "{$user->name} has been approved (valid until ".$user->fresh()->access_expires_at->format('M d, Y').").");
    }

    /** Extend a user's validity by N months (from their current expiry, or from now if lapsed). */
    public function extendAccess(int $id, int $months): void
    {
        if (! in_array($months, [1, 3, 6, 12], true)) {
            return;
        }
        $user = User::findOrFail($id);
        $base = ($user->access_expires_at && $user->access_expires_at->isFuture()) ? $user->access_expires_at : now();
        $user->update(['access_expires_at' => $base->copy()->addMonths($months)]);
        session()->flash('msg', "Extended {$user->name} by {$months} month(s) — valid until ".$user->fresh()->access_expires_at->format('M d, Y').".");
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
        $u = User::findOrFail($id);
        $expiry = ($u->access_expires_at && $u->access_expires_at->isFuture()) ? $u->access_expires_at : now()->addMonths(User::DEFAULT_VALIDITY_MONTHS);
        $u->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id(), 'remarks' => null, 'access_expires_at' => $expiry]);
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


    public function render()
    {
        $users = User::query()
            ->when($this->filter !== 'all', fn ($q) => $q->where('status', $this->filter))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->with('plan')
            ->withMax('generations', 'created_at')
            ->withCount('generations')
            ->when($this->sort === 'active',
                fn ($q) => $q->orderByRaw('last_active_at IS NULL')
                    ->orderByDesc('last_active_at')
                    ->orderByDesc('generations_max_created_at')
                    ->orderByDesc('last_login_at'),
                fn ($q) => $q->latest(),
            )
            ->paginate(12);

        return view('livewire.admin.user-manager', [
            'activeTab' => 'admin.users',
            'users' => $users,
            'stats' => [
                'total'     => User::count(),
                'pending'   => User::where('status', 'pending')->count(),
                'approved'  => User::where('status', 'approved')->count(),
                'active7d'  => User::whereHas('generations', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))->count(),
            ],
        ]);
    }
}
