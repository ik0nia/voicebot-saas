<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppTemplate extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_DISABLED = 'DISABLED';
    public const STATUS_IN_APPEAL = 'IN_APPEAL';
    public const STATUS_PENDING_DELETION = 'PENDING_DELETION';

    public const CATEGORIES = ['MARKETING', 'UTILITY', 'AUTHENTICATION'];

    protected $fillable = [
        'channel_id',
        'name',
        'category',
        'language',
        'status',
        'meta_template_id',
        'components',
        'rejection_reason',
        'sample_values',
        'submitted_at',
        'approved_at',
    ];

    protected $casts = [
        'components' => 'array',
        'sample_values' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function isSendable(): bool
    {
        return $this->status === self::STATUS_APPROVED && $this->meta_template_id !== null;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED, self::STATUS_DISABLED => 'red',
            self::STATUS_PENDING, self::STATUS_IN_APPEAL => 'amber',
            self::STATUS_PAUSED => 'orange',
            self::STATUS_PENDING_DELETION => 'slate',
            default => 'slate', // DRAFT
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Schiță',
            self::STATUS_PENDING => 'În aprobare la Meta',
            self::STATUS_APPROVED => 'Aprobat',
            self::STATUS_REJECTED => 'Respins de Meta',
            self::STATUS_PAUSED => 'Suspendat de Meta',
            self::STATUS_DISABLED => 'Dezactivat de Meta',
            self::STATUS_IN_APPEAL => 'Apel în curs',
            self::STATUS_PENDING_DELETION => 'Se șterge',
            default => $this->status,
        };
    }

    /**
     * Extract {{1}}, {{2}}, … placeholders from the body for the variables UI.
     *
     * @return array<int, int>
     */
    public function bodyVariables(): array
    {
        $body = $this->bodyText();
        if ($body === null) {
            return [];
        }
        preg_match_all('/{{(\d+)}}/', $body, $matches);
        $vars = array_unique(array_map('intval', $matches[1]));
        sort($vars);
        return $vars;
    }

    public function bodyText(): ?string
    {
        foreach ($this->components ?? [] as $component) {
            if (($component['type'] ?? '') === 'BODY') {
                return $component['text'] ?? null;
            }
        }
        return null;
    }
}
