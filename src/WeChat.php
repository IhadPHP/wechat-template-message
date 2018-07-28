<?php

namespace syrecords;

class WeChat
{
    protected $_appid;
    protected $_key;

    protected static $url;

    public $error;

    /**
     * 实例对象传入微信参数
     * WeChat constructor.
     * @param $appid
     * @param $key
     */
    public function __construct($appid,$key)
    {
        $this->_appid = $appid;
        $this->_key = $key;
    }

    /**
     * 发送curl 请求
     * @param $url
     * @param null $data
     * @return mixed
     */
    private static function curlRequest($url,$data=null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        if(!empty($data)) {
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 获取微信access token
     * @return mixed
     */
    public function getAccessToken()
    {
        if( isset($_SESSION['access_token']) && isset($_SESSION['expire_time'])) {
            if ($_SESSION['access_token'] && $_SESSION['expire_time'] > time()) {
                //仍然可以使用access_token
                $accessToken = $_SESSION['access_token'];
//        $accessToken = RedisHelper::GetString(ACCESS_TOKEN);
            } else {
                //重新获取,access_token不存在或者已经过期
                self::$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->_appid}&secret={$this->_key}";
                $tokenJson = self::curlRequest(self::$url);
                $tokenArr = json_decode($tokenJson, true);
                if (isset($tokenArr['errcode'])) {
                    $this->error = json_encode($tokenArr);
                    return false;
                }
                $_SESSION['access_token'] = $tokenArr['access_token']; //推荐使用redis去存数据
                $_SESSION['expire_time'] = time() + $tokenArr['expires_in'];
//            RedisHelper::SetString(ACCESS_TOKEN,$tokenArr['access_token'],$tokenArr['expires_in']);
                $accessToken = $tokenArr['access_token'];
            }
            return $accessToken;
        }
    }

    /**
     * 发送模板消息
     * @param $openid
     * @param $template_id
     * @param $data
     * @param null $url
     * @return bool|mixed
     */
    public function sendTemplate($openid, $template_id, $data, $url = null)
    {
        $accessToken = $this->getAccessToken();
        if(!$accessToken) {
            return false;
        }
        $msgArr["touser"] = $openid;
        $msgArr["template_id"] = $template_id;
        $msgArr["data"] = $data;
        if(!empty($url)) {
            $msgArr["url"] = $url;
        }
        $msgJson = json_encode($msgArr);
        self::$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$accessToken;

        $result = self::curlRequest(self::$url,$msgJson);
        return json_decode($result,true);

    }

    /**
     * 处理模板文本
     * @param $openid
     * @param $data
     * @param int $type
     * @param null $url
     * @return bool
     */
    public static function setTemplate($openid,$data,$type=1,$url = null)
    {
        $data = json_decode($data,true);
        $templete_id = '';
        $tem_data = [];
        switch ($type){
            case 1:
                $templete_id = ''; // 根据type去读取配置的模板id
                $tem_data = [
                    'first'=> ['value'=>'您好，欢迎注册沈唁志！'],
                    'keyword1'=> ['value'=>$data['nickname']],
                    'keyword2'=> ['value'=>$data['tel']],
                    'remark'=> ['value'=>'沈唁博客(qq52o.me)是关注PHP开发等技术的个人博客，同时是个人程序人生的点滴记录和时光储备。'],
                ];
                break;
//                ....其他模板消息信息

        }
        $data['openid'] = $openid;
        $data['template_id'] = $templete_id;
        $data['data'] = $tem_data;
        $data['url'] = $url;
        return RedisHelper::SetMessage(WX_MESSAGE,$data);
    }

    /**
     * 群发消息
     * @param $msgJson
     * @return bool
     */
    public function sendMsg($msgJson)
    {
        $accessToken = $this->getAccessToken();
        if(!$accessToken) {
            return false;
        }
        self::$url = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token='.$accessToken;

        $result = self::curlRequest(self::$url,$msgJson);
        $result = json_decode($result,true);
        if($result['errcode'] == 0) {
            return true;
        }
        return $result['errmsg'];

    }

    /**
     * 发送客服消息
     * @param $msgJson
     * @return bool
     */
    public function sendServiceMsg($msgJson)
    {
        $accessToken = $this->getAccessToken();
        if(!$accessToken) {
            return false;
        }
        self::$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$accessToken;
        $result = self::curlRequest(self::$url,$msgJson);
        $result = json_decode($result,true);

        return $result;
    }

    public function getFans($nextOpenid)
    {
        $accessToken = $this->getAccessToken();
        if(!$accessToken) {
            return false;
        }
        self::$url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$accessToken.'&next_openid='.$nextOpenid;
        $result = self::curlRequest(self::$url);
        $result = json_decode($result,true);
        return $result;
    }


}