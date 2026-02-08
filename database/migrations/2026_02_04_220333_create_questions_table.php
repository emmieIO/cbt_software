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
        Schema::create('questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('topic_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('school_class_id')->constrained()->cascadeOnDelete();
            $table->ulid('parent_id')->nullable();
            $table->text('content');
            $table->text('explanation')->nullable();
            $table->string('type');
            $table->string('difficulty');
            $table->integer('version')->default(1);
            $table->timestamp('last_used_at')->nullable();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('questions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
