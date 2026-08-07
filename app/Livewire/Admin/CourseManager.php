<?php

namespace App\Livewire\Admin;

use App\Models\Course;
use App\Models\Lesson;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CourseManager extends Component
{
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
        $this->c_description = (string) $c->description;
        $this->c_thumbnail_url = (string) $c->thumbnail_url;
        $this->c_published = $c->is_published;
        $this->c_sort = $c->sort_order;
        $this->showCourseForm = true;
    }

    public function saveCourse(): void
    {
        $this->validate([
            'c_title'         => ['required', 'string', 'max:200'],
            'c_description'   => ['nullable', 'string', 'max:4000'],
            'c_thumbnail_url' => ['nullable', 'url', 'max:500'],
            'c_sort'          => ['integer', 'min:0'],
        ]);

        Course::updateOrCreate(['id' => $this->editingCourseId], [
            'title'         => $this->c_title,
            'description'   => $this->c_description ?: null,
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
        $this->l_description = (string) $l->description;
        $this->l_free = $l->is_free_preview;
        $this->l_sort = $l->sort_order;
        $this->showLessonForm = true;
    }

    public function saveLesson(): void
    {
        $this->validate([
            'l_title'       => ['required', 'string', 'max:200'],
            'l_video_id'    => ['required', 'string', 'max:120'],
            'l_description' => ['nullable', 'string', 'max:2000'],
            'l_sort'        => ['integer', 'min:0'],
        ]);

        Lesson::updateOrCreate(['id' => $this->editingLessonId], [
            'course_id'          => $this->managingCourseId,
            'title'              => $this->l_title,
            'vdocipher_video_id' => trim($this->l_video_id),
            'description'        => $this->l_description ?: null,
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
            'courses'        => Course::withCount('lessons')->orderBy('sort_order')->orderBy('id')->get(),
            'managingCourse' => $this->managingCourseId ? Course::with('lessons')->find($this->managingCourseId) : null,
        ]);
    }
}
