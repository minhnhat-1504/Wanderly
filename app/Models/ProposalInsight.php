<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalInsight extends Model
{
    use HasFactory;

    public const BADGE_BEST_VALUE = 'best_value';

    public const BADGE_BUDGET_SAVER = 'budget_saver';

    public const BADGE_PREMIUM_CHOICE = 'premium_choice';

    protected $fillable = [
        'proposal_id',
        'pros',
        'cons',
        'hidden_costs',
        'summary_verdict',
        'score',
        'badge',
        'suggested_combos',
    ];

    protected function casts(): array
    {
        return [
            'pros' => 'array',
            'cons' => 'array',
            'hidden_costs' => 'array',
            'suggested_combos' => 'array',
            'score' => 'integer',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Human-readable label for the AI badge.
     */
    protected function badgeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->badge) {
                self::BADGE_BEST_VALUE => __('Best Value'),
                self::BADGE_BUDGET_SAVER => __('Budget Saver'),
                self::BADGE_PREMIUM_CHOICE => __('Premium Choice'),
                default => null,
            }
        );
    }

    /**
     * Tailwind color accent palette for badges.
     */
    protected function badgeClasses(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->badge) {
                self::BADGE_BEST_VALUE => [
                    'bg' => 'bg-emerald-50',
                    'text' => 'text-emerald-700',
                    'border' => 'border-emerald-200',
                    'gradient' => 'from-emerald-500 to-teal-600',
                    'icon' => '🌟',
                ],
                self::BADGE_BUDGET_SAVER => [
                    'bg' => 'bg-sky-50',
                    'text' => 'text-sky-700',
                    'border' => 'border-sky-200',
                    'gradient' => 'from-sky-500 to-blue-600',
                    'icon' => '💰',
                ],
                self::BADGE_PREMIUM_CHOICE => [
                    'bg' => 'bg-purple-50',
                    'text' => 'text-purple-700',
                    'border' => 'border-purple-200',
                    'gradient' => 'from-purple-500 to-indigo-600',
                    'icon' => '👑',
                ],
                default => [
                    'bg' => 'bg-gray-50',
                    'text' => 'text-gray-700',
                    'border' => 'border-gray-200',
                    'gradient' => 'from-gray-500 to-slate-600',
                    'icon' => '✨',
                ],
            }
        );
    }
}
