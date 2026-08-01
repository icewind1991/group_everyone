<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone\Tests;

use OCA\GroupEveryone\GroupBackend;
use OCP\Group\Backend\ICountDisabledInGroup;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\IGetDisplayNameBackend;
use OCP\Group\Backend\IGroupDetailsBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\Group\Backend\ISearchableGroupBackend;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class GroupTest extends TestCase {
	private IUserManager&MockObject $userManager;
	private IL10N&MockObject $l10n;
	private GroupBackend $backend;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')
			->willReturnArgument(0);
		$this->backend = new GroupBackend($this->userManager, $this->l10n);
	}

	private function getUser(string $name): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn($name);
		return $user;
	}

	public function testImplementsCurrentBackendInterfaces(): void {
		$this->assertInstanceOf(ICountDisabledInGroup::class, $this->backend);
		$this->assertInstanceOf(ICountUsersBackend::class, $this->backend);
		$this->assertInstanceOf(IGetDisplayNameBackend::class, $this->backend);
		$this->assertInstanceOf(IGroupDetailsBackend::class, $this->backend);
		$this->assertInstanceOf(INamedBackend::class, $this->backend);
		$this->assertInstanceOf(ISearchableGroupBackend::class, $this->backend);
	}

	public function testInGroup(): void {
		$this->assertTrue($this->backend->inGroup('foo', 'everyone'));
		$this->assertFalse($this->backend->inGroup('foo', 'not_everyone'));
	}

	public function testGroupExists(): void {
		$this->assertTrue($this->backend->groupExists('everyone'));
		$this->assertFalse($this->backend->groupExists('not_everyone'));
	}

	public function testGetUserGroups(): void {
		$this->assertEquals(['everyone'], $this->backend->getUserGroups('foo'));
	}

	public function testGroupDetails(): void {
		$this->assertEquals(['displayName' => 'Everyone'], $this->backend->getGroupDetails('everyone'));
		$this->assertEquals([], $this->backend->getGroupDetails('something else'));
	}

	public function testGetDisplayName(): void {
		$this->assertEquals('Everyone', $this->backend->getDisplayName('everyone'));
		$this->assertEquals('', $this->backend->getDisplayName('something else'));
	}

	public function testGetBackendName(): void {
		$this->assertNotEquals('', $this->backend->getBackendName());
	}

	public function testGetGroups(): void {
		$this->assertEquals(['everyone'], $this->backend->getGroups());
	}

	/**
	 * Regression test: the search parameter used to be ignored entirely, so the
	 * virtual group was returned for every group search, no matter the term.
	 */
	public function testGetGroupsHonoursSearch(): void {
		// matches the group id
		$this->assertEquals(['everyone'], $this->backend->getGroups('every'));
		// matches the display name, case insensitively
		$this->assertEquals(['everyone'], $this->backend->getGroups('EVERY'));
		// matches nothing
		$this->assertEquals([], $this->backend->getGroups('admin'));
		$this->assertEquals([], $this->backend->getGroups('not_everyone'));
	}

	/**
	 * Regression test: the search used to only be compared against the group id,
	 * so a translated display name could not be found.
	 */
	public function testGetGroupsHonoursSearchOnDisplayName(): void {
		$backend = new GroupBackend($this->userManager, $this->l10n, 'virtual_all');

		$this->assertEquals(['virtual_all'], $backend->getGroups('every'));
		$this->assertEquals(['virtual_all'], $backend->getGroups('virtual'));
		$this->assertEquals([], $backend->getGroups('admin'));
	}

	/**
	 * The database backend treats a limit of 0 as "no limit" and only applies a
	 * positive offset, so the virtual group has to behave the same way.
	 */
	public function testGetGroupsHonoursLimitAndOffset(): void {
		$this->assertEquals(['everyone'], $this->backend->getGroups('', 0));
		$this->assertEquals(['everyone'], $this->backend->getGroups('', 1));
		$this->assertEquals(['everyone'], $this->backend->getGroups('', -1));
		$this->assertEquals(['everyone'], $this->backend->getGroups('', -1, -1));
		$this->assertEquals(['everyone'], $this->backend->getGroups('', -1, 0));
		$this->assertEquals([], $this->backend->getGroups('', -1, 1));
	}

	public function testCountUsersInGroup(): void {
		$this->userManager->expects($this->once())
			->method('countUsersTotal')
			->willReturn(42);

		$this->assertEquals(42, $this->backend->countUsersInGroup('everyone'));
	}

	public function testCountUsersInGroupUncountable(): void {
		$this->userManager->method('countUsersTotal')
			->willReturn(false);

		$this->assertEquals(0, $this->backend->countUsersInGroup('everyone'));
	}

	public function testCountUsersInOtherGroup(): void {
		$this->userManager->expects($this->never())
			->method('countUsersTotal');

		$this->assertEquals(0, $this->backend->countUsersInGroup('bar'));
	}

	/**
	 * Regression test: the search parameter used to be ignored, so a filtered
	 * user list in the group was reported with the total user count.
	 */
	public function testCountUsersInGroupHonoursSearch(): void {
		$this->userManager->expects($this->never())
			->method('countUsersTotal');
		$this->userManager->expects($this->once())
			->method('searchDisplayName')
			->with('filter')
			->willReturn([
				$this->getUser('a'),
				$this->getUser('b'),
			]);

		$this->assertEquals(2, $this->backend->countUsersInGroup('everyone', 'filter'));
	}

	public function testCountDisabledInGroup(): void {
		$this->userManager->method('countDisabledUsers')
			->willReturn(3);

		$this->assertEquals(3, $this->backend->countDisabledInGroup('everyone'));
		$this->assertEquals(0, $this->backend->countDisabledInGroup('bar'));
	}

	public function testUsersInGroup(): void {
		$this->userManager->method('searchDisplayName')
			->with('filter', 2, 1)
			->willReturn([
				$this->getUser('a'),
				$this->getUser('b'),
			]);
		$this->assertEquals(['a', 'b'], $this->backend->usersInGroup('everyone', 'filter', 2, 1));
		$this->assertEquals([], $this->backend->usersInGroup('bar'));
	}

	public function testUsersInGroupUnlimited(): void {
		// -1 has to be translated to null, MySQL rejects a negative LIMIT
		$this->userManager->expects($this->once())
			->method('searchDisplayName')
			->with('', null, 0)
			->willReturn([$this->getUser('a')]);

		$this->assertEquals(['a'], $this->backend->usersInGroup('everyone'));
	}

	/**
	 * The previous implementation passed a null limit straight through as "no
	 * limit", so it must not be turned into a limit of 0 (which returns nothing).
	 */
	public function testUsersInGroupNullLimit(): void {
		$this->userManager->expects($this->once())
			->method('searchDisplayName')
			->with('', null, 0)
			->willReturn([$this->getUser('a')]);

		$this->assertEquals(['a'], $this->backend->usersInGroup('everyone', '', null));
	}

	/**
	 * Regression test: ISearchableGroupBackend was not implemented, which made
	 * the server fall back to resolving every uid separately.
	 */
	public function testSearchInGroup(): void {
		$userA = $this->getUser('a');
		$userB = $this->getUser('b');
		$this->userManager->method('searchDisplayName')
			->with('filter', 2, 1)
			->willReturn([$userA, $userB]);

		$this->assertSame(
			['a' => $userA, 'b' => $userB],
			$this->backend->searchInGroup('everyone', 'filter', 2, 1)
		);
	}

	public function testSearchInGroupOtherGroup(): void {
		$this->userManager->expects($this->never())
			->method('searchDisplayName');

		$this->assertEquals([], $this->backend->searchInGroup('bar', 'filter'));
	}

	public function testSearchInGroupZeroLimit(): void {
		$this->userManager->expects($this->never())
			->method('searchDisplayName');

		$this->assertEquals([], $this->backend->searchInGroup('everyone', '', 0));
	}

	public function testGroupsExists(): void {
		$this->assertEquals(['everyone'], $this->backend->groupsExists(['everyone', 'bar']));
	}

	public function testGetGroupsDetails(): void {
		$this->assertEquals([
			'everyone' => ['displayName' => 'Everyone'],
			'bar' => [],
		], $this->backend->getGroupsDetails(['everyone', 'bar']));
	}
}
