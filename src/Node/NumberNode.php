<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/json-parser
 */

namespace Ergebnis\Json\Parser\Node;

use Ergebnis\Json\Parser\InvalidNumber;
use Ergebnis\Json\Parser\Raw;

/**
 * @see https://datatracker.ietf.org/doc/html/rfc8259#section-6
 */
final class NumberNode implements Node
{
    private string $raw;

    private function __construct(string $raw)
    {
        $this->raw = $raw;
    }

    /**
     * @throws InvalidNumber
     */
    public static function fromRaw(Raw $raw): self
    {
        $value = $raw->toString();

        $pattern = <<<'EOD'
            /
                \A
                -?
                (?:0|[1-9][0-9]*+)
                (?:\.[0-9]++)?+
                (?:[eE][+-]?+[0-9]++)?+
                \z
            /x
            EOD;

        if (1 !== \preg_match($pattern, $value)) {
            throw InvalidNumber::notValidJsonNumber($raw);
        }

        return new self($value);
    }

    /**
     * @internal
     */
    public static function fromParsedRaw(string $raw): self
    {
        return new self($raw);
    }

    public static function fromInt(int $value): self
    {
        return new self((string) $value);
    }

    /**
     * @throws InvalidNumber
     */
    public static function fromFloat(float $value): self
    {
        if (!\is_finite($value)) {
            throw InvalidNumber::notFinite($value);
        }

        $sign = '';

        if ('-' === ((string) $value)[0]) {
            $sign = '-';
        }

        $magnitude = \abs($value);
        $precision = -1;

        do {
            ++$precision;

            $scientific = \sprintf(
                '%.' . $precision . 'e',
                $magnitude,
            );
        } while ((float) $scientific !== $magnitude);

        $parts = \explode(
            'e',
            $scientific,
        );

        $mantissa = $parts[0];
        $exponent = $parts[1];
        $decimalPointPosition = (int) $exponent + 1;

        if (
            -3 > $decimalPointPosition
            || 17 < $decimalPointPosition
        ) {
            if (0 === $precision) {
                $mantissa .= '.0';
            }

            return new self($sign . $mantissa . 'e' . $exponent);
        }

        $digits = \str_replace(
            '.',
            '',
            $mantissa,
        );

        if (0 >= $decimalPointPosition) {
            $leadingZeros = \str_repeat(
                '0',
                -$decimalPointPosition,
            );

            return new self($sign . '0.' . $leadingZeros . $digits);
        }

        if ($precision < $decimalPointPosition) {
            $trailingZeros = \str_repeat(
                '0',
                $decimalPointPosition - $precision - 1,
            );

            return new self($sign . $digits . $trailingZeros . '.0');
        }

        return new self($sign . \substr_replace(
            $digits,
            '.',
            $decimalPointPosition,
            0,
        ));
    }

    public function raw(): string
    {
        return $this->raw;
    }

    /**
     * @throws NumberCanNotBeRepresented
     */
    public function toInt(): int
    {
        [$sign, $digits, $exponent] = self::decompose($this->raw);

        $length = \strlen($digits);

        if (
            $length > $exponent
            || \strlen((string) \PHP_INT_MAX) < $exponent
        ) {
            throw NumberCanNotBeRepresented::asInt($this);
        }

        $integer = $sign . $digits . \str_repeat(
            '0',
            (int) $exponent - $length,
        );

        $value = (int) $integer;

        /**
         * Casting saturates at PHP_INT_MAX and PHP_INT_MIN.
         */
        if ((string) $value !== $integer) {
            throw NumberCanNotBeRepresented::asInt($this);
        }

        return $value;
    }

    /**
     * @throws NumberCanNotBeRepresented
     */
    public function toFloat(): float
    {
        $value = (float) $this->raw;

        /**
         * When casting rounded, the shortest spelling of the float has a different value.
         */
        if (
            !\is_finite($value)
            || self::decompose(self::fromFloat($value)->raw) !== self::decompose($this->raw)
        ) {
            throw NumberCanNotBeRepresented::asFloat($this);
        }

        return $value;
    }

    /**
     * @return array{0: string, 1: string, 2: float}
     */
    private static function decompose(string $raw): array
    {
        $sign = '';

        if ('-' === $raw[0]) {
            $sign = '-';
        }

        /**
         * Appending an exponent of 0 gives every number an exponent.
         */
        $parts = \explode(
            'e',
            \strtolower(\ltrim(
                $raw,
                '-',
            )) . 'e0',
        );

        $mantissa = $parts[0];

        $integerDigitCount = \strcspn(
            $mantissa,
            '.',
        );

        $digits = \str_replace(
            '.',
            '',
            $mantissa,
        );

        $withoutLeadingZeros = \ltrim(
            $digits,
            '0',
        );

        $significantDigits = \rtrim(
            $withoutLeadingZeros,
            '0',
        );

        if ('' === $significantDigits) {
            return [
                '',
                '0',
                1.0,
            ];
        }

        $leadingZeroCount = \strlen($digits) - \strlen($withoutLeadingZeros);

        /**
         * The value is 0.digits × 10^exponent, so that numbers with the same value have identical parts, whatever their spelling.
         * The exponent is a float, so that an exponent beyond the range of int keeps its sign instead of overflowing.
         */
        return [
            $sign,
            $significantDigits,
            (float) $parts[1] + $integerDigitCount - $leadingZeroCount,
        ];
    }
}
