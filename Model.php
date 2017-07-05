<?php
/**
 * Created by PhpStorm.
 * User: ziyun
 * Date: 2017/6/28
 * Time: 16:55
 */

namespace ziyun\model;


class Model
{
    private static $config; // 定义私有属性 用来保存和数据库相关的配置

    /**
     * 当实例化操作数据库时触发该方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // 调用本类方法 实现函数的动态调用
        return self::parseAction($name,$arguments);
    }

    /**
     * 当静态调用数据库操作时触发该函数
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        // 调用本类方法 实现函数的动态调用
        return self::parseAction($name,$arguments);
    }

    private static function parseAction($name,$arguments){
        // 获取要操作的表名  先获得他的命名空间 方便model处理
        $table = strtolower(ltrim(strrchr(get_called_class(),"\\"),"\\"));

        // 根据传参 自动调用相应的方法 实现动态调用 通过参数经数据库配置 用数据表名称出入
        return call_user_func_array([new Base(self::$config,$table),$name],$arguments);
    }


    /**
     * 导入数据库配置文件
     * @param $config array 数据库配置数组
     */
    public static function setConfig($config){
        // 将数据库配置保存到类的静态属性中 准备传入base类中 配置数据库
        self::$config = $config;
    }
}