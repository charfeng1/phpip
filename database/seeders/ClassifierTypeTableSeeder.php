<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassifierTypeTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('classifier_type')->insertOrIgnore([

            [
                'code' => 'ABS',
                'type' => json_encode(['en' => 'Abstract', 'fr' => 'Abrégé', 'de' => 'Zusammenfassung', 'zh' => '摘要']),
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
                'type' => json_encode(['en' => 'Agreement', 'fr' => 'Accord', 'de' => 'Vereinbarung', 'zh' => '协议']),
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
                'type' => json_encode(['en' => 'Business Unit', 'fr' => 'Unité commerciale', 'de' => 'Geschäftsbereich', 'zh' => '业务单元']),
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
                'type' => json_encode(['en' => 'Description', 'fr' => 'Description', 'de' => 'Beschreibung', 'zh' => '说明']),
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
                'type' => json_encode(['en' => 'Evaluation', 'fr' => 'Évaluation', 'de' => 'Bewertung', 'zh' => '评估']),
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
                'type' => json_encode(['en' => 'Image', 'fr' => 'Image', 'de' => 'Bild', 'zh' => '图片']),
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
                'type' => json_encode(['en' => 'Int. Pat. Class.', 'fr' => 'Class. Int. des Brevets', 'de' => 'Int. Pat. Klass.', 'zh' => '国际专利分类']),
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
                'type' => json_encode(['en' => 'Keyword', 'fr' => 'Mot-clé', 'de' => 'Stichwort', 'zh' => '关键词']),
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
                'type' => json_encode(['en' => 'Link', 'fr' => 'Lien', 'de' => 'Link', 'zh' => '链接']),
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
                'type' => json_encode(['en' => 'Location', 'fr' => 'Lieu', 'de' => 'Standort', 'zh' => '位置']),
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
                'type' => json_encode(['en' => 'Organization', 'fr' => 'Organisation', 'de' => 'Organisation', 'zh' => '组织']),
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
                'type' => json_encode(['en' => 'Prior Art', 'fr' => 'Art antérieur', 'de' => 'Stand der Technik', 'zh' => '现有技术']),
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
                'type' => json_encode(['en' => 'Product', 'fr' => 'Produit', 'de' => 'Produkt', 'zh' => '产品']),
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
                'type' => json_encode(['en' => 'Project', 'fr' => 'Projet', 'de' => 'Projekt', 'zh' => '项目']),
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
                'type' => json_encode(['en' => 'Technology', 'fr' => 'Technologie', 'de' => 'Technologie', 'zh' => '技术']),
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
                'type' => json_encode(['en' => 'Title', 'fr' => 'Titre', 'de' => 'Titel', 'zh' => '标题']),
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
                'type' => json_encode(['en' => 'Alt. Title', 'fr' => 'Titre alternatif', 'de' => 'Alternativer Titel', 'zh' => '替代标题']),
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
                'type' => json_encode(['en' => 'English Title', 'fr' => 'Titre anglais', 'de' => 'Englischer Titel', 'zh' => '英文标题']),
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
                'type' => json_encode(['en' => 'Official Title', 'fr' => 'Titre officiel', 'de' => 'Offizieller Titel', 'zh' => '正式标题']),
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
                'type' => json_encode(['en' => 'Trademark', 'fr' => 'Marque', 'de' => 'Marke', 'zh' => '商标']),
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
                'type' => json_encode(['en' => 'Class (TM)', 'fr' => 'Classe (Marque)', 'de' => 'Klasse (Marke)', 'zh' => '分类（商标）']),
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
                'type' => json_encode(['en' => 'Type (TM)', 'fr' => 'Type (Marque)', 'de' => 'Typ (Marke)', 'zh' => '类型（商标）']),
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
