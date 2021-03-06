<?php
// +----------------------------------------------------------------------
// | 冠美奇迹 [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.冠美奇迹.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
/**
 * 修改日期：2013-12-11
 */

namespace Restful\Controller;

use Restful\Common\SendMNS;
use Common\Controller\AdminbaseController;


class OauthController extends AdminbaseController
{

    private $appId;
    private $appSecret;

    public function _initialize()
    {
        $wxapp = D("Wxapp");
        $app = $wxapp->where(array("isuse" => 1))->find();
        $this->appId = $app["appid"];
        $this->appSecret = $app["appsecret"];
    }

    public function login($sourceUrl)
    {
        $codeUrl = urlencode('http://' . $_SERVER['SERVER_NAME'] . "/index.php?g=Restful&m=Oauth&a=wechatUser");
        $tz = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->appId . "&redirect_uri=" . $codeUrl . "&response_type=code&scope=snsapi_base&state=" . $sourceUrl;
        header("Location:" . $tz);
    }

    public function wechatJs($url)
    {
        header("Access-Control-Allow-Origin: *");
        session_start();
        if (!isset($_REQUEST['url'])) {
            echo "invalid arguments";
            exit;
        }
        $url = urldecode($url);
        $signPackage = $this->getSignPackage($url);
        $signPackage['jsApiList'] = array('checkJsApi', 'chooseImage',
            'onMenuShareTimeline', 'onMenuShareAppMessage',
            'onMenuShareQQ', 'previewImage', 'uploadImage',
            'downloadImage', 'getNetworkType', 'startRecord', 'stopRecord', 'onVoiceRecordEnd'
        , 'playVoice', 'pauseVoice', 'stopVoice', 'onVoicePlayEnd', 'uploadVoice');
        echo json_encode($signPackage);
    }


    public function wechatUser($code, $state)
    {
        //http://www.wexue.top:11111/index.php?g=Restful&m=Oauth&a=wechatUser
        //http://www.wexue.top:11111/index.php?g=Restful&m=Oauth&a=wechatUser&code=222&state=333
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->appId . '&secret=' . $this->appSecret . '&code=' . $code . '&grant_type=authorization_code';
        $user = null;
        $id = null;
        try {
            $result = $this->httpGet($url);
            $obj = json_decode($result);
            $openid = $obj->openid;

            $user = D("Oauth_user")->where(array("openid" => $openid))->find();
            if (empty($user)) {
                $id = D("Oauth_user")->add(array("openid" => $openid));
            } else {
                $id = $user["id"];
            }
        } catch (Exception $e) {
            echo $e->getTraceAsString();
        }
        $surl = urldecode($state);
        header("Location:" . $surl . "?u=" . $id);
    }

    public function userInfo($u)
    {
        $user = D("Oauth_user")->find($u);
        if (empty($user["create_time"])) {
            $at = $this->getAccessToken();
            $userInfo = $this->httpGet("https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $at . "&openid=" . $user['openid'] . "&lang=zh_CN");
            $uf = json_decode($userInfo, true);
            if (intval($uf["subscribe"]) == 0) {
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode(array('status' => false)));
            }
            $ufarray = array(
                'from' => "手机微信",
                'status' => 1,
                'name' => $this->jsonName($uf['nickname']),
                'openid' => $uf['openid'],
                'head_img' => $uf['headimgurl'],
                'access_token' => $at,
                'create_time' => date("Y-m-d H:i:s"),
                'last_login_time' => date("Y-m-d H:i:s"),
                'last_login_ip' => get_client_ip(0, true),
                'sex' => $uf['sex'],
                'city' => $uf['city'],
                'country' => $uf['country'],
                'province' => $uf['province'],
                'language' => $uf['language'],
                'subscribe_time' => $uf['subscribe_time'],
                'unionid' => $uf['unionid'],
                'remark' => $uf['remark'],
                'groupid' => $uf['groupid']
            );
            $result = D("Oauth_user")->where(array("id" => $u))->save($ufarray);
            $user = D("Oauth_user")->find($u);
        } else {
            D("Oauth_user")->where(array('id' => $u))->save(array("last_login_time" => date("Y-m-d H:i:s"), "last_login_ip" => get_client_ip(0, true), "login_times" => ($user["login_times"] + 1)));
        }
        header('Content-Type:application/json; charset=utf-8');
        $user["create_time"] = date("Y-m-d", strtotime($user["create_time"]));
        exit(json_encode(array('data' => $user, 'status' => true)));
    }

