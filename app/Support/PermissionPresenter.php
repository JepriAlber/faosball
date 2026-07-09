<?php

namespace App\Support;

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

        $modules = [
            'academy' => 'Academy',
            'role' => 'Role',
            'permission' => 'Permission',
            'player' => 'Player',
            'coach' => 'Coach',
            'team' => 'Team',
            'training' => 'Training',
            'attendance' => 'Attendance',
            'evaluation' => 'Evaluation',
            'payment' => 'Payment',
            'report' => 'Report',
            'user' => 'User',
        ];

        return ($actions[self::action($permission)] ?? ucfirst(self::action($permission)))
            . ' '
            . ($modules[self::module($permission)] ?? ucfirst(self::module($permission)));
    }

    public static function description(string $permission): string
    {
        return match (self::action($permission)) {
            'view' => 'Mengizinkan pengguna melihat data ' . strtolower(self::module($permission)) . '.',
            'create' => 'Mengizinkan pengguna menambahkan data ' . strtolower(self::module($permission)) . '.',
            'update' => 'Mengizinkan pengguna mengubah data ' . strtolower(self::module($permission)) . '.',
            'delete' => 'Mengizinkan pengguna menghapus data ' . strtolower(self::module($permission)) . '.',
            'export' => 'Mengizinkan pengguna mengekspor data ' . strtolower(self::module($permission)) . '.',
            'report' => 'Mengizinkan pengguna melihat laporan ' . strtolower(self::module($permission)) . '.',
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
}