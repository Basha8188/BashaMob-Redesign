<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_devices', function (Blueprint $table) {
            $table->string('internal_code', 40)->nullable()->unique()->after('id');
            $table->foreignId('product_serial_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->string('color', 60)->nullable()->after('model');
            $table->string('storage', 60)->nullable()->after('color');
            $table->timestamp('first_seen_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->index(['customer_id', 'type']);
        });

        Schema::create('device_ownership_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->nullableMorphs('source');
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->nullable()->index();
            $table->string('acquisition_source', 50)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['customer_device_id', 'ended_at']);
        });

        Schema::create('device_passport_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_device_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 60)->index();
            $table->nullableMorphs('eventable');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['customer_device_id', 'occurred_at']);
        });

        Schema::create('repair_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_ticket_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_device_id')->constrained()->restrictOnDelete();
            $table->boolean('has_google_account')->default(false);
            $table->boolean('has_icloud_account')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->string('lock_type', 60)->nullable();
            $table->json('accessories')->nullable();
            $table->json('damage_map')->nullable();
            $table->text('employee_notes')->nullable();
            $table->text('terms_snapshot')->nullable();
            $table->string('customer_consent', 30)->default('pending');
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('repair_inspection_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_inspection_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40)->index();
            $table->string('disk', 30)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('repair_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('repair_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_device_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 40)->default('pending_customer')->index();
            $table->text('fault_description');
            $table->text('inspection_result')->nullable();
            $table->decimal('parts_total', 14, 2)->default(0);
            $table->decimal('labor_cost', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('required_deposit', 14, 2)->default(0);
            $table->unsignedInteger('estimated_days')->nullable();
            $table->text('warranty_terms')->nullable();
            $table->text('technician_notes')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['repair_ticket_id', 'version']);
        });

        Schema::create('repair_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 14, 3)->default(1);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });

        Schema::create('public_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('purpose', 60)->index();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedSmallInteger('max_uses')->default(20);
            $table->unsignedSmallInteger('use_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('repair_quotation_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_quotation_id')->constrained()->restrictOnDelete();
            $table->string('decision', 30)->index();
            $table->text('note')->nullable();
            $table->string('phone', 24)->nullable();
            $table->json('quotation_snapshot');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        DB::table('customer_devices')->orderBy('id')->get()->each(function ($device) {
            DB::table('customer_devices')->where('id', $device->id)->update([
                'internal_code' => $device->internal_code ?: 'DEV-LEGACY-'.str_pad((string) $device->id, 8, '0', STR_PAD_LEFT),
                'first_seen_at' => $device->first_seen_at ?: ($device->created_at ?: now()),
            ]);
            if (! DB::table('device_ownership_histories')->where('customer_device_id', $device->id)->exists()) {
                DB::table('device_ownership_histories')->insert([
                    'customer_device_id' => $device->id,
                    'customer_id' => $device->customer_id,
                    'started_at' => $device->created_at ?: now(),
                    'acquisition_source' => 'legacy_import',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        DB::table('repair_tickets')->whereNull('customer_device_id')->orderBy('id')->get()->each(function ($ticket) {
            $identifier = trim((string) $ticket->imei_or_serial);
            $deviceId = null;
            if ($identifier !== '') {
                $deviceId = DB::table('customer_devices')->where('imei_1', $identifier)->orWhere('imei_2', $identifier)->orWhere('serial_number', $identifier)->value('id');
            }
            if (! $deviceId) {
                $deviceId = DB::table('customer_devices')->insertGetId([
                    'internal_code' => 'DEV-REPAIR-'.str_pad((string) $ticket->id, 8, '0', STR_PAD_LEFT),
                    'customer_id' => $ticket->customer_id,
                    'type' => $ticket->device_type,
                    'brand' => $ticket->brand,
                    'model' => $ticket->model,
                    'imei_1' => $identifier !== '' ? $identifier : null,
                    'first_seen_at' => $ticket->received_at ?: $ticket->created_at,
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at,
                ]);
                DB::table('device_ownership_histories')->insert([
                    'customer_device_id' => $deviceId,
                    'customer_id' => $ticket->customer_id,
                    'started_at' => $ticket->received_at ?: $ticket->created_at,
                    'acquisition_source' => 'legacy_repair',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('repair_tickets')->where('id', $ticket->id)->update(['customer_device_id' => $deviceId]);
            DB::table('device_passport_events')->insert([
                'customer_device_id' => $deviceId,
                'event_type' => 'repair_received',
                'eventable_type' => 'App\\Models\\RepairTicket',
                'eventable_id' => $ticket->id,
                'title' => 'تم استلام الجهاز للصيانة',
                'description' => $ticket->reported_problem,
                'metadata' => json_encode(['ticket_number' => $ticket->ticket_number], JSON_UNESCAPED_UNICODE),
                'occurred_at' => $ticket->received_at ?: $ticket->created_at,
                'actor_id' => $ticket->created_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        DB::table('customer_devices')->whereNull('product_serial_id')->orderBy('id')->get()->each(function ($device) {
            $identifiers = array_values(array_filter([$device->imei_1, $device->imei_2, $device->serial_number]));
            if (! $identifiers) {
                return;
            }
            $serialId = DB::table('product_serials')->where(function ($query) use ($identifiers) {
                $query->whereIn('imei_1', $identifiers)->orWhereIn('imei_2', $identifiers)->orWhereIn('serial_number', $identifiers);
            })->value('id');
            if ($serialId) {
                DB::table('customer_devices')->where('id', $device->id)->update(['product_serial_id' => $serialId]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_quotation_responses');
        Schema::dropIfExists('public_access_tokens');
        Schema::dropIfExists('repair_quotation_items');
        Schema::dropIfExists('repair_quotations');
        Schema::dropIfExists('repair_inspection_photos');
        Schema::dropIfExists('repair_inspections');
        Schema::dropIfExists('device_passport_events');
        Schema::dropIfExists('device_ownership_histories');
        Schema::table('customer_devices', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'type']);
            $table->dropConstrainedForeignId('product_serial_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropUnique(['internal_code']);
            $table->dropColumn(['internal_code', 'color', 'storage', 'first_seen_at', 'deleted_at']);
        });
    }
};
