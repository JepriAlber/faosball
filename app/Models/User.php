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
use Spatie\Permission\Traits\HasRoles;


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

    use HasFactory,
        Notifiable,
        SoftDeletes,
        HasRoles;

    protected $primaryKey = 'id_user';
    public $incrementing = false;
    protected $keyType = 'string';



    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {

            if(empty($model->id_user)) {
                $model->id_user = (string) Str::uuid();
            }

        });

    }


    protected function casts(): array
    {
        return [

            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
            'last_login_at' => 'datetime',

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Relation Academy
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


}