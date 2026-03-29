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
        if (! Schema::hasTable('school_user')) {
            Schema::create('school_user', function (Blueprint $table) {
                $table->id();
                $table->char('school_id', 26)->index();
                $table->char('user_id', 26)->index();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['school_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_user');
    }
};
