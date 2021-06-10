<?php

use Actengage\Wizard\Session;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWizardSessionsTable extends Migration
{
    protected $table;
    
    public function __construct()
    {
        $this->table = config(
            'wizard.session.model', Session::class
        )::make()->getTable();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->string('id', 64)->unique();
            $table->json('data');
            $table->morphs('user');
            $table->nullableMorphs('model');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}
