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

    public function initTeacher($userid)
    {
        $user = D("oauth_user")->find($userid);
        $teacher = D("teacher")->where(array("openid" => $user['openid']))->find();
        if ($teacher) {
            $courses=$this->teacherRecords($teacher['id']);
            $this->ajaxReturn(array("courses"=>$courses,"trainer" => $teacher, "status" => true), "JSON");
        } else {
            $this->ajaxReturn(array("status" => false), "JSON");
        }
    }

    private function teacherRecords($teacherid)
    {
        $courses = D("user_card_course")->where(array("teacherid" => $teacherid))->select();
        $newCourses = array();
        foreach ($courses as $cs) {
            $js = array();
            if ($cs['courseid'] == "0") {
                $time = D("time")->find($cs['timeid']);
                $startTime = strtotime($cs['cdate'] . " " . $time['stime']);
                $endTime = strtotime($cs['cdate'] . " " . $time['etime']);
                $js['cday'] = $cs['cdate'];
                $teacher = D('teacher')->where(array("id" => $cs["teacherid"]))->find();
                $js['cname'] = $teacher['tname'] . "的私教课";
                $js['id'] = $cs['id'];
            } else {
                $course = D("course")->find($cs['courseid']);
                $startTime = strtotime($course['cday'] . " " . $course['cstime']);
                $endTime = strtotime($course['cday'] . " " . $course['cetime']);
                $js['cday'] = $course['cday'];
                $teacher = D('teacher')->where(array("id" => $course["teacher"]))->find();
                $js['cname'] = $course['cname'];
                $js['id'] = $cs['courseid'];
            }
            $js["teacher"] = $teacher["tname"];
            $js["icon"] = $teacher["headimg"];
            $cTime = time();
            if ($cTime < $startTime || $cTime == $startTime) {
                $js['state'] = 0;// 未开始
            }
            if ($cTime < $endTime && $cTime > $startTime) {
                $js['state'] = 1;//进行中
            }
            if ($cTime > $endTime || $cTime == $endTime) {
                $js['state'] = 2;//结束了
            }
            $js['cstime'] = date('H:i', $startTime);
            $js['cetime'] = date('H:i', $endTime);

            array_push($newCourses, $js);
        }

        $aaa = array();
        for ($i = 0; $i < count($newCourses); $i++) {
            $aaa[$newCourses[$i]['cday'] . " " . $newCourses[$i]['cstime']] = $i;
        }
        krsort($aaa);
        $nn = array();
        foreach ($aaa as $k => $v) {
            array_push($nn, $newCourses[$v]);
        }
        return $nn;
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
        $newTimes = $this->times($id, date("Y-m-d"));
        $this->ajaxReturn(array("data" => array("trainer" => $teacher, "times" => $newTimes), "status" => true), "JSON");
    }

    public function teacherTime($teacherid, $day)
    {
        $newTimes = $this->times($teacherid, $day);
        $this->ajaxReturn(array("data" => $newTimes, "status" => true), "JSON");
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