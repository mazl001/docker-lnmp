<?php
namespace Onion\Bootstrap;

use ErrorException;
use Onion\Container\Application;

class HandleExceptions {
	
	/**
	 * 自定义错误、异常处理
	*/
	public function bootstrap(Application $application) {

		error_reporting(E_ALL);

		/**
		 * PHP自带的异常处理程序, 已经包含了完善的错误信息、代码文件、调用栈等信息
		 *
		 * 因此, 只有 关闭调试模式 时, 才使用自定义异常处理程序, 否则使用PHP自带的异常处理程序
		 */
		if(!$_ENV['app']['debug']) {

			set_error_handler([$this, 'errorHandler']);

			set_exception_handler([$this, 'exceptionHandler']);
		}
	}


	public function errorHandler($errlevel, $errstr, $errfile, $errline, $errcontext) {
		throw new ErrorException($errstr, 0, $errlevel, $errfile, $errline);
	}

	public function exceptionHandler($exception) {
		die($_ENV['app']['exceptionMessage']);
	}
}