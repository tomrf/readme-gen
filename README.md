# readme-gen - generates readme with public api documentation for php packages

[![PHP Version Require](http://poser.pugx.org/tomrf/readme-gen/require/php?style=flat-square)](https://packagist.org/packages/tomrf/readme-gen) [![Latest Stable Version](http://poser.pugx.org/tomrf/readme-gen/v?style=flat-square)](https://packagist.org/packages/tomrf/readme-gen) [![License](http://poser.pugx.org/tomrf/readme-gen/license?style=flat-square)](https://packagist.org/packages/tomrf/readme-gen)

Automatically generates a 📚 README file with public API documentation for a PHP package, 
based on docblocks and package information from composer.json

Included formatters:
 - Markdown

📔 [Go to documentation](#documentation)

## Installation
Installation via composer:

```bash
composer require tomrf/readme-gen
```

## Usage
```php
$readmeGen = new \Tomrf\ReadmeGen\ReadmeGen('.'); // path to project directory

echo $readmeGen->generate(
    new Tomrf\ReadmeGen\Formatter\MarkdownFormatter(
        \phpDocumentor\Reflection\DocBlockFactory::createInstance(),
        new \phpDocumentor\Reflection\Types\ContextFactory()
    ),
    'resources/template.md'
);
```

## Testing
```bash
composer test
```

## License
This project is released under the MIT License (MIT).
See [LICENSE](LICENSE) for more information.

## Documentation
 - [Tomrf\ReadmeGen\ReadmeGen](#-tomrfreadmegenreadmegenclass)
   - [__construct](#__construct)
   - [generate](#generate)


***

### 📂 Tomrf\ReadmeGen\ReadmeGen::class

ReadmeGen.

Very much a work in progress.

#### __construct()

```php
public function __construct(
    string $projectRoot
): void
```

#### generate()

```php
public function generate(
    Tomrf\ReadmeGen\Interface\ReadmeFormatterInterface $formatter,
    string $templateFilename
): string
```



***

_Generated 2022-06-15T22:33:50+02:00 using 📚[tomrf/readme-gen](https://packagist.org/packages/tomrf/readme-gen)_
