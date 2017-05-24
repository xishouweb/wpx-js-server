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
        $teachers = $this->teacher_model->where("state=1")->select();
        $this->ajaxReturn(array("data" => $teachers, "status" => true), "JSON");
    }

    private function courseList($userId)
    {
        $course = $this->course_model->where(array("cday" => $day))->select();
        $allCourse = array();
        foreach ($course as $c) {
            if(strtotime($day)==strtotime(date("Y-m-d"))){
                if(strtotime($c['cstime']) < time()){
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

   
}