    private function jsonName($str)
    {
        if ($str) {
            $tmpStr = json_encode($str);
            $tmpStr2 = preg_replace("#(\\\ud[0-9a-f]{3})#ie", "", $tmpStr);
            $return = json_decode($tmpStr2);
            if (!$return) {
                return jsonName($return);
            }
        } else {
            $return = '微信用户-' . time();
        }
        return $return;
    }

    private function getSignPackage($url)
    {
        $jsapiTicket = $this->getJsApiTicket();
        if (!$url) {
            $url = "http://$_SERVER[HTTP_HOST]" . '/';
        }
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId" => $this->appId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getFileName($name)
    {
        //$fileName = sys_get_temp_dir() . "/weixin-" . $name;
        $fileName = dirname(__FILE__) . "/weixin-" . $name;
        return $fileName;
    }

    private function saveData($name, $content)
    {
        $fileName = $this->getFileName($name);

        $fp = fopen($fileName, "wb+");
        if ($fp) {
            fwrite($fp, $content);
            fclose($fp);
        }

        return;
    }

    private function loadData($name, $defaultContent)
    {
        $fileName = $this->getFileName($name);
        $content = file_get_contents($fileName);

        if ($content) {
            return $content;
        } else {
            return $defaultContent;
        }
        return;
    }

    private function getJsApiTicket()
    {
        $data = json_decode($this->loadData("/opt/server/cache/wfj_jsapi_ticket.json", '{"jsapi_ticket":"", "expire_time":0}'));
        if ($data->expire_time < time()) {
            $accessToken = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url));
            $ticket = $res->ticket;
            if ($ticket) {
                $data->expire_time = time() + 3600;
                $data->jsapi_ticket = $ticket;
                $this->saveData("/opt/server/cache/zz_jsapi_ticket.json", json_encode($data));
            }
        } else {
            $ticket = $data->jsapi_ticket;
        }
        return $ticket;
    }

    private function getAccessToken()
    {
        $data = json_decode($this->loadData("/opt/server/cache/zz_access_token.json", '{"access_token":"", "expire_time": 0}'));

        if ($data->expire_time < time()) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode($this->httpGet($url));
            if (!isset($res->access_token)) {
                print_r($res);
                exit;
            }

            $access_token = $res->access_token;
            if ($access_token) {
                $data->expire_time = time() + 3000;
                $data->access_token = $access_token;
                $this->saveData("/opt/server/cache/zz_access_token.json", json_encode($data));
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;
    }


    function curl_post_ssl($url, $vars, $second = 30, $aHeader = array())
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }

    private function times($teacherid, $day)
    {
        $today = false;

        if (strtotime($day) == strtotime(date("Y-m-d") . "")) {
            $today = true;
        }
        $times = D("time")->order("stime asc")->select();
        $newTimes = array();
        foreach ($times as $time) {
            if ($today && strtotime($time['stime']) < time()) {
                continue;
            }
            $ut = D("user_card_course")->where(array("teacherid" => $teacherid, "timeid" => $time['id'], "cdate" => $day))->find();
            $state = 0;
            $userheadimg = "";
            if (!empty($ut)) {
                if ($ut['courseid'] == "0") {
                    $user = D("oauth_user")->find($ut['userid']);
                    $userheadimg = $user['head_img'];
                    $state = 1;
                }
                $cou = D("course")->where(array("teacher" => $teacherid, "timeid" => $time['id'], "cday" => $ut['cdate']))->find();
                if ($cou) {
                    $state = 2;
                }
            }
            $ntime = array("state" => $state, "userimg" => $userheadimg, "id" => $time['id'], "etime" => date("H:i", strtotime($time['etime'])), "stime" => date("H:i", strtotime($time['stime'])));
            array_push($newTimes, $ntime);
        }
        return $newTimes;
    }

    //约私教
    //这里的cardid是user_card的id
    public function orderTeacher($userid, $teacherid, $date, $timeid, $cardid)

    {
        $ut = D("user_card_course")->where(array("userid" => $userid, "cdate" => $date, "timeid" => $timeid))->select();
        if ($ut) {
            $this->ajaxReturn(array("status" => false, 'msg' => '你的时间只有一份，可是想约了两个教练？！'), "JSON");
            return;
        }
        $user = D("oauth_user")->find($userid);
        $teahcer = D("teacher")->find($teacherid);
        $time = D("time")->find($timeid);
        $ucs = D("user_card")->where(array("id" => $cardid))->find();
        $card = D("card")->find($ucs['cardid']);
        if ($user && $card && $teahcer && $time && $ucs) {
            //添加用户约哪个教练
            $utid = D("user_card_course")->add(array("userid" => $userid, "courseid" => "0", "teacherid" => $teacherid, "cdate" => $date, "timeid" => $timeid, "cardid" => $cardid, "create_time" => date("Y-m-d H:i:s")));
            //去除卡的次数
            //月卡0:不减1 ,次卡1 :减1
            if (intval($card["ctype"]) == 1 && intval($ucs["use_number"]) != 0) {
                $number = intval($ucs["use_number"]) - 1;//当前次数
                D("user_card")->where(array("id" => $cardid))->save(array("use_number" => $number));
            }
            $ucid = $ucs['id'];
            $times = $this->times($teacherid, $date, $userid);
            $this->sendMsg($user['openid'], "预约私教成功！\n您预约了" . $teahcer['sign'] . " " . $teahcer['tname'] . "的私教\n时间：" . $date . "\n" . date("H:i", strtotime($time['stime'])) . "到" . date("H:i", strtotime($time['etime'])) . "\n取消预约点击 <a href='http://www.gm-fitness.com:8080/vip'>残忍取消</a> 到健身记录中取消");
            $this->ajaxReturn(array("times" => $times, "status" => true, 'msg' => '预约成功啦'), "JSON");
        } else {
            $this->ajaxReturn(array("status" => false, 'msg' => '非法ID'), "JSON");
        }
    }


    public function cancelOrderCourse($userId, $courseId)
    {
        $ucc = D("user_card_course")->where(array("userid" => $userId, "courseid" => $courseId))->find();
        $u = D("oauth_user")->find($userId);
        $uc = D("user_card")->where(array("id" => $ucc["cardid"]))->find();
        $c = D("course")->find($courseId);
        if ($ucc && $u && $c && $uc) {
            //月卡0:不减1 ,次卡1 :减1
            if (intval($uc["use_number"]) != -1) {
                $number = intval($uc["use_number"]) + 1;//当前次数
                D("user_card")->where(array('id' => $uc['id']))->save(array("use_number" => $number));
            }
            $ccpeople = $c["ccpeople"] - 1;
            D("course")->where(array("id" => $c["id"]))->save(array("ccpeople" => $ccpeople));
            //删除user_course
            D("user_card_course")->delete($ucc["id"]);
            D("user_teacher")->delete($ucc["user_teacher_id"]);
            $this->sendMsg($uc['openid'], "取消预约团课成功！");
            $jsr = $this->jsRecords($userId);
            $this->ajaxReturn(array("status" => true, "msg" => "取消成功", "data" => $jsr), "JSON");
        } else {
            if (empty($c)) {
                $this->cancelOrderTeacher($courseId);
            }
            $this->ajaxReturn(array("status" => false, "msg" => "非法ID"), "JSON");
        }


    }

    private function cancelOrderTeacher($utid)
    {
        $ut = D("user_card_course")->find(intval($utid));
        if (empty($ut)) {
            $this->ajaxReturn(array("status" => false, 'msg' => '没约过这个教练的时间啊'), "JSON");
        } else {
            $user = D("oauth_user")->find($ut['userid']);
            $uc = D("user_card")->where(array("id" => $ut['cardid']))->find();
            $card = D("card")->find($uc["cardid"]);
            if (intval($card["ctype"]) == 1 && intval($uc["use_number"]) != 0) {
                $number = intval($uc["use_number"]) + 1;//当前次数
                D("user_card")->where(array("id" => $uc['id']))->save(array("use_number" => $number));
            }
            $this->sendMsg($uc['openid'], "取消预约私教成功！");
            D("user_card_course")->delete($utid);
            $jsr = $this->jsRecords($ut['userid']);
            $this->ajaxReturn(array("status" => true, 'msg' => '取消预约私教成功', 'data' => $jsr), "JSON");
        }

    }


    //发送客服消息
    private function sendMsg($openid, $text)
    {
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $access_token;
        $data = '{
        "touser":"' . $openid . '",
        "msgtype":"text",
        "text":
        {
             "content":"' . $text . '"
        }
    }';
        $result = $this->https_post($url, $data);
