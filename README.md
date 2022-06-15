object(ReflectionMethod)#669 (2) {
  ["name"]=>
  string(11) "__construct"
  ["class"]=>
  string(25) "Tomrf\ReadmeGen\ReadmeGen"
}
object(ReflectionMethod)#666 (2) {
  ["name"]=>
  string(8) "generate"
  ["class"]=>
  string(25) "Tomrf\ReadmeGen\ReadmeGen"
}
object(ReflectionMethod)#670 (2) {
  ["name"]=>
  string(11) "__construct"
  ["class"]=>
  string(50) "Tomrf\ReadmeGen\Interface\ReadmeFormatterInterface"
}
object(ReflectionMethod)#667 (2) {
  ["name"]=>
  string(9) "formatToc"
  ["class"]=>
  string(50) "Tomrf\ReadmeGen\Interface\ReadmeFormatterInterface"
}
object(ReflectionMethod)#666 (2) {
  ["name"]=>
  string(12) "formatMethod"
  ["class"]=>
  string(50) "Tomrf\ReadmeGen\Interface\ReadmeFormatterInterface"
}
object(ReflectionMethod)#669 (2) {
  ["name"]=>
  string(11) "formatClass"
  ["class"]=>
  string(50) "Tomrf\ReadmeGen\Interface\ReadmeFormatterInterface"
}
object(ReflectionMethod)#428 (2) {
  ["name"]=>
  string(11) "__construct"
  ["class"]=>
  string(40) "Tomrf\ReadmeGen\Formatter\DummyFormatter"
}
object(ReflectionMethod)#667 (2) {
  ["name"]=>
  string(9) "formatToc"
  ["class"]=>
  string(40) "Tomrf\ReadmeGen\Formatter\DummyFormatter"
}
object(ReflectionMethod)#670 (2) {
  ["name"]=>
  string(11) "formatClass"
  ["class"]=>
  string(40) "Tomrf\ReadmeGen\Formatter\DummyFormatter"
}
object(ReflectionMethod)#416 (2) {
  ["name"]=>
  string(12) "formatMethod"
  ["class"]=>
  string(40) "Tomrf\ReadmeGen\Formatter\DummyFormatter"
}
object(ReflectionMethod)#666 (2) {
  ["name"]=>
  string(11) "__construct"
  ["class"]=>
  string(43) "Tomrf\ReadmeGen\Formatter\MarkdownFormatter"
}
object(ReflectionMethod)#415 (2) {
  ["name"]=>
  string(9) "formatToc"
  ["class"]=>
  string(43) "Tomrf\ReadmeGen\Formatter\MarkdownFormatter"
}
object(ReflectionMethod)#417 (2) {
  ["name"]=>
  string(11) "formatClass"
  ["class"]=>
  string(43) "Tomrf\ReadmeGen\Formatter\MarkdownFormatter"
}
object(ReflectionMethod)#416 (2) {
  ["name"]=>
  string(12) "formatMethod"
  ["class"]=>
  string(43) "Tomrf\ReadmeGen\Formatter\MarkdownFormatter"
}
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

_Generated 2022-06-15T03:19:38+02:00 using 📚[tomrf/readme-gen](https://packagist.org/packages/tomrf/readme-gen)_
