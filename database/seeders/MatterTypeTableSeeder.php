<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MatterTypeTableSeeder extends Seeder
{
    public function run()
    {
        $translations = TranslatedAttributesSeeder::getMatterTypes();
        DB::table('matter_type')->insertOrIgnore([
            [
                'code' => 'CIP',
                'type' => json_encode($translations['CIP']),
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CNT',
                'type' => json_encode($translations['CNT']),
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'DIV',
                'type' => json_encode($translations['DIV']),
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'PRO',
                'type' => json_encode($translations['PRO']),
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'REI',
                'type' => json_encode($translations['REI']),
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'REX',
                'type' => json_encode($translations['REX']),
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
