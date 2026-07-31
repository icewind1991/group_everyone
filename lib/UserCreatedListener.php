<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\IGroupManager;
use OCP\User\Events\UserCreatedEvent;

/**
 * Existing shares with the virtual group are only picked up for a newly created
 * user once the "user added to group" event has been seen for them.
 *
 * @template-implements IEventListener<UserCreatedEvent>
 */
class UserCreatedListener implements IEventListener {
	public function __construct(
		private IEventDispatcher $dispatcher,
		private IGroupManager $groupManager,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserCreatedEvent)) {
			return;
		}

		$group = $this->groupManager->get(GroupBackend::GROUP_ID);
		if ($group === null) {
			// The backend is not registered (yet), nothing to announce.
			return;
		}

		$this->dispatcher->dispatchTyped(new UserAddedEvent($group, $event->getUser()));
	}
}
