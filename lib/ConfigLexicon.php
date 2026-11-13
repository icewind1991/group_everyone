<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupEveryone;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for group_everyone.
 *
 * {@see ILexicon}
 */
class ConfigLexicon implements ILexicon {
	public const EXCLUDE_GUESTS = 'exclude_guests';
	public const DISPLAY_NAME = 'display_name';

	#[\Override]
	public function getStrictness(): Strictness {
		return Strictness::NOTICE;
	}

	#[\Override]
	public function getAppConfigs(): array {
		return [
			new Entry(self::EXCLUDE_GUESTS, ValueType::BOOL, false, 'exclude guest accounts from the "everyone" group'),
			new Entry(self::DISPLAY_NAME, ValueType::STRING, '', 'custom display name of the "everyone" group, the translated "Everyone" is used when empty'),
		];
	}

	#[\Override]
	public function getUserConfigs(): array {
		return [];
	}
}
