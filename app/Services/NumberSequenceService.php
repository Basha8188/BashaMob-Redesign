<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NumberSequenceService
{
    public function next(string $key, string $prefix, int $padding = 6): string
    {
        return DB::transaction(function () use ($key, $prefix, $padding) {
            DB::table('number_sequences')->insertOrIgnore(['key' => $key, 'prefix' => $prefix, 'next_number' => 1, 'padding' => $padding, 'created_at' => now(), 'updated_at' => now()]);
            $sequence = DB::table('number_sequences')->where('key', $key)->lockForUpdate()->first();
            DB::table('number_sequences')->where('id', $sequence->id)->update(['next_number' => $sequence->next_number + 1, 'updated_at' => now()]);

            return $sequence->prefix.str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT);
        });
    }
}
