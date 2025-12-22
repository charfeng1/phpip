<?php

namespace App\Enums;

/**
 * Actor roles in matter relationships.
 *
 * These codes identify the role an actor plays in relation to an IP matter.
 */
enum ActorRole: string
{
    case CLIENT = 'CLI';
    case APPLICANT = 'APP';
    case OWNER = 'OWN';
    case AGENT = 'AGT';
    case SECONDARY_AGENT = 'AGT2';
    case INVENTOR = 'INV';
    case DELEGATE = 'DEL';
    case CONTACT = 'CNT';
    case PAYOR = 'PAY';
    case WRITER = 'WRI';
    case ANNUITY_AGENT = 'ANN';

    /**
     * Get a human-readable label for this role.
     */
    public function label(): string
    {
        return match ($this) {
            self::CLIENT => 'Client',
            self::APPLICANT => 'Applicant',
            self::OWNER => 'Owner',
            self::AGENT => 'Agent',
            self::SECONDARY_AGENT => 'Secondary Agent',
            self::INVENTOR => 'Inventor',
            self::DELEGATE => 'Delegate',
            self::CONTACT => 'Contact',
            self::PAYOR => 'Payor',
            self::WRITER => 'Writer',
            self::ANNUITY_AGENT => 'Annuity Agent',
        };
    }

    /**
     * Get roles that can be inherited from parent/container matters.
     *
     * @return array<ActorRole>
     */
    public static function inheritableRoles(): array
    {
        return [
            self::CLIENT,
            self::APPLICANT,
            self::OWNER,
            self::AGENT,
            self::CONTACT,
        ];
    }

    /**
     * Get role values that can be inherited.
     *
     * @return array<string>
     */
    public static function inheritableRoleValues(): array
    {
        return array_map(fn (ActorRole $role) => $role->value, self::inheritableRoles());
    }

    /**
     * Check if this role is typically shared across matter families.
     */
    public function isSharedRole(): bool
    {
        return in_array($this, [self::CLIENT, self::APPLICANT, self::OWNER], true);
    }
}
