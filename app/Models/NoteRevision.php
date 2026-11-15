<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteRevision extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'note_id', 'vault_id', 'operation', 'base_version',
        'version', 'title', 'sha256', 'status',
    ];

    protected function casts(): array
    {
        return [
            'base_version' => 'integer',
            'version' => 'integer',
            'vault_id' => 'integer',
        ];
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function storagePath(): string
    {
        return 'vaults/'.$this->vault_id.'/notes/'.$this->note_id.'/revisions/'.$this->getKey().'.md';
    }
}
