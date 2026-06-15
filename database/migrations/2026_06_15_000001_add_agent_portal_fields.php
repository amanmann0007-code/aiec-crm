<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAgentPortalFields extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'receptionist', 'counselor', 'telecaller', 'director', 'agent') DEFAULT 'telecaller'");

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'agent_contact')) {
                $table->string('agent_contact', 50)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'agent_branch')) {
                $table->string('agent_branch', 120)->nullable()->after('agent_contact');
            }
            if (!Schema::hasColumn('users', 'agent_reference_from')) {
                $table->string('agent_reference_from')->nullable()->after('agent_branch');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'agent_id')) {
                $table->foreignId('agent_id')->nullable()->after('telecaller_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('customers', 'visa_duration')) {
                $table->string('visa_duration', 120)->nullable()->after('special_remark_at');
            }
            if (!Schema::hasColumn('customers', 'actual_cost')) {
                $table->decimal('actual_cost', 12, 2)->nullable()->after('visa_duration');
            }
            if (!Schema::hasColumn('customers', 'b2b_cost')) {
                $table->decimal('b2b_cost', 12, 2)->nullable()->after('actual_cost');
            }
            if (!Schema::hasColumn('customers', 'margin')) {
                $table->decimal('margin', 12, 2)->nullable()->after('b2b_cost');
            }
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'agent_id')) {
                $table->dropForeign(['agent_id']);
                $table->dropColumn('agent_id');
            }
            foreach (['visa_duration', 'actual_cost', 'b2b_cost', 'margin'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach (['agent_contact', 'agent_branch', 'agent_reference_from'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'receptionist', 'counselor', 'telecaller', 'director') DEFAULT 'telecaller'");
    }
}
