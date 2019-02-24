<?php
/**
 * @author Thomas Citharel <tcit@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CalDAV\Reminder;

use OCA\DAV\CalDAV\Reminder\NotificationProvider\EmailProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\PushProvider;
use OCA\DAV\CalDAV\Reminder\NotificationTypeDoesNotExistException;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\ProviderNotAvailableException;

class NotificationProviderManager {

    /** @var array */
    private $providers = [];

    public function __construct(EmailProvider $emailProvider, PushProvider $pushProvider)
    {
        $this->providers[$emailProvider::NOTIFICATION_TYPE] = $emailProvider;
        $this->providers[$pushProvider::NOTIFICATION_TYPE] = $pushProvider;
    }

    /**
     * @var string $type
     * @return AbstractNotificationProvider
     * @throws ProviderNotAvailableException
     * @throws NotificationTypeDoesNotExistException
     */
    public function getProvider(string $type): AbstractNotificationProvider
    {
        if (in_array($type, ReminderService::REMINDER_TYPES, true)) {
            if (isset($this->providers[$type])) {
                return $this->providers[$type];
            }
            throw new ProviderNotAvailableException($type);
        }
        throw new NotificationTypeDoesNotExistException($type);
    }
}