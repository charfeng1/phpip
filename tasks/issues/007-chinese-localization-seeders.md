# Issue: Add Chinese (zh) translations to all seeders

**Labels:** i18n, localization, priority

## Context

The application UI already supports Chinese (zh) with translations in `lang/zh.json`, but the database seeders only include English (en), French (fr), and German (de) translations.

With major clients in China, the database content (event names, categories, roles, etc.) should also have Chinese translations for a fully localized experience.

## Scope

Add Chinese translations to all seeders that currently only have en/fr/de:

- `EventNameTableSeeder.php` - 95+ event entries
- `MatterCategoryTableSeeder.php` - 16 entries
- `MatterTypeTableSeeder.php` - 6 entries
- `ActorRoleTableSeeder.php` - 22 entries
- `ClassifierTypeTableSeeder.php` - 22 entries

## Technical Notes

Current format:
```php
'name' => json_encode([
    'en' => 'Abandoned',
    'fr' => 'Abandonné',
    'de' => 'Aufgegeben',
    // need to add: 'zh' => '已放弃'
]),
```

The models already use `HasTranslationsExtended` trait (Spatie Translatable), so the database schema supports this. We just need to populate the `zh` key in the JSON translations.

## Implementation Considerations

1. **Translation quality**: Need accurate patent/IP terminology in Chinese (not just literal translations)
2. **Consistency**: Use same terminology as existing `lang/zh.json` UI translations
3. **Testing**: After adding translations, verify models return correct Chinese when locale is set to 'zh'
4. **Code review gemini suggestion**: Consider keeping entries sorted alphabetically by code after adding translations

## Tasks

- [ ] Review existing Chinese UI translations (`lang/zh.json`) for terminology consistency
- [ ] Add Chinese translations to `EventNameTableSeeder` (all 95+ entries)
- [ ] Add Chinese translations to `MatterCategoryTableSeeder`
- [ ] Add Chinese translations to `MatterTypeTableSeeder`
- [ ] Add Chinese translations to `ActorRoleTableSeeder`
- [ ] Add Chinese translations to `ClassifierTypeTableSeeder`
- [ ] Run seeders and verify Chinese translations are stored correctly
- [ ] Test model queries with `setLocale('zh')` return Chinese names
- [ ] Update any related documentation

## Related

- Issue #005: Seeding eventname consistency (PR #29) - should merge before this
- Models already use `App\Traits\HasTranslationsExtended` for Spatie Translatable
