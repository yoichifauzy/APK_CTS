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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('role')
                ->constrained('categories')
                ->nullOnDelete();

            // Status teknisi/agent: free (nganggur) atau busy (sedang handle ticket)
            $table->string('availability_status')
                ->nullable()
                ->after('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn('availability_status');
        });
    }
};
