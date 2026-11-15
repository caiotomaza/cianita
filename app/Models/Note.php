<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['title', 'vault_id', 'version'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'vault_id' => 'integer'];
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(NoteRevision::class, 'revision_id');
    }

    // Apenas para leitura de notas anteriores ao protocolo de revisões.
    public function storagePath(): string
    {
        return 'vaults/'.$this->vault_id.'/notes/'.$this->getKey().'.md';
    }
}
