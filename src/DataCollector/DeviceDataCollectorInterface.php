<?php

declare(strict_types=1);

namespace MobileDetectBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Jonas HAOUZI <haouzijonas@gmail.com>
 */
interface DeviceDataCollectorInterface
{
    /**
     * Returns an array of all the properties that should be serialized when this object is serialized with `serialize()`.
     */
    public function __sleep(): array;

    /**
     * Resurrects the object after it has been unserialized with `unserialize()`.
     */
    public function __wakeup();

    /**
     * Collects data for the given Request and Response.
     */
    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void;

    /**
     * Returns the current view name as a string or null.
     */
    public function getCurrentView(): ?string;

    /**
     * Returns an array of views associated with this object.
     */
    public function getViews(): array;

    /**
     * Sets the redirect configuration for the Symfony application.
     */
    public function setRedirectConfig(array $redirectConfig): self;

    /**
     * Gets the name of the Symfony application.
     */
    public function getName(): string;

    /**
     * Retrieves the data stored in the Symfony application.
     */
    public function getData(): array;

    /**
     * Resets any configuration or data in the Symfony application to its default state.
     */
    public function reset(): void;
}
