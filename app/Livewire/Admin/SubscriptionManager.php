<?php

namespace App\Livewire\Admin;

use App\Models\SubscriptionLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SubscriptionManager extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    // Manage-modal state
    public ?int $managingId = null;
    public string $newDate = '';
    public string $note = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function manage(int $id): void
    {
        $user = User::findOrFail($id);
        $this->managingId = $user->id;
        $this->newDate = $user->access_expires_at ? $user->access_expires_at->format('Y-m-d') : '';
        $this->note = '';
        $this->resetErrorBag();
    }

    public function closeManage(): void
    {
        $this->reset('managingId', 'newDate', 'note');
        $this->resetErrorBag();
    }

    /** Quick extend by N months from the current expiry (or from now if lapsed). */
    public function extend(int $months): void
    {
        if (! $this->managingId || ! in_array($months, [1, 3, 6, 12], true)) {
            return;
        }

        $user = User::findOrFail($this->managingId);
        $old = $user->access_expires_at;
        $base = ($old && $old->isFuture()) ? $old : now();
        $new = $base->copy()->addMonths($months);

        $user->update(['access_expires_at' => $new]);
        SubscriptionLog::record($user, 'extend', $old, $new, $months, $this->trimmedNote());

        $this->newDate = $new->format('Y-m-d');
        $this->note = '';
        session()->flash('msg', "Extended {$user->name} by {$months} month(s) — valid until ".$new->format('M d, Y').".");
    }

    /** Set an exact expiry date. */
    public function setDate(): void
    {
        if (! $this->managingId) {
            return;
        }

        $this->validate(
            ['newDate' => ['required', 'date', 'after_or_equal:today']],
            ['newDate.after_or_equal' => 'Pick today or a future date.'],
        );

        $user = User::findOrFail($this->managingId);
        $old = $user->access_expires_at;
        $new = Carbon::parse($this->newDate)->endOfDay();

        $user->update(['access_expires_at' => $new]);
        SubscriptionLog::record($user, 'set', $old, $new, null, $this->trimmedNote());

        $this->note = '';
        session()->flash('msg', "Set {$user->name}'s access to expire ".$new->format('M d, Y').".");
    }

    protected function trimmedNote(): ?string
    {
        $n = trim($this->note);

        return $n === '' ? null : $n;
    }

    public function render()
    {
        $users = User::query()
            ->where('status', 'approved')
            ->where('role', '!=', 'admin')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->orderByRaw('access_expires_at IS NULL')
            ->orderBy('access_expires_at')
            ->paginate(12);

        $managingUser = $this->managingId ? User::find($this->managingId) : null;

        $logs = SubscriptionLog::with(['user', 'admin'])
            ->latest()
            ->limit(50)
            ->get();

        return view('livewire.admin.subscription-manager', [
            'activeTab'    => 'admin.subscriptions',
            'users'        => $users,
            'managingUser' => $managingUser,
            'logs'         => $logs,
        ]);
    }
}
