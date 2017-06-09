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

class TrainerController extends AdminbaseController
{

    protected $course_model;
    protected $term_relationships_model;
    protected $teacher_model;

    function _initialize()
    {
        $this->teacher_model = D("Teacher");
    }

    // 后台文章管理列表
    public function teachers()
    {
        $teachers = $this->teacher_model->where("state=1")->order("orders DESC")->select();
        $this->ajaxReturn(array("data" => $teachers, "status" => true), "JSON");
    }

    public function teacher($id)
    {
        $teacher = $this->teacher_model->where("state=1")->find($id);
        $newTimes=$this->times($id, date("Y-m-d"));
        $this->ajaxReturn(array("data" => array("trainer" => $teacher, "times" => $newTimes), "status" => true), "JSON");
    }

    public function teacherTime($teacherid, $day)
    {
        $newTimes=$this->times($teacherid, $day);
        $this->ajaxReturn(array("data" => $newTimes, "status" => true), "JSON");
    }

    private function times($teacherid, $day){
        $times = D("time")->order("stime asc")->select();
        $newTimes = array();
        foreach ($times as $time) {
            $ut = D("user_teacher")->where(array("teacherid" => $teacherid, "timeid" => $time['id'], "cdate" => $day))->select();
            $state = false;
            if (!empty($ut)) {
                $state = true;
            }
            $ntime = array("state" => $state, "id" => $time['id'], "etime" => date("H:i", strtotime($time['etime'])), "stime" => date("H:i", strtotime($time['stime'])));
            array_push($newTimes, $ntime);
        }
        return $newTimes;
    }




//    私教卡
    public function useCards($userid)
    {
        $user = D("oauth_user")->find($userid);
        $ucs = D("user_card")->where(array('openid' => $user['openid']))->select();
        $cards = array();
        foreach ($ucs as $uc) {
            $card = D("card")->find($uc['cardid']);
            if ($card['cftype'] == 3) {
                if (date("Y-m-d", strtotime($uc["expire_time"])) > date("Y-m-d") || date("Y-m-d", strtotime($uc["expire_time"])) == date("Y-m-d")) {
                    if ($uc["use_number"] != -1) {
                        if ($uc["use_number"] > 0) {
                            $newUc = array();
                            $newUc["id"] = $uc["cardid"];
                            $newUc["name"] = $card["cname"];
                            $newUc["usetimes"] = $uc["use_number"];
                            $newUc["day"] = date('Y-m-d', strtotime($uc["expire_time"]));;
                            array_push($cards, $newUc);
                        }
                    } else {
                        $newU = array();
                        $newU["id"] = $uc["cardid"];
                        $newU["name"] = $card["cname"];
                        $newU["usetimes"] = $uc["use_number"];
                        $newU["day"] = date('Y-m-d', strtotime($uc["expire_time"]));;
                        array_push($cards, $newU);
                    }
                }
            }
        }
        $this->ajaxReturn(array("data" => $cards, "status" => true), "JSON");
    }

}