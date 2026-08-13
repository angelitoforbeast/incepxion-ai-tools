<?php

namespace App\Livewire;

use App\Jobs\ProcessRtsUpload;
use App\Models\RtsUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class RtsProcessor extends Component
{
    use WithFileUploads;

    public $file = null;
    public ?int $currentUploadId = null;
    public ?string $error = null;

    public function mount(): void
    {
        // Resume watching an in-flight upload if one exists.
        $active = RtsUpload::where('user_id', auth()->id())
            ->whereIn('status', ['queued', 'scanning', 'processing', 'needs_confirmation'])
            ->latest('id')->first();
        if ($active) {
            $this->currentUploadId = $active->id;
        }
    }

    public function submitUpload(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:zip,csv,xlsx', 'max:102400'],
        ], [], ['file' => 'file']);

        $folder   = 'uploads/rts/'.now()->format('Y-m-d');
        $basename = Str::slug(pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = $basename.'__'.now()->format('His').'.'.$this->file->getClientOriginalExtension();
        $path     = $this->file->storeAs($folder, $filename, 'local');

        $upload = RtsUpload::create([
            'user_id'       => auth()->id(),
            'original_name' => $this->file->getClientOriginalName(),
            'disk'          => 'local',
            'path'          => $path,
            'status'        => 'queued',
        ]);

        ProcessRtsUpload::dispatch($upload->id);

        $this->currentUploadId = $upload->id;
        $this->reset('file', 'error');
    }

    public function confirmUpload(): void
    {
        $upload = $this->ownedUpload($this->currentUploadId);
        if (! $upload || $upload->status !== 'needs_confirmation') {
            return;
        }
        $upload->update(['status' => 'processing']);
        ProcessRtsUpload::dispatch($upload->id, true);
    }

    public function cancelUpload(): void
    {
        $upload = $this->ownedUpload($this->currentUploadId);
        if (! $upload || ! in_array($upload->status, ['needs_confirmation', 'queued', 'scanning', 'processing'], true)) {
            return;
        }

        // Discard the file and finalize immediately so the UI updates right away —
        // robust even if the worker already died. The `canceled_at` flag also makes
        // a still-running job abort at its next checkpoint before writing any data.
        try {
            if ($upload->path && Storage::disk($upload->disk ?: 'local')->exists($upload->path)) {
                Storage::disk($upload->disk ?: 'local')->delete($upload->path);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $upload->update([
            'canceled_at' => now(),
            'status'      => 'canceled',
            'finished_at' => now(),
        ]);
    }

    public function dismissCurrent(): void
    {
        $this->currentUploadId = null;
    }

    /** No-op target for wire:poll — render() reloads the live upload row. */
    public function poll(): void {}

    private function ownedUpload(?int $id): ?RtsUpload
    {
        if (! $id) {
            return null;
        }

        return RtsUpload::where('user_id', auth()->id())->find($id);
    }

    public function render()
    {
        return view('livewire.rts-processor', [
            'current' => $this->ownedUpload($this->currentUploadId),
            'history' => RtsUpload::where('user_id', auth()->id())->latest('id')->limit(30)->get(),
        ]);
    }
}
