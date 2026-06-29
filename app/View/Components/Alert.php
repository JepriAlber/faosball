<?php

namespace App\View\Components;
 
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Alert extends Component
{
    public ?array $alert=null;

   public function __construct()
    {
        $alerts=[
            'success'=>[
                'message'=>session('success'),
                'class'=>'alert-success',
                'icon'=>'success',
            ],
            'error'=>[
                'message'=>session('error'),
                'class'=>'alert-danger',
                'icon'=>'error',
            ],
            'warning'=>[
                'message'=>session('warning'),
                'class'=>'alert-warning',
                'icon'=>'warning',
            ],
            'info'=>[
                'message'=>session('info'),
                'class'=>'alert-info',
                'icon'=>'info',
            ],
        ];

        foreach($alerts as $alert){
            if($alert['message']){
                $this->alert=$alert;
                break;
            }
        }
    }

    public function render()
    {
        return view('components.alert');
    }
}
