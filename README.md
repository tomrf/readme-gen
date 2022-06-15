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
 - [Tomrf\ReadmeGen\Formatter\AbstractFormatter](#-tomrfreadmegenformatterabstractformatterclass)
   - [__construct](#__construct)
   - [formatToc](#formattoc)
   - [formatMethod](#formatmethod)
   - [formatClass](#formatclass)
 - [Tomrf\ReadmeGen\Formatter\DummyFormatter](#-tomrfreadmegenformatterdummyformatterclass)
   - [__construct](#__construct)
   - [formatToc](#formattoc)
   - [formatClass](#formatclass)
   - [formatMethod](#formatmethod)
 - [Tomrf\ReadmeGen\Formatter\MarkdownFormatter](#-tomrfreadmegenformattermarkdownformatterclass)
   - [__construct](#__construct)
   - [formatToc](#formattoc)
   - [formatClass](#formatclass)
   - [formatMethod](#formatmethod)


***

### 📂 Tomrf\ReadmeGen\ReadmeGen::class

ReadmeGen.

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

### 📂 Tomrf\ReadmeGen\Formatter\AbstractFormatter::class

#### __construct()

```php
abstract public function __construct(
    phpDocumentor\Reflection\DocBlockFactoryInterface $docBlockFactory,
    phpDocumentor\Reflection\Types\ContextFactory $contextFactory
): void
```

#### formatToc()

```php
abstract public function formatToc(
    array $structure
): string
```

#### formatMethod()

```php
abstract public function formatMethod(
    ReflectionMethod $reflection
): string
```

#### formatClass()

```php
abstract public function formatClass(
    ReflectionClass $reflection
): string
```

### 📂 Tomrf\ReadmeGen\Formatter\DummyFormatter::class

#### __construct()

```php
public function __construct(
    phpDocumentor\Reflection\DocBlockFactoryInterface $docBlockFactory,
    phpDocumentor\Reflection\Types\ContextFactory $contextFactory
): void
```

#### formatToc()

```php
public function formatToc(
    array $toc
): string
```

#### formatClass()

```php
public function formatClass(
    ReflectionClass $reflection
): string
```

#### formatMethod()

```php
public function formatMethod(
    ReflectionMethod $reflection
): string
```

### 📂 Tomrf\ReadmeGen\Formatter\MarkdownFormatter::class

#### __construct()

```php
public function __construct(
    phpDocumentor\Reflection\DocBlockFactoryInterface $docBlockFactory,
    phpDocumentor\Reflection\Types\ContextFactory $contextFactory
): void
```

#### formatToc()

```php
public function formatToc(
    array $toc
): string
```

#### formatClass()

```php
public function formatClass(
    ReflectionClass $reflection
): string
```

#### formatMethod()

```php
public function formatMethod(
    ReflectionMethod $reflection
): string
```



***

_Generated 2022-06-15T03:24:41+02:00 using 📚[tomrf/readme-gen](https://packagist.org/packages/tomrf/readme-gen)_
