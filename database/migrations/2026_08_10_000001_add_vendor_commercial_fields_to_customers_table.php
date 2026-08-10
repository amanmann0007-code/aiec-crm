<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVendorCommercialFieldsToCustomersTable extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'vendor_name')) {
                $table->string('vendor_name', 255)->nullable()->after('margin');
            }

            if (!Schema::hasColumn('customers', 'vendor_quotation_path')) {
                $table->string('vendor_quotation_path', 500)->nullable()->after('vendor_name');
            }
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            foreach (['vendor_quotation_path', 'vendor_name'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
