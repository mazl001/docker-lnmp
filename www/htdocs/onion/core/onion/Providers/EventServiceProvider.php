<?php
namespace Onion\Providers;

use Onion\Container\Application;
use Onion\Events\Dispatcher;


/**
 * 服务提供者: 事件
 */
class EventServiceProvider extends ServiceProvider {

	public function register() {
		
		$this->application->singleton('event', function(Application $application) {
			return new Dispatcher($application);
		});
	}

	public function boot() {
		$config = Config::get('event');

		if (!empty($config['observers'])) {
			foreach ($config['observers'] as $event => $observers) {
				foreach ($observers as $observer) {
					Event::attach($event, $observer);
				}
			}
		}

		if (!empty($config['subscribers'])) {
			foreach ($config['subscribers'] as $subscriber) {
				Event::subscribe($subscriber);
			}
		}
	}
}
