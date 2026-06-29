<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('phone_no')->nullable();
            $table->enum('role', ['FOOD_DONOR', 'CHARITY', 'VOLUNTEER', 'ADMIN']);
            $table->enum('account_status', ['ACTIVE', 'INACTIVE', 'SUSPENDED', 'DELETED'])->default('ACTIVE');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('partner_profiles', function (Blueprint $table) {
            $table->id('profile_id');
            $table->foreignId('user_id')->unique()->constrained('users', 'user_id');
            $table->text('address')->nullable();
            $table->enum('verification_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('verification_documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->foreignId('partner_id')->constrained('partner_profiles', 'profile_id');
            $table->string('document_type');
            $table->string('file_path');
            $table->enum('document_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
        });

        Schema::create('verification_reviews', function (Blueprint $table) {
            $table->id('review_id');
            $table->foreignId('partner_id')->constrained('partner_profiles', 'profile_id');
            $table->foreignId('reviewed_by')->constrained('users', 'user_id');
            $table->enum('decision', ['APPROVED', 'REJECTED']);
            $table->text('remarks')->nullable();
            $table->timestamp('reviewed_at')->useCurrent();
        });

        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id('session_id');
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->string('session_token');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('login_at')->useCurrent();
            $table->timestamp('logout_at')->nullable();
            $table->enum('session_status', ['ACTIVE', 'LOGGED_OUT'])->default('ACTIVE');
        });

        Schema::create('food_categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('category_name');
            $table->text('description')->nullable();
        });

        Schema::create('food_donations', function (Blueprint $table) {
            $table->id('donation_id');
            $table->foreignId('donor_id')->constrained('partner_profiles', 'profile_id');
            $table->foreignId('category_id')->constrained('food_categories', 'category_id');
            $table->string('food_name');
            $table->text('description')->nullable();
            $table->decimal('donation_quantity', 10, 2);
            $table->decimal('current_quantity', 10, 2);
            $table->string('measurement_unit');
            $table->dateTime('expiry_datetime');
            $table->text('pickup_address');
            $table->string('storage_type')->nullable();
            $table->string('halal_status')->nullable();
            $table->enum('donation_status', ['AVAILABLE', 'RESERVED', 'COMPLETED', 'CANCELLED', 'EXPIRED'])->default('AVAILABLE');
            $table->timestamp('donation_datetime')->useCurrent();
        });

        Schema::create('donation_photos', function (Blueprint $table) {
            $table->id('photo_id');
            $table->foreignId('donation_id')->constrained('food_donations', 'donation_id');
            $table->string('file_path');
            $table->timestamp('uploaded_at')->useCurrent();
        });

        Schema::create('donation_status_histories', function (Blueprint $table) {
            $table->id('donation_history_id');
            $table->foreignId('donation_id')->constrained('food_donations', 'donation_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->foreignId('changed_by')->constrained('users', 'user_id');
            $table->timestamp('changed_at')->useCurrent();
            $table->text('remarks')->nullable();
        });

        Schema::create('food_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->foreignId('charity_id')->constrained('partner_profiles', 'profile_id');
            $table->foreignId('category_id')->constrained('food_categories', 'category_id');
            $table->decimal('requested_quantity', 10, 2);
            $table->decimal('fulfilled_quantity', 10, 2)->default(0);
            $table->string('unit');
            $table->dateTime('request_deadline');
            $table->enum('request_status', ['OPEN', 'FULFILLED', 'CANCELLED'])->default('OPEN');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id('reservation_id');
            $table->foreignId('request_id')->constrained('food_requests', 'request_id');
            $table->foreignId('donation_id')->constrained('food_donations', 'donation_id');
            $table->decimal('reserved_quantity', 10, 2);
            $table->enum('reservation_status', ['PENDING', 'CONFIRMED', 'CANCELLED', 'COMPLETED'])->default('CONFIRMED');
            $table->dateTime('pickup_deadline');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('delivery_tasks', function (Blueprint $table) {
            $table->id('delivery_id');
            $table->foreignId('reservation_id')->unique()->constrained('reservations', 'reservation_id');
            $table->foreignId('volunteer_id')->constrained('partner_profiles', 'profile_id');
            $table->text('pickup_address');
            $table->text('delivery_address');
            $table->enum('delivery_status', ['ASSIGNED', 'PICKED_UP', 'DELIVERED', 'CANCELLED'])->default('ASSIGNED');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
        });

        Schema::create('delivery_status_histories', function (Blueprint $table) {
            $table->id('delivery_history_id');
            $table->foreignId('delivery_id')->constrained('delivery_tasks', 'delivery_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->foreignId('changed_by')->constrained('users', 'user_id');
            $table->timestamp('changed_at')->useCurrent();
            $table->text('remarks')->nullable();
        });
    }

    public function down(): void
    {
        foreach ([
            'delivery_status_histories',
            'delivery_tasks',
            'reservations',
            'food_requests',
            'donation_status_histories',
            'donation_photos',
            'food_donations',
            'food_categories',
            'user_sessions',
            'verification_reviews',
            'verification_documents',
            'partner_profiles',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
