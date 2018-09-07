<?php

namespace fastphp;

//框架根目录
defined('CORE_PATH') or define('CORE_PATH',__DIR__);

/**
*fastphp核心
 */

class fastphp
{
    //配置内容
    protected $config = [];

    public function __construct($config)
    {
        $this->config = $config;
    }

    //运行程序
    public function run()
    {
        spl_autoload_register(array($this,'loadClass'));
        $this->setReporting();
        $this->removeMagicQuotes();
        $this->unregisterGlobals();
        $this->setDbConfig();
        $this->route();
    }

    public function loadClass($className)
    {
        $classMap = $this->classMap();

        if(isset($classMap[$className])){
            $file = $classMap[$className];
        }elseif (strpos($className, '\\') !== false) {
            // 包含应用（app目录）文件
            $file = APP_PATH . str_replace('\\', '/', $className) . '.php';
            if (!is_file($file)) {
                return;
            }
        }else{

            set_exception_handler(array($this,'exceptionHandler'));
            return ;
        }

        include $file;

    }

    //自定义异常
    public function exceptionHandler($exception)
    {
        echo "Uncaught exception: " , $exception->getMessage(), "\n";
    }

    //内核文件命名空间映射
    public function classMap()
    {
        return [
            'fastphp\base\Controller' => CORE_PATH . '/base/Controller.php',
            'fastphp\base\Model' => CORE_PATH . '/base/Model.php',
            'fastphp\base\View' => CORE_PATH . '/base/View.php',
            'fastphp\db\Db' => CORE_PATH . '/db/Db.php',
            'fastphp\db\Sql' => CORE_PATH . '/db/Sql.php',
        ];
    }

    public function setDbConfig()
    {
        if($this->config['db']){
            define('DB_HOST', $this->config['db']['host']);
            define('DB_USER', $this->config['db']['username']);
            define('DB_NAME', $this->config['db']['dbname']);
            define('DB_PASS', $this->config['db']['password']);
        }

    }

    // PHP 4.2.0 版开始配置文件中 register_globals 的默认值从 on 改为 off
    // 检测自定义全局变量并移除。因为 register_globals 已经弃用，如果
    // 已经弃用的 register_globals 指令被设置为 on，那么局部变量也将
    // 在脚本的全局作用域中可用。 例如， $_POST['foo'] 也将以 $foo 的
    // 形式存在，这样写是不好的实现，会影响代码中的其他变量。 相关信息，
    // 参考: http://php.net/manual/zh/faq.using.php#faq.register-globals
    public function unregisterGlobals()
    {
        if (ini_get('register_globals')) {
            $array = array('_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
            foreach ($array as $value) {
                foreach ($GLOBALS[$value] as $key => $var) {
                    if ($var === $GLOBALS[$key]) {
                        unset($GLOBALS[$key]);
                    }
                }
            }
        }
    }

    // 检测敏感字符并删除,PHP 5.4.O 起将始终返回 FALSE 因为这个魔术引号功能已经从 PHP 中移除了
    public function removeMagicQuotes()
    {
        if (get_magic_quotes_gpc()) {
            $_GET = isset($_GET) ? $this->stripSlashesDeep($_GET ) : '';
            $_POST = isset($_POST) ? $this->stripSlashesDeep($_POST ) : '';
            $_COOKIE = isset($_COOKIE) ? $this->stripSlashesDeep($_COOKIE) : '';
            $_SESSION = isset($_SESSION) ? $this->stripSlashesDeep($_SESSION) : '';
        }
    }

    // 删除敏感字符
    public function stripSlashesDeep($value)
    {
        $value = is_array($value) ? array_map(array($this, 'stripSlashesDeep'), $value) : stripslashes($value);
        return $value;
    }

    public function setReporting()
    {
        if(APP_DEBUG === TRUE){
            error_reporting(E_ALL);
            ini_set('dispaly_errors', 'On');
        }else{
            error_reporting(E_ALL);
            ini_set('dispaly_errors','Off');
            ini_set('log_errors','Off');
        }
    }

    public function route()
    {
        $controllerName = $this->config['defaultController'];
        $actionName = $this->config['defaultAction'];
        $param = [];

        $url = $_SERVER['REQUEST_URI'];

        //清除？之后的内容
        $postion = strpos($url,'?');
        $url = $postion === false ? $url : substr($url,0,$postion);

        //删除前后/
        $url = trim($url, '/');

        if($url){

            $urlArray = explode('/',$url);

            //删除重复元素
            $urlArray = array_filter($urlArray);
            $controllerName = ucwords($urlArray[0]);
            array_shift($urlArray);
            $actionName = $urlArray ? $urlArray[0] : $actionName;
            array_shift($urlArray);
            $param = $urlArray ? $urlArray : [];

        }

        $controller = $this->config['applacationName'].'\\controllers\\'.$controllerName.'Controller';
        if(!class_exists($controller)){
            exit($controller.' is not exist!');
        }

        if(!method_exists($controller,$actionName)){
            exit($actionName.' is not exist!');
        }

        // 如果控制器和方法名存在，则实例化控制器，因为控制器对象里面
        // 还会用到控制器名和方法名，所以实例化的时候把他们俩的名称也
        // 传进去。结合Controller基类一起看
        $dispatch = new $controller($controllerName, $actionName);

        // $dispatch保存控制器实例化后的对象，我们就可以调用它的方法，
        // 也可以像方法中传入参数，以下等同于：$dispatch->$actionName($param)
        call_user_func_array(array($dispatch, $actionName), $param);

    }

}



















