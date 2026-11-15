<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Vault;
use App\Services\NoteWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NoteController extends Controller
{
    public function __construct(private readonly NoteWriteService $storage) {}

    public function index(Vault $vault): JsonResponse
    {
        Gate::authorize('view', $vault);

        return response()->json([
            'data' => $vault->notes()->where('version', '>=', 1)->orderBy('title')
                ->get(['id', 'vault_id', 'title', 'version', 'updated_at']),
        ]);
    }

    public function show(Vault $vault, Note $note): JsonResponse
    {
        $this->ensureNoteBelongsToVault($vault, $note);
        Gate::authorize('view', $vault);

        return response()->json(['data' => array_merge(
            $note->toArray(),
            ['body' => $this->storage->read($note)],
        )]);
    }

    public function store(Request $request, Vault $vault): JsonResponse
    {
        Gate::authorize('update', $vault);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:1000000'],
        ]);

        return response()->json(['data' => $this->storage->write(
            $vault, null, $data['title'], $data['body'] ?? '',
        )], 201);
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

        return response()->json(['data' => $this->storage->write(
            $vault, $note, $data['title'], $data['body'] ?? '', (int) $data['version'],
        )]);
    }

    public function destroy(Request $request, Vault $vault, Note $note): JsonResponse
    {
        $this->ensureNoteBelongsToVault($vault, $note);
        Gate::authorize('update', $vault);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $this->storage->archive($note, (int) $data['version']);

        return response()->json(['message' => 'Nota arquivada.']);
    }

    public function restore(Request $request, Vault $vault, string $note): JsonResponse
    {
        Gate::authorize('update', $vault);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $record = Note::withTrashed()->whereKey($note)->firstOrFail();
        $this->ensureNoteBelongsToVault($vault, $record);
        abort_unless($record->trashed(), 409, 'Nota nao esta arquivada.');

        return response()->json(['data' => $this->storage->archive($record, (int) $data['version'], true)]);
    }

    private function ensureNoteBelongsToVault(Vault $vault, Note $note): void
    {
        abort_unless($note->vault_id === $vault->getKey(), 404);
    }
}
