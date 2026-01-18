<?php

namespace App\Models;

use App\Jobs\GeocodeActorJob;
use App\Traits\HasWhatsapp;  // <--- Importa il Trait
use Carbon\Carbon;
// use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Cheesegrits\FilamentGoogleMaps\Helpers\Geocoder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Actor extends Model implements HasMedia  // <--- 1. Implementa l'interfaccia
{
    use InteractsWithMedia;  // <--- 2. Usa il Trait
    use HasWhatsapp;  // <--- Attivalo qui

    protected $table = 'actors';

    // Cast automatico da JSON MariaDB ad Array PHP
    protected $casts = [
        'birth_date' => 'date',
        'appearance' => 'array',
        'measurements' => 'array',
        'socials' => 'array',
        'is_visible' => 'boolean',
        'is_represented' => 'boolean',
        'consenso_privacy' => 'boolean',
    ];

    protected $fillable = [
        'user_id',
        'stage_name',
        'slug',
        'birth_date',
        'gender',
        'city',
        'country',
        'province',
        'height_cm',
        'weight_kg',
        'appearance',
        'measurements',
        'capabilities',
        'socials',
        'is_visible',
        'is_represented',
        'scene_nudo',
        'consenso_privacy',
        'tutor_name',
        'phone',
        'company_id',
        'customer_id',
        'latitude',
        'longitude'
    ];

    protected $guarded = [];

    protected $attributes = [
        'capabilities' => '{"skills": []}',
    ];

    // Relazione con User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relazione con Company
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /*
     * Boot del model per gestire la geocodifica automatica al salvataggio
     */

    protected static function booted()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->user_id = auth()->id();
                $model->company_id = auth()->user()->company_id;
            }
        });
        static::saved(function ($model) {
            if ($model->isDirty(['city', 'province', 'country'])) {
                GeocodeActorJob::dispatch($model);
            }
        });
    }

    /**
     * ADD THE FOLLOWING METHODS TO YOUR Location MODEL
     *
     * The 'latitude' and 'longitude' attributes should exist as fields in your table schema,
     * holding standard decimal latitude and longitude coordinates.
     *
     * The 'city' attribute should NOT exist in your table schema, rather it is a computed attribute,
     * which you will use as the field name for your Filament Google Maps form fields and table columns.
     *
     * You may of course strip all comments, if you don't feel verbose.
     */

    /**
     * Returns the 'latitude' and 'longitude' attributes as the computed 'city' attribute,
     * as a standard Google Maps style Point array with 'lat' and 'lng' attributes.
     *
     * Used by the Filament Google Maps package.
     *
     * Requires the 'city' attribute be included in this model's $fillable array.
     *
     * @return array
     */
    public function getCityAttribute(): array
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * Takes a Google style Point array of 'lat' and 'lng' values and assigns them to the
     * 'latitude' and 'longitude' attributes on this model.
     *
     * Used by the Filament Google Maps package.
     *
     * Requires the 'city' attribute be included in this model's $fillable array.
     *
     * @param ?array $location
     * @return void
     */

    /**
     * Set the city attribute.
     *
     * @param mixed $value
     * @return void
     */
    public function setCityAttribute($value): void
    {
        if (is_string($value)) {
            // Se ricevi una stringa (nome città), cerca le coordinate
            $geocoded = (new Geocoder)->geocode($value);
            if ($geocoded) {
                $this->attributes['latitude'] = $geocoded['lat'];
                $this->attributes['longitude'] = $geocoded['lng'];
            }
        } elseif (is_array($value) && isset($value['lat'], $value['lng'])) {
            // Se ricevi un array con lat e lng
            $this->attributes['latitude'] = $value['lat'];
            $this->attributes['longitude'] = $value['lng'];
        }
        // Non salvare il valore grezzo in un attributo 'city' poiché non esiste nella tabella
    }

    /**
     * Get the lat and lng attribute/field names used on this table
     *
     * Used by the Filament Google Maps package.
     *
     * @return string[]
     */
    public static function getLatLngAttributes(): array
    {
        return [
            'lat' => 'latitude',
            'lng' => 'longitude',
        ];
    }

    /**
     * Get the name of the computed location attribute
     *
     * Used by the Filament Google Maps package.
     *
     * @return string
     */
    public static function getComputedLocation(): string
    {
        return 'city';
    }

    public function getCapabilitiesAttribute($value)
    {
        if (is_array($value)) {
            $capabilities = $value;
        } elseif (is_string($value)) {
            $capabilities = json_decode($value, true) ?: [];
        } else {
            $capabilities = [];
        }

        // Ensure skills is always an array
        if (!isset($capabilities['skills']) || !is_array($capabilities['skills'])) {
            $capabilities['skills'] = [];
        }

        return $capabilities;
    }

    public function setCapabilitiesAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['capabilities'] = json_encode($value);
        } else {
            $this->attributes['capabilities'] = $value;
        }
    }

    // 3. Definisci le conversioni (Miniature)
    public function registerMediaConversions(Media $media = null): void
    {
        // Miniatura quadrata per le liste attori (Admin/Director view)
        $this
            ->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10);

        // Se è un video, estrai un fotogramma al secondo 10 come copertina
        $this
            ->addMediaConversion('preview')
            ->width(640)
            ->height(360)
            ->extractVideoFrameAtSecond(10)
            ->performOnCollections('showreels');
    }

    // 4. Definisci le Collezioni
    public function registerMediaCollections(): void
    {
        // HEADSHOTS: Solo immagini, max 10 file
        $this
            ->addMediaCollection('headshots')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk('public');  // O 's3' se usi AWS

        // SHOWREELS: Solo video, max 3 file
        $this
            ->addMediaCollection('showreels')
            ->acceptsMimeTypes(['video/mp4', 'video/quicktime'])
            ->useDisk('public');

        // CV: File PDF, massimo 1 file
        $this
            ->addMediaCollection('cv')
            ->acceptsMimeTypes(['application/pdf'])
            ->singleFile()
            ->useDisk('public');
    }

    // Relazione con Customer
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Accessor per calcolare l'età al volo
    public function getAgeAttribute(): int
    {
        return Carbon::parse($this->birth_date)->age;
    }

    public function applications()
    {
        return $this->hasMany(ProjectRoleActor::class);
    }

    public function shortlists(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Shortlist::class);
    }

    public function getMatchingRolesQuery()
    {
        return Role::query()
            ->where('is_open', true)
            ->where(function ($query) {
                // Filter by gender if specified in role requirements
                $query
                    ->whereNull('requirements->gender')
                    ->orWhere('requirements->gender', $this->gender);
            })
            ->where(function ($query) {
                // Filter by age range if specified
                if (isset($this->appearance['age_range'])) {
                    $age = $this->age;
                    $query->where(function ($q) use ($age) {
                        $q
                            ->whereNull('requirements->age_min')
                            ->orWhere('requirements->age_min', '<=', $age);
                    })->where(function ($q) use ($age) {
                        $q
                            ->whereNull('requirements->age_max')
                            ->orWhere('requirements->age_max', '>=', $age);
                    });
                }
            })
            ->where(function ($query) {
                // Filter by scene_nudo if specified in profile
                if (isset($this->scene_nudo) && $this->scene_nudo === 'no') {
                    $query->where('scene_nudo', '!=', 'si');
                }
            });

        /*
         * ->whereNotIn('id', function($query) {
         *     // Exclude roles the profile has already applied to
         *     $query->select('role_id')
         *           ->from('applications')
         *           ->where('profile_id', $this->id);
         * })
         */
    }

    public function getMatchingRolesAttribute()
    {
        return $this->getMatchingRolesQuery()->get();
    }

    public function scopeForCurrentUser($query)
    {
        if (auth()->user()->hasRole('actor')) {
            return $query->where('user_id', auth()->id());
        }
        return $query;
    }
}
