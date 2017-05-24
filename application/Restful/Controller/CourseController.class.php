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

class CourseController extends AdminbaseController
{

    protected $course_model;
    protected $term_relationships_model;
    protected $teacher_model;

    function _initialize()
    {
        $this->course_model = D("Course");
        $this->teacher_model = D("Teacher");
        $this->term_relationships_model = D("Portal/TermRelationships");
    }

    public function clist($day, $userId)
    {
        $allCourse = $this->courseList($day, $userId);
        $this->ajaxReturn(array("data" => $allCourse, "status" => true), "JSON");
    }

    public function courseTypes()
    {
        $courseTypes =D("course_type")->where(array("state"=>1))->select();
        $newcs=array();
        foreach($courseTypes as $courseType){
             $courseType["tdesc"]=str_replace("/data/upload/ueditor/","http://gm.wujiesheying.com/data/upload/ueditor/",$courseType["tdesc"]);
             array_push($newcs,$courseType);
        }
        $this->ajaxReturn(array("data" => $newcs, "status" => true), "JSON");
    }

    private function courseList($day, $userId)
    {
        $course = $this->course_model->where(array("cday" => $day))->select();
        $allCourse = array();
        foreach ($course as $c) {
            if (strtotime($day) == strtotime(date("Y-m-d"))) {
                if (strtotime($c['cstime']) < time()) {
                    continue;
                }
            }
            $c['cstime'] = date('H:i', strtotime($c['cstime']));
            $c['cetime'] = date('H:i', strtotime($c['cetime']));
            $teacher = $this->teacher_model->where(array("id" => $c["teacher"]))->find();
            $type = D("course_type")->where(array("id" => $c["ctype"]))->find();
            $c["teacher"] = $teacher["tname"];
            $c["ctype"] = $type["tname"];
            $uc = D("user_card_course")->where(array('courseid' => $c['id']))->select();
            $c["headimgs"] = array();
            $c["isOrder"] = 0;
            $c["icon"] = $type["icon"];
            $c["tag"] = $type["tag"];
            if ($uc) {
                foreach ($uc as $ucite) {
                    if ($ucite['userid'] == $userId) {
                        $c["isOrder"] = 1;
                    }
                    $user = D("Oauth_user")->field("head_img")->find($ucite['userid']);
                    array_push($c["headimgs"], $user['head_img']);
                }
            }
            if (intval($c["cpeople"] == intval($c["ccpeople"]))) {
                $c["isOrder"] = 2;
            }
            array_push($allCourse, $c);
        }
        return $allCourse;
    }

    public function order($userId, $courseId)
    {
        $courseResult = $this->course_model->find($courseId);
        $userResult = D('Oauth_user')->find($userId);
        $userCourseResult = D('user_course')->where(array('userid' => $userId, 'courseid' => $courseId))->find();
        if ($courseResult && $userResult && !$userCourseResult) {
            $re = D("user_course")->add(array('userid' => $userId, 'courseid' => $courseId));
            if ($re) {
                $course = D("course")->where(array('id' => $courseId))->find();
                if ($course['ccpeople'] > $course['cpeople']) {
                    $this->ajaxReturn(array("status" => false, "msg" => "人数满了"), "JSON");
                } else {
                    D("course")->where(array('id' => $courseId))->save(array('ccpeople' => ($course['ccpeople'] + 1)));
                    $this->ajaxReturn(array("status" => true, "courseId" => $courseId, "headimg" => $userResult['head_img']), "JSON");
                }
            } else {
                $this->ajaxReturn(array("status" => false, "msg" => "重复"), "JSON");
            }
        } else {
            $this->ajaxReturn(array("status" => false, "msg" => "非法ID"), "JSON");
        }
    }


    public function useCards($courseId, $userId)
    {
        $user = D("oauth_user")->find($userId);
        $course = D("course")->find($courseId);
        $ucs = D("user_card")->where(array('openid' => $user['openid']))->select();
        $cards = array();
        foreach ($ucs as $uc) {
            $card = D("card")->find($uc['cardid']);
            if ($course['ctype'] == $card['cftype']) {
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
        $this->ajaxReturn(array("data" => $cards), "JSON");
    }

    public function cardBuyCourse($userId, $courseId, $cardId)
    {
        $user = D("oauth_user")->find($userId);
        $card = D("card")->find($cardId);
        $course = D("course")->find($courseId);
        if (!($user && $card && $course)) {
            $this->ajaxReturn(array("status" => false, "msg" => "非法ID"), "JSON");
        }
        $uc = D("user_card_course")->where(array("userid" => $userId, "courseid" => $courseId))->count();
        if ($uc != 0) {
            $this->ajaxReturn(array("status" => false, "msg" => "该课程已经预约过了"), "JSON");
        }

        if ($course['ccpeople'] > $course['cpeople']) {
            $this->ajaxReturn(array("status" => false, "msg" => "人数满了"), "JSON");
        } else {
            D("course")->where(array('id' => $courseId))->save(array('ccpeople' => ($course['ccpeople'] + 1)));
        }


        $ucs = D("user_card")->where(array('openid' => $user['openid'], "cardid" => $cardId))->find();
        //月卡0:不减1 ,次卡1 :减1
        if (intval($card["ctype"]) == 1 && intval($ucs["use_number"]) != 0) {

            $number = intval($ucs["use_number"]) - 1;//当前次数
            D("user_card")->where(array('openid' => $user['openid'], "cardid" => $cardId))->save(array("use_number" => $number));
        }
        //用卡买课程
        D("user_card_course")->add(array("userid" => $userId, "cardid" => $cardId, "courseid" => $courseId, "create_time" => date("Y-m-d H:i:s")));
        //用卡买课程的记录
        D("user_card_course_record")->add(array("userid" => $userId, "cardid" => $cardId, "courseid" => $courseId, "create_time" => date("Y-m-d H:i:s")));
        $allCourse = $this->courseList($course["cday"], $userId);
        $this->ajaxReturn(array("status" => true, "data" => $allCourse, "msg" => "约课成功了"), "JSON");
    }


    //广告
    public function adList()
    {
        $slide_obj = M("Slide");
        $ads = $slide_obj->where("slide_status=1")->order("listorder ASC")->select();
        $adsNew = array();
        foreach ($ads as $ad) {
            $adNew = array();
            $adNew['id'] = $ad['slide_id'];
            $adNew['url'] = $ad['slide_url'];
            $adNew['img'] = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] . "/" . C("UPLOADPATH") . $ad['slide_pic'];
            array_push($adsNew, $adNew);
        }
        $this->ajaxReturn(array("status" => true, "data" => $adsNew), "JSON");
    }

    
}