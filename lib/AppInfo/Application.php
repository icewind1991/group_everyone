<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone\AppInfo;

use OCA\GroupEveryone\GroupBackend;
use OCA\GroupEveryone\UserCreatedListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IGroupManager;
use OCP\User\Events\UserCreatedEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'group_everyone';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(UserCreatedEvent::class, UserCreatedListener::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn([$this, 'registerGroupManager']);
	}

	public function registerGroupManager(IGroupManager $groupManager, GroupBackend $backend): void {
		$groupManager->addBackend($backend);
	}
}
