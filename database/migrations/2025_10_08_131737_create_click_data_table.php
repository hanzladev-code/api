<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('click_data', function (Blueprint $table) {
            $table->id();
            $table->uuid('click_id')->unique()->index();
            $table->foreignId('offer_id')->constrained('offers')->onDelete('cascade');
            $table->foreignId('ref_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip', 45);
            $table->string('real_ip', 45);
            $table->text('user_agent')->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('region', 100)->nullable();

            // UTM parameters
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_term', 100)->nullable();
            $table->string('utm_content', 100)->nullable();

            // Sub IDs for tracking
            $table->string('sub_id1', 100)->nullable();
            $table->string('sub_id2', 100)->nullable();
            $table->string('sub_id3', 100)->nullable();
            $table->string('sub_id4', 100)->nullable();
            $table->string('sub_id5', 100)->nullable();
            $table->string('sub_id6', 100)->nullable();
            $table->string('sub_id7', 100)->nullable();
            $table->string('sub_id8', 100)->nullable();
            $table->string('sub_id9', 100)->nullable();
            $table->string('sub_id10', 100)->nullable();

            // Fraud detection
            $table->boolean('vpn_detected')->default(false);
            $table->boolean('proxy_detected')->default(false);
            $table->boolean('tor_detected')->default(false);
            $table->unsignedTinyInteger('ip_risk_score')->default(0);
            $table->unsignedTinyInteger('fraud_score')->default(0);

            // Conversion tracking
            $table->boolean('converted')->default(false);
            $table->timestamp('converted_at')->nullable();
            $table->decimal('payout', 10, 2)->nullable();
            $table->decimal('revenue', 10, 2)->nullable();

            // Additional metadata
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            // Indexes for faster queries
            $table->index('click_id');
            $table->index('status');
            $table->index('converted');
            $table->index('converted_at');
            $table->index('payout');
            $table->index('revenue');
            $table->index('created_at');
            $table->index(['offer_id', 'created_at']);
            $table->index(['ref_id', 'created_at']);
            $table->index(['country', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('click_data');
    }
};
