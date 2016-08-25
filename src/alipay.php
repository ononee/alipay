<?php
/**
 * @file alipay.php
 * @author LJ (liangjian)
 * @date 2016年8月11日 下午2:53:36
 * @brief 即时到帐、二维码支付
 */
class Alipay{
    
    /**
     * 支付宝网关地址（新）
     */
    private $_alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';
    
    /**
     * HTTPS形式消息验证地址
     */
    private $_https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
    
    /**
     * HTTP形式消息验证地址
     */
    private $_http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
    
    /**
     * 接口名称
     */
    private $_service = 'create_direct_pay_by_user';
    
    /**
     * 合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串
     * 查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
     */
    private $_partner;
    
    /**
     * 参数编码字符集，目前支持 gbk 或 utf-8
     */
    private $_input_charset = 'utf-8';
    
    /**
     * 签名方式
     */
    private $_sign_type = 'MD5';
    
    /**
     * 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
     */
    private $_notify_url;
    
    /**
     * 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
     */
    private $_return_url;
    
    /**
     * 商户网站唯一订单号
     */
    private $_out_trade_no;
    
    /**
     * 商品名称
     */
    private $_subject;
    
    /**
     * 支付类型 ，无需修改
     */
    private $_payment_type = 1;
    
    /**
     * 交易金额
     */
    private $_total_fee;
    
    /**
     * 收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
     */
    private $_seller_id;
    
    /**
     * 商品描述
     */
    private $_body;
    
    /**
     * 公用回传参数
     * 用于商户回传参数，该值不能包含“=”、“&”等特殊字符。如果用户请求时传递了该参数，则返回给商户时会回传该参数。
     */
    private $_extra_common_param = 'ALIPAY-PLATFORM-QR';
    
    /**
     * 商品展示网址
     */
    private $_show_url;
    
    /**
     * 防钓鱼时间戳  若要使用请调用类文件submit中的query_timestamp函数
     */
    private $_anti_phishing_key;
    
    /**
     * 客户端的IP地址 非局域网的外网IP地址
     */
    private $_exter_invoke_ip;
    
    /**
     * 扫码支付的方式，支持前置模式和跳转模式。
     * 0：订单码-简约前置模式，对应iframe宽度不能小于600px，高度不能小于300px；
     * 1：订单码-前置模式，对应iframe宽度不能小于300px，高度不能小于600px；
     * 2：订单码-跳转模式
     * 3：订单码-迷你前置模式，对应iframe宽度不能小于75px，高度不能小于75px。
     * 4：订单码-可定义宽度的嵌入式二维码，商户可根据需要设定二维码的大小。
     */
    private $_qr_pay_mode = '0';
    
    /**
     * 商户的私钥,此处填写原始私钥去头去尾，
     * RSA公私钥生成：https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.nBDxfy&treeId=58&articleId=103242&docType=1
     */
    private $_private_key;
    
    /**
     * 支付宝的公钥，查看地址：https://b.alipay.com/order/pidAndKey.htm 
     */
    private $_public_key = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB';
    
    /**
     * md5时使用此key
     */
    private $_key;
    
    /**
     * CA证书路径地址，用于curl中ssl校验
     */
    private $_cacert;
    
    /**
     * 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
     */
    private $_transport = 'http';
    
    /**
     * 是否使用二维码支付
     */
    private $_is_qr_pay = FALSE;
    
