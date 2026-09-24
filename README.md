# json-parser

[![Integrate](https://github.com/ergebnis/json-parser/actions/workflows/integrate.yaml/badge.svg?branch=main)](https://github.com/ergebnis/json-parser/actions/workflows/integrate.yaml)
[![Merge](https://github.com/ergebnis/json-parser/actions/workflows/merge.yaml/badge.svg)](https://github.com/ergebnis/json-parser/actions/workflows/merge.yaml)
[![Release](https://github.com/ergebnis/json-parser/actions/workflows/release.yaml/badge.svg)](https://github.com/ergebnis/json-parser/actions/workflows/release.yaml)
[![Renew](https://github.com/ergebnis/json-parser/actions/workflows/renew.yaml/badge.svg)](https://github.com/ergebnis/json-parser/actions/workflows/renew.yaml)

[![Code Coverage](https://codecov.io/gh/ergebnis/json-parser/branch/main/graph/badge.svg)](https://codecov.io/gh/ergebnis/json-parser)

[![Latest Stable Version](https://poser.pugx.org/ergebnis/json-parser/v/stable)](https://packagist.org/packages/ergebnis/json-parser)
[![Total Downloads](https://poser.pugx.org/ergebnis/json-parser/downloads)](https://packagist.org/packages/ergebnis/json-parser)
[![Monthly Downloads](https://poser.pugx.org/ergebnis/json-parser/d/monthly)](https://packagist.org/packages/ergebnis/json-parser)

This project provides a [`composer`](https://getcomposer.org) package with a [JSON](https://www.json.org) parser producing an abstract syntax tree that keeps the raw text of strings and numbers as well as duplicate property names, with a printer that prints the tree back to JSON and a traverser that changes the tree.

## Installation

Run

```sh
composer require ergebnis/json-parser
```

## Usage

### Parsing

```php
<?php

declare(strict_types=1);

use Ergebnis\Json\Parser;

$parser = new Parser\Parser();

$node = $parser->parse(
    Parser\Raw::fromString('{"name": "ergebnis/json-parser", "version": 1.0, "tag": "a", "tag": "b"}'),
    Parser\MaximumDepth::default(),
);
```

`Parser::parse()` returns the root of an abstract syntax tree of `ArrayNode`, `BooleanNode`, `NullNode`, `NumberNode`, `ObjectNode`, and `StringNode` nodes. The tree keeps the raw text of strings and numbers, the order of properties, and duplicate property names. The parser accepts exactly [RFC 8259](https://datatracker.ietf.org/doc/html/rfc8259) and throws an `InvalidJson` with the position of the first error otherwise. `MaximumDepth::default()` limits the nesting depth to 512, like `json_decode()`.

### Traversing

A `Traverser` walks the tree and calls `enter()` and `leave()` on each of its visitors for every node. A visitor implements `Traverser\Visitor` and answers with an `EnterAction` (`keep()`, `replace()`, `remove()`, or `skipChildren()`) or a `LeaveAction` (`keep()`, `replace()`, or `remove()`), and the traverser changes the tree in place.

```php
<?php

declare(strict_types=1);

use Ergebnis\Json\Parser;

final class CommentRemover implements Parser\Traverser\Visitor
{
    public function enter(
        Parser\Node\Node $node,
        Parser\Traverser\Path $path
    ): Parser\Traverser\EnterAction {
        if ('/extra/patches' === $path->toJsonPointer()->toJsonString()) {
            return Parser\Traverser\EnterAction::skipChildren();
        }

        $name = $path->name();

        if (
            $name instanceof Parser\Node\StringNode
            && '_comment' === $name->toString()
        ) {
            return Parser\Traverser\EnterAction::remove();
        }

        return Parser\Traverser\EnterAction::keep();
    }

    public function leave(
        Parser\Node\Node $node,
        Parser\Traverser\Path $path
    ): Parser\Traverser\LeaveAction {
        return Parser\Traverser\LeaveAction::keep();
    }
}

$parser = new Parser\Parser();
$printer = new Parser\Printer();
$traverser = new Parser\Traverser\Traverser(new CommentRemover());

$node = $parser->parse(
    Parser\Raw::fromString('{"_comment": "Removed", "name": "ergebnis/json-parser", "extra": {"_comment": "Removed", "patches": {"_comment": "Kept"}}}'),
    Parser\MaximumDepth::default(),
);

echo $printer->print(
    $traverser->traverse($node),
    Parser\Format::compact(),
); // {"name":"ergebnis/json-parser","extra":{"patches":{"_comment":"Kept"}}}
```

### Printing

```php
<?php

declare(strict_types=1);

use Ergebnis\Json\Parser;

$parser = new Parser\Parser();
$printer = new Parser\Printer();

$node = $parser->parse(
    Parser\Raw::fromString('{"name":"ergebnis/json-parser","homepage":"https:\/\/github.com\/ergebnis\/json-parser","version":1.0,"size":1E+3,"keywords":["json","parser"]}'),
    Parser\MaximumDepth::default(),
);

echo $printer->print(
    $node,
    Parser\Format::create(
        Parser\Indent::create(
            Parser\IndentSize::fromInt(4),
            Parser\IndentStyle::space(),
        ),
        Parser\NewLine::lf(),
        Parser\FinalNewLine::present(),
    ),
);
```

This prints

```json
{
    "name": "ergebnis/json-parser",
    "homepage": "https:\/\/github.com\/ergebnis\/json-parser",
    "version": 1.0,
    "size": 1E+3,
    "keywords": [
        "json",
        "parser"
    ]
}
```

`Printer::print()` prints strings and numbers exactly as they were parsed, so escapes like `\/` and spellings like `1.0` and `1E+3` survive, where a round trip through `json_decode()` and `json_encode()` would change them. `Format::compact()` prints without insignificant whitespace, and `Format::fromRaw()` detects the indent, new line, and final new line of a JSON text, so that a changed document can be printed with the layout it had.

## Changelog

The maintainers of this project record notable changes to this project in a [changelog](CHANGELOG.md).

## Contributing

The maintainers of this project suggest following the [contribution guide](.github/CONTRIBUTING.md).

## Code of Conduct

The maintainers of this project ask contributors to follow the [code of conduct](https://github.com/ergebnis/.github/blob/main/CODE_OF_CONDUCT.md).

## General Support Policy

The maintainers of this project provide limited support.

## PHP Version Support Policy

This project currently supports the following PHP versions:

- [PHP 7.4](https://www.php.net/releases/#7.4.0) (has reached its end of life on November 28, 2022)
- [PHP 8.0](https://www.php.net/releases/#8.0.0) (has reached its end of life on November 26, 2023)
- [PHP 8.1](https://www.php.net/releases/#8.1.0) (has reached its end of life on December 31, 2025)
- [PHP 8.2](https://www.php.net/releases/#8.2.0)
- [PHP 8.3](https://www.php.net/releases/#8.3.0)
- [PHP 8.4](https://www.php.net/releases/#8.4.0)
- [PHP 8.5](https://www.php.net/releases/#8.5.0)

The maintainers of this project add support for a PHP version following its initial release and _may_ drop support for a PHP version when it has reached its [end of life](https://www.php.net/supported-versions.php).

## Security Policy

This project has a [security policy](.github/SECURITY.md).

## License

This project uses the [MIT license](LICENSE.md).

## Credits

The design of the parser, the abstract syntax tree, and the printer is inspired by [`nikic/php-parser`](https://github.com/nikic/PHP-Parser), originally licensed under BSD-3-Clause by [Nikita Popov](https://github.com/nikic).

The test fixtures in [`test/Fixture/JSONTestSuite/`](test/Fixture/JSONTestSuite) are copied from [`nst/JSONTestSuite`](https://github.com/nst/JSONTestSuite/tree/1ef36fa01286573e846ac449e8683f8833c5b26a), originally licensed under MIT by [Nicolas Seriot](https://github.com/nst).

## Social

Follow [@localheinz](https://twitter.com/intent/follow?screen_name=localheinz) and [@ergebnis](https://twitter.com/intent/follow?screen_name=ergebnis) on Twitter.
