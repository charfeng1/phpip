<?php

namespace App\Enums;

/**
 * Category codes for IP matter types.
 *
 * These codes identify the type of intellectual property right.
 */
enum CategoryCode: string
{
    case PATENT = 'PAT';
    case TRADEMARK = 'TM';
    case DESIGN = 'DES';
    case UTILITY_MODEL = 'UM';
    case COPYRIGHT = 'CR';
    case DOMAIN = 'DOM';
    case PLANT_VARIETY = 'PV';
    case SUPPLEMENTARY_PROTECTION = 'SPC';

    /**
     * Get a human-readable label for this category.
     */
    public function label(): string
    {
        return match ($this) {
            self::PATENT => 'Patent',
            self::TRADEMARK => 'Trademark',
            self::DESIGN => 'Design',
            self::UTILITY_MODEL => 'Utility Model',
            self::COPYRIGHT => 'Copyright',
            self::DOMAIN => 'Domain Name',
            self::PLANT_VARIETY => 'Plant Variety',
            self::SUPPLEMENTARY_PROTECTION => 'SPC',
        };
    }

    /**
     * Categories that typically have renewals.
     *
     * @return array<CategoryCode>
     */
    public static function renewableCategories(): array
    {
        return [self::PATENT, self::DESIGN, self::UTILITY_MODEL, self::SUPPLEMENTARY_PROTECTION];
    }

    /**
     * Get values for renewable categories.
     *
     * @return array<string>
     */
    public static function renewableCategoryValues(): array
    {
        return array_map(fn (CategoryCode $cat) => $cat->value, self::renewableCategories());
    }

    /**
     * Check if this category typically requires renewals.
     */
    public function hasRenewals(): bool
    {
        return in_array($this, self::renewableCategories(), true);
    }

    /**
     * Categories that have examination procedures.
     *
     * @return array<CategoryCode>
     */
    public static function examinedCategories(): array
    {
        return [self::PATENT, self::UTILITY_MODEL];
    }

    /**
     * Check if this category has examination procedure.
     */
    public function hasExamination(): bool
    {
        return in_array($this, self::examinedCategories(), true);
    }
}
