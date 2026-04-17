<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('truepos_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->index();
            $table->string('transaction_id')->nullable()->index();
            $table->string('gateway', 50);
            $table->string('transaction_type', 30);
            $table->string('payment_model', 30)->nullable();
            $table->string('status', 30);
            $table->unsignedBigInteger('amount');
            $table->string('currency', 5)->default('949');
            $table->unsignedTinyInteger('installment')->default(0);
            $table->string('auth_code')->nullable();
            $table->string('response_code', 20)->nullable();
            $table->string('response_message')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->string('host_reference_number')->nullable();
            $table->string('md_status', 5)->nullable();
            $table->string('card_bin', 6)->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->json('raw_response')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truepos_transactions');
    }
};
