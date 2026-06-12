<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpecialRemarkToCustomers extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'special_remark')) {
                $table->text('special_remark')->nullable()->after('visit_date');
            }

            if (!Schema::hasColumn('customers', 'special_remark_by')) {
                $table->foreignId('special_remark_by')
                    ->nullable()
                    ->after('special_remark')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('customers', 'special_remark_at')) {
                $table->timestamp('special_remark_at')->nullable()->after('special_remark_by');
            }
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'special_remark_by')) {
                $table->dropForeign(['special_remark_by']);
                $table->dropColumn('special_remark_by');
            }

            if (Schema::hasColumn('customers', 'special_remark_at')) {
                $table->dropColumn('special_remark_at');
            }

            if (Schema::hasColumn('customers', 'special_remark')) {
                $table->dropColumn('special_remark');
            }
        });
    }
}
