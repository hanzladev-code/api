<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->foreignId('network_id')->constrained('networks')->onDelete('cascade');
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->json('device_urls');
            $table->string('age');
            $table->string('click_rate');
            $table->text('details')->nullable();
            $table->longText('countries');
            $table->enum('status', ['active', 'paused', 'draft'])->default('draft');
            $table->string('port');
            $table->boolean('allow_multiple_clicks')->default(false);
            $table->boolean('proxy_check')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
