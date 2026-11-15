<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VaultAndNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_unauthenticated_api_requests_are_rejected(): void
    {
        $this->getJson('/api/vaults')->assertUnauthorized();
    }

    public function test_owner_can_create_vault_and_markdown_note(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $vaultId = $this->postJson('/api/vaults', ['name' => 'Pessoal'])
            ->assertCreated()->json('data.id');

        $response = $this->postJson('/api/vaults/'.$vaultId.'/notes', [
            'title' => 'Primeira nota', 'body' => '# Meu conhecimento',
        ])->assertCreated();

        $noteId = $response->json('data.id');
        Storage::disk('local')->assertExists('vaults/'.$vaultId.'/notes/'.$noteId.'.md');
        $this->assertSame(
            '# Meu conhecimento',
            Storage::disk('local')->get('vaults/'.$vaultId.'/notes/'.$noteId.'.md'),
        );

        $this->getJson('/api/vaults/'.$vaultId.'/notes/'.$noteId)
            ->assertOk()->assertJsonPath('data.body', '# Meu conhecimento');
    }

    public function test_other_users_cannot_read_or_modify_private_vaults(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $vault = Vault::query()->create(['name' => 'Privado', 'owner_id' => $owner->id]);

        $this->actingAs($intruder);
        $this->getJson('/api/vaults')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/vaults/'.$vault->id.'/notes')->assertForbidden();
        $this->postJson('/api/vaults/'.$vault->id.'/notes', [
            'title' => 'Intrusao', 'body' => 'Nao',
        ])->assertForbidden();
    }

    public function test_viewer_may_read_but_cannot_edit(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $vault = Vault::query()->create(['name' => 'Compartilhado', 'owner_id' => $owner->id]);
        $vault->members()->attach($viewer->id, ['role' => 'viewer']);

        $this->actingAs($viewer);
        $this->getJson('/api/vaults')->assertOk()
            ->assertJsonPath('data.0.can_edit', false);
        $this->getJson('/api/vaults/'.$vault->id.'/notes')->assertOk();
        $this->postJson('/api/vaults/'.$vault->id.'/notes', [
            'title' => 'Nao permitido',
        ])->assertForbidden();
    }

    public function test_editor_can_modify_a_shared_vault(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $vault = Vault::query()->create(['name' => 'Equipe', 'owner_id' => $owner->id]);
        $vault->members()->attach($editor->id, ['role' => 'editor']);

        $this->actingAs($editor);
        $this->getJson('/api/vaults')->assertOk()
            ->assertJsonPath('data.0.can_edit', true);
        $this->postJson('/api/vaults/'.$vault->id.'/notes', [
            'title' => 'Nota da equipe', 'body' => '',
        ])->assertCreated();
    }

    public function test_stale_version_is_rejected_without_overwriting_file(): void
    {
        $owner = User::factory()->create();
        $vault = Vault::query()->create(['name' => 'Testes', 'owner_id' => $owner->id]);
        $this->actingAs($owner);
        $noteId = $this->postJson('/api/vaults/'.$vault->id.'/notes', [
            'title' => 'Original', 'body' => 'Primeira versao',
        ])->assertCreated()->json('data.id');
        $url = '/api/vaults/'.$vault->id.'/notes/'.$noteId;

        $this->putJson($url, [
            'title' => 'Editada', 'body' => 'Segunda versao', 'version' => 1,
        ])->assertOk()->assertJsonPath('data.version', 2);

        $this->putJson($url, [
            'title' => 'Antiga', 'body' => 'Conteudo perdido', 'version' => 1,
        ])->assertStatus(409);

        $this->assertSame(
            'Segunda versao',
            Storage::disk('local')->get('vaults/'.$vault->id.'/notes/'.$noteId.'.md'),
        );
    }

    public function test_soft_delete_preserves_markdown_for_recovery(): void
    {
        $user = User::factory()->create();
        $vault = Vault::query()->create(['name' => 'Arquivo', 'owner_id' => $user->id]);
        $this->actingAs($user);
        $noteId = $this->postJson('/api/vaults/'.$vault->id.'/notes', [
            'title' => 'Arquivar', 'body' => 'Nao destruir',
        ])->assertCreated()->json('data.id');

        $this->deleteJson('/api/vaults/'.$vault->id.'/notes/'.$noteId)->assertOk();
        $this->assertSoftDeleted('notes', ['id' => $noteId]);
        Storage::disk('local')->assertExists('vaults/'.$vault->id.'/notes/'.$noteId.'.md');
        $this->getJson('/api/vaults/'.$vault->id.'/notes/'.$noteId)->assertNotFound();
    }
}
