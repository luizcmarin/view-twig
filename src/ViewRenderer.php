<?php

declare(strict_types=1);

namespace Yiisoft\View\Twig;

use Throwable;
use Twig\Environment;
use Yiisoft\View\TemplateRendererInterface;
use Yiisoft\View\ViewInterface;

use function array_merge;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_implicit_flush;
use function ob_start;
use function str_replace;

/**
 * `ViewRenderer` allows using Twig with a View service.
 */
final class ViewRenderer implements TemplateRendererInterface
{
    public function __construct(private Environment $environment)
    {
    }

    public function render(ViewInterface $view, string $template, array $parameters): string
    {
        $environment = $this->environment;
        $renderer = function () use ($view, $template, $parameters, $environment): void {
            $template = str_replace('\\', '/', $template);
            $basePath = str_replace('\\', '/', $view->getBasePath());
            $file = str_replace($basePath, '', $template);

            echo $environment->render($file, array_merge($parameters, ['this' => $view]));
        };

        $obInitialLevel = ob_get_level();
        ob_start();
        ob_implicit_flush(false);

        try {
            /** @psalm-suppress PossiblyInvalidFunctionCall,PossiblyNullFunctionCall */
            $renderer->bindTo($view)();
            return ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $obInitialLevel) {
                ob_end_clean();
            }
            throw $e;
        }
    }
}
