<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Course extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'thumbnail_url', 'is_published', 'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Course $course) {
            if (blank($course->slug) && filled($course->title)) {
                $base = Str::slug($course->title) ?: 'course';
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->where('id', '!=', $course->id ?? 0)->exists()) {
                    $slug = $base.'-'.(++$i);
                }
                $course->slug = $slug;
            }
        });
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
