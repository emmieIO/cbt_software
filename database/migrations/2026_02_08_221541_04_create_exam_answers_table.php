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
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('exam_attempt_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('question_id')->constrained()->onDelete('cascade');
            
            $table->text('answer_text')->nullable();
            $table->json('selected_options')->nullable()->comment('For multiple choice/checkbox');
            
            $table->decimal('score', 5, 2)->default(0.00);
            $table->boolean('is_correct')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};