<?php

use App\Enums\TransactionType;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', array_column(TransactionType::cases(), 'value'))->default(TransactionType::Transfer->value);
            $table->foreignUuid('account_payer_id')->nullable()->constrained('accounts');
            $table->foreignUuid('account_receiver_id')->constrained('accounts');
            $table->integer('amount');
            $table->boolean('was_returned')->default(false);
            $table->timestamp('returned_at')->nullable();
            $table->foreignUuid('return_of_transaction_id')->nullable()->constrained('transactions');
            $table->foreignUuid('is_returned_by_transaction_id')->nullable()->constrained('transactions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
