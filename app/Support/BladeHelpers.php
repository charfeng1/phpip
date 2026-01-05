<?php

namespace App\Support;

/**
 * Helper functions for Blade components.
 */
class BladeHelpers
{
    /**
     * Format an array of HTML attributes into a string.
     *
     * Converts an associative array of attributes into a properly formatted
     * HTML attribute string. Boolean true values render as standalone attributes,
     * while all other values are escaped and rendered as key="value" pairs.
     *
     * @param  array  $attributes  Associative array of attribute name => value pairs
     * @return string Formatted HTML attribute string
     *
     * @example
     * formatAttributes(['id' => 'myId', 'required' => true, 'class' => 'btn'])
     * // Returns: 'id="myId" required class="btn"'
     */
    public static function formatAttributes(array $attributes): string
    {
        return collect($attributes)
            ->map(function ($value, $key) {
                if (is_bool($value)) {
                    return $value ? $key : '';
                }

                return $key.'="'.e($value).'"';
            })
            ->filter()
            ->implode(' ');
    }
}
