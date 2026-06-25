<?php

namespace App\Scopes;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Services\AcademyService;



class AcademyScope implements Scope
{

    public function apply(  Builder $builder, Model $model ) {

        $academyService = app(AcademyService::class);

        if (!$academyService->isSuperAdmin()) {

            $builder->where(
                $model->getTable().'.id_academy',
                $academyService->currentId()
            );

        }

    }

}