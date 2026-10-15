<?php

declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone\Tests;

use OC\Collaboration\Collaborators\SearchResult;
use OCA\GroupEveryone\Collaboration\HideEveryonePlugin;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class HideEveryonePluginTest extends TestCase {
	public static function searchProvider(): array {
		return [
			'not restricted' => [false, false, ['everyone', 'staff']],
			'restricted, admin' => [true, true, ['everyone', 'staff']],
			'restricted, user' => [true, false, ['staff']],
		];
	}

	#[DataProvider('searchProvider')]
	public function testSearch(bool $restricted, bool $isAdmin, array $expected): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueBool')
			->with('group_everyone', 'restrict_sharing_to_admins')
			->willReturn($restricted);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('isAdmin')->with('alice')->willReturn($isAdmin);

		$row = fn (string $gid): array => ['label' => $gid, 'value' => ['shareType' => IShare::TYPE_GROUP, 'shareWith' => $gid]];
		$result = new SearchResult();
		$result->addResultSet(new SearchResultType('groups'), [$row('everyone'), $row('staff')], []);

		$plugin = new HideEveryonePlugin($appConfig, $groupManager, $userSession);
		$this->assertFalse($plugin->search('e', 10, 0, $result));

		$groups = $result->asArray();
		$found = array_map(
			fn (array $r): string => $r['value']['shareWith'],
			array_merge($groups['exact']['groups'], $groups['groups']),
		);
		$this->assertEquals($expected, $found);
		// The lists have to stay JSON arrays for the frontend
		$this->assertTrue(array_is_list($groups['groups']));
		$this->assertTrue(array_is_list($groups['exact']['groups']));
	}
}
