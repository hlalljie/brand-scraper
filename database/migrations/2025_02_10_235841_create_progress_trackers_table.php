<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('progress_trackers', function (Blueprint $table) {
            $table->id();
            $table->boolean('done')->default(false);
            $table->string('status')->default('validating');
            $table->json('results')->nullable();
            $table->integer('completed_batches')->default(0);
            $table->integer('total_batches')->default(0);
            $table->timestamps();  // Gives you created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_trackers');
    }
};
