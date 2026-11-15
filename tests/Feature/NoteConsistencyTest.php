<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\NoteRevision;
use App\Models\User;
use App\Models\Vault;
use App\Services\NoteWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class NoteConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function newNote(): array
    {
        $user = User::factory()->create();
        $vault = Vault::query()->create(['name' => 'Teste', 'owner_id' => $user->getKey()]);
        $this->actingAs($user);
        $id = $this->postJson('/api/vaults/'.$vault->id.'/notes', [
            'title' => 'Inicial', 'body' => 'Primeiro corpo',
        ])->assertCreated()->json('data.id');

        return [$vault, $id];
    }

    private function edit(Vault $vault, string $id, int $version = 1)
    {
        return $this->putJson('/api/vaults/'.$vault->id.'/notes/'.$id, [
            'title' => 'Nova', 'body' => 'Segundo corpo', 'version' => $version,
        ]);
    }

    private function inject(string $at): void
    {
        $this->app->instance(NoteWriteService::class, new NoteWriteService(
            static function (string $point) use ($at): void {
                if ($point === $at) {
                    throw new RuntimeException('Falha simulada sem dados de nota.');
                }
            },
        ));
    }

    private function clearInjection(): void
    {
        $this->app->forgetInstance(NoteWriteService::class);
    }

    public function test_revisions_are_immutable_and_current_pointer_is_verified(): void
    {
        [$vault, $id] = $this->newNote();
        $before = Note::findOrFail($id)->revision;
        $firstPath = $before->storagePath();
        $this->edit($vault, $id)->assertOk()->assertJsonPath('data.version', 2);

        $after = Note::findOrFail($id)->revision;
        $this->assertNotSame($firstPath, $after->storagePath());
        $this->assertSame('Primeiro corpo', Storage::disk('local')->get($firstPath));
        $this->assertSame('Segundo corpo', Storage::disk('local')->get($after->storagePath()));
        $this->assertSame(hash('sha256', 'Segundo corpo'), $after->sha256);
        $this->assertDatabaseCount('note_revisions', 2);
        $this->getJson('/api/vaults/'.$vault->id.'/notes/'.$id)
            ->assertOk()->assertJsonPath('data.body', 'Segundo corpo');
        $this->edit($vault, $id)->assertStatus(409);
    }

    public function test_interruption_before_file_write_preserves_old_revision_and_blocks_competing_writer(): void
    {
        [$vault, $id] = $this->newNote();
        $this->inject('before_file_write');
        $this->edit($vault, $id)->assertStatus(500);
        $this->clearInjection();

        $pending = NoteRevision::where('note_id', $id)->where('status', 'prepared')->firstOrFail();
        Storage::disk('local')->assertMissing($pending->storagePath());
        $this->assertSame(1, Note::findOrFail($id)->version);
        $this->getJson('/api/vaults/'.$vault->id.'/notes/'.$id)
            ->assertOk()->assertJsonPath('data.body', 'Primeiro corpo');
        $this->edit($vault, $id)->assertStatus(409);
        $this->artisan('notes:reconcile', ['--apply' => true])->assertExitCode(1);
        $this->assertSame('prepared', $pending->fresh()->status);
    }

    public function test_interruption_after_file_write_is_reconciled_once_without_overwrite(): void
    {
        [$vault, $id] = $this->newNote();
        $oldPath = Note::findOrFail($id)->revision->storagePath();
        $this->inject('after_file_write');
        $this->edit($vault, $id)->assertStatus(500);
        $this->clearInjection();

        $pending = NoteRevision::where('note_id', $id)->where('status', 'prepared')->firstOrFail();
        Storage::disk('local')->assertExists($pending->storagePath());
        $this->assertSame(1, Note::findOrFail($id)->version);
        $this->artisan('notes:reconcile', ['--apply' => true])->assertExitCode(0);
        $this->artisan('notes:reconcile', ['--apply' => true])->assertExitCode(0);
        $this->assertSame('committed', $pending->fresh()->status);
        $this->assertSame(2, Note::findOrFail($id)->version);
        $this->assertSame('Primeiro corpo', Storage::disk('local')->get($oldPath));
        $this->getJson('/api/vaults/'.$vault->id.'/notes/'.$id)
            ->assertOk()->assertJsonPath('data.body', 'Segundo corpo');
    }

    public function test_interruption_before_commit_recovers_and_interruption_after_commit_does_not_roll_back(): void
    {
        [$vault, $id] = $this->newNote();
        $this->inject('before_commit');
        $this->edit($vault, $id)->assertStatus(500);
        $this->clearInjection();
        $this->artisan('notes:reconcile', ['--apply' => true])->assertExitCode(0);

        $this->inject('after_commit');
        $this->edit($vault, $id, 2)->assertStatus(500);
        $this->clearInjection();
        $this->assertSame(3, Note::findOrFail($id)->version);
        $this->edit($vault, $id, 2)->assertStatus(409);
        $this->getJson('/api/vaults/'.$vault->id.'/notes/'.$id)
            ->assertOk()->assertJsonPath('data.body', 'Segundo corpo');
    }

    public function test_missing_or_tampered_file_causes_explicit_failure_without_mutation(): void
    {
        [$vault, $id] = $this->newNote();
        $path = Note::findOrFail($id)->revision->storagePath();
        Storage::disk('local')->put($path, 'Corpo modificado por fora');
        $this->getJson('/api/vaults/'.$vault->id.'/notes/'.$id)->assertStatus(503);
        $this->edit($vault, $id)->assertStatus(503);
        $this->artisan('notes:reconcile')->assertExitCode(1);

        Storage::disk('local')->delete($path);
        $this->getJson('/api/vaults/'.$vault->id.'/notes/'.$id)->assertStatus(503);
        $this->assertSame(1, Note::findOrFail($id)->version);
        $this->assertDatabaseCount('note_revisions', 1);
    }

    public function test_other_vault_is_denied_and_archive_restore_preserve_content(): void
    {
        [$vault, $id] = $this->newNote();
        $other = Vault::query()->create(['name' => 'Outro', 'owner_id' => $vault->owner_id]);
        $url = '/api/vaults/'.$vault->id.'/notes/'.$id;
        $wrong = '/api/vaults/'.$other->id.'/notes/'.$id;
        $this->getJson($wrong)->assertNotFound();
        $this->putJson($wrong, [
            'title' => 'Ataque', 'body' => 'Nao', 'version' => 1,
        ])->assertNotFound();
        $this->postJson($wrong.'/restore', ['version' => 1])->assertNotFound();

        $this->deleteJson($url, ['version' => 1])->assertOk();
        $this->deleteJson($url, ['version' => 1])->assertNotFound();
        $this->postJson($url.'/restore', ['version' => 1])->assertStatus(409);
        $this->postJson($url.'/restore', ['version' => 2])->assertOk()
            ->assertJsonPath('data.version', 3);
        $this->postJson($url.'/restore', ['version' => 3])->assertStatus(409);
        $this->getJson($url)->assertOk()->assertJsonPath('data.body', 'Primeiro corpo');
        $this->edit($vault, $id, 1)->assertStatus(409);
    }

    public function test_existing_legacy_markdown_is_read_only_until_supervised_migration(): void
    {
        $user = User::factory()->create();
        $vault = Vault::query()->create(['name' => 'Antigo', 'owner_id' => $user->id]);
        $note = $vault->notes()->create(['title' => 'Legado']);
        Storage::disk('local')->put($note->storagePath(), 'Legado intacto');
        $this->actingAs($user);
        $this->getJson('/api/vaults/'.$vault->id.'/notes/'.$note->id)
            ->assertOk()->assertJsonPath('data.body', 'Legado intacto');
        $this->edit($vault, $note->id)->assertStatus(503);
        $this->assertSame('Legado intacto', Storage::disk('local')->get($note->storagePath()));
    }
}
