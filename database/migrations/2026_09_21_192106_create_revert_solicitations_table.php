<?php

use App\Enums\RevertSolicitationStatus;
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
        Schema::create('revert_solicitations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('transaction_id')->constrained('transactions');
            $table->foreignUuid('requester_account_id')->constrained('accounts');
            $table->foreignUuid('approver_account_id')->constrained('accounts');
            $table->enum('status', array_column(RevertSolicitationStatus::cases(), 'value'))->default(RevertSolicitationStatus::Pending->value);
            $table->timestamps();
            $table->index(['requester_account_id', 'status']);
            $table->index(['approver_account_id', 'status']);
            $table->index(['transaction_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revert_solicitations');
    }
};
