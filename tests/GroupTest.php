<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Robin Appelman <robin@icewind.nl>
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

namespace OCA\GroupEveryone\Tests;

use OCA\GroupEveryone\GroupBackend;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use Test\TestCase;

class GroupTest extends TestCase {
	private IUserManager $userManager;
	private IL10N $l10n;
	private GroupBackend $backend;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')
			->willReturnArgument(0);
		$this->backend = new GroupBackend($this->userManager, $this->l10n);
	}

	public function testInGroup() {
		$this->assertTrue($this->backend->inGroup('foo', 'everyone'));
		$this->assertFalse($this->backend->inGroup('foo', 'not_everyone'));
	}

	public function testGroupDetails() {
		$this->assertEquals(['displayName' => 'Everyone'], $this->backend->getGroupDetails('everyone'));
		$this->assertEquals([], $this->backend->getGroupDetails('something else'));
	}

	private function getUser(string $name): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn($name);
		return $user;
	}

	public function testUsersInGroup() {
		$this->userManager->method('search')
			->with('filter', 2, 1)
			->willReturn([
				$this->getUser('a'),
				$this->getUser('b'),
			]);
		$this->assertEquals(['a', 'b'], $this->backend->usersInGroup('everyone', 'filter', 2, 1));
		$this->assertEquals([], $this->backend->usersInGroup('bar'));
	}
}
