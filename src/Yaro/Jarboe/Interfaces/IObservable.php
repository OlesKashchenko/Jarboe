<?php

namespace Yaro\Jarboe\Interfaces;


interface IObservable
{
	public function attachObserver(IObserver $observer);
	public function detachObserver(IObserver $observer);
	public function notifyObserver();
}
