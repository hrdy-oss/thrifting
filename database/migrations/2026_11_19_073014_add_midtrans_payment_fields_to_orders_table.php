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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('status');
            $table->string('payment_status')->default('unpaid')->after('payment_gateway')->index();
            $table->string('payment_reference')->nullable()->after('payment_status');
            $table->text('payment_url')->nullable()->after('payment_reference');
            $table->string('payment_method')->nullable()->after('payment_url');
            $table->string('midtrans_transaction_id')->nullable()->after('payment_method');
            $table->timestamp('paid_at')->nullable()->after('midtrans_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn([
                'payment_gateway',
                'payment_status',
                'payment_reference',
                'payment_url',
                'payment_method',
                'midtrans_transaction_id',
                'paid_at',
            ]);
        });
    }
};
