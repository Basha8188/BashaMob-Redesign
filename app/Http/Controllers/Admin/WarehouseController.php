<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function store(Request $r): JsonResponse
    {
        abort_unless($r->user()->can('adjust-inventory'), 403);
        $d = $r->validate(['name' => ['required', 'string', 'max:120'], 'code' => ['required', 'string', 'max:30', 'unique:warehouses,code'], 'address' => ['nullable', 'string'], 'is_default' => ['sometimes', 'boolean']]);
        $w = DB::transaction(function () use ($d) {
            if ($d['is_default'] ?? false) {
                Warehouse::where('is_default', true)->update(['is_default' => false]);
            }

return Warehouse::create($d);
        });

        return response()->json($w, 201);
    }

    public function update(Request $r, Warehouse $warehouse): JsonResponse
    {
        abort_unless($r->user()->can('adjust-inventory'), 403);
        $d = $r->validate(['name' => ['required', 'string', 'max:120'], 'address' => ['nullable', 'string'], 'is_active' => ['sometimes', 'boolean'], 'is_default' => ['sometimes', 'boolean']]);
        DB::transaction(function () use ($warehouse, $d) {
            if ($d['is_default'] ?? false) {
                Warehouse::where('id', '!=', $warehouse->id)->update(['is_default' => false]);
            }$warehouse->update($d);
        });

        return response()->json($warehouse->fresh());
    }
}
