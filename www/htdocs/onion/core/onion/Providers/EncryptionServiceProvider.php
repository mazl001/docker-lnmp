<?php
namespace Onion\Providers;

use Onion\Support\Encrypter;


/**
 * 服务提供者: 加密、解密
 */
class EncryptionServiceProvider extends ServiceProvider {
	
	public function register() {
		$this->application->singleton('encrypter', function() {
			
			$appKey = $this->application->config->get('app.appKey');
			
			return new Encrypter($appKey);
		
		});
	}

	public function boot() {

	}
}