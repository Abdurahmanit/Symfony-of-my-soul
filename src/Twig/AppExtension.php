<?php

namespace App\Twig;

use App\Service\ThemeLocaleService;
use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private MarkdownParserInterface $markdownParser;
    private ThemeLocaleService $themeLocaleService;
    private Security $security;

    public function __construct(MarkdownParserInterface $markdownParser, ThemeLocaleService $themeLocaleService, Security $security)
    {
        $this->markdownParser = $markdownParser;
        $this->themeLocaleService = $themeLocaleService;
        $this->security = $security;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_current_theme', [$this->themeLocaleService, 'getTheme']),
            new TwigFunction('get_current_locale', [$this->themeLocaleService, 'getLocale']),
            new TwigFunction('is_admin', [$this, 'isAdmin']),
        ];
    }

    public function parseMarkdown(?string $content): string
    {
        if ($content === null) {
            return '';
        }
        return $this->markdownParser->parse($content);
    }

    public function isAdmin(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }
}