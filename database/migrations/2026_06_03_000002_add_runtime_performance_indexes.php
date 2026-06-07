<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRuntimePerformanceIndexes extends Migration
{
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read', 'id'], 'notifications_user_read_id_index');
            $table->index(['user_id', 'customer_id'], 'notifications_user_customer_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index(['assigned_counselor_id', 'id'], 'customers_counselor_id_index');
            $table->index(['telecaller_id', 'id'], 'customers_telecaller_id_index');
            $table->index(['status', 'id'], 'customers_status_id_index');
        });

        Schema::table('follow_ups', function (Blueprint $table) {
            $table->index(['status', 'user_id'], 'follow_ups_status_user_index');
        });
    }

    public function down()
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropIndex('follow_ups_status_user_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_counselor_id_index');
            $table->dropIndex('customers_telecaller_id_index');
            $table->dropIndex('customers_status_id_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_read_id_index');
            $table->dropIndex('notifications_user_customer_index');
        });
    }
}
