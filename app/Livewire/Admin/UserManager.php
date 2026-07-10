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
        ]);
        session()->flash('msg', "{$user->name} ay na-approve na.");
    }

    public function reject(int $id): void
    {
        User::whereKey($id)->update(['status' => 'rejected']);
        session()->flash('msg', 'Na-reject ang user.');
    }

    public function suspend(int $id): void
    {
        User::whereKey($id)->update(['status' => 'suspended']);
        session()->flash('msg', 'Na-suspend ang user.');
    }

    public function reinstate(int $id): void
    {
        User::whereKey($id)->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id()]);
        session()->flash('msg', 'Na-reinstate ang user.');
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
