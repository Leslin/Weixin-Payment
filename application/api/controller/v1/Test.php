<?php
namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use think\Response;
use think\Db;
use think\Cache;
use think\Log;
use app\api\model\Pay as PayModel;
/**
* 
*/
class Test extends Controller
{
	public function index()
	{
		$xml=file_get_contents('php://input', 'r');
		Log::info('测试支付回调接受post信息'.$xml);
		$return = [
			'code' => 200,
			'message' => 'ok'
		];
		exit(json_encode($return));
	}
}