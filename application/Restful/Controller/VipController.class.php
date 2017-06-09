<?php
// +----------------------------------------------------------------------
// | 冠美奇迹 [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.冠美奇迹.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
namespace Restful\Controller;

use Common\Controller\AdminbaseController;
use Common\Lib\WxPay\JsApi_pub;
use Common\Lib\WxPay\UnifiedOrder_pub;
use Common\Lib\WxPay\Notify_pub;
use Restful\Controller\OauthController;


class VipController extends AdminbaseController
{

    protected $oauth_model;
    protected $user_course_model;

    function _initialize()
    {
        $this->oauth_model = D("Oauth_user");
        $this->user_course_model = D("user_card_course");
    }

    // 手机号绑定
    public function binding($tel, $truename, $openid)
    {
        $result = $this->oauth_model->where(array("openid" => $openid))->save(array('tel' => $tel, 'true_name' => $truename));
        $user = $this->oauth_model->where(array("openid" => $openid))->find();
        $user["create_time"] = date("Y-m-d", strtotime($user["create_time"]));
        if (empty($result)) {
            $this->ajaxReturn(array("status" => false), "JSON");
        } else {
            $this->ajaxReturn(array("status" => true, "data" => $user), "JSON");
        }
    }

    // 健身记录
    public function jsrecord($userId)
    {
        $jsr = $this->jsRecords($userId);
        $this->ajaxReturn(array("data" => $jsr), "JSON");
    }

    //今天的课程
    public function jsrecordByToDay($userid)
    {
        $courses = $this->user_course_model->join('jmqjcourse ON jmqjcourse.id = jmqjuser_card_course.courseid')->order("jmqjcourse.cday DESC")->where(array("jmqjuser_card_course.userid" => $userid, "jmqjcourse.cday" => date("Y-m-d")))->field('jmqjuser_card_course.id id,jmqjcourse.cname cname,jmqjcourse.cstime cstime,jmqjcourse.cetime cetime,jmqjuser_card_course.ischeck ischeck,jmqjuser_card_course.check_time check_time')->select();
        $this->ajaxReturn(array("data" => $courses), "JSON");
    }

    private function jsRecords($userId)
    {
        $courses = $this->user_course_model->join('jmqjcourse ON jmqjcourse.id = jmqjuser_card_course.courseid')->order("jmqjcourse.cday DESC")->where(array("jmqjuser_card_course.userid" => $userId))->select();
        $uts = D("user_teacher")->where(array("userid" => $userId))->select();
        $newCourses = array();
        foreach ($courses as $cs) {
            $c = D("course")->find($cs['courseid']);
            $startTime = strtotime($c['cday'] . " " . $c['cstime']);
            $endTime = strtotime($c['cday'] . " " . $c['cetime']);
            $cTime = time();
            if ($cTime < $startTime || $cTime == $startTime) {
                $c['state'] = 0;// 未开始
            }
            if ($cTime < $endTime && $cTime > $startTime) {
                $c['state'] = 1;//进行中
            }
            if ($cTime > $endTime || $cTime == $endTime) {
                $c['state'] = 2;//结束了
                continue;
            }
            $c['cstime'] = date('H:i', strtotime($c['cstime']));
            $c['cetime'] = date('H:i', strtotime($c['cetime']));
            $teacher = D('teacher')->where(array("id" => $c["teacher"]))->find();
            $c["teacher"] = $teacher["tname"];
            $c["icon"] = $teacher["headimg"];
            array_push($newCourses, $c);
        }
//        foreach ($uts as $ut) {
//            $u = array();
//            $teacher = D("teacher")->find($ut['teacherid']);
//            $u['icon'] = $teacher['headimg'];
//            $u['teacher'] = $teacher['tname'];
//            $u['cday'] = date("Y-m-d", strtotime($ut['cdate']));
//            $u['cname'] = $teacher['tname'] . "的私教课";
//
//            $time = D("time")->find($ut["timeid"]);
//            $startTime = strtotime($ut['date'] . " " . $time['stime']);
//            $endTime = strtotime($ut['date'] . " " . $time['etime']);
//            $cTime = time();
//            $u['cstime'] = date('H:i', $startTime);
//            $u['cetime'] = date('H:i', $endTime);
//            if ($cTime < $startTime || $cTime == $startTime) {
//                $u['state'] = 0;// 未开始
//            }
//            if ($cTime < $endTime && $cTime > $startTime) {
//                $u['state'] = 1;//进行中
//            }
//            if ($cTime > $endTime || $cTime == $endTime) {
//                $u['state'] = 2;//结束了
//                continue;
//            }
//            $u['id'] = $ut['id'];
//            array_push($newCourses, $u);
//        }
//        $aaa = array();
//        for ($i = 0; $i < count($newCourses); $i++) {
//            $aaa[$newCourses[$i]['cday'] . " " . $newCourses[$i]['cstime']] = $i;
//        }
//        krsort($aaa);
//        $nn = array();
//        foreach ($aaa as $k => $v) {
//            array_push($nn, $newCourses[$v]);
//        }
        return $newCourses;
    }