    /**
     * __construct()
     */
    public function __construct(){
        $this->_cacert = dirname(__FILE__).DIRECTORY_SEPARATOR.'cacert.pem';
        if($this->_sign_type == 'RSA'){
            $this->_private_key = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'rsa_private_key.pem');
        }
        if(PHP_OS == 'Linux'){
            // $this->_anti_phishing_key = $this->queryTimestamp();
        }
        if($_SERVER['SERVER_PORT'] == 443){
            $this->_transport = 'https';
        }
    }
    
    /**
     * 合作身份者ID
     */
    public function setPartner($partner){
        $this->_partner = $partner;
        return $this;
    }
    
    /**
     * 签名方式
     */
    public function setSignType($signType){
        $this->_sign_type = $signType;
        return $this;
    }
    
    /**
     * 服务器异步通知页面路径
     */
    public function setNotifyUrl($notifyUrl){
        $this->_notify_url = $notifyUrl;
        return $this;
    }
    
    /**
     * 页面跳转同步通知页面路径
     */
    public function setReturnUrl($returnUrl){
        $this->_return_url = $returnUrl;
        return $this;
    }
    
    /**
     * 商户网站唯一订单号
     */
    public function setOutTradeNo($outTradeNo){
        $this->_out_trade_no = $outTradeNo;
        return $this;
    }
    
    /**
     * 商品名称
     */
    public function setSubject($subject){
        $this->_subject = $subject;
        return $this;
    }
    
    /**
     * 交易金额
     */
    public function setTotalFree($totalFree){
        $this->_total_fee = $totalFree;
        return $this;
    }
    
    /**
     * 收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
     */
    public function setSellerId($sellerId){
        $this->_seller_id = $sellerId;
        return $this;
    }
    
    /**
     * 商品描述
     */
    public function setBody($body){
        $this->_body = $body;
        return $this;
    }
    
    /**
     * 商品展示网址
     */
    public function setShowUrl($showUrl){
        $this->_show_url = $showUrl;
        return $this;
    }
    
    /**
     * 客户端的IP地址 非局域网的外网IP地址
     */
    public function setExterInvokeIp($exterInvokeIp){
        $this->_exter_invoke_ip = $exterInvokeIp;
        return $this;
    }
    
    /**
     * 设置MD5的key
     */
    public function setKey($key){
        $this->_key = $key;
        return $this;
    }
    
    /**
     * 公用回传参数
     */
    public function setExtraCommonParam($param){
        $this->_extra_common_param = $param;
        return $this;
    }
    
    /**
     * 扫码支付的方式，支持前置模式和跳转模式。
     */
    public function setQrPayMode($mode){
        $this->_qr_pay_mode = $mode;
        return $this;
    }
    
    /**
     * 是否使用二维码支付
     */
    public function isQrPay($bool = FALSE){
        $this->_is_qr_pay = $bool;
        return $this;
    }
    
    /**
     * 获取支付链接
     */
    public function getPayLink(){
        $parameter = array(
            'service' => $this->_service,
            'partner' => $this->_partner,
            '_input_charset' => strtolower(trim($this->_input_charset)),
            'notify_url' => $this->_notify_url,
            'return_url' => $this->_return_url,
            'payment_type' => $this->_payment_type,
            'seller_id' => $this->_seller_id,
            'out_trade_no' => $this->_out_trade_no,
            'subject' => $this->_subject,
            'total_fee' => $this->_total_fee,
            'body' => $this->_body,
            'show_url' => $this->_show_url,
            'anti_phishing_key' => $this->_anti_phishing_key,
            'exter_invoke_ip' => $this->_exter_invoke_ip
        );
        if($this->_is_qr_pay){
            $parameter['extra_common_param'] = $this->_extra_common_param;
            $parameter['qr_pay_mode'] = $this->_qr_pay_mode;
        }
        $para = $this->buildRequestPara($parameter);
        return $this->_alipay_gateway_new . $this->createLinkstring($para);
    }
    
    /**
     * 生成要请求给支付宝的参数数组
     */
    private function buildRequestPara($parameter){
        $paraFilter = $this->paraFilter($parameter);
        $paraSort = $this->argSort($paraFilter);
        $paraSort['sign'] = $this->buildRequestSign($paraSort);
        $paraSort['sign_type'] = strtoupper(trim($this->_sign_type));
        
        return $paraSort;
    }
    
    /**
     * 获取返回时的签名验证结果
     * @param $paraTemp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
    function getSignVeryfy($paraTemp, $sign) {
        $paraFilter = $this->paraFilter($paraTemp);
        $paraSort = $this->argSort($paraFilter);
        $preStr = $this->createLinkstring($paraSort, false);
    
        $isSgin = false;
        switch (strtoupper(trim($this->_sign_type))) {
            case 'RSA' :
                $isSgin = $this->rsaVerify($preStr, trim($this->_public_key), $sign);
                break;
            case 'MD5' :
                $isSgin = $this->md5Verify($preStr, $sign, $this->_key);
                break;
            default:
                $isSgin = false;
        }
    
        return $isSgin;
    }
    
    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     */
    public function verifyNotify(){
        if(empty($_POST)) {
            return false;
        }
        $isSign = $this->getSignVeryfy($_POST, $_POST['sign']);
        $responseTxt = 'false';
        if (!empty($_POST['notify_id'])){
            $responseTxt = $this->getResponse($_POST['notify_id']);
        }
        return (preg_match("/true$/i", $responseTxt) && $isSign) ? true : false;
    }
    
    /**
     * 针对return_url验证消息是否是支付宝发出的合法消息
     */
    public function verifyReturn(){
        if(empty($_GET)) {
            return false;
        }
        $isSign = $this->getSignVeryfy($_GET, $_GET['sign']);
        $responseTxt = 'false';
        if (!empty($_GET['notify_id'])){
            $responseTxt = $this->getResponse($_GET['notify_id']);
        }
        return (preg_match("/true$/i", $responseTxt) && $isSign) ? true : false;
    }
    
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    private function createLinkstring($para, $urlencode = true){
        $arg  = '';
        while (list ($key, $val) = each ($para)) {
            $arg.=$key.'='.($urlencode ? urlencode($val) : $val).'&';
        }
        $arg = substr($arg,0,count($arg)-2);
        
        if(get_magic_quotes_gpc()){
            $arg = stripslashes($arg);
        }
        return $arg;
    }
    
    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    private function paraFilter($para){
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == 'sign' || $key == 'sign_type' || $val == ''){
                continue;
            }else{
                $para_filter[$key] = $para[$key];
            }
        }
        return $para_filter;
    }
    
    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    private function argSort($para){
        ksort($para);
        reset($para);
        return $para;
    }
    
    /**
     * 生成签名结果
     */
    private function buildRequestSign($paraSort){
        $preStr = $this->createLinkstring($paraSort, false);
        
        $mySign = '';
        switch (strtoupper(trim($this->_sign_type))) {
            case 'RSA' :
                $mySign = $this->rsaSign($preStr, $this->_private_key);
                break;
            case 'MD5' :
                $mySign = $this->md5Sign($preStr);
                break;
            default :
                $mySign = '';
        }
        
        return $mySign;
    }
    
    /**
     * 用于防钓鱼，调用接口query_timestamp来获取时间戳的处理函数
     * 注意：该功能PHP5环境及以上支持，因此必须服务器、本地电脑中装有支持DOMDocument、SSL的PHP配置环境
     * 
     * return 时间戳字符串
     */
    private function queryTimestamp() {
		$url = $this->_alipay_gateway_new.'service=query_timestamp&partner='.trim(strtolower($this->_partner)).'&_input_charset='.trim(strtolower($this->_input_charset));
        
		$doc = new \DOMDocument();
		$doc->load($url);
		$itemEncrypt_key = $doc->getElementsByTagName('encrypt_key');
		$encrypt_key = $itemEncrypt_key->item(0)->nodeValue;
		
		return $encrypt_key;
	}
	
	/**
	 * 获取远程服务器ATN结果,验证返回URL
	 * @param $notifyId 通知校验ID
	 * @return 服务器ATN结果
	 * 验证结果集：
	 * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
	 * true 返回正确信息
	 * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
	 */
	private function getResponse($notifyId) {
	    $transport = strtolower(trim($this->_transport));
	    $partner = trim($this->_partner);
	    $verify_url = ($transport == 'https') ? $this->_https_verify_url : $this->_http_verify_url;
	    $verify_url = $verify_url. 'partner=' . $partner . '&notify_id=' . $notifyId;
	    return $this->getHttpResponseGET($verify_url, $this->_cacert);
	}
    
    /**
     * 远程获取数据，POST模式
     */
	private function getHttpResponsePOST($url, $cacertUrl, $para, $inputCharset = ''){
	    if (trim($inputCharset) != '') {
	        $url = $url.'_input_charset='.$inputCharset;
	    }
	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	    curl_setopt($curl, CURLOPT_CAINFO, $cacertUrl);
	    curl_setopt($curl, CURLOPT_HEADER, 0 );
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_POST,true);
	    curl_setopt($curl, CURLOPT_POSTFIELDS,$para);
	    $responseText = curl_exec($curl);
	    curl_close($curl);
	    
	    return $responseText;
	}
    
    /**
     * 远程获取数据，GET模式
     */
	private function getHttpResponseGET($url, $cacertUrl){
	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_HEADER, 0 );
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
	    curl_setopt($curl, CURLOPT_CAINFO, $cacertUrl);
	    $responseText = curl_exec($curl);
	    curl_close($curl);
	    return $responseText;
	}
    
    /**
     * 实现多种字符编码方式
     * @param $input 需要编码的字符串
     * @param $outputCharset 输出的编码格式
     * @param $inputCharset 输入的编码格式
     * return 编码后的字符串
     */
	private function charsetEncode($input, $outputCharset, $inputCharset) {
    	$output = '';
    	if(!isset($outputCharset) ){
    	    $outputCharset  = $inputCharset;
    	}
    	if($inputCharset == $outputCharset || $input ==null ) {
    		$output = $input;
    	} elseif (function_exists('mb_convert_encoding')) {
    		$output = mb_convert_encoding($input, $outputCharset, $inputCharset);
    	} elseif(function_exists('iconv')) {
    		$output = iconv($inputCharset,$outputCharset,$input);
    	} else {
    	    die('sorry, you have no libs support for charset change.');
    	}
    	return $output;
    }
    
    /**
     * 实现多种字符解码方式
     * @param $input 需要解码的字符串
     * @param $outputCharset 输出的解码格式
     * @param $inputCharset 输入的解码格式
     * return 解码后的字符串
     */
    function charsetDecode($input, $inputCharset, $outputCharset) {
        $output = '';
        if(!isset($inputCharset) )$inputCharset  = $inputCharset ;
        if($inputCharset == $outputCharset || $input ==null ) {
            $output = $input;
        } elseif (function_exists('mb_convert_encoding')) {
            $output = mb_convert_encoding($input,$outputCharset,$inputCharset);
        } elseif(function_exists('iconv')) {
            $output = iconv($inputCharset,$outputCharset,$input);
        } else {
            die('sorry, you have no libs support for charset changes.');
        }
        return $output;
    }
    
    /**
     * MD5签名
     */
    private function md5Sign($preStr){
        return md5($preStr.$this->_key);
    }
    
    /**
     * MD5验签
     */
    private function md5Verify($preStr, $sign, $key){
        $mySign = md5($preStr.$key);
        return $mySign == $sign ? true : false;
    }
    
    /**
     * RSA签名
     */
    private function rsaSign($data, $privateKey){
        $privateKey = str_replace('-----BEGIN RSA PRIVATE KEY-----', '', $privateKey);
        $privateKey = str_replace('-----END RSA PRIVATE KEY-----', '', $privateKey);
        $privateKey = str_replace("\n", '', $privateKey);
        $privateKey = '-----BEGIN RSA PRIVATE KEY-----'.PHP_EOL .wordwrap($privateKey, 64, "\n", true). PHP_EOL.'-----END RSA PRIVATE KEY-----';
        
        $res=openssl_get_privatekey($privateKey);
        if($res){
            openssl_sign($data, $sign,$res);
        } else {
            die('您的私钥格式不正确!'.PHP_EOL.'The format of your private_key is incorrect!');
        }
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }
    
    /**
     * RSA验签
     */
    private function rsaVerify($data, $publicKey, $sign){
        $publicKey = str_replace('-----BEGIN PUBLIC KEY-----', '', $publicKey);
        $publicKey = str_replace('-----END PUBLIC KEY-----', '', $publicKey);
        $publicKey = str_replace("\n", '', $publicKey);
        
        $publicKey = '-----BEGIN PUBLIC KEY-----'.PHP_EOL.wordwrap($publicKey, 64, "\n", true).PHP_EOL.'-----END PUBLIC KEY-----';
        $res = openssl_get_publickey($publicKey);
        if($res){
            $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        } else {
            die('您的支付宝公钥格式不正确!'.PHP_EOL.'The format of your alipay_public_key is incorrect!');
        }
        openssl_free_key($res);
        return $result;
    }
    
}