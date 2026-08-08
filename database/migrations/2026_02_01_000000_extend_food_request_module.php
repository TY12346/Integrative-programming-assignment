<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : database/migrations/2026_02_01_000000_extend_food_request_module.php
 * Purpose: Additive migration for the Food Request Management module.
 *          It is kept separate from the shared base migration so that the other
 *          team members' modules are not disturbed by my schema changes.
 *
 *          1. Widens food_requests.request_status to the lifecycle required by
 *             module 3.3 (PENDING / PARTIALLY_FULFILLED / COMPLETED /
 *             CANCELLED / EXPIRED) and migrates the old OPEN / FULFILLED rows.
 *          2. Adds food_requests.notes so a charity can state special
 *             requirements, plus indexes used by the request dashboard.
 *          3. Adds users.api_token (SHA-256 hash) used by the module's REST
 *             web service for bearer token authentication.
 *
 *          No new entity class is introduced: the analysis class diagram is the
 *          contract for the team, so the request lifecycle is derived from the
 *          request row and its reservations rather than from a separate history
 *          table.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Statuses used by the Food Request Management module. */
    private const STATUSES = ['PENDING', 'PARTIALLY_FULFILLED', 'COMPLETED', 'CANCELLED', 'EXPIRED'];

    public function up(): void
    {
        Schema::table('food_requests', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('unit');
            // Dashboard lists always filter by owner + status, and sort by deadline.
            $table->index(['charity_id', 'request_status'], 'food_requests_owner_status_index');
            $table->index('request_deadline', 'food_requests_deadline_index');
        });

        $this->rewriteStatusColumn();

        Schema::table('users', function (Blueprint $table) {
            // Stores a SHA-256 hash of the API token, never the token itself.
            $table->string('api_token', 64)->nullable()->unique()->after('password_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE food_requests SET request_status = 'PENDING' WHERE request_status IN ('PARTIALLY_FULFILLED', 'EXPIRED')");
            DB::statement("ALTER TABLE food_requests MODIFY request_status ENUM('OPEN','FULFILLED','CANCELLED','PENDING','PARTIALLY_FULFILLED','COMPLETED','EXPIRED') NOT NULL DEFAULT 'OPEN'");
            DB::statement("UPDATE food_requests SET request_status = 'OPEN' WHERE request_status = 'PENDING'");
            DB::statement("UPDATE food_requests SET request_status = 'FULFILLED' WHERE request_status = 'COMPLETED'");
            DB::statement("ALTER TABLE food_requests MODIFY request_status ENUM('OPEN','FULFILLED','CANCELLED') NOT NULL DEFAULT 'OPEN'");
        }

        Schema::table('food_requests', function (Blueprint $table) {
            $table->dropIndex('food_requests_owner_status_index');
            $table->dropIndex('food_requests_deadline_index');
            $table->dropColumn('notes');
        });
    }

    /**
     * Replace the old three-value enum with the module 3.3 lifecycle.
     * Done with raw DDL because an enum has to be widened, back-filled and then
     * narrowed again; that cannot be expressed with a single Blueprint change().
     */
    private function rewriteStatusColumn(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return; // Non-MySQL drivers store the column as text already.
        }

        $combined = $this->enumList(array_merge(['OPEN', 'FULFILLED'], self::STATUSES));
        DB::statement("ALTER TABLE food_requests MODIFY request_status ENUM($combined) NOT NULL DEFAULT 'PENDING'");

        DB::statement("UPDATE food_requests SET request_status = 'PENDING' WHERE request_status = 'OPEN'");
        DB::statement("UPDATE food_requests SET request_status = 'COMPLETED' WHERE request_status = 'FULFILLED'");

        $final = $this->enumList(self::STATUSES);
        DB::statement("ALTER TABLE food_requests MODIFY request_status ENUM($final) NOT NULL DEFAULT 'PENDING'");
    }

    /** Build a quoted enum value list from a fixed, code-controlled array. */
    private function enumList(array $values): string
    {
        return implode(',', array_map(fn (string $value) => "'".$value."'", $values));
    }
};
