<?php

namespace App\Enums;

/**
 * Classifier type codes for matter classifications.
 *
 * These codes identify different types of classifiers attached to matters.
 */
enum ClassifierType: string
{
    case TITLE = 'TIT';
    case TITLE_EN = 'TITEN';
    case TITLE_OFFICIAL = 'TITOF';
    case IPC_CLASS = 'IPC';
    case NICE_CLASS = 'NICE';
    case LOCARNO_CLASS = 'LOC';
    case KEYWORD = 'KW';
    case IMAGE = 'IMG';
    case ABSTRACT = 'ABS';
    case TRADEMARK_NAME = 'TM';
    case TRADEMARK_CLASS = 'TMCL';

    /**
     * Get a human-readable label for this classifier type.
     */
    public function label(): string
    {
        return match ($this) {
            self::TITLE => 'Title',
            self::TITLE_EN => 'Title (English)',
            self::TITLE_OFFICIAL => 'Title (Official)',
            self::IPC_CLASS => 'IPC Classification',
            self::NICE_CLASS => 'Nice Classification',
            self::LOCARNO_CLASS => 'Locarno Classification',
            self::KEYWORD => 'Keyword',
            self::IMAGE => 'Image',
            self::ABSTRACT => 'Abstract',
            self::TRADEMARK_NAME => 'Trademark Name',
            self::TRADEMARK_CLASS => 'Trademark Class',
        };
    }

    /**
     * Get all title-related classifier types.
     *
     * @return array<ClassifierType>
     */
    public static function titleTypes(): array
    {
        return [self::TITLE, self::TITLE_EN, self::TITLE_OFFICIAL];
    }

    /**
     * Get values for title types.
     *
     * @return array<string>
     */
    public static function titleTypeValues(): array
    {
        return array_map(fn (ClassifierType $type) => $type->value, self::titleTypes());
    }

    /**
     * Check if this is a title-type classifier.
     */
    public function isTitle(): bool
    {
        return in_array($this, self::titleTypes(), true);
    }

    /**
     * Get classification types (IPC, Nice, Locarno).
     *
     * @return array<ClassifierType>
     */
    public static function classificationTypes(): array
    {
        return [self::IPC_CLASS, self::NICE_CLASS, self::LOCARNO_CLASS];
    }

    /**
     * Check if this is a classification-type classifier.
     */
    public function isClassification(): bool
    {
        return in_array($this, self::classificationTypes(), true);
    }
}
