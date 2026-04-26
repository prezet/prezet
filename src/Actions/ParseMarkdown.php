<?php

namespace Prezet\Prezet\Actions;

use Exception;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Output\RenderedContentInterface;
use Phiki\CommonMark\PhikiExtension;
use Phiki\Theme\Theme;
use Prezet\Prezet\Exceptions\InvalidConfigurationException;
use Prezet\Prezet\Extensions\MarkdownBladeExtension;

class ParseMarkdown
{
    /**
     * @throws Exception|CommonMarkException
     */
    public function handle(string $md): RenderedContentInterface
    {
        $config = config('prezet.commonmark.config');
        if (! is_array($config)) {
            throw new InvalidConfigurationException('prezet.commonmark.config', $config, 'is not an array');
        }

        $environment = new Environment($config);
        $extensions = $this->getExtensions();

        foreach ($extensions as $extension) {
            if ($extension === PhikiExtension::class) {
                $phikiConfig = $this->getPhikiConfig();
                $environment->addExtension(new PhikiExtension(
                    $phikiConfig['theme'],
                    withWrapper: $phikiConfig['with_wrapper'],
                    withGutter: $phikiConfig['with_gutter']
                ));
            } else {
                $environment->addExtension(new $extension);
            }
        }

        $converter = new MarkdownConverter($environment);

        MarkdownBladeExtension::$allowBladeForNextDocument = true;
        $result = $converter->convert($md);
        MarkdownBladeExtension::$allowBladeForNextDocument = false;

        return $result;
    }

    /**
     * @return array<int, class-string<ExtensionInterface>>
     *
     * @throws InvalidConfigurationException
     */
    protected function getExtensions(): array
    {
        $extensions = config('prezet.commonmark.extensions');
        if (! is_array($extensions)) {
            throw new InvalidConfigurationException('prezet.commonmark.extensions', $extensions, 'is not an array');
        }

        $validExtensions = [];

        foreach ($extensions as $extension) {
            if (! is_string($extension) || ! is_subclass_of($extension, ExtensionInterface::class)) {
                throw new InvalidConfigurationException('prezet.commonmark.extensions', $extension, 'does not implement League\CommonMark\Extension\ExtensionInterface');
            }

            $validExtensions[] = $extension;
        }

        return $validExtensions;
    }

    /**
     * @return array{theme: string|array<mixed>|Theme, with_wrapper: bool, with_gutter: bool}
     *
     * @throws InvalidConfigurationException
     */
    protected function getPhikiConfig(): array
    {
        $config = config('prezet.commonmark.config.phiki');
        if (! is_array($config)) {
            throw new InvalidConfigurationException('prezet.commonmark.config.phiki', $config, 'is not an array');
        }

        $theme = $config['theme'] ?? null;
        if (! is_string($theme) && ! is_array($theme) && ! $theme instanceof Theme) {
            throw new InvalidConfigurationException('prezet.commonmark.config.phiki.theme', $theme, 'is not a string, array, or Phiki theme');
        }

        $withWrapper = $config['with_wrapper'] ?? null;
        if (! is_bool($withWrapper)) {
            throw new InvalidConfigurationException('prezet.commonmark.config.phiki.with_wrapper', $withWrapper, 'is not a boolean');
        }

        $withGutter = $config['with_gutter'] ?? null;
        if (! is_bool($withGutter)) {
            throw new InvalidConfigurationException('prezet.commonmark.config.phiki.with_gutter', $withGutter, 'is not a boolean');
        }

        return [
            'theme' => $theme,
            'with_wrapper' => $withWrapper,
            'with_gutter' => $withGutter,
        ];
    }
}
