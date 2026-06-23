<?php

namespace App\Models;

use Database\Factories\UserFactory;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


#[Fillable([
    'id_academy',
    'name',
    'email',
    'password',
    'status',
])]

#[Hidden([
    'password',
    'remember_token',
])]

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory,
        Notifiable,
        SoftDeletes;


    /**
     * Primary Key
     */
    protected $primaryKey = 'id_user';


    /**
     * UUID Configuration
     */
    public $incrementing = false;


    protected $keyType = 'string';



    /**
     * Generate UUID automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            if (empty($model->id_user)) {

                $model->id_user = (string) Str::uuid();

            }

        });
    }



    /**
     * Attribute Casting
     */
    protected function casts(): array
    {
        return [

            'email_verified_at' => 'datetime', 
            'password' => 'hashed', 
            'status' => 'boolean', 
            'last_login_at' => 'datetime',

        ];
    }



    /**
     * User belongs to Academy
     */
    public function academy()
    {
        return $this->belongsTo(
            Academy::class,
            'id_academy',
            'id_academy'
        );
    }
}