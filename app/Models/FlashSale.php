<?php

namespace App\Models;

use App\Enums\FlashSaleStatus;
use Carbon\CarbonInterface;
use Database\Factories\FlashSaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A time-boxed campaign that sells chosen variants at a lower price, optionally with a quota.
 *
 * @property int $id
 * @property string $name
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property bool $is_active
 */
#[Fillable(['name', 'starts_at', 'ends_at', 'is_active'])]
class FlashSale extends Model
{
    /** @use HasFactory<FlashSaleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<FlashSaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class)->orderBy('id');
    }

    /**
     * Sales whose price applies right now.
     *
     * @param  Builder<FlashSale>  $query
     */
    public function scopeRunning(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    /**
     * Active sales that have not started yet.
     *
     * @param  Builder<FlashSale>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('is_active', true)->where('starts_at', '>', now());
    }

    /**
     * Sales whose schedule overlaps the given window.
     *
     * @param  Builder<FlashSale>  $query
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $startsAt, CarbonInterface $endsAt): void
    {
        $query->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt);
    }

    public function status(): FlashSaleStatus
    {
        // Same boundaries as the running scope: starts_at inclusive, ends_at exclusive.
        return match (true) {
            ! $this->is_active => FlashSaleStatus::Inactive,
            ! $this->ends_at->isFuture() => FlashSaleStatus::Ended,
            $this->starts_at->isFuture() => FlashSaleStatus::Scheduled,
            default => FlashSaleStatus::Running,
        };
    }

    public function isRunning(): bool
    {
        return $this->status() === FlashSaleStatus::Running;
    }
}
