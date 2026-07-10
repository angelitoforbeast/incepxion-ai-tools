<?php

namespace App\Livewire\Admin;

use App\Models\Generation;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class GenerationLog extends Component
{
    use WithPagination;

    public ?int $expanded = null;

    public function toggle(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    public function render()
    {
        return view('livewire.admin.generation-log', [
            'logs' => Generation::with(['user', 'tool'])->latest()->paginate(20),
        ]);
    }
}
