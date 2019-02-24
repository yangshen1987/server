<?php
/**
 * @copyright Copyright (c) 2019, Thomas Citharel
 *
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
namespace OCA\DAV\Tests\unit\CalDAV\Reminder;

use OCA\DAV\CalDAV\Reminder\NotificationProviderManager;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\EmailProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\PushProvider;
use Test\TestCase;

class NotificationProviderManagerTest extends TestCase {

    /** @var EmailProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailProvider;

    /** @var PushProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $pushProvider;

    public function setUp() {
		parent::setUp();

        $this->emailProvider = $this->createMock(EmailProvider::class);
        $this->pushProvider = $this->createMock(PushProvider::class);
        $this->providerManager = new NotificationProviderManager($this->emailProvider, $this->pushProvider);
    }

    /**
     * @expectedException OCA\DAV\CalDAV\Reminder\NotificationTypeDoesNotExistException
     * @expectedExceptionMessage Type NOT EXISTENT is not an accepted type of notification
     */
    public function testGetProviderForUnknownType(): void
    {
        $this->providerManager->getProvider('NOT EXISTENT');
    }

    /**
     * @expectedException OCA\DAV\CalDAV\Reminder\NotificationProvider\ProviderNotAvailableException
     * @expectedExceptionMessage No notification provider for type AUDIO available
     */
    public function testGetProviderForUnRegisteredType(): void
    {
        $this->providerManager->getProvider('AUDIO');
    }

    public function testGetProvider(): void
    {
        $provider = $this->providerManager->getProvider('EMAIL');
        $this->assertInstanceOf(EmailProvider::class, $provider);
    }
}