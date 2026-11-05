<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $seen = [];
        DB::table('product_serials')->orderBy('id')->chunkById(100, function ($serials) use (&$seen) {
            foreach ($serials as $serial) {
                foreach (['imei_1', 'imei_2', 'serial_number'] as $kind) {
                    if (! $serial->{$kind}) {
                        continue;
                    }
                    $identifier = strtoupper(trim($serial->{$kind}));
                    if (isset($seen[$identifier])) {
                        throw new RuntimeException('Duplicate IMEI or serial identifiers must be resolved before this migration: '.$identifier);
                    }
                    $seen[$identifier] = true;
                }
            }
        });
        Schema::create('product_serial_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_serial_id')->constrained()->cascadeOnDelete();
            $table->string('identifier')->unique();
            $table->string('kind', 20);
            $table->timestamps();
        });
        DB::table('product_serials')->orderBy('id')->chunkById(100, function ($serials) {
            foreach ($serials as $serial) {
                foreach (['imei_1', 'imei_2', 'serial_number'] as $kind) {
                    if ($serial->{$kind}) {
                        DB::table('product_serial_identifiers')->insert([
                            'product_serial_id' => $serial->id,
                            'identifier' => strtoupper(trim($serial->{$kind})),
                            'kind' => $kind,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->foreignId('product_serial_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('device_condition', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_serial_id');
            $table->dropColumn('device_condition');
        });
        Schema::dropIfExists('product_serial_identifiers');
    }
};
