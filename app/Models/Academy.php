<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Academy extends Model
{
    use HasUuids;

    protected $table = 'academies';
    protected $primaryKey = 'id_academy';
    public $incrementing = false;
    protected $keyType = 'string';

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
}
