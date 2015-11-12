<?php

namespace Yaro\Jarboe\Interfaces;


interface IObserver
{
	public function update(IObservable $observable);
}
