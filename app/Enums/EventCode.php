<?php

namespace App\Enums;

/**
 * Event codes for IP matter lifecycle events.
 *
 * These codes identify significant milestones in the life of an IP right.
 */
enum EventCode: string
{
    // Core lifecycle events
    case FILING = 'FIL';
    case PCT_FILING = 'PFIL';
    case PUBLICATION = 'PUB';
    case GRANT = 'GRT';
    case REGISTRATION = 'REG';
    case PRIORITY = 'PRI';
    case ENTRY = 'ENT';
    case RECEIVED = 'REC';
    case ALLOWANCE = 'ALL';

    // Task-related events
    case RENEWAL = 'REN';
    case PRIORITY_CLAIM = 'PR';
    case EXAMINATION = 'EXA';
    case REPLY = 'REP';
    case PAYMENT = 'PAY';

    // Status events
    case ABANDONED = 'ABA';
    case LAPSED = 'LAP';

    // Procedure step events (from OPS integration)
    case EXAM_REPORT = 'EXRE';
    case RENEWAL_FEE = 'RFEE';
    case INTENTION_TO_GRANT = 'IGRA';
    case FILING_REQUEST = 'EXAM52';

    /**
     * Get a human-readable label for this event code.
     */
    public function label(): string
    {
        return match ($this) {
            self::FILING => 'Filing',
            self::PCT_FILING => 'PCT Filing',
            self::PUBLICATION => 'Publication',
            self::GRANT => 'Grant',
            self::REGISTRATION => 'Registration',
            self::PRIORITY => 'Priority',
            self::ENTRY => 'National/Regional Entry',
            self::RECEIVED => 'Received',
            self::ALLOWANCE => 'Allowance',
            self::RENEWAL => 'Renewal',
            self::PRIORITY_CLAIM => 'Priority Claim',
            self::EXAMINATION => 'Examination',
            self::REPLY => 'Reply',
            self::PAYMENT => 'Payment',
            self::ABANDONED => 'Abandoned',
            self::LAPSED => 'Lapsed',
            self::EXAM_REPORT => 'Examination Report',
            self::RENEWAL_FEE => 'Renewal Fee',
            self::INTENTION_TO_GRANT => 'Intention to Grant',
            self::FILING_REQUEST => 'Filing Request (Rule 52)',
        };
    }

    /**
     * Events that have associated public registry numbers.
     *
     * @return array<EventCode>
     */
    public static function eventsWithNumbers(): array
    {
        return [self::FILING, self::PUBLICATION, self::GRANT, self::REGISTRATION];
    }

    /**
     * Events that are linkable to external patent office registries.
     *
     * @return array<EventCode>
     */
    public static function linkableEvents(): array
    {
        return [self::FILING, self::PUBLICATION, self::GRANT];
    }

    /**
     * Check if this event code is linkable to external registries.
     */
    public function isLinkable(): bool
    {
        return in_array($this, self::linkableEvents(), true);
    }

    /**
     * Events that can serve as grant-equivalent events.
     *
     * @return array<EventCode>
     */
    public static function grantEquivalentEvents(): array
    {
        return [self::GRANT, self::REGISTRATION];
    }

    /**
     * Get values for grant-equivalent events.
     *
     * @return array<string>
     */
    public static function grantEquivalentValues(): array
    {
        return array_map(fn (EventCode $event) => $event->value, self::grantEquivalentEvents());
    }

    /**
     * Events that trigger renewal start calculations.
     *
     * @return array<EventCode>
     */
    public static function renewalTriggerEvents(): array
    {
        return [self::FILING, self::GRANT, self::PRIORITY_CLAIM];
    }

    /**
     * Check if this is a renewal trigger event.
     */
    public function isRenewalTrigger(): bool
    {
        return in_array($this, self::renewalTriggerEvents(), true);
    }
}
