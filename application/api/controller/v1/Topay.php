<?php

namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use app\api\controller\Api;
use think\Response;
use think\Db;
use think\Log;
use app\api\controller\UnauthorizedException;
use app\api\controller\Factory;
use app\api\controller\Send;
use Payment\Pay\Wx\AppCharge;
use app\api\model\Pay as PayModel;

class Topay extends Wechat
{	
    use Send;
	public function pay()
	{	
		$data = input('');
		$time = time();
        $orderInfo = Db::name('order')->where('pay_token',$data['token'])->find();
        if(empty($orderInfo)){
           $this->error('订单信息有误，请重新发起支付');
        }
        if($orderInfo['pay_token_time'] < $time){
            $this->error('订单已过期');
        }
        if($orderInfo['pay_status'] == 1){
            $this->error('订单已支付');
        }

        $res = $this->topay($orderInfo);
        Log::info('支付参数返回:'.json_encode($res));
        if(empty($res['paySign']) || empty($res['package'])){
        	
            $this->error('订单创建错误'.$res);
        }
        $this->assign('payinfo',json_encode($res));
        $this->assign('orderinfo',$orderInfo);
        return $this->fetch();
	}

	/**
     * @param array order
     * 用户支付
     * @return array paypakge
     */
    private function topay($data = [])
    {   
        $userinfo = Db::name('admin')->where('user_name',$data['user_id'])->find();

        //第三方支付
        //=========== 威富通 ===========
        //$pay = Factory::getInstance(\app\api\controller\v1\Swiftpass::class);
        //return json_decode($pay->submitOrderInfo(),true);
        //=========== end  ============
        if(empty($userinfo['defult_mch_id'])){   //不存在默认商户
            $mch_list = Db::name('mch_list')->where('is_open',1)->select();
            $mch_id = $mch_list[rand(0,count($mch_list)-1)]['sub_mch_id'];
        }else{
            $mch_id = $userinfo['defult_mch_id'];
        }
        $data['channel'] = 'wx_pub';
        $data['sub_mch_id'] = $mch_id;
        $data['openid'] = session('openid');
        $pay = Factory::getInstance(\Payment\Pay\Wx\AppCharge::class);
        return $pay->pay($data);
    }
    public function ok()
    {
        return fetch();
    }
}