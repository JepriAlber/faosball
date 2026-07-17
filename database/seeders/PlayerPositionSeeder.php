<?php

namespace Database\Seeders;

use App\Models\PlayerPosition;
use Illuminate\Database\Seeder;

class PlayerPositionSeeder extends Seeder
{
    /**
     * Master posisi pemain -- data GLOBAL, dipakai seluruh academy.
     *
     * sort_order sengaja diberi jarak (1, 10-15, 20-24, 30-34) supaya posisi
     * baru bisa disisipkan di tengah tanpa menomori ulang semuanya.
     */
    public function run(): void
    {
        $positions = [

            // Goalkeeper
            ['code' => 'GK',  'name' => 'Goalkeeper',           'position_group' => 'Goalkeeper', 'sort_order' => 1,  'description' => 'Penjaga gawang.'],

            // Defender
            ['code' => 'SW',  'name' => 'Sweeper',              'position_group' => 'Defender',   'sort_order' => 10, 'description' => 'Bek penyapu di belakang bek tengah.'],
            ['code' => 'CB',  'name' => 'Center Back',          'position_group' => 'Defender',   'sort_order' => 11, 'description' => 'Bek tengah.'],
            ['code' => 'LB',  'name' => 'Left Back',            'position_group' => 'Defender',   'sort_order' => 12, 'description' => 'Bek kiri.'],
            ['code' => 'RB',  'name' => 'Right Back',           'position_group' => 'Defender',   'sort_order' => 13, 'description' => 'Bek kanan.'],
            ['code' => 'LWB', 'name' => 'Left Wing Back',       'position_group' => 'Defender',   'sort_order' => 14, 'description' => 'Bek sayap kiri yang aktif menyerang.'],
            ['code' => 'RWB', 'name' => 'Right Wing Back',      'position_group' => 'Defender',   'sort_order' => 15, 'description' => 'Bek sayap kanan yang aktif menyerang.'],

            // Midfielder
            ['code' => 'CDM', 'name' => 'Defensive Midfielder', 'position_group' => 'Midfielder', 'sort_order' => 20, 'description' => 'Gelandang bertahan.'],
            ['code' => 'CM',  'name' => 'Center Midfielder',    'position_group' => 'Midfielder', 'sort_order' => 21, 'description' => 'Gelandang tengah.'],
            ['code' => 'CAM', 'name' => 'Attacking Midfielder', 'position_group' => 'Midfielder', 'sort_order' => 22, 'description' => 'Gelandang serang.'],
            ['code' => 'LM',  'name' => 'Left Midfielder',      'position_group' => 'Midfielder', 'sort_order' => 23, 'description' => 'Gelandang kiri.'],
            ['code' => 'RM',  'name' => 'Right Midfielder',     'position_group' => 'Midfielder', 'sort_order' => 24, 'description' => 'Gelandang kanan.'],

            // Forward
            ['code' => 'LW',  'name' => 'Left Winger',          'position_group' => 'Forward',    'sort_order' => 30, 'description' => 'Penyerang sayap kiri.'],
            ['code' => 'RW',  'name' => 'Right Winger',         'position_group' => 'Forward',    'sort_order' => 31, 'description' => 'Penyerang sayap kanan.'],
            ['code' => 'SS',  'name' => 'Second Striker',       'position_group' => 'Forward',    'sort_order' => 32, 'description' => 'Penyerang bayangan.'],
            ['code' => 'CF',  'name' => 'Center Forward',       'position_group' => 'Forward',    'sort_order' => 33, 'description' => 'Penyerang tengah.'],
            ['code' => 'ST',  'name' => 'Striker',              'position_group' => 'Forward',    'sort_order' => 34, 'description' => 'Penyerang utama.'],

        ];

        foreach ($positions as $position) {

            PlayerPosition::firstOrCreate(
                ['code' => $position['code']],
                $position
            );
        }
    }
}
