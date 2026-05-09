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
        Schema::table('grades', function (Blueprint $table) {
            $table->boolean('locked')->default(false)->after('active');
            $table->timestamp('locked_at')->nullable()->after('locked');
            $table->foreignId('locked_by')->nullable()->constrained('users')->after('locked_at');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign(['locked_by']);
            $table->dropColumn(['locked', 'locked_at', 'locked_by']);
        });
    }
};
