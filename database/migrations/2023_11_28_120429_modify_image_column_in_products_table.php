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
        Schema::table('products', function (Blueprint $table) {
            $table->string('image1')->nullable()->change();
            $table->string('image2')->nullable()->change();
            $table->string('image3')->nullable()->change();
            $table->string('image4')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image1')->nullable(false)->change();
            $table->string('image2')->nullable(false)->change();
            $table->string('image3')->nullable(false)->change();
            $table->string('image4')->nullable(false)->change();
        });
    }
};
