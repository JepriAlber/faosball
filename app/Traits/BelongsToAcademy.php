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

        $academyService = app(AcademyService::class);

        // Super Admin tidak punya academy aktif (currentId() selalu null), tapi
        // boleh membuat data untuk academy manapun selama Service sudah
        // menentukan id_academy secara eksplisit (mis. dari dropdown Academy).
        // User academy TIDAK boleh lewat jalur ini -- id_academy mereka selalu
        // dipaksa dari academy sendiri, apapun yang coba mereka kirim.
        if ($academyService->isSuperAdmin() && !empty($model->id_academy)) {
            return;
        }

        $academyId = $academyService->currentId();


        if(!$academyId){

            throw new \Exception(
                'Academy harus dipilih.'
            );

        }


        $model->id_academy = $academyId;

    });

    }

}