    public function cardlist()
    {
        $cardsMonth = array();
        $cardsTimes = array();
        $courseTypes = D("course_type")->where("state=1")->select();

        foreach ($courseTypes as $courseType) {
            $cardsT = D("card")->where(array("ctype" => 1, "cftype" => $courseType["id"], "state" => 1))->select();
            $cardsM = D("card")->where(array("ctype" => 0, "cftype" => $courseType["id"], "state" => 1))->select();
            if ($cardsT) {
                array_push($cardsTimes, array($courseType["tname"] => $cardsT));
            }
            if ($cardsM) {
                array_push($cardsMonth, array($courseType["tname"] => $cardsM));
            }
        }
        $cards = array("month" => $cardsMonth, "times" => $cardsTimes);
        $this->ajaxReturn(array("data" => $cards), "JSON");
    }

    public function buyCard($openid, $price, $cardname, $cardid)
    {
        $ucard = D("user_card")->where(array('openid' => $openid, 'cardid' => $cardid))->find();
        if ($ucard) {
            if ($ucard['use_number'] == -1 && strtotime($ucard['expire_time']) > time()) {
                $this->ajaxReturn(array("status" => false, 'msg' => '您已经购买过该时长卡,还能使用奥'), "JSON");
            }
            if ($ucard['use_number'] != 0 && strtotime($ucard['expire_time']) > time()) {
                $this->ajaxReturn(array("status" => false, 'msg' => '您已经购买过该次卡,还有' . $ucard['use_number'] . '次可用,不要重复购买'), "JSON");
            }
        }

        $jsApi = new JsApi_pub();
        $paytime = date("YmdHis");
        $orderid = date("YmdHis", time()) . rand(1000, 9999);
        $orderName = $cardname;

        $unifiedOrder = new UnifiedOrder_pub();

        $total_fee = intval($price, 10) * 100;
        $unifiedOrder->setParameter("openid", "$openid");//商品描述
        $unifiedOrder->setParameter("body", "$orderName");//商品描述
        $timeStamp = time();
        $unifiedOrder->setParameter("out_trade_no", "$orderid");//商户订单号
        $unifiedOrder->setParameter("total_fee", $total_fee);//总金额
        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
        $starttime = date("YmdHis", strtotime($paytime));
        $endtime = date("YmdHis", strtotime($paytime) + 360);
        $attach = $cardid;
        $unifiedOrder->setParameter("time_start", "$starttime");//交易起始时间
        $unifiedOrder->setParameter("time_expire", "$endtime");//交易结束时间
        $unifiedOrder->setParameter("attach", "$attach");//附加数据
//$unifiedOrder->setParameter("sub_mch_id", "1291693801");//子商户号
//$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
        $unifiedOrder->setParameter("product_id", "2323");//商品ID
        $prepay_result = $unifiedOrder->getPrepayId();
        $prepay_id = $prepay_result["prepay_id"];
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        $this->ajaxReturn(array("data" => json_decode($jsApiParameters), 'status' => true), "JSON");
    }

