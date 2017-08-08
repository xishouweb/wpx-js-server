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
            $courses = $this->tuankeRecords($teacher['id']);
            $this->ajaxReturn(array("courses" => $courses, "trainer" => $teacher, "status" => true), "JSON");
        } else {
            $this->ajaxReturn(array("status" => false), "JSON");
        }
    }

    public function sijiaos($userid)
    {
        $user = D("oauth_user")->find($userid);
        $teacher = D("teacher")->where(array("openid" => $user['openid']))->find();
        if ($teacher) {
            $courses = $this->sijiaoRecords($teacher['id']);
            $this->ajaxReturn(array("data" => $courses, "status" => true), "JSON");
        } else {
            $this->ajaxReturn(array("status" => false), "JSON");
        }
    }

    public function tuankes($userid)
    {
        $user = D("oauth_user")->find($userid);
        $teacher = D("teacher")->where(array("openid" => $user['openid']))->find();
        if ($teacher) {
            $courses = $this->tuankeRecords($teacher['id']);
            $this->ajaxReturn(array("data" => $courses, "status" => true), "JSON");
        } else {
            $this->ajaxReturn(array("status" => false), "JSON");
        }
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
            }
            $cou = D("course")->where(array("teacher" => $teacherid, "timeid" => $time['id'], "cday" => $day))->find();
            if ($cou) {
                $state = 2;
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
            //精品私教课 id是3 不可更改
            if ($card['cftype'] == 3) {
                if (date("Y-m-d", strtotime($uc["expire_time"])) > date("Y-m-d") || date("Y-m-d", strtotime($uc["expire_time"])) == date("Y-m-d")) {
                    if ($uc["use_number"] != -1) {
                        if ($uc["use_number"] > 0) {
                            $newUc = array();
                            $newUc["id"] = $uc["id"];
                            $newUc["name"] = $card["cname"];
                            $newUc["usetimes"] = $uc["use_number"];
                            $newUc["day"] = date('Y-m-d', strtotime($uc["expire_time"]));;
                            array_push($cards, $newUc);
                        }
                    } else {
                        $newU = array();
                        $newU["id"] = $uc["id"];
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

    private function tuankeRecords($teacherid)
    {
//        $peosonCourses = D("user_card_course")->where("teacherid=" . $teacherid . " and courseid='0'")->select();
//        $newCourses = array();
//        foreach ($peosonCourses as $cs) {
//            $js = array();
//            $time = D("time")->find($cs['timeid']);
//            $startTime = strtotime($cs['cdate'] . " " . $time['stime']);
//            $endTime = strtotime($cs['cdate'] . " " . $time['etime']);
//            $js['cday'] = $cs['cdate'];
//            $teacher = D('teacher')->where(array("id" => $cs["teacherid"]))->find();
//            $js['cname'] = $teacher['tname'] . "的私教课";
//            $js['id'] = $cs['id'];
//            $js['cftype'] = "精品私教课";
//            $js['type'] = 0;//私教
//            $users = array();
//            $user = D("oauth_user")->find($cs['userid']);
//            array_push($users, $user);
//            $js['users'] = $users;
//
//            $js["teacher"] = $teacher["tname"];
//            $js["icon"] = $teacher["headimg"];
//            $cTime = time();
//            if ($cTime < $startTime || $cTime == $startTime) {
//                $js['state'] = 0;// 未开始
//            }
//            if ($cTime < $endTime && $cTime > $startTime) {
//                $js['state'] = 1;//进行中
//            }
//            if ($cTime > $endTime || $cTime == $endTime) {
//                $js['state'] = 2;//结束了
//            }
//            $js['cstime'] = date('H:i', $startTime);
//            $js['cetime'] = date('H:i', $endTime);
//
//            array_push($newCourses, $js);
//        }

        $courses = D("course")->where("teacher=" . $teacherid . " and ccpeople!=0")->order("cday DESC")->select();
        $newCourses = array();
        foreach ($courses as $course) {
            $c = array();
            $startTime = strtotime($course['cday'] . " " . $course['cstime']);
            $endTime = strtotime($course['cday'] . " " . $course['cetime']);
            $c['cday'] = $course['cday'];
            $c['cname'] = $course['cname'];
            $c['id'] = $course['id'];
            $type = D("course_type")->find($course['cftype']);
            $c['cftype'] = $type['tname'];
            $c['ccpeople'] = $course['ccpeople'];
            $users=array();
            $uccs=D("user_card_course")->where(array("courseid"=>$course['id']))->select();
            foreach ($uccs as $ucc){
                $u=array();
                $u['userid']=$ucc['userid'];
                $user=D("oauth_user")->find($ucc['userid']);
                $u['username']=$user['name'];
                $u['tel']=$user['tel'];
                $u['truename']=$user['true_name'];
                $u['headimg']=$user['head_img'];
                array_push($users,$u);
            }

            $c['users'] = $users;
            $cTime = time();
            if ($cTime < $startTime || $cTime == $startTime) {
                $c['state'] = "未开始";// 未开始
            }
            if ($cTime < $endTime && $cTime > $startTime) {
                $c['state'] = "进行中";//进行中
            }
            if ($cTime > $endTime || $cTime == $endTime) {
                $c['state'] = "结束了";//结束了
            }
            $c['cstime'] = date('H:i', $startTime);
            $c['cetime'] = date('H:i', $endTime);

            array_push($newCourses, $c);

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
        return $newCourses;
    }

    private function sijiaoRecords($teacherid)
    {
        $peosonCourses = D("user_card_course")->where("teacherid=" . $teacherid . " and courseid='0'")->group("userid")->select();
        $newCourses = array();
        foreach ($peosonCourses as $cs) {
            $js = array();
            $time = D("time")->find($cs['timeid']);
            $startTime = strtotime($cs['cdate'] . " " . $time['stime']);
            $endTime = strtotime($cs['cdate'] . " " . $time['etime']);
            $js['cday'] = $cs['cdate'];
            $js['userid']=$cs['userid'];
            $user = D("oauth_user")->find($cs['userid']);
            $js['username']=$user['name'];
            $js['truename']=$user['true_name'];
            $js['headimg']=$user['head_img'];
            $js['tel']=$user['tel'];
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

    //课程学员
    public function courseUser($id, $type)
    {
        $users = array();
        if ($type == 0) {
            //私教
            $ucc = D("user_card_course")->find($id);
            $user = D("oauth_user")->find($ucc['userid']);
            array_push($users, $user);
        }
        if ($type == 1) {
            $ucs = D("user_card_course")->where(array("courseid" => $id))->select();
            foreach ($ucs as $uc) {
                $u = D("oauth_user")->find($uc['userid']);
                array_push($users, $u);
            }
        }
        $this->ajaxReturn(array("data" => $users, "status" => true), "JSON");
    }
}