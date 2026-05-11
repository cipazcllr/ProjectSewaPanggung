<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->string('employee_name');
            $table->string('employee_email')->nullable();
            $table->string('position')->nullable();
            $table->string('period', 7);
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('allowance', 15, 2)->default(0);
            $table->decimal('deduction', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2);
            $table->string('status')->default('Unpaid');
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['period', 'status']);
            $table->index('employee_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
    }
};
