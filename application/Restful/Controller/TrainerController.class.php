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


   
}