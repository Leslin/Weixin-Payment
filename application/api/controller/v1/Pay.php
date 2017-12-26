<?php
namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use think\View;
use app\api\controller\Api;
use think\Response;
use app\api\controller\UnauthorizedException;
use app\api\controller\Factory;
use app\api\controller\Send;
use Payment\Pay\Wx\AppCharge;
use app\api\model\Pay as PayModel;

class Pay extends Api
{   
    /**
     * 允许访问的方式列表，资源数组如果没有对应的方式列表，请不要把该方法写上，如user这个资源，客户端没有delete操作
     */
    public $restMethodList = 'get|post|put';

    public function __construct(Request $request = null) {
       
       $this->pay = new PayModel();
    }
    
    
    /**
     * post方式
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save()
    {
        $data = input('');
        $logic  = Factory::getInstance(\app\api\logic\Pay::class);  //验证优惠券,购买等信息
        $price = $logic->check($data);
        $result = $this->pay->createOrder($data,'api');
        //创建订单成功
        if($result){
            //$result['package'] = $this->pay($result,$data);
            return $this->returnmsg(200,'success',$result);
        }else{
           return $this->returnmsg(401,'订单创建失败');
        }
    }

    /**
     * get方式
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id = '')
    {  
        $time = time();
        $orderInfo = $this->pay->getOrderByToken($id);
        if(empty($orderInfo)){
           return $this->returnmsg(401,'订单错误');
        }
        if($orderInfo['pay_token_time'] < $time){
            return $this->returnmsg(401,'订单已过期');
        }

        $res = $this->pay($orderInfo);
        think\View::share('payinfo',json_encode($res));
        return $this->fetch('pay');
    }
    
    /**
     * @param array order
     * 用户支付
     * @return array paypakge
     */
    private function pay($data = [])
    {
        $data['channel'] = 'wx_pub';
        $data['sub_mch_id'] = '1489821102';
        $data['openid'] = 'o3GyPv4CcNZ2vY-IlhrsotRX70gY';
        $pay = Factory::getInstance(\Payment\Pay\Wx\AppCharge::class);
        return $pay->pay($data);
    }
}
