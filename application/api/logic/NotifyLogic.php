<?php
/**
 * Created by PhpStorm.
 * User: dalin
 * Date: 2017/9/23
 * Time: 11:49
 */
namespace app\api\logic;

use app\api\utils\ArrayUtil;
use app\api\utils\DataParser;
/**
 * Class Notify
 * 支付回调相关数据逻辑处理
 * @package app\api\logic
 */
class NotifyLogic{

    /**
     * 获取微信支付相关配置
     * @return mixed
     */
    public static function wxConfig()
    {
        $wxConfig = require_once EXTEND_PATH . '/Payment/Pay/wxconfig.php';
        return $wxConfig;
    }
    
    /**
     * @param array $array
     * 对微信支付回调数据做签名验证
     * @return bool
     */
    public static function checkSign($array = [])
    {
        $wxConfig = self::wxConfig();
        $sign = $array['sign'];   //老的签名
        $data = ArrayUtil::removeKeys($array,'sign');   //返回数据去除原始签名
        $string = ArrayUtil::createLinkstring($data).'&key='.$wxConfig['md5_key'];
        return strtoupper(md5($string)) === $sign;
    }

}