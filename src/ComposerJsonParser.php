<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen;

use Exception;
use RuntimeException;

/**
 * ComposerJsonParser.
 *
 * @internal
 */
final class ComposerJsonParser
{
    private $composerJson;
    private array $rootProperties = [
        'abandoned',
        'archive',
        'authors',
        'autoload',
        'autoload-dev',
        'bin',
        'config',
        'conflict',
        'description',
        'extra',
        'funding',
        'homepage',
        'include-path',
        'keywords',
        'license',
        'minimum-stability',
        'name',
        'non-feature-branches',
        'prefer-stable',
        'provide',
        'readme',
        'replace',
        'repositories',
        'require',
        'require-dev',
        'scripts',
        'suggest',
        'support',
        'target-dir',
        'time',
        'type',
        'version',
    ];

    public function __construct(private string $composerJsonPath)
    {
        if (!file_exists($composerJsonPath)) {
            throw new RuntimeException(sprintf('No such file or directory: %s', $composerJsonPath));
        }

        if (!is_readable($composerJsonPath)) {
            throw new RuntimeException(sprintf('File not readable, check permissions: %s', $composerJsonPath));
        }

        try {
            $contents = file_get_contents($composerJsonPath);
        } catch (Exception $exception) {
            throw new RuntimeException(sprintf('Failed to read file: %s: %s', $composerJsonPath, $exception));
        }

        try {
            $this->composerJson = json_decode($contents, false, 128, JSON_THROW_ON_ERROR);
        } catch (Exception $exception) {
            throw new RuntimeException(sprintf('Failed to decode JSON from file: %s: %s', $composerJsonPath, $exception));
        }
    }

    public function get(string $property): mixed
    {
        if (!$this->has($property)) {
            throw new RuntimeException(sprintf('Property not set: %s', $property));
        }

        return $this->composerJson->{$property};
    }

    public function has(string $property): bool
    {
        return isset($this->composerJson->{$property});
    }

    public function isValidRootProperty(string $property): bool
    {
        return isset($this->rootProperties[$property]);
    }

    public function getRootProperties(): array
    {
        return $this->rootProperties;
    }
}
