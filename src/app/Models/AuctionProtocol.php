<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PDF-протокол итогов аукциона (фаза 8.11).
 *
 * @property int $id
 * @property int $procedure_id
 * @property string $file_path
 * @property int|null $generated_by
 * @property \Illuminate\Support\Carbon $generated_at
 * @property int $template_version
 */
class AuctionProtocol extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'procedure_id',
        'file_path',
        'generated_by',
        'generated_at',
        'template_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'template_version' => 'integer',
        ];
    }

    /**
     * Аукцион, по которому сформирован протокол.
     *
     * @return BelongsTo<Procedure, $this>
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /**
     * Администратор, инициировавший генерацию (null при автофинише).
     *
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
