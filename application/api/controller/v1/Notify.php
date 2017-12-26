<?php
namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use app\api\utils\DataParser;
use app\api\logic\NotifyLogic;
use think\Response;
use app\api\controller\Factory;
use think\Log;
use think\Db;

class Notify
{
	public function index()
	{	
		$xml=file_get_contents('php://input', 'r');
		Log::info('支付回调信息'.$xml);
		//检测通知数据是否正确,防止假数据或者非法数据提交,这里一定要做签名检测
        $returndata = DataParser::toArray($xml);
        $chceksign = NotifyLogic::checkSign($returndata);
        if($chceksign){
            $checkNotify =  Db::name('order')->where('order_sn',$returndata['out_trade_no'])->find();
            if($checkNotify['callback_status'] == 1){    //防止微信出现多次重复回调
                Log::info('已经回调过了');
                Log::info('返回给微信'.self::returnwxsuccess());
                exit(self::returnwxsuccess());  //返回给微信
            }
        	Log::info('签名正确');
        	$this->payInfoAdd($returndata);
        	$up['pay_status'] = 1;
        	$up['sub_mch_id'] = $returndata['sub_mch_id'];
        	$up['pay_time'] = time();
        	$update = Db::name('order')->where('order_sn',$returndata['out_trade_no'])->update($up);
        	if($update){
        		//回调
        		$callback = $this->callback($returndata);
        		if($callback){   //正确返回
                    Log::info('返回给微信'.self::returnwxsuccess());
        			exit(self::returnwxsuccess());  //返回给微信
        		}else{
        			Log::info('支付回调客户返回失败'.json_encode($callback));
        			exit('fali');
        		}
        		
        	}
        }else{
        	exit('sign fail');
        }
	}

	public function callback($data = [])
	{
		unset($data['sign']); //去除签名
		$order = self::getOrderInfo($data['out_trade_no']);
		$userinfo = Db::name('admin')->where('app_key',$order['user_id'])->find();
		if(empty($userinfo)){
			return false;
		}

		$data['sign'] = $this->_getOrderMd5($data,$userinfo['appsecret']);  //得到完整的签名后的数据
		Log::info('开始回调客户'.json_encode($data));
		$result = json_decode($this->httpRequest($userinfo['callback'],json_encode($data)),true);
		Log::info('回调客户返回结果'.json_encode($result));
		if($result['code'] == 200){  //正确返回
			$back['callback_status'] = 1;
			$back['returndata'] = json_encode($result);
			Db::name('order')->where('order_sn',$data['out_trade_no'])->update($back);
			return true;
		}else{
			$back['callback_status'] = 2;
			$back['returndata'] = json_encode($result);
			Db::name('order')->where('order_sn',$data['out_trade_no'])->update($back);
			return false;
		}

	}

	private function _getOrderMd5($params = [] , $app_secret = '') {
        ksort($params);
        $params['key'] = $app_secret;
        return strtolower(md5(urldecode(http_build_query($params))));
    }




	/**
     * @param array $data
     * 插入支付回调数据
     * @return array
     */
    public function payInfoAdd($data = [])
    {
        $orderInfo = Db::name('payinfo')->where('out_trade_no',$data['out_trade_no'])->find();
        if(empty($orderInfo)){
            $order = self::getOrderInfo($data['out_trade_no']);
            $data['user_id'] = $order['user_id'];
            $data['total_fee'] = $data['total_fee']/100;
            $data['return_data'] = json_encode($data); //数据原路存为json格式到数据库
            $data['time'] = time();
            return Db::name('payinfo')->insert($data);
        }else{
            return false;
        }

    }

    /**
     * @param string $order_sn
     * 返回订单信息
     * @return array|false|\PDOStatement|string|Model
     */
    public static function getOrderInfo($order_sn = '')
    {
        $orderInfo = Db::name('order')->where('order_sn',$order_sn)->find();
        return $orderInfo;
    }

    /**
     * @return mixed
     * 返回给微信,支付成功
     */
    public static function returnwxsuccess()
    {
        $return['return_code'] = 'SUCCESS';
        $return['return_msg'] = 'OK';
        return DataParser::toXml($return);
    }

    function httpRequest($URL, $params) {
		$ch = curl_init($URL);
        $timeout = 5;
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
        $file_contents = curl_exec($ch);//获得返回值

        curl_close($ch);
        return $file_contents;
	}

}