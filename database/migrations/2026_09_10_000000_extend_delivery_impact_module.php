<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : database/migrations/2026_09_10_000000_extend_delivery_impact_module.php
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->text('delivery_notes')->nullable()->after('delivery_status');
            $table->index(['volunteer_id', 'delivery_status'], 'delivery_volunteer_status_index');
        });

        Schema::create('delivery_impacts', function (Blueprint $table) {
            $table->id('impact_id');
            $table->foreignId('delivery_id')->constrained('delivery_tasks', 'delivery_id')->cascadeOnDelete();
            $table->string('impact_type', 50);
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('measurement_unit', 50)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->unique(['delivery_id', 'impact_type'], 'delivery_impact_unique_event');
        });

        Schema::create('delivery_api_requests', function (Blueprint $table) {
            $table->id('request_log_id');
            $table->string('request_id', 100)->unique();
            $table->unsignedBigInteger('request_timestamp');
            $table->string('http_method', 10);
            $table->string('request_path', 255);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('processed_at')->useCurrent();
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_api_requests');
        Schema::dropIfExists('delivery_impacts');

        Schema::table('delivery_tasks', function (Blueprint $table) {
            $table->dropIndex('delivery_volunteer_status_index');
            $table->dropColumn('delivery_notes');
        });
    }
};
