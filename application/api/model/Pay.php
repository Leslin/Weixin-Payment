<?php
namespace app\api\model;

use think\Model;
use think\Db;
use app\api\controller\Factory;
/**
 * 订单类相关模型
 */
class Pay extends Model
{
	/**
	 * 起始分页
	 */
	public static $page = 0;

	/**
	 * 默认分页数据量
	 */
	public static $size = 10;

	/**
	 * 创建订单
	 * @param array data
	 * @return array 
	 */
	public function createOrder($data = [],$order_source = '')
	{
		$params = array(
			'order_sn' => $data['out_trade_no'], // 订单号
			'order_source'   => $order_source,//订单来源
			'third_order_sn' => '400'.self::_getRandomStr(8).date("YmdHis",time()), //第三方订单号\
			'pay_token' => md5(time().self::_getRandomStr(8)),
			'pay_token_time' => time() + 600,
			'user_id'   => $data['app_key'],
			'order_status' => 1,       //订单状态，1订单提交成功，2订单已支付，3订单已退款
			'shipping_status' => 0,    //发货状态，0未发货，1已发货
			'pay_status'     => 0,     //支付状态，0未支付，1已支付,2已退款，3退款失败
			'total_amount'   => $data['total_fee']/100,
			'add_time'    => time(),
			'success_url' => $data['success_url'],
			'fail_url'        => $data['fail_url']
		);
		$add = Db::name('order')->insert($params);
		if($add){
			$this->createOrderAction($params['order_sn'],$data['app_key'],1,0,0,'您提交了订单，请进行支付','提交订单');
			$params['pay_url'] = 'https://pay.vcxws.com/v1/topay/pay/'.$params['pay_token'];
			$return['order_sn'] = $params['order_sn'];
			$return['pay_token'] = $params['pay_token'];
			$return['pay_token_time'] = $params['pay_token_time'];
			$return['total_amount'] = $params['total_amount'];
			$return['success_url']  = $params['success_url'];
			$return['fail_url'] = $params['success_url'];
			$return['pay_url'] = $params['pay_url'];
			return $return;
		}else{
			return false;
		}
	}

	/**
	 * 创建订单动作日志
	 */
	public function createOrderAction($ordersn='',$action_user = '',$order_status='',$shipping_status = '',$pay_status = '',$action_note = '',$status_desc = '')
	{
		$data['order_id'] = $ordersn;
		$data['action_user'] = $action_user;
		$data['order_status'] = $order_status;
		$data['shipping_status'] = $shipping_status;
		$data['pay_status'] = $pay_status;
		$data['action_note'] = $action_note;
		$data['status_desc'] = $status_desc;
		$data['log_time'] = time();
		return Db::name('order_action')->insert($data);

	}

	/**
	 * @param string $order_sn
	 * @param string $address_id
	 * 创建订单送货地址
	 * @return bool
	 */
	public function createOrderAddress($order_sn = '' ,$address_id = '')
	{
		$addressInfo = Db::name('user_address')->where('id',$address_id)->find();
		if(empty($addressInfo)){
			return false;
		}
		$data['order_sn'] = $order_sn;
		$data['name'] = $addressInfo['recipients'];
		$data['mobile'] = $addressInfo['mobilephone'];
		$data['city'] = $addressInfo['city'];
		$data['address'] = $addressInfo['address'];
		$data['in_time'] = time();
		return Db::name('order_address')->insertGetId($data);
	}

	/**
	 * 生成一个订单号
	 */
	public static function createOrderId($lenght = '')
	{
		return date("YmdHis").self::_getRandomStr($lenght);
	}

	/**
	 * 返回随机填充的字符串
	 */
	private static function _getRandomStr($lenght = 16)	{
		$str_pol = "1234567890";
		return substr(str_shuffle($str_pol), 0, $lenght);
	}

	/**
	 * @param string $order_sn
	 * 根据订单号获取订单详情
	 * @return array|false|\PDOStatement|string|Model
	 */
	public function getOrderById($order_sn = '')
	{
		$orderInfo =  Db::name('order')->where('order_sn',$order_sn)->find();
		//$orderInfo['goodslist'] = $this->getGoodsByOrder($order_sn);
		//$orderInfo['address'] = Db::name('order_address')->where('order_sn',$order_sn)->find();
		return $orderInfo;
	}

	/**
	 * @param string $pay_token
	 * 根据token获取订单详情
	 * @return array|false|\PDOStatement|string|Model
	 */
	public function getOrderByToken($pay_token = '')
	{
		$orderInfo =  Db::name('order')->where('pay_token',$pay_token)->find();
		//$orderInfo['goodslist'] = $this->getGoodsByOrder($order_sn);
		//$orderInfo['address'] = Db::name('order_address')->where('order_sn',$order_sn)->find();
		return $orderInfo;
	}

	/**
	 * @param array $where
	 * 获取订单列表
	 * @return false|\PDOStatement|string|\think\Collection
	 */
	public function getOrderList($where = [])
	{
		$condition = [];
		if(!isset($where['page'])){ $page = self::$page; }else{$page = $where['page'];}
		if(!isset($where['size'])){ $size = self::$size; }else{$size = $where['size'];}
		return Db::name('order')->where($condition)->limit($page,$size)->select();
	}

	/**
	 * @param string $order_sn
	 * 获取指定订单下的商品列表
	 * @return array|false|\PDOStatement|string|Model
	 */
	public function getGoodsByOrder($order_sn = '')
	{
		return Db::name('order_goods')->where('order_id',$order_sn)->select();
	}
}