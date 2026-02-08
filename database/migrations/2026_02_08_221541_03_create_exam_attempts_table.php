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
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('exam_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('exam_version_id')->constrained()->onDelete('cascade');
            
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            
            $table->decimal('score', 8, 2)->default(0.00);
            $table->string('status')->default('ongoing'); // App\Enums\AttemptStatus
            
            $table->json('metadata')->nullable()->comment('Browser info, IP, etc');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};