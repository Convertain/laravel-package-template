<?php

declare(strict_types=1);

namespace Convertain\PackageTemplate\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait HasPublicId.
 *
 * Adds UUID support to models while keeping integer primary keys.
 * The UUID is stored in a 'uuid' column and used for public-facing routes.
 *
 * @property string $uuid
 */
trait HasPublicId
{
    /**
     * Boot the HasPublicId trait for a model.
     */
    protected static function bootHasPublicId(): void
    {
        static::creating(function (Model $model): void {
            /** @var self $model */
            if (!isset($model->uuid) || empty($model->uuid)) {
                $model->uuid = static::generatePublicId();
            }
        });
    }

    /**
     * Initialize the HasPublicId trait for a model.
     */
    public function initializeHasPublicId(): void
    {
        if (!isset($this->casts['uuid'])) {
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
            /** @var string */
            return method_exists($this, 'getSeoSlugColumn') ? $this->getSeoSlugColumn() : 'uuid';
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
        if (!method_exists($this, 'useSeoSlugForRouting')) {
            return false;
        }

        /** @var bool */
        return $this->useSeoSlugForRouting();
    }

    /**
     * Scope a query to find by public ID (UUID).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWherePublicId(Builder $query, string $publicId): Builder
    {
        return $query->where('uuid', $publicId);
    }

    /**
     * Find a model by its public ID.
     *
     * @return static|null
     */
    public static function findByPublicId(string $publicId): ?Model
    {
        /** @var static|null */
        return static::wherePublicId($publicId)->first();
    }

    /**
     * Find a model by its public ID or fail.
     *
     * @return static
     */
    public static function findByPublicIdOrFail(string $publicId): Model
    {
        /** @var static */
        return static::wherePublicId($publicId)->firstOrFail();
    }
}
