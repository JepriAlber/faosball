<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


class Player extends FaosModel
{

    use SoftDeletes;

    protected $table = 'players';
    protected $primaryKey = 'id_player';


    protected $fillable = [

        'id_user',
        'player_code',
        'name',
        'nick_name',
        'birth_date',
        'gender',
        'nationality',
        'height',
        'weight',
        'preferred_foot',
        'primary_position',
        'secondary_position',
        'join_date',
        'status',
        'photo',
        'notes',

    ];



    protected function casts(): array
    {
        return [

            'birth_date' => 'date',
            'join_date' => 'date',
            'height' => 'integer',
            'weight' => 'integer',
            'deleted_at' => 'datetime',

        ];
    }



    /*
    |--------------------------------------------------------------------------
    | Relationship Academy
    |--------------------------------------------------------------------------
    */


    public function academy()
    {
        return $this->belongsTo(
            Academy::class,
            'id_academy',
            'id_academy'
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Relationship User Account
    |--------------------------------------------------------------------------
    */


    public function user()
    {
        return $this->belongsTo(
            User::class,
            'id_user',
            'id_user'
        );
    }



}