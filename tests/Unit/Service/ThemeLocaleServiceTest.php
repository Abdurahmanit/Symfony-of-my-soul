<?php

namespace App\Tests\Unit\Service;

use App\Service\ThemeLocaleService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class ThemeLocaleServiceTest extends TestCase
{
    private Session $session;
    private ThemeLocaleService $service;

    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
        $this->service = new ThemeLocaleService($this->session, 'en');
    }

    public function testSetAndGetTheme(): void
    {
        $this->service->setTheme('dark');
        $this->assertEquals('dark', $this->service->getTheme());
        $this->assertEquals('dark', $this->session->get('_app_theme'));
    }

    public function testDefaultThemeIsLight(): void
    {
        $this->assertEquals('light', $this->service->getTheme());
    }

    public function testApplyThemeToRequest(): void
    {
        $request = new Request();
        $this->service->setTheme('dark');
        $this->service->applyThemeToRequest($request);

        $this->assertEquals('dark', $request->attributes->get('app_theme'));
    }

    public function testSetAndGetLocale(): void
    {
        $this->service->setLocale('pl');
        $this->assertEquals('pl', $this->service->getLocale());
        $this->assertEquals('pl', $this->session->get('_locale'));
    }

    public function testDefaultLocaleIsEn(): void
    {
        $this->assertEquals('en', $this->service->getLocale());
    }

    public function testSetLocaleFromRequestWithSessionValue(): void
    {
        $this->session->set('_locale', 'es');
        $request = new Request();
        $this->service->setLocaleFromRequest($request);

        $this->assertEquals('es', $request->getLocale());
    }

    public function testSetLocaleFromRequestWithPreferredLanguage(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'fr-FR,fr;q=0.9,en;q=0.8');
        $this->service->setLocaleFromRequest($request);

        $this->assertEquals('fr', $request->getLocale());
    }
}