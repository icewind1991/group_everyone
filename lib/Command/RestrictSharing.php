<?php

declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone\Command;

use OCA\GroupEveryone\AppInfo\Application;
use OCA\GroupEveryone\RestrictSharingListener;
use OCP\IAppConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RestrictSharing extends Command {
	public function __construct(
		private IAppConfig $appConfig,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('group_everyone:restrict-sharing')
			->setDescription('Show or change whether only administrators can share with the "everyone" group')
			->addOption('on', null, InputOption::VALUE_NONE, 'only allow administrators to share with the "everyone" group')
			->addOption('off', null, InputOption::VALUE_NONE, 'allow every user to share with the "everyone" group');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$on = (bool)$input->getOption('on');
		$off = (bool)$input->getOption('off');
		if ($on && $off) {
			$output->writeln('<error>--on and --off cannot be used together</error>');
			return self::INVALID;
		}

		$restricted = $this->appConfig->getValueBool(Application::APP_ID, RestrictSharingListener::CONFIG_RESTRICT_SHARING);
		if (($on || $off) && $on !== $restricted) {
			$restricted = $on;
			$this->appConfig->setValueBool(Application::APP_ID, RestrictSharingListener::CONFIG_RESTRICT_SHARING, $restricted);
		}

		$output->writeln('Sharing with the "everyone" group is ' . ($restricted ? 'restricted to administrators' : 'allowed for every user'));
		return self::SUCCESS;
	}
}
