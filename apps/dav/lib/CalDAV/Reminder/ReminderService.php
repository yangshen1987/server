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

use OC\User\NoUserException;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use Sabre\VObject;
use Sabre\VObject\Component\VAlarm;
use Sabre\VObject\Reader;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\EmailProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\PushProvider;

class ReminderService {

    /** @var Backend */
    private $backend;
    
    /** @var NotificationProviderManager */
    private $notificationProviderManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserSession */
	private $userSession;
    
    public const REMINDER_TYPE_EMAIL = 'EMAIL';
	public const REMINDER_TYPE_DISPLAY = 'DISPLAY';
	public const REMINDER_TYPE_AUDIO = 'AUDIO';

    public const REMINDER_TYPES = [self::REMINDER_TYPE_EMAIL, self::REMINDER_TYPE_DISPLAY, self::REMINDER_TYPE_AUDIO];

    public function __construct(Backend $backend,
                                NotificationProviderManager $notificationProviderManager,
								IUserManager $userManager,
								IGroupManager $groupManager,
								IUserSession $userSession) {
        $this->backend = $backend;
        $this->notificationProviderManager = $notificationProviderManager;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
    }

	/**
	 * Process reminders to activate
	 *
	 * @throws NoUserException
	 * @throws NotificationProvider\ProviderDoesNotExistException
	 */
    public function processReminders(): void
    {
        $reminders = $this->backend->getRemindersToProcess();

		error_log('reminder background job run');
		foreach ($reminders as $reminder) {
			error_log('running reminder');
			$calendarData = Reader::read($reminder['calendardata']);

			$user = $this->userManager->get($reminder['uid']);

            if ($user === null) {
				throw new NoUserException('User not found for calendar');
			}

			$notificationProvider = $this->notificationProviderManager->getProvider($reminder['type']);
			$notificationProvider->send($calendarData, $user);
			$this->backend->removeReminder($reminder['id']);
		}
    }

	/**
	 * Saves reminders when a calendar object with some alarms was created/updated/deleted
	 *
	 * @param string $action
	 * @param array $calendarData
	 * @param array $shares
	 * @param array $objectData
	 * @return void
	 * @throws VObject\InvalidDataException
	 * @throws NoUserException
	 */
	public function onTouchCalendarObject($action, array $calendarData, array $shares, array $objectData): void
	{
		if (!isset($calendarData['principaluri'])) {
			return;
		}
		error_log('ontouchCalendarObject');

		// Always remove existing reminders for this event
		$this->backend->cleanRemindersForEvent($objectData['calendarid'], $objectData['uri']);

		/**
		 * If we are deleting the event, no need to go further
		 */
		if ($action === '\OCA\DAV\CalDAV\CalDavBackend::deleteCalendarObject') {
			return;
		}

		$users = $this->getUsersForShares($shares);

		$user = $this->userSession->getUser();

		if ($user === null) {
			throw new NoUserException('No user in session');
		}

		$users[] = $user->getUID();

		$vobject = VObject\Reader::read($objectData['calendardata']);

		foreach ($vobject->VEVENT->VALARM as $alarm) {
			if ($alarm instanceof VAlarm) {
				error_log('found an alarm');
				$type = strtoupper($alarm->ACTION->getValue());
				if (in_array($type, self::REMINDER_TYPES, true)) {
					error_log('right alarm type');
					$time = $alarm->getEffectiveTriggerTime();

					foreach ($users as $uid) {
						$this->backend->insertReminder(
							$uid,
							$objectData['calendarid'],
							$objectData['uri'],
							$type,
							$time->getTimestamp(),
							$vobject->VEVENT->DTSTART->getDateTime()->getTimestamp());

					}
				}
			}
		}
	}


	/**
	 * Get all users that have access to a given calendar
	 *
	 * @param array $shares
	 * @return string[]
	 */
	private function getUsersForShares(array $shares): array
	{
		$users = $groups = [];
		foreach ($shares as $share) {
			$principal = explode('/', $share['{http://owncloud.org/ns}principal']);
			if ($principal[1] === 'users') {
				$users[] = $principal[2];
			} else if ($principal[1] === 'groups') {
				$groups[] = $principal[2];
			}
		}

		if (!empty($groups)) {
			foreach ($groups as $gid) {
				$group = $this->groupManager->get($gid);
				if ($group instanceof IGroup) {
					foreach ($group->getUsers() as $user) {
						$users[] = $user->getUID();
					}
				}
			}
		}

		return array_unique($users);
	}
}