    /**
     * 通用通知接口demo
     * ====================================================
     * 支付完成后，微信会把相关支付和用户信息发送到商户设定的通知URL，
     * 商户接收回调信息后，根据需要设定相应的处理流程。
     * wx-scan-code-notify.php
     * 这里举例使用log文件形式记录回调信息。
     * <xml>
     * <appid><![CDATA[wx4069e1635ae1be38]]></appid>
     * <attach><![CDATA[{"starttime":"20170227232734","endtime":"20170227233334"}]]></attach>
     * <bank_type><![CDATA[CFT]]></bank_type>
     * <cash_fee><![CDATA[1]]></cash_fee>
     * <fee_type><![CDATA[CNY]]></fee_type>
     * <is_subscribe><![CDATA[Y]]></is_subscribe>
     * <mch_id><![CDATA[1381605302]]></mch_id>
     * <nonce_str><![CDATA[9wus7zqwdwaw8q59pum13nhv173bdely]]></nonce_str>
     * <openid><![CDATA[oDs6gwVdlvZhV2N3RIJKereAktKY]]></openid>
     * <out_trade_no><![CDATA[201702272327346473]]></out_trade_no>
     * <result_code><![CDATA[SUCCESS]]></result_code>
     * <return_code><![CDATA[SUCCESS]]></return_code>
     * <sign><![CDATA[961178E20C6AC200FF6D8C5A091F8F1A]]></sign>
     * <time_end><![CDATA[20170227232739]]></time_end>
     * <total_fee>1</total_fee>
     * <trade_type><![CDATA[JSAPI]]></trade_type>
     * <transaction_id><![CDATA[4007132001201702271587207403]]></transaction_id>
     * </xml>
     */
    public function wxnotify()
    {
        $notify = new Notify_pub();
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $xmlDocument = new \DOMDocument();
        $xmlDocument->loadXML($xml);
        $openidXml = $xmlDocument->getElementsByTagName('openid');
        $openid = $openidXml->item(0)->nodeValue;

        $transactionIdXml = $xmlDocument->getElementsByTagName('transaction_id');
        $transactionId = $transactionIdXml->item(0)->nodeValue;

        $cashFeeXml = $xmlDocument->getElementsByTagName('cash_fee');
        $cashFee = $cashFeeXml->item(0)->nodeValue;

        $timeEndXml = $xmlDocument->getElementsByTagName('time_end');
        $timeEnd = $timeEndXml->item(0)->nodeValue;
        $attach = $xmlDocument->getElementsByTagName('attach');
        $cardId = $attach->item(0)->nodeValue;

        $notify->saveData($xml);

        if ($notify->checkSign() == FALSE) {
            $notify->setReturnParameter("return_code", "FAIL");//返回状态码
            $notify->setReturnParameter("return_msg", "签名失败");//返回信息
        } else {
            $notify->setReturnParameter("return_code", "SUCCESS");//设置返回码
            $notify->setReturnParameter("appid", "wx4069e1635ae1be38");
            $notify->setReturnParameter("mch_id", "1381605302");
            $notify->setReturnParameter("nonce_str", "232fafwf323232323442");
            $notify->setReturnParameter("result_code", "SUCCESS");
        }
        $count = D("user_card")->where(array("transactionid" => $transactionId))->count();
        if ($count == 0) {
            $card = D("card")->find($cardId);
            $days = $card['cdays'];
            $expire = date("Y-m-d", strtotime("+" . $days . " day"));
            //用户累积消费
            $ou = D("oauth_user")->where(array("openid" => $openid))->find();
            $sum_fee = intval($ou["sum_fee"]) + intval($cashFee);
            D("oauth_user")->where(array("openid" => $openid))->save(array("sum_fee" => $sum_fee));
            //用户消费记录 卡关联
            D("user_card")->add(array("openid" => $openid, "cashfee" => $cashFee, "transactionid" => $transactionId, "cardid" => $cardId, "buy_time" => date('Y-m-d H:m:s'), 'use_number' => $card['ctimes'], 'expire_time' => $expire));
            $oc = new OauthController();
            $oc->sendTemMsgForWxPay($openid, $cardId);
        }
        $returnXml = $notify->returnXml();
        echo $returnXml;
    }


