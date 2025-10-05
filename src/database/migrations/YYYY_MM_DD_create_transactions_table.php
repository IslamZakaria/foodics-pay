<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->unsignedBigInteger('client_id')->index();
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date')->index();
            $table->string('bank_type', 50);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};