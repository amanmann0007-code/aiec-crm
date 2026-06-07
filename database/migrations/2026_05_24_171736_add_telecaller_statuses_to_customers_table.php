<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddTelecallerStatusesToCustomersTable extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM(
            'walkin',
            'assigned',
            'interested',
            'wv again',
            'pursuing ielts/pte',
            'arranging docs',
            'in process',
            'not eligible',
            'plan drop',
            'will visit',
            'drop',
            'jfi'
        ) NOT NULL DEFAULT 'walkin'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM(
            'walkin',
            'assigned',
            'interested',
            'wv again',
            'pursuing ielts/pte',
            'arranging docs',
            'in process',
            'not eligible',
            'plan drop'
        ) NOT NULL DEFAULT 'walkin'");
    }
}
