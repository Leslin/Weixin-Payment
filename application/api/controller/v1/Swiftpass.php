<?php
namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use think\Response;
use think\Db;
use app\api\swiftpass\Utils;
use app\api\swiftpass\RequestHandler;
use app\api\swiftpass\ClientResponseHandler;
use app\api\swiftpass\PayHttpClient;
use app\api\swiftpass\Config;

/** 
 * 第三方调用说明
 * 威富通支付
 */
class Swiftpass extends Controller {

	public function __construct(){
        $this->Request();
    }

    public function Request(){
        $this->resHandler = new ClientResponseHandler();
        $this->reqHandler = new RequestHandler();
        $this->pay = new PayHttpClient();
        $this->cfg = new Config();

        $this->reqHandler->setGateUrl($this->cfg->C('url'));
        $this->reqHandler->setKey($this->cfg->C('key'));
    }

	public function submitOrderInfo(){
        $this->reqHandler->setParameter('service','pay.weixin.jspay');//接口类型
        $this->reqHandler->setParameter('mch_id',$this->cfg->C('mchId'));//必填项，商户号，由平台分配
        $this->reqHandler->setParameter('version',$this->cfg->C('version'));
        $this->reqHandler->setParameter('limit_credit_pay','1');
		$this->reqHandler->setParameter('op_user_id','yida');
		$this->reqHandler->setParameter('out_trade_no','das2'.time());
		$this->reqHandler->setParameter('body','das2'.time());  
		$this->reqHandler->setParameter('total_fee',11);
		//$this->reqHandler->setParameter('appid','wx50bb165fb00ef149');
		$this->reqHandler->setParameter('op_shop_id','yida1');
		$this->reqHandler->setParameter('op_device_id','yida2');
		$this->reqHandler->setParameter('mch_create_ip','127.0.0.1'); 
		//$this->reqHandler->setParameter('time_expire','20170306152200');
		$this->reqHandler->setParameter('is_raw','1');
        //通知地址，必填项，接收平台通知的URL，需给绝对路径，255字符内格式如:http://wap.tenpay.com/tenpay.asp
        //$notify_url = 'http://'.$_SERVER['HTTP_HOST'];
        //$this->reqHandler->setParameter('notify_url',$notify_url.'/payInterface/request.php?method=callback');
		$this->reqHandler->setParameter('notify_url','http://zhangwei.dev.swiftpass.cn/payInterface_gzzh1/request.php?method=callback');//
		$this->reqHandler->setParameter('callback_url','https://www.baidu.com/');
        $this->reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
		//$this->reqHandler->setParameter('sub_openid','oZ1CSjktOzQRa-q2feBrBXg9VXuM');
        $this->reqHandler->createSign();//创建签名
        
        $data = Utils::toXml($this->reqHandler->getAllParameters());
        //dump(Utils::toArray($data));die;
        $this->pay->setReqContent($this->reqHandler->getGateURL(),$data);
        if($this->pay->call()){
            $this->resHandler->setContent($this->pay->getResContent());
            $this->resHandler->setKey($this->reqHandler->getKey());
            if($this->resHandler->isTenpaySign()){
                //当返回状态与业务结果都为0时才返回，其它结果请查看接口文档
                if($this->resHandler->getParameter('status') == 0 && $this->resHandler->getParameter('result_code') == 0){
                	return $this->resHandler->getParameter('pay_info');
                    return json_encode(array('token_id'=>$this->resHandler->getParameter('token_id'),
										'pay_info'=>$this->resHandler->getParameter('pay_info')));
                    exit();
                }else{
                    return json_encode(array('status'=>500,'msg'=>'Error Code:'.$this->resHandler->getParameter('status').' Error Message:'.$this->resHandler->getParameter('message')));
                    exit();
                }
            }
            return json_encode(array('status'=>500,'msg'=>'Error Code:'.$this->resHandler->getParameter('status').' Error Message:'.$this->resHandler->getParameter('message')));
        }else{
            return json_encode(array('status'=>500,'msg'=>'Response Code:'.$this->pay->getResponseCode().' Error Info:'.$this->pay->getErrInfo()));
        }
    }
}