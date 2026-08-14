<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemindAtToNotificationsTable extends Migration
{
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->timestamp('remind_at')->nullable()->after('is_read');
            $table->index(['user_id', 'remind_at'], 'notifications_user_remind_at_index');
        });
    }

    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_remind_at_index');
            $table->dropColumn('remind_at');
        });
    }
}
