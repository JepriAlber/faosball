<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Academy extends Model
{
    use HasFactory;

    protected $table        = 'academies';
    protected $primaryKey   = 'id_academy'; 
    public $incrementing    = false; 
    protected $keyType      = 'string';


    protected $fillable = [
        'id_owner_user',
        'name',
        'code',
        'slug',
        'phone',
        'email',
        'address',
        'tagline',
        'status',
        'subscription_type',
        'subscription_fee',
        'subscription_started_at',
        'subscription_ends_at',
        'logo',
        'logo_sidebar',
        'logo_favicon',
        'description',
    ];


    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'subscription_fee' => 'decimal:2',
            'subscription_started_at' => 'date',
            'subscription_ends_at' => 'date',
        ];
    }


    public function owner()
    {
        return $this->belongsTo(User::class, 'id_owner_user');
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