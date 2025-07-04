<?php

namespace App\EventSubscriber;

use App\Service\ThemeLocaleService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AppEventSubscriber implements EventSubscriberInterface
{
    private ThemeLocaleService $themeLocaleService;

    public function __construct(ThemeLocaleService $themeLocaleService)
    {
        $this->themeLocaleService = $themeLocaleService;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Set locale from user preference (session or cookie)
        $this->themeLocaleService->setLocaleFromRequest($request);

        // Apply theme from user preference (session or cookie)
        $this->themeLocaleService->applyThemeToRequest($request);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 10]],
        ];
    }
}