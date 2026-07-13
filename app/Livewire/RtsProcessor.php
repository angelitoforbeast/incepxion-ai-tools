<?php

namespace App\Livewire;

use App\Jobs\ProcessRtsUpload;
use App\Models\FromJnt;
use App\Models\RtsUpload;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class RtsProcessor extends Component
{
    use WithFileUploads;

    public string $tab = 'upload';

    // Upload tab
    public $file = null;
    public ?string $batchAt = null;
    public ?int $currentUploadId = null;
    public ?string $error = null;

    // Monitoring tab
    public string $from = '';
    public string $to = '';

    public function mount(): void
    {
        $this->from = Carbon::now('Asia/Manila')->subMonthNoOverflow()->startOfMonth()->toDateString();
        $this->to   = Carbon::now('Asia/Manila')->toDateString();

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
            'file'    => ['required', 'file', 'mimes:zip,csv,xlsx', 'max:102400'],
            'batchAt' => ['nullable', 'string'],
        ], [], ['file' => 'file']);

        $batch = null;
        if (! empty($this->batchAt)) {
            try {
                $batch = Carbon::createFromFormat('Y-m-d\TH:i', $this->batchAt, 'Asia/Manila');
            } catch (\Throwable $e) {
                try {
                    $batch = Carbon::parse($this->batchAt, 'Asia/Manila');
                } catch (\Throwable $e2) {
                    $batch = null;
                }
            }
        }

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
            'batch_at'      => $batch,
        ]);

        ProcessRtsUpload::dispatch($upload->id);

        $this->currentUploadId = $upload->id;
        $this->reset('file', 'batchAt', 'error');
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
        if (! $upload || ! in_array($upload->status, ['needs_confirmation', 'queued'], true)) {
            return;
        }
        try {
            if ($upload->path && Storage::disk($upload->disk ?: 'local')->exists($upload->path)) {
                Storage::disk($upload->disk ?: 'local')->delete($upload->path);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        $upload->update(['status' => 'canceled', 'finished_at' => now()]);
        $this->currentUploadId = null;
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

    /** Aggregated RTS monitoring rows for the selected date range (this user only). */
    private function monitoringResults(): array
    {
        if (! $this->from || ! $this->to) {
            return [];
        }

        $fromDt = Carbon::parse($this->from, 'Asia/Manila')->startOfDay();
        $toDt   = Carbon::parse($this->to, 'Asia/Manila')->endOfDay();

        $rows = DB::table('from_jnts')
            ->where('user_id', auth()->id())
            ->whereBetween('submission_time', [$fromDt, $toDt])
            ->selectRaw("
                COALESCE(sender,'')    as sender,
                COALESCE(item_name,'') as item_name,
                COALESCE(cod,'')       as cod,
                COUNT(*)               as quantity,
                MIN(submission_time)   as min_sub,
                MAX(submission_time)   as max_sub,
                SUM(CASE
                    WHEN LOWER(status) LIKE '%return%' OR LOWER(status) LIKE '%rts%' THEN 1
                    WHEN TRIM(COALESCE(rts_reason,'')) <> ''
                         AND LOWER(status) NOT LIKE '%delivered%'
                         AND LOWER(status) NOT LIKE '%returned%' THEN 1
                    ELSE 0 END) as rts_count,
                SUM(CASE WHEN LOWER(status) LIKE '%delivered%' THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN LOWER(status) LIKE '%problem%'   THEN 1 ELSE 0 END) as problematic_count,
                SUM(CASE WHEN LOWER(status) LIKE '%detain%'    THEN 1 ELSE 0 END) as detained_count
            ")
            ->groupBy('sender', 'item_name', 'cod')
            ->get();

        $fmt = function ($v) {
            try {
                return Carbon::parse($v, 'Asia/Manila')->format('Y-m-d');
            } catch (\Throwable $e) {
                return (string) $v;
            }
        };

        return collect($rows)->map(function ($r) use ($fmt) {
            $total       = max(1, (int) $r->quantity);
            $rts         = (int) $r->rts_count;
            $delivered   = (int) $r->delivered_count;
            $problematic = (int) $r->problematic_count;
            $detained    = (int) $r->detained_count;

            $rtsPct       = round($rts / $total * 100, 2);
            $deliveredPct = round($delivered / $total * 100, 2);
            $transitPct   = round(max(0, 100 - $rtsPct - $deliveredPct), 2);

            $currentBase = $rts + $delivered;
            $currentRts  = $currentBase > 0 ? round($rts / $currentBase * 100, 2) : null;

            $maxBase = $rts + $problematic + $detained + $delivered;
            $maxRts  = $maxBase > 0 ? round(($rts + $problematic + $detained) / $maxBase * 100, 2) : null;

            return [
                'date_range'        => $fmt($r->min_sub).' to '.$fmt($r->max_sub),
                'sender'            => trim((string) $r->sender),
                'item'              => trim((string) $r->item_name),
                'cod'               => trim((string) $r->cod),
                'quantity'          => (int) $r->quantity,
                'rts_count'         => $rts,
                'delivered_count'   => $delivered,
                'transit_count'     => max(0, (int) $r->quantity - $rts - $delivered),
                'rts_percent'       => $rtsPct,
                'delivered_percent' => $deliveredPct,
                'transit_percent'   => $transitPct,
                'current_rts'       => $currentRts,
                'max_rts'           => $maxRts,
            ];
        })->sortByDesc('rts_percent')->values()->all();
    }

    public function render()
    {
        $current = $this->ownedUpload($this->currentUploadId);

        $history = RtsUpload::where('user_id', auth()->id())
            ->latest('id')->limit(30)->get();

        $results = $this->tab === 'monitoring' ? $this->monitoringResults() : [];

        return view('livewire.rts-processor', [
            'current' => $current,
            'history' => $history,
            'results' => $results,
        ]);
    }
}
