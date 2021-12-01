<?php
/*
|--------------------------------------------------------------------------
| 自定义函数
|--------------------------------------------------------------------------
*/

use Onion\Http\Response;
use Onion\Container\Application;

/*
|--------------------------------------------------------------------------
| 获取应用实例
|--------------------------------------------------------------------------
*/
function app($abstract = null, $parameters = []) {
    if (is_null($abstract)) {
        return Application::getInstance();
    }

    return Application::getInstance()->make($abstract, $parameters);
}


/*
|--------------------------------------------------------------------------
| 获取项目根目录
|--------------------------------------------------------------------------
*/
function root_path($path = null) {
	return app()->getRootPath($path);
}


/*
|--------------------------------------------------------------------------
| 获取应用存储路径
|--------------------------------------------------------------------------
*/
function storage_path($path = null) {
	return app()->getStoragePath($path);
}


/*
|--------------------------------------------------------------------------
| 获取视图
|--------------------------------------------------------------------------
*/
function view($path, $data = []) {
        
    $blade  = app('view');

    $_token = app('session')->get('_token');

    $data   = array_merge(['_token' => $_token], $data);

    return $blade->make($path, $data)->render(); 
}


/*
|--------------------------------------------------------------------------
| 根据路由名称生成 URL 
|--------------------------------------------------------------------------
| @param string $name           路由名称
| @param array  $parameters     路由参数
*/
function route($name, array $parameters = []) {

    $route  = app('router')->findRouteByName($name);

    $url    = '/'. trim($route->getUri(), '/');

    $searches   = array_map(function($value) {
        return '/\{'.$value.'\??\}/';
    }, array_keys($parameters));

    $replaces   = array_values($parameters);

    //用于删除未赋值的路由可选参数
    $searches[] = '/\/\{[\w]+\?\}/';
    $replaces[] = '';

    return preg_replace($searches, $replaces, $url);
}


/*
|--------------------------------------------------------------------------
| 重定向
|--------------------------------------------------------------------------
| @param string $name           路由名称
| @param array  $parameters     路由参数
*/
function redirect($name, array $parameters = []) {
    return new Response('', 302, ['location' => route($name, $parameters)]);
}


/*
|--------------------------------------------------------------------------
| 生成随机字符串(字母+数字)
|--------------------------------------------------------------------------
*/
function _randomString($length = 16) {
    $string = '';

    while (($len = strlen($string)) < $length) {
        $size = $length - $len;

        $bytes = random_bytes($size);

        $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
    }

    return $string;
}


/*
|--------------------------------------------------------------------------
| 生成Token sha1算法
|--------------------------------------------------------------------------
*/
function _generateToken() {
    return sha1(uniqid('', true)._randomString(25).microtime(true));
}


/*
|--------------------------------------------------------------------------
| 判断Token是否有效
|--------------------------------------------------------------------------
*/
function _isValidToken($id) {
    return is_string($id) && preg_match('/^[a-f0-9]{40}$/', $id);
}


/*
|--------------------------------------------------------------------------
| 递归遍历目录下的文件
|--------------------------------------------------------------------------
*/
function _scanDir($dir, $ext = 'php') {
    $result = [];
    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
        $path = $dir.DIRECTORY_SEPARATOR.$file;

        if (is_dir($path)) {
            $result = array_merge($result, _scanDir($path));
        }

        if (pathinfo($file, PATHINFO_EXTENSION) == $ext) {
            $result[] = $path;
        }
    }

    return $result;
}


/*
|--------------------------------------------------------------------------
| 构造SQL查询 and 条件数组
|--------------------------------------------------------------------------
| _and(['id' => 1, ['status', '!=', 1]])
|
*/
function _and($conditions) {
    $conditions['_logic'] = 'and';
    return $conditions;
}

/*
|--------------------------------------------------------------------------
| 构造SQL查询 or 条件数组
|--------------------------------------------------------------------------
| _or(['id' => 1, ['status', '!=', 1]])
*/
function _or($conditions) {
    $conditions['_logic'] = 'or';
    return $conditions;
}

/*
|--------------------------------------------------------------------------
| 判断是否一维数组
|--------------------------------------------------------------------------
*/
function _isOneDimensionalArray(array $array) {
    return count($array, COUNT_NORMAL) === count($array, COUNT_RECURSIVE);
}