//        echo(json_encode($result));
    }

    public function sendTemMsgForClassReminder($openid, $courseId, $userId)
    {
        $course = D("course")->find($courseId);
        $teacher = D("teacher")->find($course["teacher"]);
        $msg = '{
           "touser":"' . $openid . '",
           "template_id":"ASBkD_w71U8bFY0fQNestJx_yYIqXxJC0rsyxarlQhs",
           "url":"http://gm.wujiesheying.com:8080/vip",  
           "miniprogram":{
             "appid":"wx1d82d29572bf34e8",
             "pagepath":"pages/main/index?cid=' . $userId . '"
           },      
           "data":{
            "first": {
                "value":"亲爱的,您预约的今天的课程要开始了!\n",
                       "color":"#173177"
                   },
                   "keyword1":{
                "value":"' . $course["cname"] . '  ' . date("H:i", strtotime($course['cstime'])) . "-" . date("H:i", strtotime($course['cetime'])) . '\n",
                       "color":"#FF0000"
                   },
                   "keyword2": {
                "value":"' . $teacher["tname"] . '",
                       "color":"#173177"
                   },
                   "keyword3": {
                "value":"北京市朝阳区观湖国际壹座1709",
                       "color":"#173177"
                   },
                   "remark":{
                "value":"\n冠美奇迹欢迎您的光临\n如有任何疑问，请直接在公众号内留言\n\n ----GM Fitness\n\n点击详情打开签到小程序",
                       "color":"#38d4d6"
                   }
           }
       }';

        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $this->getAccessToken();
        $result = $this->https_post($url, $msg);
        D("wxmsg")->add(array("userid" => $userId, "courseid" => $course["id"], "msg_time" => date("Y-m-d H:i:s"), "type" => "上课提醒", "msg_content" => $msg));

        echo(json_encode($result));
    }

    public function classReminder()
    {
        //查看今天所有课程
        $courses = D("course")->where(array("cday" => date("Y-m-d")))->select();

        foreach ($courses as $course) {
            $cstime = strtotime($course['cstime']);
            $currtime = time();
            $cc = $cstime - $currtime;
            if ($cc < 60 * 60 && $cc > 5 * 60) {
                $ucc = D("user_card_course")->where(array("courseid" => $course["id"]))->select();

                foreach ($ucc as $uc) {
                    $re = D("wxmsg")->where(array("userid" => $uc["userid"], "courseid" => $course["id"], "type" => "上课提醒"))->find();
                    if ($re) {
                        echo "发送过了";
                        continue;
                    }
                    $user = D("oauth_user")->find($uc["userid"]);
                    $this->sendTemMsgForClassReminder($user["openid"], $course["id"], $uc["userid"]);
                }
            }
        }
    }

