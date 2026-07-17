<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->json('items');
            $table->decimal('total', 14, 2)->default(0);
            $table->string('status', 40)->default('pending_review')->index();
            $table->timestamps();
        });

        DB::table('maintenance_requests')->orderBy('created_at')->get()->each(function ($request) {
            DB::table('customers')->upsert([[
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'is_active' => true,
                'deleted_at' => null,
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
            ]], ['phone'], ['name', 'email', 'is_active', 'deleted_at', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};
