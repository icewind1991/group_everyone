<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\GroupEveryone;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\IGroupManager;
use OCP\User\Events\UserCreatedEvent;

class UserCreatedListener implements IEventListener {
	private IEventDispatcher $dispatcher;
	private IGroupManager $groupManager;

	public function __construct(IEventDispatcher $dispatcher, IGroupManager $groupManager) {
		$this->dispatcher = $dispatcher;
		$this->groupManager = $groupManager;
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserCreatedEvent)) {
			return;
		}

		$group = $this->groupManager->get('everyone');

		$this->dispatcher->dispatchTyped(new UserAddedEvent($group, $event->getUser()));
	}
}
