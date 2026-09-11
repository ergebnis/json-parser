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

namespace Ergebnis\Json\Parser\Test\Unit\Node;

use Ergebnis\Json\Parser\InvalidJson;
use Ergebnis\Json\Parser\InvalidNumber;
use Ergebnis\Json\Parser\MaximumDepth;
use Ergebnis\Json\Parser\Node;
use Ergebnis\Json\Parser\Parser;
use Ergebnis\Json\Parser\Raw;
use Ergebnis\Json\Parser\Test;
use PHPUnit\Framework;
use Symfony\Component\Finder;

/**
 * @covers \Ergebnis\Json\Parser\Node\NumberNode
 *
 * @uses \Ergebnis\Json\Parser\Bytes
 * @uses \Ergebnis\Json\Parser\InvalidJson
 * @uses \Ergebnis\Json\Parser\InvalidNumber
 * @uses \Ergebnis\Json\Parser\MaximumDepth
 * @uses \Ergebnis\Json\Parser\Node\NumberCanNotBeRepresented
 * @uses \Ergebnis\Json\Parser\Parser
 * @uses \Ergebnis\Json\Parser\Raw
 */
final class NumberNodeTest extends Framework\TestCase
{
    use Test\Util\Helper;

    /**
     * @dataProvider provideInvalidRaw
     */
    public function testFromRawThrowsInvalidNumberWhenRawIsInvalid(string $value): void
    {
        $raw = Raw::fromString($value);

        $this->expectException(InvalidNumber::class);

        Node\NumberNode::fromRaw($raw);
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideInvalidRaw(): iterable
    {
        $values = [
            'decimal-point-without-digits-after' => '1.',
            'decimal-point-without-digits-before' => '.1',
            'empty' => '',
            'exponent-without-digits' => '1e',
            'exponent-without-digits-after-sign' => '1e+',
            'hexadecimal' => '0x1',
            'infinity' => 'Infinity',
            'leading-plus-sign' => '+1',
            'leading-whitespace' => ' 1',
            'leading-zero' => '01',
            'minus-sign' => '-',
            'negative-leading-zero' => '-01',
            'not-a-number' => 'NaN',
            'trailing-new-line' => "1\n",
            'trailing-whitespace' => '1 ',
            'two-decimal-points' => '1.0.0',
            'two-minus-signs' => '--1',
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideRawFromJsonTestSuiteThatParserDoesNotAccept
     */
    public function testFromRawThrowsInvalidNumberWhenParserDoesNotAcceptRaw(string $value): void
    {
        $raw = Raw::fromString($value);

        $this->expectException(InvalidNumber::class);

        Node\NumberNode::fromRaw($raw);
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideRawFromJsonTestSuiteThatParserDoesNotAccept(): iterable
    {
        foreach (self::rawNumbersFromJsonTestSuite() as $key => $value) {
            if (!self::parserAccepts($value)) {
                yield $key => [
                    $value,
                ];
            }
        }
    }

    /**
     * @dataProvider provideValidRaw
     */
    public function testFromRawReturnsNumberNodeWhenRawIsValid(string $value): void
    {
        $raw = Raw::fromString($value);

        $node = Node\NumberNode::fromRaw($raw);

        self::assertSame($value, $node->raw());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideValidRaw(): iterable
    {
        $values = [
            'exponent-with-capital-e' => '5E3',
            'exponent-with-minus-sign' => '1E-5',
            'exponent-with-plus-sign' => '1e+2',
            'float-greater-than-zero' => '1.5',
            'float-greater-than-zero-with-trailing-zero' => '1.0',
            'float-less-than-zero' => '-3.14',
            'float-with-exponent' => '1.5e2',
            'int-beyond-int-range' => '99999999999999999999',
            'int-greater-than-zero' => '42',
            'int-less-than-zero' => '-42',
            'int-negative-zero' => '-0',
            'int-with-400-digits' => \str_repeat('9', 400),
            'int-with-exponent' => '1e10',
            'int-zero' => '0',
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideRawFromJsonTestSuiteThatParserAccepts
     */
    public function testFromRawReturnsNumberNodeWhenParserAcceptsRaw(string $value): void
    {
        $raw = Raw::fromString($value);

        $node = Node\NumberNode::fromRaw($raw);

        self::assertSame($value, $node->raw());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideRawFromJsonTestSuiteThatParserAccepts(): iterable
    {
        foreach (self::rawNumbersFromJsonTestSuite() as $key => $value) {
            if (self::parserAccepts($value)) {
                yield $key => [
                    $value,
                ];
            }
        }
    }

    public function testFromParsedRawReturnsNumberNode(): void
    {
        $raw = (string) self::faker()->numberBetween();

        $node = Node\NumberNode::fromParsedRaw($raw);

        self::assertSame($raw, $node->raw());
    }

    /**
     * @dataProvider \Ergebnis\DataProvider\IntProvider::arbitrary
     * @dataProvider provideIntThatIsMaximumOrMinimum
     */
    public function testFromIntReturnsNumberNode(int $value): void
    {
        $node = Node\NumberNode::fromInt($value);

        self::assertSame((string) $value, $node->raw());
        self::assertSame($value, $node->toInt());
        self::assertEquals($node, Node\NumberNode::fromRaw(Raw::fromString($node->raw())));
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function provideIntThatIsMaximumOrMinimum(): iterable
    {
        $values = [
            'int-max' => \PHP_INT_MAX,
            'int-min' => \PHP_INT_MIN,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideFloatThatIsNotFinite
     */
    public function testFromFloatThrowsInvalidNumberWhenValueIsNotFinite(float $value): void
    {
        $this->expectException(InvalidNumber::class);

        Node\NumberNode::fromFloat($value);
    }

    /**
     * @return \Generator<string, array{0: float}>
     */
    public static function provideFloatThatIsNotFinite(): iterable
    {
        $values = [
            'infinity' => \INF,
            'negative-infinity' => -\INF,
            'not-a-number' => \NAN,
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideFloatAndRaw
     */
    public function testFromFloatReturnsNumberNodeWhenValueIsFinite(
        float $value,
        string $raw
    ): void {
        $node = Node\NumberNode::fromFloat($value);

        self::assertSame($raw, $node->raw());
        self::assertSame($value, $node->toFloat());
    }

    /**
     * @return \Generator<string, array{0: float, 1: string}>
     */
    public static function provideFloatAndRaw(): iterable
    {
        $values = [
            'exponent-for-largest-float' => [
                1.7976931348623157e308,
                '1.7976931348623157e+308',
            ],
            'exponent-for-smallest-float' => [
                5e-324,
                '5.0e-324',
            ],
            'exponent-when-decimal-point-follows-18-digits' => [
                1e17,
                '1.0e+17',
            ],
            'exponent-when-fraction-has-4-leading-zeros' => [
                0.00001,
                '1.0e-5',
            ],
            'exponent-with-fraction' => [
                1.2345678901234568e17,
                '1.2345678901234568e+17',
            ],
            'exponent-with-negative-fraction' => [
                -1.5e-7,
                '-1.5e-7',
            ],
            'fraction' => [
                123.456,
                '123.456',
            ],
            'fraction-less-than-one' => [
                0.5,
                '0.5',
            ],
            'fraction-with-17-digits' => [
                1234567890123456.8,
                '1234567890123456.8',
            ],
            'fraction-with-3-leading-zeros' => [
                0.0001,
                '0.0001',
            ],
            'fraction-with-shortest-digits' => [
                0.30000000000000004,
                '0.30000000000000004',
            ],
            'integral' => [
                1.0,
                '1.0',
            ],
            'integral-with-17-digits' => [
                12345678901234568.0,
                '12345678901234568.0',
            ],
            'integral-with-trailing-zeros' => [
                100.0,
                '100.0',
            ],
            'integral-when-decimal-point-follows-17-digits' => [
                1e16,
                '10000000000000000.0',
            ],
            'negative-fraction' => [
                -1.25,
                '-1.25',
            ],
            'negative-zero' => [
                -0.0,
                '-0.0',
            ],
            'zero' => [
                0.0,
                '0.0',
            ],
        ];

        foreach ($values as $key => [$value, $raw]) {
            yield $key => [
                $value,
                $raw,
            ];
        }
    }

    public function testFromFloatReturnsNumberNodeSpelledLikeJsonEncodeWithPreserveZeroFraction(): void
    {
        $faker = self::faker();

        $values = \array_map(static function () use ($faker): float {
            return $faker->randomFloat(null, -1, 1) * 10 ** $faker->numberBetween(-320, 300);
        }, \range(1, 1000));

        $serializePrecision = (string) \ini_get('serialize_precision');

        \ini_set('serialize_precision', '-1');

        $expected = \array_map(static function (float $value): string {
            return \json_encode(
                $value,
                \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR,
            );
        }, $values);

        \ini_set('serialize_precision', $serializePrecision);

        $raws = \array_map(static function (float $value): string {
            return Node\NumberNode::fromFloat($value)->raw();
        }, $values);

        self::assertSame($expected, $raws);
    }

    public function testFromFloatReturnsNumberNodeIndependentOfSerializePrecision(): void
    {
        $serializePrecision = (string) \ini_get('serialize_precision');

        \ini_set('serialize_precision', '17');

        $node = Node\NumberNode::fromFloat(0.1);

        \ini_set('serialize_precision', $serializePrecision);

        self::assertSame('0.1', $node->raw());
    }

    /**
     * @dataProvider provideRawThatCanNotBeRepresentedAsInt
     */
    public function testToIntThrowsNumberCanNotBeRepresentedWhenValueCanNotBeRepresentedAsInt(string $value): void
    {
        $node = Node\NumberNode::fromRaw(Raw::fromString($value));

        $this->expectException(Node\NumberCanNotBeRepresented::class);

        $node->toInt();
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideRawThatCanNotBeRepresentedAsInt(): iterable
    {
        $values = [
            'exponent-beyond-int-range' => '1e99999999999999999999',
            'fraction' => '1.5',
            'fraction-less-than-one' => '0.5',
            'fraction-with-exponent' => '1.25e1',
            'greater-than-int-max' => '9223372036854775808',
            'greater-than-int-max-with-exponent' => '9.223372036854775808e18',
            'greater-than-int-max-with-twenty-digits' => '1e19',
            'less-than-int-min' => '-9223372036854775809',
            'negative-exponent' => '1e-1',
            'negative-exponent-beyond-int-range' => '1e-99999999999999999999',
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideRawAndInt
     */
    public function testToIntReturnsIntWhenValueCanBeRepresentedAsInt(
        string $raw,
        int $value
    ): void {
        $node = Node\NumberNode::fromRaw(Raw::fromString($raw));

        self::assertSame($value, $node->toInt());
    }

    /**
     * @return \Generator<string, array{0: string, 1: int}>
     */
    public static function provideRawAndInt(): iterable
    {
        $values = [
            'exponent' => [
                '1e2',
                100,
            ],
            'exponent-with-capital-e' => [
                '1E2',
                100,
            ],
            'exponent-with-leading-zeros' => [
                '1e002',
                100,
            ],
            'exponent-with-minus-sign' => [
                '100e-2',
                1,
            ],
            'exponent-with-plus-sign' => [
                '1e+2',
                100,
            ],
            'fraction-with-exponent' => [
                '1.5e1',
                15,
            ],
            'fraction-with-leading-zeros-and-exponent' => [
                '0.0015e4',
                15,
            ],
            'fraction-with-trailing-zeros' => [
                '1.0',
                1,
            ],
            'int-greater-than-zero' => [
                '42',
                42,
            ],
            'int-less-than-zero' => [
                '-42',
                -42,
            ],
            'int-max' => [
                '9223372036854775807',
                \PHP_INT_MAX,
            ],
            'int-max-with-exponent' => [
                '9.223372036854775807e18',
                \PHP_INT_MAX,
            ],
            'int-min' => [
                '-9223372036854775808',
                \PHP_INT_MIN,
            ],
            'int-with-trailing-zeros' => [
                '1000',
                1000,
            ],
            'negative-zero' => [
                '-0',
                0,
            ],
            'negative-zero-with-fraction-and-exponent' => [
                '-0.000e-5',
                0,
            ],
            'zero' => [
                '0',
                0,
            ],
            'zero-with-exponent-beyond-int-range' => [
                '0e99999999999999999999',
                0,
            ],
        ];

        foreach ($values as $key => [$raw, $value]) {
            yield $key => [
                $raw,
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideRawThatCanNotBeRepresentedAsFloat
     */
    public function testToFloatThrowsNumberCanNotBeRepresentedWhenValueCanNotBeRepresentedAsFloat(string $value): void
    {
        $node = Node\NumberNode::fromRaw(Raw::fromString($value));

        $this->expectException(Node\NumberCanNotBeRepresented::class);

        $node->toFloat();
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideRawThatCanNotBeRepresentedAsFloat(): iterable
    {
        $values = [
            'exact-value-of-float-longer-than-shortest-spelling' => '0.1000000000000000055511151231257827021181583404541015625',
            'exponent-beyond-float-range' => '1e400',
            'exponent-beyond-float-range-less-than-zero' => '-1e400',
            'exponent-beyond-int-range' => '1e99999999999999999999',
            'fraction-rounded' => '0.10000000000000001',
            'int-beyond-float-precision' => '9007199254740993',
            'int-rounded' => '99999999999999999999',
            'negative-exponent-beyond-float-range' => '1e-400',
            'negative-exponent-beyond-float-range-less-than-zero' => '-1e-400',
            'negative-exponent-beyond-int-range' => '1e-99999999999999999999',
            'subnormal-rounded' => '4.9e-324',
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideRawAndFloat
     */
    public function testToFloatReturnsFloatWhenValueCanBeRepresentedAsFloat(
        string $raw,
        float $value
    ): void {
        $node = Node\NumberNode::fromRaw(Raw::fromString($raw));

        self::assertSame($value, $node->toFloat());
    }

    /**
     * @return \Generator<string, array{0: string, 1: float}>
     */
    public static function provideRawAndFloat(): iterable
    {
        $values = [
            'exponent' => [
                '1e2',
                100.0,
            ],
            'exponent-with-capital-e' => [
                '1E2',
                100.0,
            ],
            'float-max' => [
                '1.7976931348623157e308',
                1.7976931348623157e308,
            ],
            'float-min-subnormal' => [
                '5e-324',
                5e-324,
            ],
            'fraction' => [
                '0.1',
                0.1,
            ],
            'fraction-less-than-zero' => [
                '-1.5',
                -1.5,
            ],
            'fraction-with-exponent' => [
                '1.5e-7',
                1.5e-7,
            ],
            'fraction-with-shortest-digits' => [
                '0.30000000000000004',
                0.30000000000000004,
            ],
            'fraction-with-trailing-zeros' => [
                '100.000',
                100.0,
            ],
            'int' => [
                '42',
                42.0,
            ],
            'int-beyond-int-range' => [
                '10000000000000000000',
                1e19,
            ],
            'int-within-float-precision' => [
                '9007199254740992',
                9007199254740992.0,
            ],
            'zero' => [
                '0',
                0.0,
            ],
            'zero-with-exponent-beyond-int-range' => [
                '0e99999999999999999999',
                0.0,
            ],
        ];

        foreach ($values as $key => [$raw, $value]) {
            yield $key => [
                $raw,
                $value,
            ];
        }
    }

    /**
     * @dataProvider provideRawThatIsNegativeZero
     */
    public function testToFloatReturnsNegativeZeroWhenValueIsNegativeZero(string $value): void
    {
        $node = Node\NumberNode::fromRaw(Raw::fromString($value));

        self::assertSame('-0', (string) $node->toFloat());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function provideRawThatIsNegativeZero(): iterable
    {
        $values = [
            'negative-zero' => '-0',
            'negative-zero-with-exponent-beyond-int-range' => '-0e99999999999999999999',
            'negative-zero-with-fraction' => '-0.0',
        ];

        foreach ($values as $key => $value) {
            yield $key => [
                $value,
            ];
        }
    }

    /**
     * @return array<string, string>
     */
    private static function rawNumbersFromJsonTestSuite(): array
    {
        $finder = Finder\Finder::create()
            ->files()
            ->in(__DIR__ . '/../../Fixture/JSONTestSuite/test_parsing')
            ->name('*.json')
            ->sortByName();

        $values = [];

        foreach ($finder as $file) {
            $matches = [];

            \preg_match_all(
                '/[-+.0-9][-+.0-9A-Za-z]*+/',
                $file->getContents(),
                $matches,
            );

            foreach (\array_unique($matches[0]) as $index => $value) {
                $key = \sprintf(
                    '%s #%d',
                    $file->getFilename(),
                    $index,
                );

                $values[$key] = $value;
            }
        }

        return $values;
    }

    private static function parserAccepts(string $value): bool
    {
        $parser = new Parser();

        try {
            $parser->parse(
                Raw::fromString($value),
                MaximumDepth::default(),
            );
        } catch (InvalidJson $exception) {
            return false;
        }

        return true;
    }
}
