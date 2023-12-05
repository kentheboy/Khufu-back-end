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
        Schema::create('customfields_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customfield_id');
            $table->unsignedBigInteger('entity_id')->comment('specify module, either product, schedule or other.');
            $table->unsignedBigInteger('master_id')->comment('specify main module\'s record id.');
            $table->string('value', 250);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customfields_data');
    }
};
