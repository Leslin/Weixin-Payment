<?php
namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use think\Response;
use think\Db;

class Payment extends Controller {

	public function ok()
	{
		return $this->fetch();
	}
}