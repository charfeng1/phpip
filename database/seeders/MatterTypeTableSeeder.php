<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MatterTypeTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('matter_type')->insertOrIgnore([

            [
                'code' => 'CIP',
                'type' => json_encode(['en' => 'Continuation in Part', 'fr' => 'Continuation partielle', 'de' => 'Teilfortsetzungsanmeldung', 'zh' => '部分延续']),
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'CNT',
                'type' => json_encode(['en' => 'Continuation', 'fr' => 'Continuation', 'de' => 'Fortsetzungsanmeldung', 'zh' => '延续案']),
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'DIV',
                'type' => json_encode(['en' => 'Divisional', 'fr' => 'Divisionnaire', 'de' => 'Teilanmeldung', 'zh' => '分案']),
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'PRO',
                'type' => json_encode(['en' => 'Provisional', 'fr' => 'Provisoire', 'de' => 'Vorläufige Anmeldung', 'zh' => '临时申请']),
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'REI',
                'type' => json_encode(['en' => 'Reissue', 'fr' => 'Redélivrance', 'de' => 'Neuerteilung', 'zh' => '重新颁发']),
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'REX',
                'type' => json_encode(['en' => 'Re-examination', 'fr' => 'Réexamen', 'de' => 'Neuprüfungsverfahren', 'zh' => '复审']),
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
