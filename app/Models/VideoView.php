<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'course_id', 'lesson_id', 'ip_address',
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
    public static function record(int $userId, ?int $courseId, ?int $lessonId, ?string $code): void
    {
        try {
            static::create([
                'user_id'        => $userId,
                'course_id'      => $courseId,
                'lesson_id'      => $lessonId,
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
