<?php

namespace Tests\Feature;

use App\Livewire\Admin\CourseManager;
use App\Livewire\Courses\CourseIndex;
use App\Livewire\Courses\CourseShow;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CoursesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_course_and_lesson(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);

        Livewire::actingAs($admin)->test(CourseManager::class)
            ->call('newCourse')
            ->set('c_title', 'Ads Mastery')
            ->call('saveCourse')
            ->assertHasNoErrors();

        $course = Course::first();
        $this->assertNotNull($course);
        $this->assertSame('ads-mastery', $course->slug); // auto-slug

        Livewire::actingAs($admin)->test(CourseManager::class)
            ->call('manageLessons', $course->id)
            ->call('newLesson')
            ->set('l_title', 'Lesson 1')
            ->set('l_video_id', 'abc123videoid')
            ->call('saveLesson')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('lessons', [
            'course_id'          => $course->id,
            'vdocipher_video_id' => 'abc123videoid',
        ]);
    }

    public function test_course_index_lists_published_only(): void
    {
        $user = User::factory()->create(['status' => 'approved']);
        Course::create(['title' => 'Visible Course', 'is_published' => true]);
        Course::create(['title' => 'Hidden Course', 'is_published' => false]);

        Livewire::actingAs($user)->test(CourseIndex::class)
            ->assertSee('Visible Course')
            ->assertDontSee('Hidden Course');
    }

    public function test_player_shows_friendly_error_when_vdocipher_not_configured(): void
    {
        config(['services.vdocipher.secret' => null]);

        $user = User::factory()->create(['status' => 'approved']);
        $course = Course::create(['title' => 'C1', 'is_published' => true]);
        $course->lessons()->create(['title' => 'L1', 'vdocipher_video_id' => 'vid1']);

        Livewire::actingAs($user)->test(CourseShow::class, ['course' => $course])
            ->assertSee('not set up');
    }

    public function test_lesson_requires_video_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $course = Course::create(['title' => 'C', 'is_published' => true]);

        Livewire::actingAs($admin)->test(CourseManager::class)
            ->call('manageLessons', $course->id)
            ->call('newLesson')
            ->set('l_title', 'No video')
            ->set('l_video_id', '')
            ->call('saveLesson')
            ->assertHasErrors(['l_video_id']);
    }
}
