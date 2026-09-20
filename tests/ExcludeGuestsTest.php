<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Git'Fellow <12234510+solracsf@users.noreply.github.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone\Tests;

use OCA\GroupEveryone\Command\ExcludeGuests;
use OCA\GroupEveryone\GroupBackend;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IAppConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class ExcludeGuestsTest extends TestCase {
	public static function optionsProvider(): array {
		return [
			'show' => [false, [], Command::SUCCESS, false, []],
			'on' => [false, ['--on' => true], Command::SUCCESS, true, [UserRemovedEvent::class]],
			'off' => [true, ['--off' => true], Command::SUCCESS, false, [UserAddedEvent::class]],
			'unchanged' => [true, ['--on' => true], Command::SUCCESS, true, []],
			'on and off' => [false, ['--on' => true, '--off' => true], Command::INVALID, false, []],
		];
	}

	#[DataProvider('optionsProvider')]
	public function testExecute(bool $excluded, array $options, int $exitCode, bool $expectedExcluded, array $expectedEvents): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueBool')
			->with('group_everyone', 'exclude_guests')
			->willReturnCallback(function () use (&$excluded): bool {
				return $excluded;
			});
		$appConfig->method('setValueBool')
			->with('group_everyone', 'exclude_guests')
			->willReturnCallback(function (string $app, string $key, bool $value) use (&$excluded): bool {
				$excluded = $value;
				return true;
			});

		$backend = $this->createMock(GroupBackend::class);
		$backend->method('getGuestUids')
			->willReturn(['guest']);
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('get')
			->with('everyone')
			->willReturn($this->createMock(IGroup::class));
		$guest = $this->createMock(IUser::class);
		$userManager = $this->createMock(IUserManager::class);
		$userManager->method('get')
			->with('guest')
			->willReturn($guest);

		$events = [];
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->method('dispatchTyped')
			->willReturnCallback(function (UserAddedEvent|UserRemovedEvent $event) use ($guest, &$events): void {
				$this->assertSame($guest, $event->getUser());
				$events[] = $event::class;
			});

		$tester = new CommandTester(new ExcludeGuests($appConfig, $backend, $groupManager, $userManager, $dispatcher));
		$this->assertEquals($exitCode, $tester->execute($options));
		$this->assertEquals($expectedExcluded, $excluded);
		$this->assertEquals($expectedEvents, $events);
	}
}
