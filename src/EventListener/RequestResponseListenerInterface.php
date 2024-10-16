<?php

declare(strict_types=1);

namespace MobileDetectBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 * @author HenriVesala <henri.vesala@gmail.com>
 */
interface RequestResponseListenerInterface
{
    public const REDIRECT = 'redirect';
    public const NO_REDIRECT = 'no_redirect';
    public const REDIRECT_WITHOUT_PATH = 'redirect_without_path';

    public const MOBILE = 'mobile';
    public const TABLET = 'tablet';
    public const FULL = 'full';

    /**
     * Handle the incoming request event and perform necessary actions.
     * This method will be invoked when a request event is triggered in the application.
     */
    public function handleRequest(RequestEvent $event): void;

    /**
     * Will this request listener modify the response? This flag will be set during the "handleRequest" phase.
     * Made public for testability.
     */
    public function needsResponseModification(): bool;

    /**
     * Handles the response event by performing certain actions based on the given ResponseEvent object.
     */
    public function handleResponse(ResponseEvent $event): void;
}
