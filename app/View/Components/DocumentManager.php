<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class DocumentManager extends Component
{
    public Model $documentable;
    public string $uploadRoute;
    public array $types;
    public bool $canManage;

    public function __construct(Model $documentable, string $uploadRoute, array $types, bool $canManage = false)
    {
        $this->documentable = $documentable;
        $this->uploadRoute = $uploadRoute;
        $this->types = $types;
        $this->canManage = $canManage;
    }

    public function render(): View
    {
        return view('components.document-manager', [
            'documents' => $this->documentable->documents,
        ]);
    }
}
