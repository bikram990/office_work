<?php
/**
 * Nextcloud - passman
 *
 * @copyright Copyright (c) 2016, Sander Brand (brantje@gmail.com)
 * @copyright Copyright (c) 2016, Marcos Zuriaga Miguel (wolfi@wolfi.es)
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Passman\Service;

use OCP\IConfig;
use OCP\AppFramework\Db\DoesNotExistException;

use OCA\Passman\Db\FileMapper;


class NotificationService {

	private $manager;

	public function __construct() {
		$this->manager = \OC::$server->getNotificationManager();
	}

	function credentialExpiredNotification($credential){
		$urlGenerator = \OC::$server->getURLGenerator();
		$link = $urlGenerator->getAbsoluteURL($urlGenerator->linkTo('','index.php/apps/passman/#/vault/'. $credential->getVaultId() .'/edit/'. $credential->getId()));
		$api = $urlGenerator->getAbsoluteURL($urlGenerator->linkTo('', 'index.php/apps/passman'));
		$notification = $this->manager->createNotification();
		$remindAction = $notification->createAction();
		$remindAction->setLabel('remind')
			->setLink($api. '/api/internal/notifications/remind/'. $credential->getId() , 'POST');

		$declineAction = $notification->createAction();
		$declineAction->setLabel('ignore')
			->setLink($api . '/api/internal/notifications/read/'. $credential->getId(), 'DELETE');

		$notification->setApp('passman')
			->setUser($credential->getUserId())
			->setDateTime(new \DateTime())
			->setObject('credential', $credential->getId()) // Set notification type and id
			->setSubject('credential_expired', [$credential->getLabel()]) // set subject and parameters
			->setLink($link)
			->addAction($declineAction)
			->addAction($remindAction);

		$this->manager->notify($notification);
	}


	function credentialSharedNotification($data){
		$urlGenerator = \OC::$server->getURLGenerator();
		$link = $urlGenerator->getAbsoluteURL($urlGenerator->linkTo('','index.php/apps/passman/#/'));
		$api = $urlGenerator->getAbsoluteURL($urlGenerator->linkTo('', 'index.php/apps/passman'));
		$notification = $this->manager->createNotification();

		$declineAction = $notification->createAction();
		$declineAction->setLabel('decline')
			->setLink($api . '/api/v2/sharing/decline/'. $data['req_id'], 'DELETE');

		$notification->setApp('passman')
			->setUser($data['target_user'])
			->setDateTime(new \DateTime())
			->setObject('passman_share_request', $data['req_id']) // type and id
			->setSubject('credential_shared', [$data['from_user'], $data['credential_label']]) // subject and parameters
			->setLink($link)
			->addAction($declineAction);

		$this->manager->notify($notification);
	}


	function credentialDeclinedSharedNotification($data){
		$notification = $this->manager->createNotification();
		$notification->setApp('passman')
			->setUser($data['target_user'])
			->setDateTime(new \DateTime())
			->setObject('passman_share_request', $data['req_id']) // type and id
			->setSubject('credential_share_denied', [$data['from_user'], $data['credential_label']]); // subject and parameters
		$this->manager->notify($notification);
	}


	function credentialAcceptedSharedNotification($data){
		$notification = $this->manager->createNotification();
		$notification->setApp('passman')
			->setUser($data['target_user'])
			->setDateTime(new \DateTime())
			->setObject('passman_share_request', $data['req_id']) // type and id
			->setSubject('credential_share_accepted', [$data['from_user'], $data['credential_label']]); // subject and parameters
		$this->manager->notify($notification);
	}

}