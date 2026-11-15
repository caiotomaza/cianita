<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class NoteController extends Controller
{
    public function index(Vault $vault): JsonResponse
    {
        Gate::authorize('view', $vault);

        return response()->json([
            'data' => $vault->notes()->orderBy('title')
                ->get(['id', 'vault_id', 'title', 'version', 'updated_at']),
        ]);
    }

    public function show(Vault $vault, Note $note): JsonResponse
    {
        $this->ensureNoteBelongsToVault($vault, $note);
        Gate::authorize('view', $vault);

        $body = Storage::disk('local')->get($note->storagePath());

        if (! is_string($body)) {
            return response()->json(['message' => 'Arquivo Markdown indisponivel.'], 503);
        }

        return response()->json(['data' => array_merge($note->toArray(), ['body' => $body])]);
    }

    public function store(Request $request, Vault $vault): JsonResponse
    {
        Gate::authorize('update', $vault);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:1000000'],
        ]);

        $note = DB::transaction(function () use ($vault, $data): Note {
            $note = $vault->notes()->create(['title' => $data['title']]);

            if (! Storage::disk('local')->put($note->storagePath(), $data['body'] ?? '')) {
                throw new RuntimeException('Falha ao persistir o arquivo Markdown.');
            }

            return $note;
        });

        return response()->json(['data' => $note], 201);
    }

    public function update(Request $request, Vault $vault, Note $note): JsonResponse
    {
        $this->ensureNoteBelongsToVault($vault, $note);
        Gate::authorize('update', $vault);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:1000000'],
            'version' => ['required', 'integer', 'min:1'],
        ]);

        return DB::transaction(function () use ($vault, $note, $data): JsonResponse {
            $locked = $vault->notes()->whereKey($note->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->version !== (int) $data['version']) {
                return response()->json(['message' => 'A nota foi alterada em outra sessao. Recarregue-a.'], 409);
            }

            $disk = Storage::disk('local');
            $path = $locked->storagePath();
            $previous = $disk->get($path);

            if (! is_string($previous)) {
                return response()->json(['message' => 'Arquivo original indisponivel.'], 503);
            }

            $temporary = $path.'.pending-'.Str::uuid();

            if (! $disk->put($temporary, $data['body'] ?? '')) {
                throw new RuntimeException('Falha ao preparar a nova versao Markdown.');
            }

            $replaced = false;

            try {
                if (! $disk->move($temporary, $path)) {
                    throw new RuntimeException('Falha ao substituir o Markdown.');
                }

                $replaced = true;
                $locked->title = $data['title'];
                $locked->version++;
                $locked->save();
            } catch (Throwable $error) {
                if ($replaced && ! $disk->put($path, $previous)) {
                    Log::critical('Falha ao restaurar Markdown apos erro no banco.', ['note_id' => $locked->getKey()]);
                }

                throw $error;
            } finally {
                $disk->delete($temporary);
            }

            return response()->json(['data' => $locked]);
        });
    }

    public function destroy(Vault $vault, Note $note): JsonResponse
    {
        $this->ensureNoteBelongsToVault($vault, $note);
        Gate::authorize('update', $vault);
        $note->delete(); // Soft delete: o arquivo continua disponivel para recuperacao administrativa.

        return response()->json(['message' => 'Nota arquivada.']);
    }

    private function ensureNoteBelongsToVault(Vault $vault, Note $note): void
    {
        abort_unless($note->vault_id === $vault->getKey(), 404);
    }
}
