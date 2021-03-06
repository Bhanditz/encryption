<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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

namespace OCA\Encryption\Tests\Users;

use OCA\Encryption\Users\Setup;
use Test\TestCase;

class SetupTest extends TestCase {
	/**
	 * @var \OCA\Encryption\KeyManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $keyManagerMock;
	/**
	 * @var \OCA\Encryption\Crypto\Crypt|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $cryptMock;
	/**
	 * @var Setup
	 */
	private $instance;

	protected function setUp() {
		parent::setUp();
		$logMock = $this->createMock('OCP\ILogger');
		$userSessionMock = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$this->cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->disableOriginalConstructor()
			->getMock();

		$this->keyManagerMock = $this->getMockBuilder('OCA\Encryption\KeyManager')
			->disableOriginalConstructor()
			->getMock();

		/** @var \OCP\ILogger $logMock */
		/** @var \OCP\IUserSession $userSessionMock */
		$this->instance = new Setup($logMock,
			$userSessionMock,
			$this->cryptMock,
			$this->keyManagerMock);
	}

	public function testSetupSystem() {
		$this->keyManagerMock->expects($this->once())->method('validateShareKey');
		$this->keyManagerMock->expects($this->once())->method('validateMasterKey');

		$this->instance->setupSystem();
	}

	/**
	 * @dataProvider dataTestSetupUser
	 *
	 * @param bool $hasKeys
	 * @param bool $expected
	 */
	public function testSetupUser($hasKeys, $expected) {
		$this->keyManagerMock->expects($this->once())->method('userHasKeys')
			->with('uid')->willReturn($hasKeys);

		if ($hasKeys) {
			$this->keyManagerMock->expects($this->never())->method('storeKeyPair');
		} else {
			$this->cryptMock->expects($this->once())->method('createKeyPair')->willReturn('keyPair');
			$this->keyManagerMock->expects($this->once())->method('storeKeyPair')
				->with('uid', 'password', 'keyPair')->willReturn(true);
		}

		$this->assertSame($expected,
			$this->instance->setupUser('uid', 'password')
		);
	}

	public function dataTestSetupUser() {
		return [
			[true, true],
			[false, true]
		];
	}
}
