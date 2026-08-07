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
        Schema::table('devices', function (Blueprint $table) {
            $table->string('sim_status')->nullable()->default('UNKNOWN')->after('operator');
            $table->string('reg_status')->nullable()->default('UNKNOWN')->after('sim_status');
            $table->string('pending_command')->nullable()->after('reg_status');
            $table->text('command_response')->nullable()->after('pending_command');
            $table->timestamp('command_updated_at')->nullable()->after('command_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'sim_status',
                'reg_status',
                'pending_command',
                'command_response',
                'command_updated_at',
            ]);
        });
    }
};
