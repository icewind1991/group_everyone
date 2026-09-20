<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Git'Fellow <12234510+solracsf@users.noreply.github.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone\Command;

use OCA\GroupEveryone\AppInfo\Application;
use OCA\GroupEveryone\GroupBackend;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExcludeGuests extends Command {
	public function __construct(
		private IAppConfig $appConfig,
		private GroupBackend $backend,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private IEventDispatcher $dispatcher,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('group_everyone:exclude-guests')
			->setDescription('Show or change whether guest accounts are excluded from the "everyone" group')
			->addOption('on', null, InputOption::VALUE_NONE, 'exclude guest accounts from the "everyone" group')
			->addOption('off', null, InputOption::VALUE_NONE, 'include guest accounts in the "everyone" group');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$on = (bool)$input->getOption('on');
		$off = (bool)$input->getOption('off');
		if ($on && $off) {
			$output->writeln('<error>--on and --off cannot be used together</error>');
			return self::INVALID;
		}

		$excluded = $this->appConfig->getValueBool(Application::APP_ID, GroupBackend::CONFIG_EXCLUDE_GUESTS);
		if (($on || $off) && $on !== $excluded) {
			$excluded = $on;
			$this->appConfig->setValueBool(Application::APP_ID, GroupBackend::CONFIG_EXCLUDE_GUESTS, $excluded);
			$group = $this->groupManager->get(GroupBackend::GROUP_ID);
			foreach ($this->backend->getGuestUids() as $uid) {
				$user = $this->userManager->get($uid);
				$this->dispatcher->dispatchTyped($excluded ? new UserRemovedEvent($group, $user) : new UserAddedEvent($group, $user));
			}
		}

		$output->writeln('Guest accounts are ' . ($excluded ? 'excluded from' : 'included in') . ' the "everyone" group');
		return self::SUCCESS;
	}
}
