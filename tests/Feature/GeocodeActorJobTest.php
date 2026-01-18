<?php

namespace Tests\Feature;

use App\Jobs\GeocodeActorJob;
use App\Models\Actor;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GeocodeActorJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_geocode_job_is_dispatched_when_actor_address_changes()
    {
        Queue::fake();

        $user = User::factory()->create();
        $company = Company::create(['name' => 'Test Company', 'email' => 'test@company.com', 'slug' => 'test-company']);
        $user->company_id = $company->id;
        $user->save();

        $this->actingAs($user);

        $actor = Actor::create([
            'stage_name' => 'Test Actor',
            'city' => 'Rome',
            'province' => 'RM',
            'country' => 'Italy',
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        Queue::assertPushed(GeocodeActorJob::class);
    }

    public function test_geocode_job_is_not_dispatched_when_address_does_not_change()
    {
        Queue::fake();

        $user = User::factory()->create();
        $company = Company::create(['name' => 'Test Company 2', 'email' => 'test2@company.com', 'slug' => 'test-company-2']);
        $user->company_id = $company->id;
        $user->save();

        $this->actingAs($user);

        $actor = Actor::create([
            'stage_name' => 'Test Actor',
            'city' => 'Rome',
            'province' => 'RM',
            'country' => 'Italy',
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        Queue::assertPushed(GeocodeActorJob::class);

        // Clear pushed jobs
        // Queue::fake(); // Resetting fake might not work as expected in same test, but let's try asserting count or just new push

        $actor->update(['stage_name' => 'Updated Name']);

        // We expect it NOT to be pushed again.
        // Since Queue::fake() accumulates, we need to check if it was pushed TWICE?
        // Or better, use a fresh fake if possible, or just check that it was pushed 1 time total.

        Queue::assertPushed(GeocodeActorJob::class, 1);
    }
}
