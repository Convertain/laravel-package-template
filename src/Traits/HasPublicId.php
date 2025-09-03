<?php

declare(strict_types=1);

namespace Convertain\PackageTemplate\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait HasPublicId
 * 
 * Adds UUID support to models while keeping integer primary keys.
 * The UUID is stored in a 'uuid' column and used for public-facing routes.
 */
trait HasPublicId
{
    /**
     * Boot the HasPublicId trait for a model.
     */
    protected static function bootHasPublicId(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->uuid)) {
                $model->uuid = static::generatePublicId();
            }
        });
    }

    /**
     * Initialize the HasPublicId trait for a model.
     */
    public function initializeHasPublicId(): void
    {
        if (! isset($this->casts['uuid'])) {
            $this->casts['uuid'] = 'string';
        }
    }

    /**
     * Generate a new public ID.
     */
    protected static function generatePublicId(): string
    {
        $config = config('package-template.public_id_type', 'uuid');
        
        return match ($config) {
            'ulid' => (string) Str::ulid(),
            'uuid' => (string) Str::uuid(),
            default => (string) Str::uuid(),
        };
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        // Check if model has SEO slug support and it's configured
        if ($this->hasSeoSlug() && $this->shouldUseSeoSlug()) {
            return $this->getSeoSlugColumn();
        }

        return 'uuid';
    }

    /**
     * Check if the model has SEO slug support.
     */
    protected function hasSeoSlug(): bool
    {
        return method_exists($this, 'getSeoSlugColumn');
    }

    /**
     * Check if SEO slug should be used for routing.
     */
    protected function shouldUseSeoSlug(): bool
    {
        if (! method_exists($this, 'useSeoSlugForRouting')) {
            return false;
        }

        return $this->useSeoSlugForRouting();
    }

    /**
     * Scope a query to find by public ID (UUID).
     */
    public function scopeWherePublicId($query, string $publicId)
    {
        return $query->where('uuid', $publicId);
    }

    /**
     * Find a model by its public ID.
     */
    public static function findByPublicId(string $publicId): ?static
    {
        return static::wherePublicId($publicId)->first();
    }

    /**
     * Find a model by its public ID or fail.
     */
    public static function findByPublicIdOrFail(string $publicId): static
    {
        return static::wherePublicId($publicId)->firstOrFail();
    }
}
