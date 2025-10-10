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
        Schema::table('offers', function (Blueprint $table) {
            $table->boolean('vpn_allowed')->default(false)->after('proxy_check');
            $table->boolean('tor_allowed')->default(false)->after('vpn_allowed');
            $table->unsignedTinyInteger('max_risk_score')->default(50)->after('tor_allowed');
            $table->timestamp('expires_at')->nullable()->after('max_risk_score');
            $table->unsignedInteger('daily_cap')->nullable()->after('expires_at');
            $table->unsignedInteger('total_cap')->nullable()->after('daily_cap');
            $table->decimal('payout', 10, 2)->nullable()->after('total_cap');
            $table->decimal('revenue', 10, 2)->nullable()->after('payout');
            $table->json('targeting_rules')->nullable()->after('revenue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn([
                'vpn_allowed',
                'tor_allowed',
                'max_risk_score',
                'expires_at',
                'daily_cap',
                'total_cap',
                'payout',
                'revenue',
                'targeting_rules'
            ]);
        });
    }
};
