<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends FaosModel
{
    use HasFactory;

    protected $table = 'documents';
    protected $primaryKey = 'id_document';

    protected $fillable = [
        'id_academy', 'documentable_type', 'documentable_id',
        'type', 'original_name', 'path', 'mime_type', 'size', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id_user');
    }
}
