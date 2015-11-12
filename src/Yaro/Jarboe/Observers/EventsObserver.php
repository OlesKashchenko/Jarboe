<?php

namespace Yaro\Jarboe\Observers;


use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Yaro\Jarboe\Interfaces\IObserver;
use Yaro\Jarboe\Interfaces\IObservable;


class EventsObserver implements IObserver
{

	public function update(IObservable $observable)
	{
		$event = $observable->getEvent();
		$event->save();

		if (\File::exists(\Config::get('jarboe::log.file_path'))) {
			$viewLog = new Logger('View Logs');
			$viewLog->pushHandler(new StreamHandler(\Config::get('jarboe::log.file_path')));
			$viewLog->addInfo(
				'User id: '. $observable->getEvent()->getUserId() .'. '.
				'Ip: '. $observable->getEvent()->getIp() .'. '.
				'Action: '. $observable->getEvent()->getAction() .'. '.
				'Entity table: '. $observable->getEvent()->getEntityTable() .'. '.
				'Entity id: '. $observable->getEvent()->getEntityId() .'. '
			);
		}
	}
}
