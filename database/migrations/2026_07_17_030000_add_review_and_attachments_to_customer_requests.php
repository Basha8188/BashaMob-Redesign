<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('problem');
            $table->text('review_message')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('review_message')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->text('review_message')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('review_message')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', fn (Blueprint $table) => $table->dropConstrainedForeignId('reviewed_by'));
        Schema::table('maintenance_requests', fn (Blueprint $table) => $table->dropColumn(['attachments', 'review_message', 'reviewed_at']));
        Schema::table('customer_orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('reviewed_by'));
        Schema::table('customer_orders', fn (Blueprint $table) => $table->dropColumn(['review_message', 'reviewed_at']));
    }
};
