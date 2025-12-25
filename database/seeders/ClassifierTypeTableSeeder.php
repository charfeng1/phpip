<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassifierTypeTableSeeder extends Seeder
{
    public function run()
    {
        $translations = TranslatedAttributesSeeder::getClassifierTypes();

        DB::table('classifier_type')->insertOrIgnore([

            [
                'code' => 'ABS',
                'type' => json_encode($translations['ABS']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'AGR',
                'type' => json_encode($translations['AGR']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'BU',
                'type' => json_encode($translations['BU']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'DESC',
                'type' => json_encode($translations['DESC']),
                'main_display' => 0,
                'for_category' => 'PAT',
                'display_order' => 5,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'EVAL',
                'type' => json_encode($translations['EVAL']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'IMG',
                'type' => json_encode($translations['IMG']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'IPC',
                'type' => json_encode($translations['IPC']),
                'main_display' => 1,
                'for_category' => 'PAT',
                'display_order' => 15,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'KW',
                'type' => json_encode($translations['KW']),
                'main_display' => 1,
                'for_category' => null,
                'display_order' => 10,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'LNK',
                'type' => json_encode($translations['LNK']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'LOC',
                'type' => json_encode($translations['LOC']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'ORG',
                'type' => json_encode($translations['ORG']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'PA',
                'type' => json_encode($translations['PA']),
                'main_display' => 0,
                'for_category' => 'PAT',
                'display_order' => 20,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'PROD',
                'type' => json_encode($translations['PROD']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'PROJ',
                'type' => json_encode($translations['PROJ']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'TECH',
                'type' => json_encode($translations['TECH']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'TIT',
                'type' => json_encode($translations['TIT']),
                'main_display' => 1,
                'for_category' => null,
                'display_order' => 5,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'TITAL',
                'type' => json_encode($translations['TITAL']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'TITEN',
                'type' => json_encode($translations['TITEN']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'TITOF',
                'type' => json_encode($translations['TITOF']),
                'main_display' => 0,
                'for_category' => null,
                'display_order' => 127,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'TM',
                'type' => json_encode($translations['TM']),
                'main_display' => 1,
                'for_category' => 'TM',
                'display_order' => 5,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'TMCL',
                'type' => json_encode($translations['TMCL']),
                'main_display' => 1,
                'for_category' => 'TM',
                'display_order' => 10,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'code' => 'TMTYP',
                'type' => json_encode($translations['TMTYP']),
                'main_display' => 0,
                'for_category' => 'TM',
                'display_order' => 15,
                'notes' => null,
                'creator' => 'system',
                'updater' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
