<?php
/**
 * Created by PhpStorm.
 * User: ziyun
 * Date: 2017/6/28
 * Time: 16:55
 */

namespace ziyun\model;

use PDO;
use PDOException;

class Base
{
    private static $pdo = null ; // 保存pdo对象 默认为空时进行数据库连接 不为空值不用连接数据库
    private $table;  // 私有属性 用于保存传递过来的表的名称
    private $where = '';

    /**
     * 构造方法
     * 主要用于连接数据库
     * Base constructor.
     * @param $config
     * @param $table
     */
    public function __construct($config,$table)
    {
        // 接收数据库的配置信息 用于连接数据库
        // 调用connect函数连接数据库
        $this->connect($config);

        // 接收数据库需要操作的表的名称 方便函数调用
        $this->table = $table ;

    }

    /**
     * 连接数据库方法
     * @param $config array 连接数据库配置项
     */
    private function connect($config){
        // 如果类静态属性中以保存$pdo即已经连接过数据库 ，就可以不需要连接数据库了
        // 减少系统开销
        if(!is_null(self::$pdo)) return;
        try{
            // 配置连接数据库的主机地址 和数据库地址 通过读取数据库中的配置文件
            $dsn = "mysql:host=".$config['db_host'] . ";dbname=" . $config['db_name'];
//            var_dump($dsn);
            // 配置连接数据库的用户名 通过取得数据库配置文件
            $user = $config['db_user'];
            // 配置连接数据库的密码 通过取得数据库配置文件
            $passwd = $config['db_password'];

            // 连接数据库 通过实例化pdo
            $pdo = new PDO($dsn,$user,$passwd);

            // 设置错误级别 当程序出错时 通过异常抛出
            $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

            // 设置字符集 保证数据不乱码
            $pdo->exec('SET NAMES ' . $config['db_charset']);

            // 将$pdo 保存到静态属性中 不用每次都连接数据库 类中其他方法也可以操作数据库
            self::$pdo = $pdo;

        }catch (PDOException $e){
            // 当连接数据库出现异常时 输出错误提示 可以自主处理错误显示
            exit($e->getMessage());
        }
    }

    /**
     * 可以直接接受原生sql语句进行操作数据库。
     * 操作数据库最基础的方法
     * @param $sql
     */
    public function query($sql){
        // 将代码放入异常处理中 防止程序直接报错
        try{
            // 直接执行sql 进行原生sql数据查询
            $result = self::$pdo->query($sql);
            // 通过数据库查询获取 结果集中的关联数组
             $data = $result->fetchAll(PDO::FETCH_ASSOC);
            // 将获取的数据库数据返回 进行进一步处理
            return $data;

        }catch (PDOException $e){
            // 当连接数据库出现异常时 输出错误提示 可以自主处理错误显示
            exit($e->getMessage());
        }
    }

    /**
     * 获取某张数据表中的全部数据
     */
    public function get(){
        // 拼接sql 进行数据查询
        $sql = "SELECT * FROM {$this->table} {$this->where}";
        // 通过调用对象中的query方法，进行数据查询 并进行数据返回
        return $this->query($sql);
    }

    /**
     * 获取表的主键
     * 方便其他方法进行数据查询
     */
    public function getPri(){
        // 通过调用query方法 获取数据表的结构数据
        $desc = $this->query("DESC {$this->table}");
        // 定义空变量 用于接受主键
        $priField = '';
        // 遍历数组 进行判断哪个字段是主键
        foreach ($desc as $v){
            // 当字段为PRI 时 进行主键确定
            if ($v['Key'] == 'PRI'){
                //将主键字段复制给$priField字段
                $priField = $v['Field'];
                // 结束循环 当找到主键字段时
                break;
            }
        }
        return $priField;
    }


    /**
     * 查询数据库中的指定id数据
     * 通过主键
     * @param $pri
     */
    public function find($pri){
        // 通过getPri方法 获取数据表的主键字段
        $priField = $this->getPri();

        $this->where("{$priField}={$pri}");
        // 拼接sql进行数据查询
        $sql = "SELECT * FROM {$this->table} {$this->where}";

        // 调用query方法 进行数据查询
        $data = $this->query($sql);
        // 将返回结果由二维数组 变成一维数组
        $data = current($data);

        // 将数据保存到对象属性中 因为要返回$this进行链式调用 只能保存到属性中
        // 进行数据的读取
        $this->data = $data;
        // 返回本对象 进行链式调用
        return $this;
    }

    /**
     * 将find的查询结果返回
     * @return mixed
     */
    public function toArray(){
        //将find的查询结果返回
        return $this->data;
    }

    /**
     * find 的替代 可以直接返回find的结果
     * @param $pri
     * @return mixed
     */
    public function findArray($id){
        $obj = $this->find($id);
        return $obj->data;
    }

    /**
     * 获取条件
     * 进行有条件大查询 链式查询
     */
    public function where($where){
        // 将查询条件保存到 成员属性中 共其他方法使用
        $this->where = " WHERE {$where}";
        // 返回本对象 进行链式调用
        return $this;
    }

    /**
     * 进行数量的查询
     * @param string $field 查询的字段 默认为* 所有
     */
    public function count($field = "*"){
        // 拼接sql 方便查询
        $sql = "SELECT count({$field}) as num FROM {$this->table} {$this->where}";

        // 调用query方法进行数据查询
        $data = $this->query($sql);
        // 返回查询的数据 符合条件的数量
        return $data[0]['num'];

    }

    /**
     * 执行无结果集的基础函数
     * 直接执行原生sql操作
     * @param $sql
     */
    public function e($sql){
        // 将pdo有关的sql执行放到try{}catch(){}中
        // 方便对错误的处理
        try{
            // 无结果即操作 直接可以将执行结果返回
            return $data = self::$pdo->exec($sql);

        }catch (PDOException $e){
            // 输出错误信息
            exit($e->getMessage());
        }
    }

    /**
     * 添加数据方法
     * @param $data array 添加数据
     */
   /* public function add($data){
        // 定义空字符串 用于拼接字段组合
        $key='';
        // 定义空字符串 用于拼接值的组合
        $value='';
        // 通过遍历数据进行拼接
        p($data);
        foreach ($data as $k=>$v){
            $key.=" `{$k}` ,";
            $value.=" `{$value}` ,";
        }
        // 取出字符串右边“,”
        $key = rtrim($key,',');
        $value= rtrim($value,',');

        // 获取表的主键
        $pri = $this->getPri();

        // 拼接sql
        $sql = "INSERT INTO {$this->table} ($pri,$key) VALUES (NULL ,$value)";

        p($sql);

        //执行操作  返回数据
        return $this->e($sql);
    }*/

    /**
     * 删除操作
     * 没有where条件不操作
     * 实例 : Article::where("cid=1")->destory();
     */
    public function destory(){
        // 当有where条件时 执行删除操作
       if(!empty($this->where)){
           //拼接sql删除语句
           $sql = "DELETE FROM {$this->table} {$this->where}";

           // 执行e函数 执行无结果集操作
           return $this->e($sql);
       }

       // 当没有where条件是 不进行任何操作
       return false;
    }
}
















