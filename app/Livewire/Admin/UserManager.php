<?php

namespace App\Livewire\Admin;

use App\Models\Generation;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Admin · Users')]
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

        $this->dispatch('notify', message: "Pre-approved {$this->inviteEmail}. They get access the moment they sign in.", type: 'success');
        $this->reset('inviteEmail', 'inviteAdmin', 'showInvite');
    }

    public function approve(int $id): void
    {
        $user = User::findOrFail($id);
        $old = $user->access_expires_at;
        $new = $old ?? now()->addMonths(User::DEFAULT_VALIDITY_MONTHS);
        $user->update([
            'status'            => 'approved',
            'approved_at'       => now(),
            'approved_by'       => auth()->id(),
            'remarks'           => null,
            'access_expires_at' => $new,
        ]);
        \App\Models\SubscriptionLog::record($user, 'approve', $old, $new, null, null);
        $this->dispatch('notify', message: "✅ {$user->name} approved — access until ".$user->fresh()->access_expires_at->format('M d, Y').".", type: 'success');
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
            $user = User::findOrFail($this->rejectingId);
            $user->update([
                'status'  => 'rejected',
                'remarks' => trim($this->rejectRemarks),
            ]);
            $this->dispatch('notify', message: "🚫 {$user->name} rejected.", type: 'success');
        }

        $this->cancelReject();
    }

    public function suspend(int $id): void
    {
        User::whereKey($id)->update(['status' => 'suspended']);
        $this->dispatch('notify', message: 'User suspended.', type: 'success');
    }

    public function reinstate(int $id): void
    {
        $u = User::findOrFail($id);
        $old = $u->access_expires_at;
        $expiry = ($old && $old->isFuture()) ? $old : now()->addMonths(User::DEFAULT_VALIDITY_MONTHS);
        $u->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id(), 'remarks' => null, 'access_expires_at' => $expiry]);
        \App\Models\SubscriptionLog::record($u, 'reinstate', $old, $expiry, null, null);
        $this->dispatch('notify', message: "{$u->name} reinstated.", type: 'success');
    }

    /** Move a user back to the pending queue for re-review (revokes access). */
    public function resetToPending(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('notify', message: "You can't move your own account to pending.", type: 'error');

            return;
        }

        $user = User::findOrFail($id);
        $user->update([
            'status'      => 'pending',
            'approved_at' => null,
            'approved_by' => null,
            'remarks'     => null,
        ]);
        $this->dispatch('notify', message: "{$user->name} moved back to pending.", type: 'success');
    }

    public function makeAdmin(int $id): void
    {
        User::whereKey($id)->update(['role' => 'admin', 'status' => 'approved']);
        $this->dispatch('notify', message: 'User is now an admin.', type: 'success');
    }

    public function removeAdmin(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('notify', message: "You can't remove your own admin role.", type: 'error');

            return;
        }

        User::whereKey($id)->update(['role' => 'user']);
        $this->dispatch('notify', message: 'Admin role removed.', type: 'success');
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
