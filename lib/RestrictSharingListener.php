<?php

declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone;

use OCA\GroupEveryone\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\Share\Events\BeforeShareCreatedEvent;
use OCP\Share\IShare;

/**
 * When enabled, only administrators of the instance may create shares
 * with the virtual "everyone" group.
 *
 * @template-implements IEventListener<BeforeShareCreatedEvent>
 */
class RestrictSharingListener implements IEventListener {
	public const CONFIG_RESTRICT_SHARING = 'restrict_sharing_to_admins';

	public function __construct(
		private IAppConfig $appConfig,
		private IGroupManager $groupManager,
		private IL10N $l10n,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeShareCreatedEvent)) {
			return;
		}

		$share = $event->getShare();
		if ($share->getShareType() !== IShare::TYPE_GROUP
			|| $share->getSharedWith() !== GroupBackend::GROUP_ID) {
			return;
		}

		if (!$this->appConfig->getValueBool(Application::APP_ID, self::CONFIG_RESTRICT_SHARING)) {
			return;
		}

		$sharedBy = (string)$share->getSharedBy();
		if ($sharedBy !== '' && $this->groupManager->isAdmin($sharedBy)) {
			return;
		}

		// The share manager only rejects the share when the propagation is stopped
		// as well. Exceptions thrown here would be logged and ignored.
		$event->setError($this->l10n->t('Only administrators can share with the "Everyone" group'));
		$event->stopPropagation();
	}
}
