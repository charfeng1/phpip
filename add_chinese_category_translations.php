<?php

// Run this with: php artisan tinker < add_chinese_category_translations.php

use App\Models\Category;

$translations = [
    'LTG' => '诉讼',
    'OP' => '异议（专利）',
    'OPI' => '意见',
    'OTH' => '其他',
    'PAT' => '专利',
    'SO' => 'Soleau信封',
    'SR' => '检索',
    'TM' => '商标',
    'TMOP' => '异议（商标）',
    'TS' => '商业秘密',
    'UC' => '实用证书',
    'UM' => '实用新型',
    'WAT' => '监控',
];

foreach ($translations as $code => $chineseName) {
    $category = Category::find($code);
    if ($category) {
        $currentTranslations = $category->getTranslations('category');
        $currentTranslations['zh'] = $chineseName;
        $category->setTranslations('category', $currentTranslations);
        $category->save();
        echo "Updated {$code}: {$chineseName}\n";
    }
}

echo "Done! Chinese translations added for all categories.\n";
