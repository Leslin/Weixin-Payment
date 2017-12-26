<?php
/**
 * 获取accesstoken
 */
namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use app\api\controller\Api;
use think\Response;
use app\api\controller\UnauthorizedException;
use app\api\controller\Send;
use app\api\controller\Oauth as Oauth2;
use app\api\model\Oauth as Oauth;
use app\api\controller\Factory;
use think\Db;
use think\Cache;
class Token extends Controller
{	
	use Send;
	//手机客户端请求验证规则
	public static $rule_mobile = [
        'app_key'     =>  'require',
        'sign'        =>  'require',
        'nonce'       =>  'require',
        'timestamp'   =>  'require',
        'captcha'     =>  'number'   //手机验证码
    ];
    //微信端请求验证规则
    public static $rule_wechat = [
        'app_key'     =>  'require',
        'open_id'     =>  'require',
        'nonce'       =>  'require',
        'timestamp'   =>  'require',
        'union_id'    =>  'require',
        'access_token'=>  'require' //微信端的access_token用于验证用户的信息是否真实
    ];
    
    /**
     * 构造函数
     * 初始化检测请求时间，签名等
     */
    public function __construct()
    {
        $this->request = Request::instance();
        //为了调试注释掉时间验证与前面验证，请开发者自行测试
        ///$this->checkTime();
        $this->checkSign();
    }

	public function wechat()
	{    
		$this->checkAppkey(self::$rule_wechat);  //检测appkey
	}

	/**
	 * 为客户端提供access_token
	 * 手机号登录，手机号登录必须是注册过的手机号，不支持手机号注册
	 */
	public function gettoken()
	{	
		//检测appkey
		$this->checkAppkey(self::$rule_mobile);
		//获取短信验证码
		if(!empty($this->request->param('captcha'))){
			//$sms = Factory::getInstance(\app\api\controller\Sms::class);
			//$code = $sms->getMobileCode($this->request->param('mobilephone')); 
			//return $this->returnmsg(200,'success',['code'=>$code]);
		}else{
			$userInfo = Db::name('admin')->field('app_key')->where('app_key',$this->request->param('app_key'))->find();  //得到数据库的appkey

			if(!empty($userInfo)){
				try {
					$accessTokenInfo = $this->setAccessToken($userInfo);
					if($accessTokenInfo){
						$up['access_token'] = $accessTokenInfo['access_token'];
						$up['expires_time'] = $accessTokenInfo['expires_time'];
						$upuser = Db::name('admin')->where('app_key',$this->request->param('app_key'))->update($up);
						if($upuser){
							return $this->returnmsg(200,'success',$accessTokenInfo);
						}else{
							return $this->returnmsg(401,'access_token生成失败');
						}
					}
					
				} catch (\Exception $e) {
					$this->sendError(500, 'server error!!', 500);
				}
			}else{
				return $this->returnmsg(401,'app_key is not bind');
			}
		}
	}

	/**
	 * 检测时间+_300秒内请求会异常
	 */
	public function checkTime()
	{
		$time = $this->request->param('timestamp');
		if($time > time()+300  || $time < time()-300){
			return $this->returnmsg(401,'请求时间与服务器时间不能超过300秒');
		}
	}

	/**
	 * 检查微信用户是否真实
	 */
	public function checkWechat()
	{
		#todo
	}

	/**
	 * 检测appkey的有效性
	 * @param 验证规则数组
	 */
	public function checkAppkey($rule)
	{
		$result = $this->validate($this->request->param(),$rule);
		if(true !== $result){
			return $this->returnmsg(405,$result);
		}
        //====调用模型验证app_key是否正确，这里注释，请开发者自行建表======
		// $result = Oauth::get(function($query){
		// 	$query->where('app_key', $this->request->param('app_key'));
		// 	$query->where('expires_in','>' ,time());
		// });
		if(empty($result)){
			return $this->returnmsg(401,'App_key does not exist or has expired. Please contact management');
		}
	}
	/**
	 * 检查签名
	 */
	public function checkSign()
	{	

		$baseAuth = Factory::getInstance(\app\api\controller\Oauth::class);
		$app_secret = Db::name('admin')->where('app_key',$this->request->param('app_key'))->find();// Oauth::get(['app_key' => $this->request->param('app_key')]);
    	$sign = $baseAuth->makesign($this->request->param(),$app_secret['appsecret']);     //生成签名
    	if($sign !== $this->request->param('sign')){
    		return $this->returnmsg(401,'Signature error');
    	}
	}

	/**
     * 设置AccessToken
     * @param $clientInfo
     * @return int
     */
    protected function setAccessToken($clientInfo)
    {
        //生成令牌
        $accessToken = self::buildAccessToken();
        $accessTokenInfo = [
            'access_token' => $accessToken,//访问令牌
            'expires_time' => time() + Oauth2::$expires,      //过期时间时间戳
            'client' => $clientInfo,//用户信息
        ];
        //self::saveAccessToken($accessToken, $accessTokenInfo);
        return $accessTokenInfo;
    }

    /**
     * 生成AccessToken
     * @return string
     */
    protected static function buildAccessToken($lenght = 32)
    {
        //生成AccessToken
        $str_pol = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz";
		return substr(str_shuffle($str_pol), 0, $lenght);

    }

    /**
     * 存储
     * @param $accessToken
     * @param $accessTokenInfo
     */
    protected static function saveAccessToken($accessToken, $accessTokenInfo)
    {
        //存储accessToken
        Cache::set(Oauth2::$accessTokenPrefix . $accessToken, $accessTokenInfo, Oauth2::$expires);
        //存储用户与信息索引 用于比较,这里涉及到user_id，如果有需要请关掉注释
        //Cache::set(self::$accessTokenAndClientPrefix . $accessTokenInfo['client']['user_id'], $accessToken, self::$expires);
    }
}