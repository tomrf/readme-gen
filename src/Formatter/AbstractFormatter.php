<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen\Formatter;

use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionMethod;
use Tomrf\ReadmeGen\Interface\ReadmeFormatterInterface;

abstract class AbstractFormatter implements ReadmeFormatterInterface
{
    protected DocBlockFactoryInterface $docBlockFactory;
    protected ContextFactory $contextFactory;

    protected function getMethodDefinition(ReflectionMethod $method): string
    {
        $parametersString = '';

        $parameters = $method->getParameters();
        foreach ($parameters as $n => $param) {
            $type = $param->getType() ? sprintf('%s ', $param->getType()) : '';
            $parametersString .= sprintf('    %s$%s', $type, $param->getName());

            if ($param->isDefaultValueAvailable()) {
                if ('array' === (string) $param->getType()) {
                    $parametersString .= ' = []';
                } elseif (str_contains((string) $param->getType(), 'string')) {
                    $parametersString .= sprintf(' = \'%s\'', $param->getDefaultValue());
                } else {
                    $parametersString .= sprintf(' = %s', $param->getDefaultValue());
                }
            }

            if (array_key_last($parameters) !== $n) {
                $parametersString .= ','.PHP_EOL;
            }
        }

        return sprintf(
            "%s function %s(\n%s\n): %s",
            $this->getAccessForReflectionMethod($method),
            $method->getName(),
            $parametersString,
            ($method->getReturnType() ?? 'void')
        );
    }

    protected function getMethodTagsString(ReflectionMethod $method, Context $context = null): string
    {
        $tagsString = '';

        foreach ($this->getTags($method, $context) as $tag) {
            $tagsString .= sprintf("@%-8s %s\n", key($tag), (string) $tag[key($tag)]);
        }

        return $tagsString;
    }

    protected function getAccessForReflectionMethod(ReflectionMethod $method): string
    {
        return trim(sprintf(
            '%s%s%s%s%s%s',
            $method->isAbstract() ? 'abstract ' : '',
            $method->isStatic() ? 'static ' : '',
            $method->isPrivate() ? 'private ' : '',
            $method->isProtected() ? 'protected ' : '',
            $method->isPublic() ? 'public ' : '',
            $method->isInternal() ? 'private ' : '',
        ));
    }

    protected function getTags($objectOrString, Context $context = null): array
    {
        $tags = [];
        $docBlock = $this->docBlockFactory->create($objectOrString, $context);

        foreach ($docBlock->getTags() as $tag) {
            $tags[] = [
                $tag->getName() => str_replace(
                    ["\n", "\r"],
                    ' ',
                    (string) $tag
                ),
            ];
        }

        return $tags;
    }
}
