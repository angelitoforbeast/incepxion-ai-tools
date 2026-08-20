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
            'activeTab' => 'admin.logs',
            // Order by the primary key (not created_at): id is auto-increment so this is
            // the same newest-first order, but it uses the PK index instead of a filesort.
            // A filesort on `select *` tries to pack each full row — including the large
            // generated-content column — into the sort buffer and blows it (SQLSTATE HY001,
            // "Out of sort memory") even with only a handful of rows.
            'logs'      => Generation::with(['user', 'tool'])->latest('id')->paginate(20),
        ]);
    }
}