//恭喜您购买会籍合约成功！
//服务类型：会籍合约
//服务商品名称：北京68元超值体验
//俱乐部名称：北京LG店
//欢迎加入健身大家庭。很开心看到您启动了健康幸福生活的按钮，让我们一起运动改变生活！
    public function sendTemMsgForWxPay($openid, $cardId)
    {
        $user = D("oauth_user")->field("id")->where(array("openid" => $openid))->find();
        $card = D("card")->find($cardId);
        $card_type = D("course_type")->find($card["cftype"]);
        $msg = '{
           "touser":"' . $openid . '",
           "template_id":"3U_ChtNt0ZNjvFPm-2NWuKR65OuzFVYpi4NoRpbOgYE",
           "url":"http://gm.wujiesheying.com:8080/vip",     
           "data":{
            "first": {
                "value":"亲爱的,您的消费卡已经揣进了自己的腰包\n",
                       "color":"#173177"
                   },
                   "keyword1":{
                       "value":"' . $card_type["tname"] . '",
                       "color":"#FF0000"
                   },
                   "keyword2": {
                    "value":"' . $card["cname"] . '",
                       "color":"#FF0000"
                   },
                   "keyword3": {
                    "value":"冠美奇迹私教俱乐部",
                       "color":"#173177"
                   },
                   "remark":{
                "value":"\n冠美奇迹欢迎您的光临\n如有任何疑问，请直接在公众号内留言\n\n ----GM Fitness",
                       "color":"#38d4d6"
                   }
           }
       }';

        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $this->getAccessToken();
        var_dump($msg);
        $result = $this->https_post($url, $msg);

        D("wxmsg")->add(array("userid" => $user["id"], "cardid" => $cardId, "msg_time" => date("Y-m-d H:i:s"), "type" => "购买消费卡成功", "msg_content" => $msg));

        echo(json_encode($result));
    }

    //发送验证码
    public function sendCheckShartMsg($tel, $userid)
    {
        $code = rand(1000, 9999);
        D("oauth_user")->save(array("id" => $userid, "checkcode" => $code));
        $sms = new SendMNS();
        //测试模式
        $status = $sms->send_verify($tel, $code);
        if (!$status) {
            echo $sms->error;
        }
        echo 'success';

    }

    private function https_post($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            return 'Errno' . curl_error($curl);
        }
        curl_close($curl);
        return $result;
    }

    private function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

}