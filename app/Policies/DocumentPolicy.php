<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Player;
use App\Models\Staff;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Reuse permission module pemilik dokumen -- BUKAN permission baru
     * document.* (Aturan Emas). Module baru yang butuh Document tinggal
     * tambah 1 arm match baru di kedua method ini.
     */
    public function view(User $user, Document $document): bool
    {
        return match ($document->documentable_type) {
            Staff::class => $user->can('staff.view'),
            Player::class => $user->can('player.view'),
            default => false,
        };
    }

    public function delete(User $user, Document $document): bool
    {
        return match ($document->documentable_type) {
            Staff::class => $user->can('staff.update'),
            Player::class => $user->can('player.update'),
            default => false,
        };
    }
}
