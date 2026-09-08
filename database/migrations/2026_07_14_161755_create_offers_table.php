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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('material')->index();
            $table->decimal('quantity_tons', 10, 2);
            $table->decimal('price_per_ton', 10, 2);
            $table->unsignedSmallInteger('generation_year');
            $table->decimal('moisture_percentage', 5, 2)->nullable();
            $table->decimal('impurities_percentage', 5, 2)->nullable();
            $table->string('province')->index();
            $table->string('municipality')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
