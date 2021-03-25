<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Tests\Unit\Application\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\EventLogBundle\Application\Subscriber\DispatchSpecificDomainEventSubscriber;
use Sulu\Bundle\EventLogBundle\Tests\Application\Domain\Event\TestDomainEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DispatchSpecificDomainEventSubscriberTest extends TestCase
{
    /**
     * @var EventDispatcherInterface|ObjectProphecy
     */
    private $eventDispatcher;

    public function setUp(): void
    {
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    }

    public function testDispatchDomainEventWithSpecificEventName()
    {
        $subscriber = $this->createDispatchSpecificDomainEventSubscriber();

        $event = new TestDomainEvent();
        $this->eventDispatcher->dispatch($event, TestDomainEvent::class)
            ->shouldBeCalled()
            ->willReturn($event);

        $subscriber->dispatchDomainEventWithSpecificEventName($event);
    }

    private function createDispatchSpecificDomainEventSubscriber(): DispatchSpecificDomainEventSubscriber
    {
        return new DispatchSpecificDomainEventSubscriber(
            $this->eventDispatcher->reveal()
        );
    }
}
