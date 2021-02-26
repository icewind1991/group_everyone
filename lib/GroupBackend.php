<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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

use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\ICountUsersBackend;
use OCP\Group\Backend\IGroupDetailsBackend;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;

/**
 * Provides a virtual group containing all users on the instance.
 */
class GroupBackend extends ABackend implements ICountUsersBackend, IGroupDetailsBackend {
	/** @var IUserManager */
	private $userManager;

	/** @var string */
	private $groupName;

	/** @var IL10N */
	private $l10n;


	public function __construct(IUserManager $userManager, IL10N $l10n, $groupName = 'everyone') {
		$this->groupName = $groupName;
		$this->userManager = $userManager;
		$this->l10n = $l10n;
	}

	public function inGroup($uid, $gid) {
		return $gid === $this->groupName;
	}

	public function getUserGroups($uid) {
		return [$this->groupName];
	}

	public function getGroups($search = '', $limit = -1, $offset = 0) {
		// Guard "$limit" which will be used in a SQL Query.
		// At least in MySQL, LIMIT has to be a nonnegative integer
		// (however, 'null' works fine).  Changing the interfaces (and implementations)
		// to default to a valid value should be a TODO upstream.
		$limit = ($limit < 0) ? null : $limit;

		return ($offset === 0 || $offset === null) ? [$this->groupName] : [];
	}

	public function groupExists($gid) {
		return $gid === $this->groupName;
	}

	public function countUsersInGroup(string $gid, string $search = ''): int {
		if ($gid === $this->groupName) {
			return (int)array_sum($this->userManager->countUsers());
		} else {
			return 0;
		}
	}

	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		// Guard "$limit" which will be used in a SQL Query.
		// At least in MySQL, LIMIT has to be a nonnegative integer
		// (however, 'null' works fine).  Changing the interfaces (and implementations)
		// to default to a valid value should be a TODO upstream.
		$limit = ($limit < 0) ? null : $limit;

		if ($gid === $this->groupName) {
			$users = $this->userManager->search($search, $limit, $offset);
			return array_map(function (IUser $user) {
				return $user->getUID();
			}, $users);
		} else {
			return [];
		}
	}

	public function getGroupDetails(string $gid): array {
		if ($gid === $this->groupName) {
			return ['displayName' => $this->l10n->t('Everyone')];
		} else {
			return [];
		}
	}
}
