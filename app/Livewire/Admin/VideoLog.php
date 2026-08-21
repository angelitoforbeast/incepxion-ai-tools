<?php

namespace App\Livewire\Admin;

use App\Models\Lesson;
use App\Models\User;
use App\Models\VideoView;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Admin · Video Log')]
class VideoLog extends Component
{
    use WithPagination;

    /** An account watching from more than this many IPs in a day looks shared. */
    private const SHARING_IP_THRESHOLD = 3;

    private const SHARING_WINDOW_HOURS = 24;

    #[Url(as: 'u', except: '')]
    public string $userId = '';

    #[Url(as: 'l', except: '')]
    public string $lessonId = '';

    #[Url(as: 'ip', except: '')]
    public string $ip = '';

    #[Url(as: 'from', except: '')]
    public string $from = '';

    #[Url(as: 'to', except: '')]
    public string $to = '';

    public int $perPage = 50;

    public function updated($property): void
    {
        if ($property !== 'perPage') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('userId', 'lessonId', 'ip', 'from', 'to');
        $this->resetPage();
    }

    /** Jump straight from a sharing alert into that account's history. */
    public function focusUser(int $id): void
    {
        $this->reset('lessonId', 'ip', 'from', 'to');
        $this->userId = (string) $id;
        $this->resetPage();
    }

    public function getActiveFiltersProperty(): int
    {
        return (int) ($this->userId !== '') + (int) ($this->lessonId !== '')
            + (int) ($this->ip !== '') + (int) ($this->from !== '') + (int) ($this->to !== '');
    }

    /**
     * Accounts watching from several IPs at once — the everyday abuse this log catches,
     * far more common than a leaked recording.
     */
    public function getSharingProperty()
    {
        $since = now()->subHours(self::SHARING_WINDOW_HOURS);

        return VideoView::query()
            ->where('created_at', '>=', $since)
            ->whereNotNull('ip_address')
            ->select('user_id')
            ->selectRaw('COUNT(DISTINCT ip_address) as ips')
            ->selectRaw('COUNT(*) as views')
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT ip_address) >= ?', [self::SHARING_IP_THRESHOLD])
            ->orderByDesc('ips')
            ->with('user:id,name,email')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        $rows = VideoView::query()
            ->with(['user:id,name,email', 'lesson:id,title'])
            ->when($this->userId !== '', fn ($q) => $q->where('user_id', (int) $this->userId))
            ->when($this->lessonId !== '', fn ($q) => $q->where('lesson_id', (int) $this->lessonId))
            ->when($this->ip !== '', fn ($q) => $q->where('ip_address', 'like', trim($this->ip).'%'))
            ->when($this->from !== '', fn ($q) => $q->where('created_at', '>=', $this->from.' 00:00:00'))
            ->when($this->to !== '', fn ($q) => $q->where('created_at', '<=', $this->to.' 23:59:59'))
            ->latest('id')
            ->paginate($this->perPage);

        return view('livewire.admin.video-log', [
            'activeTab' => 'admin.video-log',
            'rows'      => $rows,
            'users'     => User::whereIn('id', DB::table('video_views')->distinct()->pluck('user_id'))
                ->orderBy('name')->get(['id', 'name', 'email']),
            'lessons'   => Lesson::whereIn('id', DB::table('video_views')->whereNotNull('lesson_id')->distinct()->pluck('lesson_id'))
                ->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
