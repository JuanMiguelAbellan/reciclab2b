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
            $table->decimal('exact_latitude', 10, 7)->nullable()->after('municipality');
            $table->decimal('exact_longitude', 10, 7)->nullable()->after('exact_latitude');
            $table->decimal('public_latitude', 10, 7)->nullable()->after('exact_longitude');
            $table->decimal('public_longitude', 10, 7)->nullable()->after('public_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['exact_latitude', 'exact_longitude', 'public_latitude', 'public_longitude']);
        });
    }
};
