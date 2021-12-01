<?php
namespace Onion\Container;

use Onion\Http\Request;
use Onion\Http\Kernel;
use Onion\Providers\ServiceProvider;
use Onion\Routing\Router;
use Onion\Support\ConfigurationRepository;


class Application extends Container {

    /**
     * 项目根目录 
     */
	protected $rootPath;

    /**
     * 应用是否初始化
     */
    protected $appHasBeenBootstrapped = false;

    /**
     * 系统服务提供者是否初始化
     */
    protected $serviceProviderHasBeenBootstrapped = false;

    /**
     * 已注册的系统服务提供者
     */
    protected $registeredServiceProviders = [];


    /**
     * 应用引导程序
     */
    protected $bootstrappers = [
        \Onion\Bootstrap\DetectEnvironment::class,
        \Onion\Bootstrap\LoadConfiguration::class,
        \Onion\Bootstrap\HandleExceptions::class,
        \Onion\Bootstrap\RegisterFacades::class,
        \Onion\Bootstrap\RegisterProviders::class,
        \Onion\Bootstrap\BootProviders::class
    ];


    /**
     * 别名
     * 数组结构：完整类名 => (在容器中绑定时使用的) 简略抽象名称
     */
    protected $aliases = [
        \Onion\Container\Application::class             => 'app',
        \Onion\Container\Container::class               => 'app',
        \Onion\Cookie\CookieManager::class              => 'cookie',
        \Onion\Support\ConfigurationRepository::class   => 'config',
        \Onion\Database\DatabaseManager::class          => 'db',
        \Onion\Events\Dispatcher::class                 => 'event',
        \Onion\Support\Encrypter::class                 => 'encrypter',
        \Onion\Http\Kernel::class                       => 'kernel',
        \Onion\Http\Request::class                      => 'request',
        \Onion\Database\Redis::class                    => 'redis',
        \Onion\Routing\Router::class                    => 'router',
        \Onion\Session\SessionManager::class            => 'session'
    ];

    /**
     * 构造方法
     */
	public function __construct($rootPath) {
        $this->rootPath = $rootPath;

        static::$instance = $this;
		$this->instance('app', $this);

        $this->registerBaseBindings();
	}

    /**
     * 注册核心服务, 其他非核心功能在service provider中注册
     */
    public function registerBaseBindings() {
        $coreBindings = [
            'kernel'    =>  Kernel::class,
            'router'    =>  Router::class,
            'config'    =>  ConfigurationRepository::class,
            'request'   =>  function() { return Request::capture(); },
        ];

        foreach($coreBindings as $abstract => $concrete) {
            $this->singleton($abstract, $concrete);
        }
    }

    /**
     * 初始化应用(供Onion\Http\Kernel->handle方法调用)
     * 1、加载.env.json文件，设置环境变量
     * 2、从config目录加载配置项
     * 3、自定义错误、异常处理
     * 4、门面Facade设置类别名、Application实例
     * 5、运行服务提供者注册方法
     * 6、运行服务提供者启动方法
     */    
    public function bootstrap() {
        if ($this->appHasBeenBootstrapped) return;

        $this->appHasBeenBootstrapped = true;

        foreach($this->bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap($this);
        }
    }


    /**
     * 注册服务
     */
    public function registerServiceProvider(ServiceProvider $provider) {

        $className = get_class($provider);

        if (!isset($this->registeredServiceProviders[$className])) {

            $provider->register();
            
            $this->registeredServiceProviders[$className] = $provider;
        }
    }


    /**
     * 启动服务
     */
    public function bootServiceProvider() {
        if ($this->serviceProviderHasBeenBootstrapped) return;

        $this->serviceProviderHasBeenBootstrapped = true;

        foreach($this->registeredServiceProviders as $serviceProvider) {
            if (method_exists($serviceProvider, 'boot')) {
                $this->invokeMethod($serviceProvider, 'boot');
            }
        }
    }


    /**
     * 获取根目录
     */
    public function getRootPath($path = null) {
        return $path ? $this->rootPath.DIRECTORY_SEPARATOR.$path : $this->rootPath;
    }

    /**
     * 获取应用程序根目录
     */
    public function getAppPath($path = null) {
        return $this->getRootPath('app'.DIRECTORY_SEPARATOR.$path);
    }

    /**
     * 获取配置文件目录
     */
    public function getConfigFilePath() {
        return $this->getRootPath('config');
    }
    
    /**
     * 获取存储目录
     */    
    public function getStoragePath($path = null) {
        return $this->getRootPath('storage'.DIRECTORY_SEPARATOR.$path);
    }

    /**
     * 获取环境变量文件路径
     */
     public function getEnvironmentFilePath() {
        return $this->rootPath.DIRECTORY_SEPARATOR.'.env.json';
     }
}