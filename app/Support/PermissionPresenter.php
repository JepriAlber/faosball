<?php

namespace App\Support;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class PermissionPresenter
{
    /**
     * Representasi permission untuk kebutuhan tampilan (form & detail).
     */
    public static function present(Permission $permission): array
    {
        return [
            'name' => $permission->name,
            'action' => self::actionLabel($permission->name),
            'label' => self::label($permission->name),
            'description' => self::description($permission->name),
            'badge' => self::badge($permission->name),
        ];
    }

    public static function action(string $permission): string
    {
        return explode('.', $permission)[1] ?? '';
    }

    public static function module(string $permission): string
    {
        return explode('.', $permission)[0] ?? '';
    }

    public static function label(string $permission): string
    {
        $actions = [
            'view' => 'Lihat',
            'create' => 'Tambah',
            'update' => 'Ubah',
            'delete' => 'Hapus',
            'export' => 'Export',
            'report' => 'Laporan',
        ];

        return ($actions[self::action($permission)] ?? ucfirst(self::action($permission)))
            . ' '
            . self::moduleLabel($permission);
    }

    /**
     * Label module untuk tampilan.
     *
     * Module yang belum terdaftar jatuh ke Str::headline() supaya tetap
     * terbaca ("player_type" -> "Player Type"), bukan "Player_type".
     */
    public static function moduleLabel(string $permission): string
    {
        $modules = [
            'academy' => 'Academy',
            'role' => 'Role',
            'permission' => 'Permission',
            'player' => 'Player',
            'player_position' => 'Player Position',
            'player_type' => 'Player Type',
            'player_category' => 'Player Category',
            'coach' => 'Coach',
            'team' => 'Team',
            'season' => 'Season',
            'team_staff_position' => 'Team Staff Position',
            'training' => 'Training',
            'attendance' => 'Attendance',
            'evaluation' => 'Evaluation',
            'payment' => 'Payment',
            'report' => 'Report',
            'user' => 'User',
        ];

        return $modules[self::module($permission)]
            ?? Str::headline(self::module($permission));
    }

    public static function description(string $permission): string
    {
        return match (self::action($permission)) {
            'view' => 'Mengizinkan pengguna melihat data ' . strtolower(self::moduleLabel($permission)) . '.',
            'create' => 'Mengizinkan pengguna menambahkan data ' . strtolower(self::moduleLabel($permission)) . '.',
            'update' => 'Mengizinkan pengguna mengubah data ' . strtolower(self::moduleLabel($permission)) . '.',
            'delete' => 'Mengizinkan pengguna menghapus data ' . strtolower(self::moduleLabel($permission)) . '.',
            'export' => 'Mengizinkan pengguna mengekspor data ' . strtolower(self::moduleLabel($permission)) . '.',
            'report' => 'Mengizinkan pengguna melihat laporan ' . strtolower(self::moduleLabel($permission)) . '.',
            default => $permission,
        };
    }

    public static function badge(string $permission): string
    {
        return match (self::action($permission)) {
            'view' => 'badge-primary',
            'create' => 'badge-success',
            'update' => 'badge-warning',
            'delete' => 'badge-danger',
            'export' => 'badge-info',
            'report' => 'badge-info',
            default => 'badge-secondary',
        };
    }

    public static function actionLabel(string $permission): string
    {
        return ucfirst(self::action($permission));
    }

    /**
     * Daftar action yang dikenali sistem, dipakai sebagai opsi datalist
     * pada form Create Permission.
     */
    public static function actions(): array
    {
        return ['view', 'create', 'update', 'delete', 'export', 'report'];
    }
}