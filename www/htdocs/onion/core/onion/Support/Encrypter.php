<?php
namespace Onion\Support;

/**
 * 加密器
 * @see https://www.php.net/manual/zh/function.openssl-encrypt.php
 */
class Encrypter {

	/**
	 * 密钥
	 */
	protected $passphrase;

	/**
	 * 密码学方式
	 */
	protected $cipherAlgo = 'AES-128-ECB';


	/**
	 * 构造函数
	 */
	public function __construct($passphrase) {
		$this->passphrase = $passphrase;
	}

	/**
	 * 加密
	 */
	public function encrypt($value) {
		return openssl_encrypt($value, $this->cipherAlgo, $this->passphrase);
	}

	/**
	 * 解密
	 */
	public function decrypt($value) {
		return openssl_decrypt($value, $this->cipherAlgo, $this->passphrase);
	}
}