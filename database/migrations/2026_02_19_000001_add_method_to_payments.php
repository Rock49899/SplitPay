<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) return;
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'method')) {
                $table->enum('method', ['mtn', 'moov', 'payplus'])->default('payplus')->after('amount')->comment('mtn|moov|payplus');
                // index pour filtres/reports par méthode (MTN/MOOV)
                $table->index('method', 'payments_method_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) return;
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'method')) {
                // supprimer l'index avant la colonne si présent
                if (Schema::hasColumn('payments', 'method')) {
                    $sm = Schema::getConnection()->getDoctrineSchemaManager();
                    $indexes = array_map(function($i){ return $i->getName(); }, $sm->listTableIndexes('payments'));
                    if (in_array('payments_method_index', $indexes)) {
                        $table->dropIndex('payments_method_index');
                    }
                }
                $table->dropColumn('method');
            }
        });
    }
};
