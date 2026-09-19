<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Git'Fellow <12234510+solracsf@users.noreply.github.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Guests;

use OCP\User\Backend\ABackend;
use OCP\User\Backend\ICountUsersBackend;

abstract class UserBackend extends ABackend implements ICountUsersBackend {
}
