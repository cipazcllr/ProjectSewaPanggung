<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->timestamp('email_sent_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->dropColumn('email_sent_at');
        });
    }
};
