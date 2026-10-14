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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained('rooms')
                ->noActionOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->noActionOnDelete();

            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            $table->string('title');
            $table->integer('participants_count');
            $table->text('notes')->nullable();

            $table->foreignId('status_id')
                ->constrained('booking_statuses')
                ->noActionOnDelete();

            $processedBy = $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users');

            // SQL Server rejects the cascade paths users -> bookings ->
            // booking_attachments and users -> booking_attachments.
            if (Schema::getConnection()->getDriverName() === 'sqlsrv') {
                $processedBy->noActionOnDelete();
            } else {
                $processedBy->nullOnDelete();
            }

            $table->timestamp('processed_at')->nullable();
            $table->text('processed_notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index(['room_id', 'date']);
            $table->index(['date', 'status_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
