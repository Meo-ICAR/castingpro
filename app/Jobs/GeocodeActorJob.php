<?php

namespace App\Jobs;

use App\Models\Actor;
use Cheesegrits\FilamentGoogleMaps\Helpers\Geocoder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeocodeActorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Actor $actor
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $address = "{$this->actor->city}, {$this->actor->province}, {$this->actor->country}";

        // Use the plugin's helper to get coordinates
        $result = (new Geocoder)->geocode($address);

        if ($result) {
            // We use quiet save to avoid triggering the observer again if we had one,
            // though here we are just updating specific fields.
            // Using saveQuietly ensures we don't trigger 'saved' events that might re-dispatch
            $this->actor->latitude = $result['lat'];
            $this->actor->longitude = $result['lng'];
            $this->actor->saveQuietly();
        }
    }
}
