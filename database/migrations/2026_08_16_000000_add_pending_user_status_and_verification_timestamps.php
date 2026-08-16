<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['PENDING', 'ACTIVE', 'INACTIVE', 'SUSPENDED', 'DELETED'])
                ->default('PENDING')
                ->change();
        });

        if (! Schema::hasColumn('verification_documents', 'submitted_at')) {
            Schema::table('verification_documents', function (Blueprint $table) {
                $table->timestamp('submitted_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['ACTIVE', 'INACTIVE', 'SUSPENDED', 'DELETED'])
                ->default('ACTIVE')
                ->change();
        });

        if (Schema::hasColumn('verification_documents', 'submitted_at')) {
            Schema::table('verification_documents', function (Blueprint $table) {
                $table->dropColumn('submitted_at');
            });
        }
    }
};