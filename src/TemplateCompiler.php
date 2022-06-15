<?php

declare(strict_types=1);

namespace Tomrf\ReadmeGen;

/**
 * TemplateCompiler.
 *
 * @internal
 */
final class TemplateCompiler
{
    public function compile(ComposerJsonParser $composerJsonParser, string $template, array $extra = []): string
    {
        $return = $template;

        // set $extra keys
        foreach ($extra as $key => $value) {
            if (\is_array($value)) {
                $value = implode(PHP_EOL, $value);
            }
            $return = str_replace(sprintf(':%s', $key), $value, $return);
        }

        // set basic composer package keys
        foreach (['name', 'type', 'description', 'homepage', 'license', 'keywords', 'readme'] as $key) {
            $value = '<not_set>';

            if ($composerJsonParser->has($key)) {
                $value = $composerJsonParser->get($key);
            }

            if (\is_array($value)) {
                $value = trim(implode(', ', $value), ', ');
            }

            $return = str_replace(sprintf(':package_%s', $key), $value, $return);
        }

        // set composer extra keys
        if (!$composerJsonParser->has('extra')) {
            return $return;
        }

        foreach ($composerJsonParser->get('extra') as $key => $value) {
            if (\is_array($value)) {
                $value = implode(PHP_EOL, $value);
            }
            $return = str_replace(sprintf(':package_extra_%s', $key), $value, $return);
        }

        return $return;
    }
}
