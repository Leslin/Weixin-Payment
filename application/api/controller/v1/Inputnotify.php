<?php
namespace app\api\controller\v1;

use think\Controller;
use think\Request;

class Inputnotify
{
	public function index()
	{
		$return = [
			'code' =>200,
			'message' => 'ok'
		];
		exit(json_encode($return));
	}
}