<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {


            /*
            |--------------------------------------------------------------------------
            | Primary Key
            |--------------------------------------------------------------------------
            */

            $table->uuid('id_player')
                ->primary();


            /*
            |--------------------------------------------------------------------------
            | Tenant & Account Relation
            |--------------------------------------------------------------------------
            */

            $table->uuid('id_academy');

            $table->uuid('id_user')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Player Identity
            |--------------------------------------------------------------------------
            */

            $table->string('player_code', 30)
                ->unique();


            $table->string('name');

            $table->string('nick_name')
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Biodata
            |--------------------------------------------------------------------------
            */

            $table->date('birth_date');

            $table->enum('gender', [
                'male',
                'female'
            ]);


            $table->string('nationality', 50)
                ->default('Indonesia');



            /*
            |--------------------------------------------------------------------------
            | Physical Information
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('height')
                ->nullable();

            $table->unsignedSmallInteger('weight')
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Football Information
            |--------------------------------------------------------------------------
            */

            $table->enum('preferred_foot', [
                'left',
                'right',
                'both'
            ])
            ->nullable();


            $table->string('primary_position',20);

            $table->string('secondary_position',20)
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Academy Information
            |--------------------------------------------------------------------------
            */

            $table->date('join_date')
                ->nullable();


            $table->enum('status',[
                'active',
                'inactive',
                'graduated',
                'left'
            ])
            ->default('active');



            /*
            |--------------------------------------------------------------------------
            | Media
            |--------------------------------------------------------------------------
            */

            $table->string('photo')
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Additional
            |--------------------------------------------------------------------------
            */

            $table->text('notes')
                ->nullable();



            /*
            |--------------------------------------------------------------------------
            | Timestamp
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            $table->softDeletes();



            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */

            $table->index('id_academy');

            $table->index('id_user');



            /*
            |--------------------------------------------------------------------------
            | Foreign Key
            |--------------------------------------------------------------------------
            */

            $table->foreign('id_academy')
                ->references('id_academy')
                ->on('academies')
                ->cascadeOnDelete();


            $table->foreign('id_user')
                ->references('id_user')
                ->on('users')
                ->nullOnDelete();


        });
    }


    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};