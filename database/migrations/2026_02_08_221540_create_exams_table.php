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
        Schema::create('exams', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('subject_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('school_class_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('academic_session_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('created_by')->constrained('users')->onDelete('cascade');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            
            $table->integer('duration')->comment('Duration in minutes');
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            
            $table->string('type'); // App\Enums\ExamType
            $table->string('status'); // App\Enums\ExamStatus
            
            $table->json('settings')->nullable()->comment('Config for randomization, proctoring, etc');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};