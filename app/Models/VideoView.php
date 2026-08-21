<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoView extends Model
{
    public $timestamps = false;

    /** Minutes a heartbeat must be apart from the last one, enforced server-side. */
    public const HEARTBEAT_MINUTES = 5;

    protected $fillable = [
        'user_id', 'course_id', 'lesson_id', 'kind', 'ip_address',
        'user_agent', 'watermark_code', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Record a playback. Best-effort: the viewer already has their OTP by this point, so a
     * write failure here must never stop them watching the video they paid for.
     */
    public static function record(int $userId, ?int $courseId, ?int $lessonId, ?string $code, string $kind = 'start'): void
    {
        try {
            static::create([
                'user_id'        => $userId,
                'course_id'      => $courseId,
                'lesson_id'      => $lessonId,
                'kind'           => $kind,
                'ip_address'     => request()->ip(),
                'user_agent'     => mb_substr((string) request()->userAgent(), 0, 255),
                'watermark_code' => $code,
                'created_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Video view not recorded', [
                'user' => $userId, 'lesson' => $lessonId, 'msg' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Periodic "this lesson is still open" marker.
     *
     * The interval is enforced here rather than trusted from the page, so however often the
     * browser calls in, the table only grows at the rate we allow.
     */
    public static function heartbeat(int $userId, ?int $courseId, ?int $lessonId, ?string $code): bool
    {
        $recent = static::where('user_id', $userId)
            ->where('lesson_id', $lessonId)
            ->where('created_at', '>=', now()->subMinutes(self::HEARTBEAT_MINUTES))
            ->exists();

        if ($recent) {
            return false;
        }

        static::record($userId, $courseId, $lessonId, $code, 'heartbeat');

        return true;
    }

    /** Short device label from the user agent, for the admin table. */
    public function getDeviceAttribute(): string
    {
        $ua = (string) $this->user_agent;

        return match (true) {
            str_contains($ua, 'iPhone')             => 'iPhone',
            str_contains($ua, 'iPad')               => 'iPad',
            str_contains($ua, 'Android')            => 'Android',
            str_contains($ua, 'Macintosh')          => 'Mac',
            str_contains($ua, 'Windows')            => 'Windows',
            str_contains($ua, 'Linux')              => 'Linux',
            default                                 => '—',
        };
    }
}
