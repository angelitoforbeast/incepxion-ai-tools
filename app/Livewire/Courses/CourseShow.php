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
            // Per-viewer forensic watermark: email · name · code · IP (captured at playback
            // time). The code is what still identifies the account if the email or name is
            // changed later — those are editable, the code is derived from the user id.
            $code = \App\Support\WatermarkCode::for(auth()->id());
            $parts = array_filter([
                auth()->user()->email ?? 'guest',
                auth()->user()->name,
                'WM-'.$code,
                request()->ip(),
            ]);
            $watermark = implode(' · ', $parts);
            $res = $service->otp($lesson->vdocipher_video_id, $watermark);
            $this->otp = $res['otp'];
            $this->playbackInfo = $res['playbackInfo'];

            // Nothing plays without the OTP above, so recording it here is something the
            // viewer cannot avoid — the one part of this that isn't in their hands.
            \App\Models\VideoView::record(auth()->id(), $this->course->id, $lesson->id, $code);
        } catch (\Throwable $e) {
            $this->error = 'Could not load the video. '.$e->getMessage();
        }
    }

    /**
     * Called by the page while a lesson is open, so a session paused and resumed the next
     * day is recorded at the time and IP it was actually resumed from, not just when it was
     * first loaded. The lesson comes from server-side state, not from the request.
     */
    public function heartbeat(): void
    {
        if (! $this->lessonId || ! $this->otp) {
            return; // nothing loaded, or playback failed
        }

        \App\Models\VideoView::heartbeat(
            auth()->id(),
            $this->course->id,
            $this->lessonId,
            \App\Support\WatermarkCode::for(auth()->id()),
        );
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
