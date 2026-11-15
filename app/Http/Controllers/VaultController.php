<?php

namespace App\Http\Controllers;

use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VaultController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Vault::class);

        return response()->json([
            'data' => Vault::query()->visibleTo($request->user())
                ->orderBy('name')->get(['id', 'name', 'owner_id', 'created_at']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Vault::class);
        $validated = $request->validate(['name' => ['required', 'string', 'min:1', 'max:120']]);

        $vault = Vault::query()->create([
            'name' => $validated['name'],
            'owner_id' => $request->user()->getKey(),
        ]);

        return response()->json(['data' => $vault], 201);
    }
}
