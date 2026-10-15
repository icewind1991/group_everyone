<?php

declare(strict_types=1);

/**
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone\Collaboration;

use OCA\GroupEveryone\AppInfo\Application;
use OCA\GroupEveryone\GroupBackend;
use OCA\GroupEveryone\RestrictSharingListener;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserSession;

/**
 * Removes the "everyone" group from the share recipient suggestions of
 * non-admin users while sharing with it is restricted to administrators.
 *
 * It runs after the core group plugin, which is always registered first.
 * IHideFromCollaborationBackend is deliberately not used: it would also hide
 * the group from CalDAV/CardDAV, which makes calendars and address books shared
 * with it inaccessible.
 */
class HideEveryonePlugin implements ISearchPlugin {
	public function __construct(
		private IAppConfig $appConfig,
		private IGroupManager $groupManager,
		private IUserSession $userSession,
	) {
	}

	public function search($search, $limit, $offset, ISearchResult $searchResult): bool {
		if (!$this->appConfig->getValueBool(Application::APP_ID, RestrictSharingListener::CONFIG_RESTRICT_SHARING)) {
			return false;
		}

		$user = $this->userSession->getUser();
		if ($user !== null && $this->groupManager->isAdmin($user->getUID())) {
			return false;
		}

		$type = new SearchResultType('groups');
		$results = $searchResult->asArray();
		$wide = $results[$type->getLabel()] ?? [];
		$exact = $results['exact'][$type->getLabel()] ?? [];
		$filteredWide = $this->withoutEveryone($wide);
		$filteredExact = $this->withoutEveryone($exact);
		if (count($filteredWide) === count($wide) && count($filteredExact) === count($exact)) {
			return false;
		}

		// ISearchResult::removeCollaboratorResult() leaves gaps in the lists, which
		// would then be serialised as JSON objects instead of arrays.
		$searchResult->unsetResult($type);
		$searchResult->addResultSet($type, $filteredWide, $filteredExact);
		return false;
	}

	private function withoutEveryone(array $rows): array {
		return array_values(array_filter(
			$rows,
			fn (array $row): bool => ($row['value']['shareWith'] ?? null) !== GroupBackend::GROUP_ID,
		));
	}
}
