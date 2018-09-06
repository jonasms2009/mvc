<?php
namespace fastphp\db;

use PDO;
use PDOException;

//简单的单例模式，确保运行期内只有一个pdo实例
class Db
{
    private static $pdo;

    private function __construct()
    {
    }

    private function __clone()
    {
    }


    public static function pdo()
    {
        if(self::$pdo instanceof self){
            return self::$pdo;
        }

        try{
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8',DB_HOST,DB_NAME);
            $option = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);

            return self::$pdo = new PDO($dsn,DB_USER,DB_PASS,$option);
        }catch (PDOException $exception){
            exit($exception->getMessage());
        }
    }

}