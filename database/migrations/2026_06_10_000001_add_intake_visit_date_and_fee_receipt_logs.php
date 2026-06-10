<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIntakeVisitDateAndFeeReceiptLogs extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'intake_month')) {
                $table->unsignedTinyInteger('intake_month')->nullable()->after('visa_type');
            }
            if (!Schema::hasColumn('customers', 'intake_year')) {
                $table->unsignedSmallInteger('intake_year')->nullable()->after('intake_month');
            }
            if (!Schema::hasColumn('customers', 'visit_date')) {
                $table->date('visit_date')->nullable()->after('status');
            }
        });

        Schema::create('fee_receipt_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_no', 40)->unique();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fee_receipt_logs');

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'visit_date')) {
                $table->dropColumn('visit_date');
            }
            if (Schema::hasColumn('customers', 'intake_year')) {
                $table->dropColumn('intake_year');
            }
            if (Schema::hasColumn('customers', 'intake_month')) {
                $table->dropColumn('intake_month');
            }
        });
    }
}
