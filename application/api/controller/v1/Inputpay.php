<?php
namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use think\Response;
use think\Db;
use think\Cache;
use app\api\controller\Factory;
use Payment\Pay\Wx\AppCharge;
use app\api\model\Pay as PayModel;
/**
* 
*/
class Inputpay extends Wechat
{

	public function index()
	{	
		$request = Request::instance();
		if(empty(input('token'))){
			$this->error('信息有误，请重新打开！');
		}

		$check = self::checkuser(input('token'));
		if($check){      //得到appid
			$valuetoken = md5(md5(rand(10000,99999).time()));   //秘钥
			Cache::set($valuetoken,$check,600);  //存储，600秒有效
		}else{
			$this->error('非法请求，请重新扫码支付');
		}

		$this->assign('valuetoken',$valuetoken);
		return $this->fetch();
	}


	public function createOrder()
	{
		if (Request::instance()->isAjax()){

			$moeny = input('post.moeny');
			$valuetoken = input('post.valuetoken');
			if(!Cache::get($valuetoken)){
				 return ['msg'=>'请求信息错误','status'=>0];
			}
			$this->pay = new PayModel();
			$data['app_key'] = Cache::get($valuetoken);
			$data['total_fee'] = (float)$moeny * 100;
			$data['out_trade_no'] = 'input'.date('YmdHis',time()).rand(100000,999999);
			$data['success_url']  = 'https://pay.vcxws.com/v1/payment/ok';
			$data['fail_url'] = 'fail_url';
			$data['access_token'] = 'access_token';
			$result = $this->pay->createOrder($data,'input');
			if($result){
                return ['msg'=>$result['pay_url'],'status'=>1];
	        }else{
	          return ['msg'=>'订单创建失败','status'=>0];
	        }

		}
	}

	public function checkuser($token = '')
	{
		$token = explode(':', base64_decode(base64_decode(base64_decode($token))));
		$appid = $token[0];  //取得appid
		$check = Db::name('admin')->where('app_key',$appid)->find();
		if(empty($check)){
			return false;
		}else{
			return $appid;
		}
	}


	//===================test=============
	/**
	 * 商户检测
	 */
	public function test()
	{	
		$request = Request::instance();
		if(empty(input('token'))){
			$this->error('信息有误，请重新打开！');
		}

		$check = self::checkuser(input('token'));
		if($check){      //得到appid
			$valuetoken = md5(md5(rand(10000,99999).time()));   //秘钥
			Cache::set($valuetoken,$check,12000);  //存储，600秒有效
		}else{
			$this->error('非法请求');
		}
		$mch_list = Db::name('mch_list')->order("is_open desc")->select();
		$this->assign('valuetoken',$valuetoken);
		$this->assign('mch_list',$mch_list);
		return $this->fetch();
	}

	public function testpay()
	{
		$sub_mch_id = input('sub_mch_id');
		$moeny = 0.01;
		$valuetoken = input('valuetoken');
		if(!Cache::get($valuetoken)){
			 $this->error('请求信息错误');
		}
		$this->pay = new PayModel();
		$data['app_key'] = Cache::get($valuetoken);
		$data['total_fee'] = (float)$moeny * 100;
		$data['out_trade_no'] = 'input'.date('YmdHis',time()).rand(100000,999999);
		$data['success_url']  = 'https://pay.vcxws.com/v1/payment/ok';
		$data['fail_url'] = 'fail_url';
		$data['access_token'] = 'access_token';
		$result = $this->pay->createOrder($data,'input');
		if($result){
			$orderInfo = Db::name('order')->where('order_sn',$result['order_sn'])->find();
			$res = $this->topay($orderInfo,$sub_mch_id);
	        if(empty($res['paySign']) || empty($res['package'])){
	        	
	            $this->error($res);
	        }else{
	        	$this->success('商户正常');
	        }
	        $this->assign('payinfo',json_encode($res));
	        $this->assign('orderinfo',$orderInfo);
			return $this->fetch('v1/_topay/pay');
        }else{
          $this->error('创建订单失败');
        }
	}

	private function topay($data = [],$sub_mch_id = '')
    {   
        $data['channel'] = 'wx_pub';
        $data['sub_mch_id'] = $sub_mch_id;
        $data['openid'] = session('openid');
        $pay = Factory::getInstance(\Payment\Pay\Wx\AppCharge::class);
        return $pay->pay($data);
    }



}