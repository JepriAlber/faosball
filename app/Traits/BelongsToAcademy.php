<?php

namespace App\Traits;

use App\Scopes\AcademyScope;
use App\Services\AcademyService;


trait BelongsToAcademy
{

    protected static function bootBelongsToAcademy()
    {

        static::addGlobalScope(
            new AcademyScope
        );


       static::creating(function ($model) {

        $academyId = app(
            AcademyService::class
        )->currentId();


        if(!$academyId){

            throw new \Exception(
                'Academy harus dipilih.'
            );

        }


        $model->id_academy = $academyId;

    });

    }

}