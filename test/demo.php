<?php
/**
 * author：沈唁
 * link：https://qq52o.me
 */
include_once './../src/WeChat.php';

use syrecords\WeChat;

//初始化WeChat
$appid = ''; //微信公众号appid
$appKey = ''; //微信公众号appkey
$wechat = new WeChat($appid,$appKey);

$openid = ''; //用户的openid
$template_id = ''; // 模板id
$message = ''; //组装的消息 是个数组
$url = 'http://qq52o.me' ? 'http://qq52o.me' : null; // 跳转的url
$res = $wechat->sendTemplate($openid, $template_id, $message, $url);
