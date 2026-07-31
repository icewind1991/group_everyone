<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone;

use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\ICountDisabledInGroup;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\IGetDisplayNameBackend;
use OCP\Group\Backend\IGroupDetailsBackend;
use OCP\Group\Backend\INamedBackend;
use OCP\Group\Backend\ISearchableGroupBackend;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;

/**
 * Provides a virtual group containing all users on the instance.
 */
class GroupBackend extends ABackend implements
	ICountDisabledInGroup,
	ICountUsersBackend,
	IGetDisplayNameBackend,
	IGroupDetailsBackend,
	INamedBackend,
	ISearchableGroupBackend {
	public const GROUP_ID = 'everyone';

	public function __construct(
		private IUserManager $userManager,
		private IL10N $l10n,
		private string $groupName = self::GROUP_ID,
	) {
	}

	public function inGroup($uid, $gid): bool {
		return $gid === $this->groupName;
	}

	/**
	 * @return list<string>
	 */
	public function getUserGroups($uid): array {
		return [$this->groupName];
	}

	/**
	 * @return list<string>
	 */
	public function getGroups(string $search = '', int $limit = -1, int $offset = 0): array {
		// A single virtual group can only ever be the first result. Like the
		// database backend, a limit of 0 is treated as "no limit" here.
		if ($offset > 0) {
			return [];
		}

		return $this->matchesGroupSearch($search) ? [$this->groupName] : [];
	}

	public function groupExists($gid): bool {
		return $gid === $this->groupName;
	}

	public function countUsersInGroup(string $gid, string $search = ''): int {
		if ($gid !== $this->groupName) {
			return 0;
		}

		if ($search !== '') {
			// There is no "count matching users" API that spans all user backends,
			// so the matches have to be materialised to be counted.
			return count($this->userManager->searchDisplayName($search));
		}

		$count = $this->userManager->countUsersTotal();
		return $count === false ? 0 : $count;
	}

	public function countDisabledInGroup(string $gid): int {
		if ($gid !== $this->groupName) {
			return 0;
		}

		return (int)$this->userManager->countDisabledUsers();
	}

	/**
	 * @return array<string,IUser>
	 */
	public function searchInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array {
		if ($gid !== $this->groupName || $limit === 0) {
			return [];
		}

		// Guard "$limit" which will be used in a SQL Query.
		// At least in MySQL, LIMIT has to be a nonnegative integer
		// (however, 'null' works fine).  Changing the interfaces (and implementations)
		// to default to a valid value should be a TODO upstream.
		$users = $this->userManager->searchDisplayName($search, $limit < 0 ? null : $limit, $offset);

		$result = [];
		foreach ($users as $user) {
			$result[$user->getUID()] = $user;
		}
		return $result;
	}

	/**
	 * @return list<string>
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0): array {
		// A null limit reached this untyped method before and means "no limit"
		$limit = $limit === null ? -1 : (int)$limit;

		return array_keys($this->searchInGroup((string)$gid, (string)$search, $limit, (int)$offset));
	}

	/**
	 * @return array{displayName?: string}
	 */
	public function getGroupDetails(string $gid): array {
		if ($gid !== $this->groupName) {
			return [];
		}

		return ['displayName' => $this->getDisplayName($gid)];
	}

	public function getDisplayName(string $gid): string {
		return $gid === $this->groupName ? $this->l10n->t('Everyone') : '';
	}

	public function getBackendName(): string {
		return 'Everyone';
	}

	/**
	 * Mirrors the group search of the database backend, which matches the group
	 * id as well as the display name case-insensitively.
	 */
	private function matchesGroupSearch(string $search): bool {
		return $search === ''
			|| mb_stripos($this->groupName, $search) !== false
			|| mb_stripos($this->getDisplayName($this->groupName), $search) !== false;
	}
}
