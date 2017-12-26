 # 微信支付服务商交易系统

- 基于微信服务商多商户交易系统，后台可以配置多个服务商，每个服务商下可配置多个子商户，交易支持微信原生jsapi支付，扫码、刷卡支付，同时支持微信桥支付。

- 每一笔支付都能随机指定到某一个商户，防止微信端支付被投诉导致商户号被封

- 后台可以开设子账号，分配接口给用户，并进行单独商户对账，最高权限账号统一管理账务

- 支持支付宝等交易 
- 交易商户对账、不同管理员对账管理
- 商户权限检测
- 支持扫码手动输入金额付款

## 架构
- 代码基于ThinkPHP5.0开发，结合restful api进行接口权限管理，具体restful api请看本github第一个项目，这里不做具体演示。



```
graph LR
用户-->接口调用
接口调用-->商户系统
商户系统-->微信支付API
微信支付API-->接口调用
接口调用-->用户
```


## 代码说明
### 1.服务商商户配置

-  服务商配置代码路径
```
/extend/Payment/Pay/weixinconfig.php
```
修改 app_id、mch_id(服务商商户id)、md5_key(支付key)、notify_url(回调地址)等参数

### 2.用生成的token调用支付接口
- 接口请求地址 POST

```
https://****.com/v1/pay
```
如图：
![](https://github.com/Leslin/Weixin-Payment/blob/master/screenshot/8.png)
请求成功后，接口会同步返回一个支付链接地址，地址生成了一个唯一的支付token，用户识别订单，用户只需要点击该链接地址就会自动跳转至微信jsapi支付。

如图
![](https://github.com/Leslin/Weixin-Payment/blob/master/screenshot/11.png)
### 3.微信公众号配置

- 微信基础类Wechat.php在v1/目录下，这里微信的基类的作用是用户点击点击支付链接，进行微信授权，拿到用户的openid，根据支付的token提交支付订单给微信，根据返回的预支付id等参数调用jsapi进行支付。

### 4.支付回调
- 后台增加一个支付调用者，就会对应填写客户的回调域名，在客户调用支付成功后，系统会收到微信回调通知，系统同步把微信通知数据post给客户填写的回调域名。这里数据的sign做了重新处理，根据客户的私钥重新进行了签名，保证数据安全

```
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
```

### 5.手动输入付款金额付款
- 系统支持用户扫码手动输入付款金额进行付款，该支付方式不需要调用API，直接提供一个生产二维码的URL，具体控制在/v1/Inputpay.php，提取出链接后，直接在草料等平台生产二维码即可
如图
![](https://github.com/Leslin/Weixin-Payment/blob/master/screenshot/12.png)

### 6.商户防封检测
- 当系统支付客户接入过多后，会造成商户支付权限被冻结，导致支付失败，这里提供了商户支付权限检测功能，代码位置在/v1/Inputpay.php test方法
如图
![](https://github.com/Leslin/Weixin-Payment/blob/master/screenshot/13.png)