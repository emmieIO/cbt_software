<?php

namespace Tests\Unit\Repositories;

use App\Models\School;
use App\Models\User;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected EloquentUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentUserRepository;
        \Spatie\Permission\Models\Role::create(['name' => 'candidate', 'category' => 'student', 'guard_name' => 'web']);
    }

    public function test_can_sync_multiple_schools_to_user(): void
    {
        $user = User::factory()->create();
        $schools = School::factory()->count(3)->create();
        $schoolIds = $schools->pluck('id')->toArray();

        $this->repository->syncSchools($user->id, $schoolIds, $schoolIds[0]);

        $this->assertCount(3, $user->fresh()->schools);
        $this->assertEquals($schoolIds[0], $user->fresh()->primary_school->id);
    }

    public function test_sync_schools_updates_is_primary_flag(): void
    {
        $user = User::factory()->create();
        $schools = School::factory()->count(2)->create();
        $schoolIds = $schools->pluck('id')->toArray();

        // Initially set first as primary
        $this->repository->syncSchools($user->id, $schoolIds, $schoolIds[0]);
        $this->assertEquals($schoolIds[0], $user->fresh()->primary_school->id);

        // Update second as primary
        $this->repository->syncSchools($user->id, $schoolIds, $schoolIds[1]);
        $this->assertEquals($schoolIds[1], $user->fresh()->primary_school->id);
    }

    public function test_can_get_paginated_students_with_filters(): void
    {
        $school = School::factory()->create();

        // Create 5 students in school A
        User::factory()->count(5)->create([
            'school_id' => $school->id,
            'status' => 'active',
        ])->each(fn ($u) => $user = $u->assignRole('candidate'));

        // Create 2 students in school B
        User::factory()->count(2)->create([
            'status' => 'active',
        ])->each(fn ($u) => $user = $u->assignRole('candidate'));

        $results = $this->repository->getPaginatedStudents(15, ['school_id' => $school->id]);

        $this->assertEquals(5, $results->total());
    }
}
