<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel untuk member MLM
        Schema::create('mlm_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('sponsor_id')->nullable()->constrained('mlm_members')->onDelete('set null');
            $table->integer('level')->default(1);
            $table->integer('total_downlines')->default(0);
            $table->decimal('total_commission_earned', 15, 2)->default(0);
            $table->decimal('pending_commission', 15, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index('sponsor_id');
        });

        // Tabel untuk tracking referral/downline relationships
        Schema::create('mlm_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_member_id')->constrained('mlm_members')->onDelete('cascade');
            $table->foreignId('downline_member_id')->constrained('mlm_members')->onDelete('cascade');
            $table->integer('level'); // Level dari sponsor (1 = direct, 2 = second level, etc)
            $table->timestamp('referred_at')->useCurrent();
            $table->timestamps();

            $table->unique(['sponsor_member_id', 'downline_member_id']);
            $table->index(['sponsor_member_id', 'level']);
        });

        // Tabel untuk komisi MLM
        Schema::create('mlm_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mlm_member_id')->constrained('mlm_members')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('commission_type', 50); // referral, level, bonus, etc
            $table->decimal('amount', 15, 2);
            $table->integer('from_level')->nullable(); // Level downline yang menghasilkan komisi
            $table->foreignId('from_member_id')->nullable()->constrained('mlm_members')->onDelete('set null');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('earned_at')->useCurrent();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['mlm_member_id', 'status']);
            $table->index('order_id');
        });

        // Tabel untuk rank/achievement MLM
        Schema::create('mlm_ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->integer('min_downlines')->default(0);
            $table->decimal('min_sales', 15, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0); // Percentage
            $table->json('benefits')->nullable(); // JSON untuk benefit tambahan
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabel untuk member rank history
        Schema::create('mlm_member_ranks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mlm_member_id')->constrained('mlm_members')->onDelete('cascade');
            $table->foreignId('mlm_rank_id')->constrained('mlm_ranks')->onDelete('cascade');
            $table->timestamp('achieved_at')->useCurrent();
            $table->timestamp('expired_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['mlm_member_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mlm_member_ranks');
        Schema::dropIfExists('mlm_ranks');
        Schema::dropIfExists('mlm_commissions');
        Schema::dropIfExists('mlm_referrals');
        Schema::dropIfExists('mlm_members');
    }
};
