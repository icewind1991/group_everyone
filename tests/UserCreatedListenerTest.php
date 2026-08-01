<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone\Tests;

use OCA\GroupEveryone\UserCreatedListener;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\UserAddedEvent;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\User\Events\UserCreatedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UserCreatedListenerTest extends TestCase {
	private IEventDispatcher&MockObject $dispatcher;
	private IGroupManager&MockObject $groupManager;
	private UserCreatedListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->listener = new UserCreatedListener($this->dispatcher, $this->groupManager);
	}

	public function testDispatchesUserAddedEvent(): void {
		$user = $this->createMock(IUser::class);
		$group = $this->createMock(IGroup::class);

		$this->groupManager->method('get')
			->with('everyone')
			->willReturn($group);

		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(function (UserAddedEvent $event) use ($group, $user) {
				return $event->getGroup() === $group && $event->getUser() === $user;
			}));

		$this->listener->handle(new UserCreatedEvent($user, ''));
	}

	/**
	 * Regression test: a missing group was passed straight into UserAddedEvent,
	 * which made creating a user fail with a TypeError.
	 */
	public function testIgnoresMissingGroup(): void {
		$this->groupManager->method('get')
			->with('everyone')
			->willReturn(null);

		$this->dispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->listener->handle(new UserCreatedEvent($this->createMock(IUser::class), ''));
	}

	public function testIgnoresUnrelatedEvent(): void {
		$this->groupManager->expects($this->never())
			->method('get');
		$this->dispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->listener->handle(new Event());
	}
}
