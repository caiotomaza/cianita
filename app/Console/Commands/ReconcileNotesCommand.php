<?php

namespace App\Console\Commands;

use App\Models\Note;
use App\Models\NoteRevision;
use App\Services\NoteWriteService;
use Illuminate\Console\Command;
use Throwable;

class ReconcileNotesCommand extends Command
{
    protected $signature = 'notes:reconcile {--apply : Publica somente revisoes preparadas verificadas}';

    protected $description = 'Verifica referencias de notas e reconcilia revisoes com hash valido sem excluir arquivos';

    public function handle(NoteWriteService $storage): int
    {
        $unresolved = 0;
        $reviewed = 0;

        NoteRevision::query()->where('status', 'prepared')
            ->chunkById(100, function ($revisions) use ($storage, &$unresolved, &$reviewed): void {
                foreach ($revisions as $revision) {
                    $reviewed++;
                    try {
                        // Verifica corpo e hash mesmo no modo somente leitura.
                        $storage->readRevision($revision);
                        if ($this->option('apply')) {
                            $storage->finalize($revision);
                            $this->line('Confirmada: '.$revision->getKey());
                        } else {
                            $unresolved++;
                            $this->warn('Pendente verificavel: '.$revision->getKey());
                        }
                    } catch (Throwable $error) {
                        $unresolved++;
                        $this->error('Investigacao necessaria: '.$revision->getKey());
                    }
                }
            });

        Note::withTrashed()->whereNotNull('revision_id')->orderBy('id')
            ->chunk(100, function ($notes) use ($storage, &$unresolved, &$reviewed): void {
                foreach ($notes as $note) {
                    $reviewed++;
                    try {
                        $storage->read($note);
                    } catch (Throwable $error) {
                        $unresolved++;
                        $this->error('Referencia confirmada inconsistente: '.$note->getKey());
                    }
                }
            });

        $this->info('Itens verificados: '.$reviewed.'; pendencias: '.$unresolved.'. Nenhum arquivo removido.');

        return $unresolved === 0 ? self::SUCCESS : self::FAILURE;
    }
}
