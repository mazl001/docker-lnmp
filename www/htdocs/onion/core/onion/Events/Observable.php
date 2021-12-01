<?php
namespace Onion\Events;


interface Observable {
	public function attach(string $event, $observer, $method = 'handle');
	public function notify(string $event, array $parameters = []);
	public function subscribe(string $subscriber);
};