    //我已经购买的卡
    public function mycard($userid)
    {
        $user = D("oauth_user")->find($userid);
        $ucs = D("user_card")->where(array('openid' => $user['openid']))->select();
        $newUcs = array();
        foreach ($ucs as $uc) {
            $newUc = array();
            $card = D("card")->find($uc["cardid"]);
            $ct = D("course_type")->find($card['cftype']);
            $newUc["type"] = $ct["tname"];
            $newUc["id"] = $uc["cardid"];
            $newUc["name"] = $card["cname"];
            $newUc["usetimes"] = $uc["use_number"];
            $newUc["day"] = date("Y-m-d", strtotime($uc["expire_time"]));
            if (strtotime($uc["expire_time"]) < time()) {
                $newUc["day"] = "过期了";
            }
            array_push($newUcs, $newUc);
        }
        $this->ajaxReturn(array("data" => $newUcs), "JSON");
    }


    //添加体验卡
    public function getTry($u)
    {
        $cardId = "cd{417ed3d4-a686-144a-f86a-bfcde6533d6e}";
        $card = D("card")->find($cardId);
        $user = D("oauth_user")->find($u);
        if ($card && $user) {
            $openid = $user['openid'];
            $ouc = D("user_card")->where(array("openid" => $openid, "cardid" => $cardId))->count();
            if ($ouc != 0) {
                $this->ajaxReturn(array("status" => false, 'msg' => '已经领取过了,直接去约课吧'), "JSON");
            }
            $cashFee = $card['cprice'];
            $days = $card['cdays'];
            $expire = date("Y-m-d", strtotime("+" . $days . " day"));
            //用户累积消费
            $ou = D("oauth_user")->where(array("openid" => $openid))->find();
            $sum_fee = intval($ou["sum_fee"]) + intval($cashFee);
            D("oauth_user")->where(array("openid" => $openid))->save(array("sum_fee" => $sum_fee));
            //用户消费记录 卡关联
            D("user_card")->add(array("openid" => $openid, "cashfee" => $cashFee, "cardid" => $cardId, "buy_time" => date('Y-m-d H:i:s'), 'use_number' => $card['ctimes'], 'expire_time' => $expire));
            $this->ajaxReturn(array("status" => true, 'msg' => '领取成功'), "JSON");
        } else {
            $this->ajaxReturn(array("status" => false, 'msg' => '非法ID'), "JSON");
        }
    }

    //是否已经领取了
    public function ifTry($u)
    {
        $cardId = "cd{417ed3d4-a686-144a-f86a-bfcde6533d6e}";
        $user = D("oauth_user")->find($u);
        if ($user) {
            $openid = $user['openid'];
            $ouc = D("user_card")->where(array("openid" => $openid, "cardid" => $cardId))->count();
            if ($ouc != 0) {
                $this->ajaxReturn(array("status" => true, 'msg' => '已经领取过了,直接去约课吧'), "JSON");
            } else {
                $this->ajaxReturn(array("status" => false, 'msg' => '没有领过'), "JSON");
            }
        } else {
            $this->ajaxReturn(array("status" => false, 'msg' => '非法ID'), "JSON");
        }
    }



}