<?php

declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone\Tests;

use OCA\GroupEveryone\RestrictSharingListener;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\Share\Events\BeforeShareCreatedEvent;
use OCP\Share\IShare;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class RestrictSharingListenerTest extends TestCase {
	public static function shareProvider(): array {
		return [
			'disabled, user' => [false, IShare::TYPE_GROUP, 'everyone', false, false],
			'enabled, admin' => [true, IShare::TYPE_GROUP, 'everyone', true, false],
			'enabled, user' => [true, IShare::TYPE_GROUP, 'everyone', false, true],
			'enabled, other group' => [true, IShare::TYPE_GROUP, 'staff', false, false],
			'enabled, user share' => [true, IShare::TYPE_USER, 'everyone', false, false],
		];
	}

	#[DataProvider('shareProvider')]
	public function testHandle(bool $restricted, int $shareType, string $sharedWith, bool $isAdmin, bool $expectError): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueBool')
			->with('group_everyone', 'restrict_sharing_to_admins')
			->willReturn($restricted);
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('isAdmin')
			->with('alice')
			->willReturn($isAdmin);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn($shareType);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getSharedBy')->willReturn('alice');

		$event = new BeforeShareCreatedEvent($share);
		(new RestrictSharingListener($appConfig, $groupManager, $l10n))->handle($event);

		$this->assertSame($expectError, $event->getError() !== null);
		$this->assertSame($expectError, $event->isPropagationStopped());
	}
}
