<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ThemeLocaleService
{
    private const THEME_SESSION_KEY = '_app_theme';
    private const LOCALE_SESSION_KEY = '_locale';

    private SessionInterface $session;
    private string $defaultLocale;

    public function __construct(SessionInterface $session, string $defaultLocale = 'en')
    {
        $this->session = $session;
        $this->defaultLocale = $defaultLocale;
    }

    public function setTheme(string $theme): void
    {
        $this->session->set(self::THEME_SESSION_KEY, $theme);
    }

    public function getTheme(): string
    {
        return $this->session->get(self::THEME_SESSION_KEY, 'light'); // Default to light
    }

    public function applyThemeToRequest(Request $request): void
    {
        $theme = $this->getTheme();
        // You would typically add a CSS class to the <body> tag in Twig based on this.
        // For server-side rendering, you might set a request attribute.
        $request->attributes->set('app_theme', $theme);
    }

    public function setLocale(string $locale): void
    {
        $this->session->set(self::LOCALE_SESSION_KEY, $locale);
    }

    public function getLocale(): string
    {
        return $this->session->get(self::LOCALE_SESSION_KEY, $this->defaultLocale);
    }

    public function setLocaleFromRequest(Request $request): void
    {
        // Try to get locale from session, then from request headers, then default
        $locale = $this->session->get(self::LOCALE_SESSION_KEY, $request->getPreferredLanguage($request->getLanguages()));
        $request->setLocale($locale);
    }
}