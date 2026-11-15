<?php

namespace App\Services;

use App\Models\Note;
use App\Models\NoteRevision;
use App\Models\Vault;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class NoteWriteService
{
    public function __construct(private readonly ?Closure $checkpoint = null) {}

    /**
     * Cada operação possui um registro PostgreSQL durável ANTES de gravar
     * um novo objeto Markdown, e publica a referência em outro commit.
     * Nenhum objeto confirmado é substituído ou excluído.
     */
    public function write(Vault $vault, ?Note $note, string $title, string $body, ?int $expectedVersion = null): Note
    {
        $revision = DB::transaction(function () use ($vault, $note, $title, $body, $expectedVersion): NoteRevision {
            if ($note === null) {
                $note = $vault->notes()->create(['title' => $title, 'version' => 0]);
                $base = 0;
                $operation = 'create';
            } else {
                abort_unless($note->vault_id === $vault->getKey(), 404);
                $note = $vault->notes()->whereKey($note->getKey())->lockForUpdate()->firstOrFail();
                abort_if($note->revision_id === null, 503, 'Nota legada: migracao supervisionada necessaria.');
                abort_if($note->version !== $expectedVersion, 409, 'Versao desatualizada. Preserve seu rascunho.');
                abort_if($this->hasPending($note), 409, 'Gravacao pendente de reconciliacao.');
                $this->read($note); // Não editar uma referência já inconsistente.
                $base = $note->version;
                $operation = 'update';
            }

            return NoteRevision::query()->create([
                'id' => (string) Str::uuid(),
                'note_id' => $note->getKey(),
                'vault_id' => $vault->getKey(),
                'operation' => $operation,
                'base_version' => $base,
                'version' => $base + 1,
                'title' => $title,
                'sha256' => hash('sha256', $body),
                'status' => 'prepared',
            ]);
        });

        $this->checkpoint('after_prepare');
        $disk = Storage::disk('local');
        $path = $revision->storagePath();
        $temporary = $path.'.pending-'.Str::uuid();

        $this->checkpoint('before_file_write');
        try {
            if ($disk->exists($path) || ! $disk->put($temporary, $body)) {
                abort(503, 'Falha ao preparar Markdown; operacao preservada para investigacao.');
            }

            if (! $disk->move($temporary, $path)) {
                abort(503, 'Falha ao publicar Markdown; operacao preservada para investigacao.');
            }
        } catch (Throwable $error) {
            // Não tentar compensar destruindo objetos: pode haver crash/commit incerto.
            abort(503, 'Gravacao nao confirmada; reconciliacao necessaria.');
        }

        $this->checkpoint('after_file_write');
        $this->readRevision($revision);
        $this->checkpoint('before_commit');
        $result = $this->finalize($revision);
        $this->checkpoint('after_commit');

        return $result;
    }

    /**
     * Pode ser reexecutado. Só confirma a revisão preparada quando a
     * versão base ainda coincide e o arquivo possui o hash esperado.
     */
    public function finalize(NoteRevision $revision): Note
    {
        return DB::transaction(function () use ($revision): Note {
            $note = Note::query()->whereKey($revision->note_id)->lockForUpdate()->firstOrFail();
            $revision = NoteRevision::query()->whereKey($revision->getKey())->lockForUpdate()->firstOrFail();

            abort_if($revision->vault_id !== $note->vault_id, 503, 'Escopo divergente; investigacao necessaria.');

            if ($revision->status === 'committed' && $note->revision_id === $revision->getKey()) {
                $this->readRevision($revision);

                return $note;
            }

            abort_if($revision->status !== 'prepared', 409, 'Operacao nao pode ser publicada.');
            abort_if($note->trashed() || $note->version !== $revision->base_version, 409, 'Estado divergente; investigacao necessaria.');
            abort_unless($revision->version === $revision->base_version + 1, 503, 'Sequencia de revisao invalida.');
            if ($revision->operation === 'create') {
                abort_unless($note->version === 0 && $note->revision_id === null, 503, 'Criacao ambigua.');
            } elseif ($revision->operation === 'update') {
                abort_if($note->revision_id === null, 503, 'Revisao anterior ausente.');
                $this->read($note);
            } else {
                abort(503, 'Operacao de journal desconhecida.');
            }
            $this->readRevision($revision);

            $note->title = $revision->title;
            $note->version = $revision->version;
            $note->revision_id = $revision->getKey();
            $note->save();

            $revision->status = 'committed';
            $revision->save();

            return $note;
        });
    }

    public function read(Note $note): string
    {
        if ($note->revision_id === null) {
            abort_if($note->version === 0, 503, 'Criacao pendente de reconciliacao.');

            // Compatibilidade somente de leitura; não alterar o arquivo legado.
            return $this->readFile($note->storagePath());
        }

        $revision = NoteRevision::query()->find($note->revision_id);
        abort_if(
            $revision === null || $revision->note_id !== $note->getKey()
                || $revision->vault_id !== $note->vault_id || $revision->status !== 'committed',
            503,
            'Referencia de conteudo invalida.',
        );

        return $this->readRevision($revision);
    }

    public function readRevision(NoteRevision $revision): string
    {
        $body = $this->readFile($revision->storagePath());
        abort_unless(hash_equals($revision->sha256, hash('sha256', $body)), 503, 'Hash Markdown divergente.');

        return $body;
    }

    public function archive(Note $note, int $version, bool $restore = false): Note
    {
        return DB::transaction(function () use ($note, $version, $restore): Note {
            $note = Note::withTrashed()->whereKey($note->getKey())->lockForUpdate()->firstOrFail();
            abort_if($note->version !== $version, 409, 'Versao desatualizada.');
            abort_if($this->hasPending($note), 409, 'Gravacao pendente de reconciliacao.');
            abort_if($restore ? ! $note->trashed() : $note->trashed(), 409, 'Estado de arquivamento divergente.');
            $this->read($note);
            if ($restore) {
                $note->restore();
            } else {
                $note->delete();
            }
            $note->version++;
            $note->save();

            return $note;
        });
    }

    public function hasPending(Note $note): bool
    {
        return NoteRevision::query()->where('note_id', $note->getKey())
            ->where('status', 'prepared')->exists();
    }

    private function readFile(string $path): string
    {
        try {
            $disk = Storage::disk('local');
            if (! $disk->exists($path)) {
                abort(503, 'Arquivo Markdown ausente.');
            }
            $body = $disk->get($path);
        } catch (Throwable $error) {
            abort(503, 'Nao foi possivel ler o Markdown privado.');
        }

        abort_unless(is_string($body), 503, 'Arquivo Markdown indisponivel.');

        return $body;
    }

    private function checkpoint(string $name): void
    {
        if ($this->checkpoint !== null) {
            ($this->checkpoint)($name);
        }
    }
}
