<?php

namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MainController extends AdminbaseController
{

    public function index()
    {

        $mysql = M()->query("select VERSION() as version");
        $mysql = $mysql[0]['version'];
        $mysql = empty($mysql) ? L('UNKNOWN') : $mysql;


        $courseNum = D("course")->count();
        $cardNum = D("card")->count();
        $feeSum = D("user_card")->where("openid!='opVb9wKsqcutvf2PSzQjNu-I3JQE'")->sum("cashfee");
        $userNum = D("oauth_user")->count();
        $payUserNum = D("user_card")->where("cashfee!=0")->count("id");
        $wxpayfeeSum = D("user_card")->where("transactionid!='' and openid!='opVb9wKsqcutvf2PSzQjNu-I3JQE'")->sum("cashfee");

        $xjpayfeeSum = D("user_card")->where("transactionid ='' and cashfee!=0 and openid!='opVb9wKsqcutvf2PSzQjNu-I3JQE'")->sum("cashfee");

        $this->assign('courseNum', $courseNum);
        $this->assign('cardNum', $cardNum);
        $this->assign('feeSum', $feeSum / 100);
        $this->assign('userNum', $userNum);
        $this->assign('cardNum', $payUserNum);
        $this->assign('wxpayfeeSum', $wxpayfeeSum / 100);
        $this->assign('xjpayfeeSum', $xjpayfeeSum / 100);


        //server infomaions
        $info = array(
            L('OPERATING_SYSTEM') => PHP_OS,
            L('OPERATING_ENVIRONMENT') => $_SERVER["SERVER_SOFTWARE"],
            L('PHP_VERSION') => PHP_VERSION,
            L('PHP_RUN_MODE') => php_sapi_name(),
            L('PHP_VERSION') => phpversion(),
            L('MYSQL_VERSION') => $mysql,
            L('PROGRAM_VERSION') => ThinkCMF_VERSION . "&nbsp;&nbsp;&nbsp; [<a href='http://www.gmqj.com' target='_blank'>冠美奇迹</a>]",
            L('UPLOAD_MAX_FILESIZE') => ini_get('upload_max_filesize'),
            L('MAX_EXECUTION_TIME') => ini_get('max_execution_time') . "s",
            L('DISK_FREE_SPACE') => round((@disk_free_space(".") / (1024 * 1024)), 2) . 'M',
        );
        $this->assign('server_info', $info);
        $this->display();
    }

    public function check()
    {
        $checkRe = D("user_card_course")->join('jmqjcourse ON jmqjcourse.id = jmqjuser_card_course.courseid')
            ->join('jmqjoauth_user ON jmqjoauth_user.id = jmqjuser_card_course.userid')->order("jmqjcourse.cday DESC")->where(array("jmqjuser_card_course.ischeck" => 1))->field('jmqjuser_card_course.id id,jmqjcourse.cname cname,jmqjcourse.cstime cstime,jmqjcourse.cetime cetime,jmqjuser_card_course.ischeck ischeck,jmqjuser_card_course.check_time check_time,jmqjoauth_user.name username,jmqjoauth_user.true_name truename')->select();
        $this->assign('checkRe', $checkRe);
        $this->display();
    }

    public function checkIn($id)
    {
        $userCourse = D("user_card_course")->find($id);
        if ($userCourse) {
            D("user_card_course")->where("id=" . $id)->save(array("ischeck" => 1, "check_time" => date("Y-m-d H:i:s")));
            $this->success("签到成功！");
        } else {
            $this->error("非法请求");
        }
    }
}