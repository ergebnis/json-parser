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

namespace Ergebnis\Json\Parser\Test\Benchmark;

final class Fixture
{
    private const COUNT = 100000;
    private const DEPTH = 4095;

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return [
            'composer-json',
            'composer-lock',
            'deep-nesting',
            'numbers',
            'numbers-pretty',
            'strings-ascii',
            'strings-ascii-pretty',
            'strings-non-ascii',
            'strings-non-ascii-pretty',
            'wide-array',
            'wide-array-pretty',
            'wide-object',
            'wide-object-pretty',
        ];
    }

    public static function json(string $name): string
    {
        $committedPath = \sprintf(
            '%s/../Fixture/Benchmark/%s.json',
            __DIR__,
            $name,
        );

        if (\is_file($committedPath)) {
            return self::read($committedPath);
        }

        $generatedPath = \sprintf(
            '%s/../../.build/phpbench/fixture/%s.json',
            __DIR__,
            $name,
        );

        if (
            !\is_file($generatedPath)
            || \filemtime($generatedPath) < \filemtime(__FILE__)
        ) {
            $directory = \dirname($generatedPath);

            if (!\is_dir($directory)) {
                \mkdir(
                    $directory,
                    0777,
                    true,
                );
            }

            \file_put_contents(
                $generatedPath,
                self::generate($name),
            );
        }

        return self::read($generatedPath);
    }

    private static function read(string $path): string
    {
        $json = \file_get_contents($path);

        if (!\is_string($json)) {
            throw new \RuntimeException(\sprintf(
                'File "%s" could not be read.',
                $path,
            ));
        }

        return $json;
    }

    private static function generate(string $name): string
    {
        if ('deep-nesting' === $name) {
            return \str_repeat(
                '[',
                self::DEPTH,
            ) . '1' . \str_repeat(
                ']',
                self::DEPTH,
            );
        }

        $flags = \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE;
        $dataName = $name;

        $suffix = '-pretty';

        if (
            \strlen($name) > \strlen($suffix)
            && \substr($name, -\strlen($suffix)) === $suffix
        ) {
            $flags |= \JSON_PRETTY_PRINT;
            $dataName = \substr(
                $name,
                0,
                -\strlen($suffix),
            );
        }

        return \json_encode(
            self::data($dataName),
            $flags,
        );
    }

    /**
     * @return array<int|string, mixed>
     */
    private static function data(string $name): array
    {
        if ('numbers' === $name) {
            $data = [];

            for ($i = 0; self::COUNT > $i; ++$i) {
                $data[] = [
                    \round(
                        -180 + 360 * $i / self::COUNT,
                        6,
                    ),
                    \round(
                        -90 + 180 * (($i * 7919) % self::COUNT) / self::COUNT,
                        6,
                    ),
                ];
            }

            return $data;
        }

        if ('strings-ascii' === $name) {
            $data = [];

            for ($i = 0; self::COUNT > $i; ++$i) {
                $data[] = \sprintf(
                    'Lorem ipsum dolor sit amet, consectetur adipiscing elit %d',
                    $i,
                );
            }

            return $data;
        }

        if ('strings-non-ascii' === $name) {
            $data = [];

            for ($i = 0; self::COUNT > $i; ++$i) {
                $data[] = \sprintf(
                    'Grüße aus Köln – 日本語のテキスト – Ελληνικά %d',
                    $i,
                );
            }

            return $data;
        }

        if ('wide-array' === $name) {
            return \range(
                0,
                self::COUNT - 1,
            );
        }

        if ('wide-object' === $name) {
            $data = [];

            for ($i = 0; self::COUNT > $i; ++$i) {
                $data[\sprintf(
                    'property-%d',
                    $i,
                )] = \sprintf(
                    'value-%d',
                    $i,
                );
            }

            return $data;
        }

        throw new \InvalidArgumentException(\sprintf(
            'Fixture "%s" is not known.',
            $name,
        ));
    }
}
