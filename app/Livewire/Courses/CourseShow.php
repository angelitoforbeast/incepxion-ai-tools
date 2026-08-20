<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Models\Lesson;
use App\Services\VdoCipherService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Course')]
class CourseShow extends Component
{
    public Course $course;
    public ?int $lessonId = null;

    // Player state for the selected lesson.
    public ?string $otp = null;
    public ?string $playbackInfo = null;
    public ?string $error = null;

    public function mount(Course $course): void
    {
        abort_unless($course->is_published, 404);
        $this->course = $course;

        $first = $course->lessons()->first();
        if ($first) {
            $this->selectLesson($first->id);
        }
    }

    public function selectLesson(int $lessonId): void
    {
        $lesson = $this->course->lessons()->whereKey($lessonId)->first();
        if (! $lesson) {
            return;
        }

        $this->lessonId = $lesson->id;
        $this->otp = $this->playbackInfo = $this->error = null;

        $service = app(VdoCipherService::class);
        if (! $service->configured()) {
            $this->error = 'Video playback is not set up yet. (Admin: add VDOCIPHER_API_SECRET.)';

            return;
        }

        try {
            // Per-viewer forensic watermark: email · name · IP (captured at playback time).
            $parts = array_filter([
                auth()->user()->email ?? 'guest',
                auth()->user()->name,
                request()->ip(),
            ]);
            $watermark = implode(' · ', $parts);
            $res = $service->otp($lesson->vdocipher_video_id, $watermark);
            $this->otp = $res['otp'];
            $this->playbackInfo = $res['playbackInfo'];
        } catch (\Throwable $e) {
            $this->error = 'Could not load the video. '.$e->getMessage();
        }
    }

    public function getCurrentLessonProperty(): ?Lesson
    {
        return $this->lessonId
            ? $this->course->lessons()->whereKey($this->lessonId)->first()
            : null;
    }

    public function render()
    {
        return view('livewire.courses.course-show', [
            'lessons'       => $this->course->lessons()->get(),
            'currentLesson' => $this->currentLesson,
        ]);
    }
}
