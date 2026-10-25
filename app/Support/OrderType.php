<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Single source of truth for the `order_type` business rule.
 *
 * An order is either Cash-on-Delivery ('cod') or Prepaid ('prepaid'). Every place
 * that accepts an order type — manual order creation, API integrations, bulk import
 * and each courier's shipment-creation call — must run the value through here so the
 * rule stays consistent across the whole application.
 */
class OrderType
{
    public const COD = 'cod';
    public const PREPAID = 'prepaid';

    /** The only accepted (canonical, lowercase) values. */
    public const ALLOWED = [self::COD, self::PREPAID];

    /** Human-facing message shown wherever an invalid value is rejected. */
    public const INVALID_MESSAGE = 'Order Type must be either COD or Prepaid.';

    /**
     * Normalise a raw value: trim + lowercase, so "COD", " Cod ", "PREPAID"
     * all collapse to their canonical form.
     */
    public static function sanitize($value): string
    {
        return strtolower(trim((string) $value));
    }

    /** True only when the value is exactly cod or prepaid (after sanitising). */
    public static function isValid($value): bool
    {
        return in_array(self::sanitize($value), self::ALLOWED, true);
    }

    /**
     * Case-insensitive check for a COD order. Use this instead of a raw
     * `$order->order_type == 'cod'`, which misses "COD", "Cod", etc.
     */
    public static function isCod($value): bool
    {
        return self::sanitize($value) === self::COD;
    }

    /** Case-insensitive check for a Prepaid order. */
    public static function isPrepaid($value): bool
    {
        return self::sanitize($value) === self::PREPAID;
    }

    /**
     * Return the canonical lowercase order type, or throw when the value is
     * empty, null or anything other than cod / prepaid.
     *
     * @throws InvalidArgumentException
     */
    public static function validate($value): string
    {
        $sanitized = self::sanitize($value);

        if (!in_array($sanitized, self::ALLOWED, true)) {
            throw new InvalidArgumentException(self::INVALID_MESSAGE);
        }

        return $sanitized;
    }
}
