<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; 
use Illuminate\Support\Str;

class Academy extends Model
{ 

    protected $table        = 'academies'; 
    protected $primaryKey   = 'id_academy'; 
    public $incrementing    = false; 
    protected $keyType      = 'string';


    protected $fillable = [
        'name',
        'slug',
        'phone',
        'email',
        'address',
        'tagline',
        'status',
        'logo',
        'description',
    ];


    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }


    protected static function boot()
    {
        parent::boot(); 

        static::creating(function ($model){

            if(empty($model->id_academy)){ 
                $model->id_academy = (string) Str::uuid(); 
            }

        });

    }
}