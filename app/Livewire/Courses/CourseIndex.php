<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CourseIndex extends Component
{
    public function render()
    {
        return view('livewire.courses.course-index', [
            'courses' => Course::where('is_published', true)
                ->withCount('lessons')
                ->orderBy('sort_order')->orderBy('id')
                ->get(),
        ]);
    }
}
