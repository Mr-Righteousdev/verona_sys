<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. "S1 East"
            $table->string('level');          // S1..S4
            $table->foreignId('class_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('admission_no')->unique();
            $table->string('gender', 1); // M | F
            $table->timestamps();
        });

        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('colour', 7); // fixed paint colour used everywhere for this competency
            $table->decimal('benchmark', 3, 2)->default(2.00); // expected level on the 1-3 CBC scale
            $table->text('intervention');                       // recommendation shown on alerts
            $table->timestamps();
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('type'); // assignment | test | project | observation
            $table->unsignedTinyInteger('term');
            $table->date('assessed_on');
            $table->timestamps();
        });

        Schema::create('assessment_competency', function (Blueprint $table) {
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->primary(['assessment_id', 'competency_id']);
        });

        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level'); // 1 Basic, 2 Moderate, 3 Outstanding
            $table->timestamps();
            $table->unique(['assessment_id', 'student_id', 'competency_id']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('present');
            $table->timestamps();
            $table->unique(['student_id', 'date']);
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->string('severity');  // low | medium | high
            $table->json('reasons');
            $table->decimal('recent_average', 3, 2);
            $table->decimal('projected_level', 3, 2)->nullable();
            $table->text('recommendation');
            $table->string('status')->default('open'); // open | resolved
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'competency_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['alerts', 'attendances', 'scores', 'assessment_competency', 'assessments', 'competencies', 'students', 'subjects', 'classrooms'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
