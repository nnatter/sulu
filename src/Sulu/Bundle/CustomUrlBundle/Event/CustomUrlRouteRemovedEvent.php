<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Event;

use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Bundle\EventLogBundle\Event\DomainEvent;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;

class CustomUrlRouteRemovedEvent extends DomainEvent
{
    /**
     * @var CustomUrlDocument
     */
    private $customUrlDocument;

    /**
     * @var string
     */
    private $webspaceKey;

    /**
     * @var string
     */
    private $routeUuid;

    public function __construct(
        CustomUrlDocument $customUrlDocument,
        string $webspaceKey,
        string $routeUuid
    ) {
        parent::__construct();

        $this->customUrlDocument = $customUrlDocument;
        $this->webspaceKey = $webspaceKey;
        $this->routeUuid = $routeUuid;
    }

    public function getEventType(): string
    {
        return 'route_removed';
    }

    public function getEventPayload(): array
    {
        return [];
    }

    public function getResourceKey(): string
    {
        return 'custom_urls';
    }

    public function getResourceId(): string
    {
        return (string) $this->customUrlDocument->getUuid();
    }

    public function getResourceLocale(): ?string
    {
        return null;
    }

    public function getResourceTitle(): ?string
    {
        return $this->customUrlDocument->getTitle();
    }

    public function getResourceSecurityContext(): ?string
    {
        return CustomUrlAdmin::getCustomUrlSecurityContext($this->webspaceKey);
    }

    public function getResourceSecurityType(): ?string
    {
        return null;
    }
}
