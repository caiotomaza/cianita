<?php

/**
 * Ensaio isolado de SIGKILL com PostgreSQL real e Markdown no disco privado.
 *
 * Não é teste PHPUnit. Só executa em CI com banco de testes local e chave
 * explícita; não pode limpar ou alterar volumes de instâncias do operador.
 */
use App\Models\Note;
use App\Models\NoteRevision;
use App\Models\User;
use App\Models\Vault;
use App\Services\NoteWriteService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$db = (string) config('database.connections.pgsql.database');
if (
    getenv('CI') !== 'true'
    || getenv('CIANITA_CRASH_TEST') !== '1'
    || ! app()->environment('testing')
    || config('database.default') !== 'pgsql'
    || ! str_ends_with($db, '_test')
    || config('database.connections.pgsql.host') !== '127.0.0.1'
    || ! extension_loaded('pcntl')
    || ! extension_loaded('posix')
) {
    fwrite(STDERR, "BLOQUEADO: ensaio SIGKILL exige CI, PostgreSQL local isolado e pcntl/posix.\n");
    exit(2);
}

function requireState(bool $valid, string $context): void
{
    if (! $valid) {
        throw new RuntimeException('Invariante falhou em '.$context);
    }
}

function fixture(string $suffix): array
{
    $user = User::factory()->create();
    $vault = Vault::query()->create(['name' => 'SIGKILL '.$suffix, 'owner_id' => $user->getKey()]);

    return [$vault, new NoteWriteService];
}

function killedAt(string $point, Closure $operation): void
{
    // Conexões herdadas pelo fork podem corromper o protocolo PostgreSQL.
    DB::disconnect('pgsql');
    $pid = pcntl_fork();
    requireState($pid !== -1, 'fork');

    if ($pid === 0) {
        DB::purge('pgsql');
        try {
            $writer = new NoteWriteService(static function (string $event) use ($point): void {
                if ($event === $point) {
                    posix_kill(getmypid(), SIGKILL);
                    exit(31);
                }
            });
            $operation($writer);
            exit(32);
        } catch (Throwable $error) {
            fwrite(STDERR, "Filho encerrou sem atingir ponto de corte.\n");
            exit(33);
        }
    }

    requireState(pcntl_waitpid($pid, $status) === $pid, 'waitpid');
    DB::purge('pgsql');
    requireState(pcntl_wifsignaled($status) && pcntl_wtermsig($status) === SIGKILL, $point);
}

function testNewNoteCut(): void
{
    [$vault, $writer] = fixture('create');
    killedAt('after_file_write', static function (NoteWriteService $writer) use ($vault): void {
        $writer->write($vault, null, 'Criada no ensaio', 'Corpo sintético de criação');
    });

    $note = Note::query()->where('vault_id', $vault->getKey())->firstOrFail();
    requireState($note->version === 0 && $note->revision_id === null, 'criacao preparada');
    $pending = NoteRevision::query()->where('note_id', $note->getKey())->where('status', 'prepared')->firstOrFail();
    requireState(Storage::disk('local')->exists($pending->storagePath()), 'novo objeto presente');
    $writer->finalize($pending);
    $writer->finalize($pending); // Idempotência após confirmação.
    requireState(Note::findOrFail($note->getKey())->version === 1, 'criacao confirmada');
    requireState($writer->read(Note::findOrFail($note->getKey())) === 'Corpo sintético de criação', 'leitura nova');
}

function testUpdateCut(string $point): void
{
    [$vault, $writer] = fixture($point);
    $note = $writer->write($vault, null, 'Original', 'Corpo sintético original');
    $oldPath = $note->revision->storagePath();
    $id = $note->getKey();

    killedAt($point, static function (NoteWriteService $writer) use ($vault, $id): void {
        $writer->write(
            $vault, Note::findOrFail($id),
            'Atualizada', 'Corpo sintético atualizado', 1,
        );
    });

    $current = Note::findOrFail($id);
    requireState(Storage::disk('local')->get($oldPath) === 'Corpo sintético original', 'versao anterior preservada');

    if ($point === 'after_commit') {
        requireState($current->version === 2, 'commit duravel apos morte do processo');
        requireState(
            $writer->read($current) === 'Corpo sintético atualizado',
            'resposta perdida sem perda de commit',
        );

        return;
    }

    requireState($current->version === 1, 'referencia ainda antiga');
    $pending = NoteRevision::query()->where('note_id', $id)->where('status', 'prepared')->firstOrFail();

    if ($point === 'before_file_write') {
        requireState(! Storage::disk('local')->exists($pending->storagePath()), 'objeto ausente');

        try {
            $writer->finalize($pending);
            throw new RuntimeException('Reconciliação confirmou objeto ausente.');
        } catch (HttpException $error) {
            requireState($error->getStatusCode() === 503, 'falha explicita por objeto ausente');
        }
        requireState(Note::findOrFail($id)->version === 1, 'arquivo ausente nao promove');

        return;
    }

    requireState(Storage::disk('local')->exists($pending->storagePath()), 'objeto de revisao preservado');
    $writer->finalize($pending);
    $writer->finalize($pending);
    requireState(Note::findOrFail($id)->version === 2, 'reconciliacao idempotente');
    requireState(
        $writer->read(Note::findOrFail($id)) === 'Corpo sintético atualizado',
        'leitura reconciliada',
    );
}

testNewNoteCut();
testUpdateCut('after_file_write');
testUpdateCut('before_commit');
testUpdateCut('after_commit');
testUpdateCut('before_file_write');

fwrite(STDOUT, "APROVADO: SIGKILL real em criacao, escrita, pre-commit, pos-commit, objeto ausente e reconciliacao repetida.\n");
