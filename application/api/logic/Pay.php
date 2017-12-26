<?php
namespace app\api\logic;

use app\api\controller\v1\fans;
use think\Controller;
use think\Response;
use think\Db;
use think\Cache;
use app\api\controller\Send;
/**
* 逻辑类操作
*/
class Pay
{
    use Send;

    /**
     * 检测创建订单参数等
     */
    public function check($data = [])
    {
    	if(empty($data['total_fee'])){

    		return $this->returnmsg(401,'金额不能为空',$data);
    	}

    	if($data['total_fee'] > 10000000){
    		return $this->returnmsg(401,'金额最大不能超过100000元',[]);
    	}

    	if($data['total_fee'] == 0){
    		return $this->returnmsg(401,'金额错误',[]);
    	}

    	if(ceil($data['total_fee']) != $data['total_fee']){

    		return $this->returnmsg(401,'金额单位为分，不能有小数点，最小金额为1分',[]);
    	}

    	if(empty($data['out_trade_no'])){

    		return $this->returnmsg(401,'请传入订单号',[]);
    	}
        $checkorder_sn = Db::name('order')->where('order_sn',$data['out_trade_no'])->find();
        if(!empty($checkorder_sn)){
            return $this->returnmsg(401,'订单号重复，请重新传入订单号',[]);
        }
        $checkaccess_token = Db::name('admin')
                            ->where('app_key',$data['app_key'])
                            ->where('access_token',$data['access_token'])
                            ->where('expires_time','>',time())
                            ->find();
        if(empty($checkaccess_token)){
            return $this->returnmsg(401,'access_token无效或已经过期',[]);
        }
    	// if(empty($data['notify_url'])){
    	// 	$this->returnmsg(401,'支付回调地址不能为空',[]);
    	// }
    }
}