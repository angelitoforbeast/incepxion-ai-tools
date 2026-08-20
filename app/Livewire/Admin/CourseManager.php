<?php

namespace App\Livewire\Admin;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Setting;
use App\Services\VdoCipherService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Admin · Courses')]
class CourseManager extends Component
{
    // Watermark settings (global)
    public string $wm_color = '#FF3333';
    public int $wm_size = 12;
    public int $wm_opacity = 50;
    public int $wm_speed = 6000;
    public bool $wm_two_tone = true;
    public string $wm_position = 'top-left';

    public function mount(): void
    {
        $wm = array_merge(VdoCipherService::watermarkDefaults(), (array) Setting::get('watermark', []));
        $this->wm_color    = '#'.ltrim((string) $wm['color'], '#');
        $this->wm_size     = (int) $wm['size'];
        $this->wm_opacity  = (int) $wm['opacity'];
        $this->wm_speed    = (int) $wm['speed'];
        $this->wm_two_tone = (bool) $wm['two_tone'];
        $this->wm_position = (string) $wm['position'];
    }

    public function saveWatermark(): void
    {
        $this->validate([
            'wm_color'    => ['required', 'string', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'wm_size'     => ['integer', 'min:6', 'max:60'],
            'wm_opacity'  => ['integer', 'min:5', 'max:100'],
            'wm_speed'    => ['integer', 'in:3000,5000,6000,8000'],
            'wm_position' => ['in:top-left,top-right,bottom-left,bottom-right'],
        ]);

        Setting::put('watermark', [
            'color'    => ltrim($this->wm_color, '#'),
            'size'     => $this->wm_size,
            'opacity'  => $this->wm_opacity,
            'speed'    => $this->wm_speed,
            'two_tone' => $this->wm_two_tone,
            'position' => $this->wm_position,
        ]);

        session()->flash('msg', 'Watermark settings saved. Applies to the next video load.');
    }

    // Course form
    public ?int $editingCourseId = null;
    public string $c_title = '';
    public string $c_description = '';
    public string $c_thumbnail_url = '';
    public bool $c_published = true;
    public int $c_sort = 0;
    public bool $showCourseForm = false;

    // Which course's lessons are being managed
    public ?int $managingCourseId = null;

    // Lesson form
    public ?int $editingLessonId = null;
    public string $l_title = '';
    public string $l_video_id = '';
    public string $l_description = '';
    public bool $l_free = false;
    public int $l_sort = 0;
    public bool $showLessonForm = false;

    // ---------- Courses ----------
    public function newCourse(): void
    {
        $this->reset('editingCourseId', 'c_title', 'c_description', 'c_thumbnail_url');
        $this->c_published = true;
        $this->c_sort = Course::max('sort_order') + 1;
        $this->showCourseForm = true;
    }

    public function editCourse(int $id): void
    {
        $c = Course::findOrFail($id);
        $this->editingCourseId = $c->id;
        $this->c_title = $c->title;
        // Legacy plain-text descriptions get line breaks turned into HTML so the
        // rich-text editor shows them correctly (and saves as HTML going forward).
        $desc = (string) $c->description;
        $this->c_description = ($desc === '' || \App\Support\RichText::isHtml($desc)) ? $desc : nl2br(e($desc));
        $this->c_thumbnail_url = (string) $c->thumbnail_url;
        $this->c_published = $c->is_published;
        $this->c_sort = $c->sort_order;
        $this->showCourseForm = true;
    }

    public function saveCourse(): void
    {
        $this->validate([
            'c_title'         => ['required', 'string', 'max:200'],
            'c_description'   => ['nullable', 'string', 'max:20000'],
            'c_thumbnail_url' => ['nullable', 'url', 'max:500'],
            'c_sort'          => ['integer', 'min:0'],
        ]);

        // Treat an "empty" rich-text value (e.g. "<p><br></p>") as no description.
        $desc = trim((string) $this->c_description);
        $desc = trim(strip_tags($desc)) === '' ? null : \App\Support\RichText::clean($desc);

        Course::updateOrCreate(['id' => $this->editingCourseId], [
            'title'         => $this->c_title,
            'description'   => $desc,
            'thumbnail_url' => $this->c_thumbnail_url ?: null,
            'is_published'  => $this->c_published,
            'sort_order'    => $this->c_sort,
        ]);

        $this->showCourseForm = false;
        session()->flash('msg', 'Course saved.');
    }

    public function deleteCourse(int $id): void
    {
        Course::whereKey($id)->delete(); // cascades lessons
        if ($this->managingCourseId === $id) {
            $this->managingCourseId = null;
        }
        session()->flash('msg', 'Course deleted.');
    }

    // ---------- Lessons ----------
    public function manageLessons(int $courseId): void
    {
        $this->managingCourseId = $courseId;
        $this->showLessonForm = false;
    }

    public function newLesson(): void
    {
        $this->reset('editingLessonId', 'l_title', 'l_video_id', 'l_description');
        $this->l_free = false;
        $this->l_sort = (Lesson::where('course_id', $this->managingCourseId)->max('sort_order') ?? 0) + 1;
        $this->showLessonForm = true;
    }

    public function editLesson(int $id): void
    {
        $l = Lesson::findOrFail($id);
        $this->editingLessonId = $l->id;
        $this->managingCourseId = $l->course_id;
        $this->l_title = $l->title;
        $this->l_video_id = $l->vdocipher_video_id;
        $desc = (string) $l->description;
        $this->l_description = ($desc === '' || \App\Support\RichText::isHtml($desc)) ? $desc : nl2br(e($desc));
        $this->l_free = $l->is_free_preview;
        $this->l_sort = $l->sort_order;
        $this->showLessonForm = true;
    }

    public function saveLesson(): void
    {
        $this->validate([
            'l_title'       => ['required', 'string', 'max:200'],
            'l_video_id'    => ['required', 'string', 'max:120'],
            'l_description' => ['nullable', 'string', 'max:20000'],
            'l_sort'        => ['integer', 'min:0'],
        ]);

        // Normalize empty rich-text to null; otherwise store sanitized HTML.
        $ldesc = trim((string) $this->l_description);
        $ldesc = trim(strip_tags($ldesc)) === '' ? null : \App\Support\RichText::clean($ldesc);

        Lesson::updateOrCreate(['id' => $this->editingLessonId], [
            'course_id'          => $this->managingCourseId,
            'title'              => $this->l_title,
            'vdocipher_video_id' => trim($this->l_video_id),
            'description'        => $ldesc,
            'is_free_preview'    => $this->l_free,
            'sort_order'         => $this->l_sort,
        ]);

        $this->showLessonForm = false;
        session()->flash('msg', 'Lesson saved.');
    }

    public function deleteLesson(int $id): void
    {
        Lesson::whereKey($id)->delete();
        session()->flash('msg', 'Lesson deleted.');
    }

    public function render()
    {
        return view('livewire.admin.course-manager', [
            'activeTab'      => 'admin.courses',
            'courses'        => Course::withCount('lessons')->orderBy('sort_order')->orderBy('id')->get(),
            'managingCourse' => $this->managingCourseId ? Course::with('lessons')->find($this->managingCourseId) : null,
        ]);
    }
}
