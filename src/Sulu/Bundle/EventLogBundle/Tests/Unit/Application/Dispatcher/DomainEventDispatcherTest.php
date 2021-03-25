<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Unit\Application\Dispatcher;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\EventLogBundle\Application\Dispatcher\DomainEventDispatcher;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\EventLogBundle\Tests\Application\Domain\Event\TestDomainEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DomainEventDispatcherTest extends TestCase
{
    /**
     * @var EventDispatcherInterface|ObjectProphecy
     */
    private $innerEventDispatcher;

    public function setUp(): void
    {
        $this->innerEventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    }

    public function testDispatch()
    {
        $dispatcher = $this->createDomainEventDispatcher();

        $event = new TestDomainEvent();
        $this->innerEventDispatcher->dispatch($event, DomainEvent::class)
            ->shouldBeCalled()
            ->willReturn($event);

        $dispatcher->dispatch($event);
    }

    private function createDomainEventDispatcher(): DomainEventDispatcher
    {
        return new DomainEventDispatcher(
            $this->innerEventDispatcher->reveal()
        );
    }
}
