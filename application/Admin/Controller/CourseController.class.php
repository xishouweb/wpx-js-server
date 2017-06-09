<?php
/**
 * 后台首页
 */

namespace Admin\Controller;

use Common\Controller\AdminbaseController;
use Org\Util\String;


class CourseController extends AdminbaseController
{

    private $course_model;
    private $teacher_model;


    public function _initialize()
    {

        parent::_initialize();
        $this->course_model = D("Course");
        $this->teacher_model = D("Teacher");
    }

    /**
     * 全部课程
     */
    public function alllist()
    {
        $count = $this->course_model->count();
        $page = $this->page($count, 20);
        $courses = $this->course_model
            ->order("cday DESC")
            ->limit($page->firstRow, $page->listRows)
            ->select();
        $newCourses = array();
        foreach ($courses as $course) {
            $course['cstime'] = date('H:i', strtotime($course['cstime']));
            $course['cetime'] = date('H:i', strtotime($course['cetime']));
            $teacher = $this->teacher_model->where(array('id' => $course['teacher']))->find();
            $course['teacher'] = $teacher['tname'];
            $type = D("course_type")->where(array("id" => $course["ctype"]))->find();
            $course["ctype"] = $type["tname"];
            array_push($newCourses, $course);
        }
        $this->assign("page", $page->show('Admin'));
        $this->assign("courses", $newCourses);
        $this->display();
    }

    public function course_users($id)
    {
        $uc = D("user_card_course")->where(array('courseid' => $id))->select();
        $users = array();
        foreach ($uc as $u) {
            $user = D("oauth_user")->find($u["userid"]);
            array_push($users, $user);
        }
        $this->assign("users", $users);
        $this->display();
    }

    public function add()
    {
        $teachers = $this->teacher_model->order("id ASC")->select();
        $ct = D("course_type")->order("id ASC")->select();
        $time = D("time")->order("stime ASC")->select();

        $this->assign("types", $ct);
        $this->assign("teachers", $teachers);
        $this->assign("times", $time);
        $this->display();
    }


    public function add_course()
    {
        if (IS_POST) {
            $timeid=$_POST['timeid'];
            $time = D("time")->find($timeid);
            $cstime = $time['stime'];
            $cetime = $time['etime'];
            $cday = $_POST['cday'];
            $id = "ce" . String::uuid();
            $course = array("id" => $id, "ctype" => $_POST['ctype'], "cname" => $_POST['cname'], "cday" => $cday, "cpeople" => $_POST['cpeople'], "cdesc" => $_POST['cdesc'], "cstime" => $cstime, "cetime" => $cetime, "teacher" => $_POST['teacher'], "create_time" => date("Y-m-d H:i:s"),"timeid"=>$timeid);
            $this->course_model->add($course);
            $this->success("添加成功！", U("course/alllist"));
        } else {
            $this->error("非法请求");
        }
    }

    public function edit_course()
    {
        if (IS_POST) {
            $timeid=$_POST['timeid'];
            $time = D("time")->find($timeid);
            $cstime = $time['stime'];
            $cetime = $time['etime'];
            $cday = $_POST['cday'];
            $course = array("ctype" => $_POST['ctype'], "cname" => $_POST['cname'], "cday" => $cday, "cpeople" => $_POST['cpeople'], "cdesc" => $_POST['cdesc'], "cstime" => $cstime, "cetime" => $cetime, "teacher" => $_POST['teacher'], "create_time" => date("Y-m-d H:i:s"),"timeid"=>$timeid);
            $this->course_model->where(array("id" => $_POST['id']))->save($course);
            $this->success("编辑成功！", U("course/alllist"));
        } else {
            $this->error("非法请求");
        }
    }

    public function edit($id)
    {
        $course = D("course")->find($id);
        $types = D("course_type")->where("state=1")->select();
        $teachers = D("teacher")->select();
        $time = D("time")->order("stime ASC")->select();

        $this->assign("times", $time);
        $this->assign("course", $course);
        $this->assign("types", $types);
        $this->assign("teachers", $teachers);

        $this->display();
    }

    public function type()
    {
        $ts = D("course_type")->select();
        $this->assign("types", $ts);
        $this->display();
    }

    public function type_add()
    {
        $this->display();
    }

    public function add_type()
    {
        if (IS_POST) {
            $type = array();
            $type["tname"] = $_POST["tname"];
            $type["tdesc"] = $_POST["tdesc"];
            $type["tag"] = $_POST["tag"];
            $type["create_time"] = date("Y-m-d H:i:s");
            D("course_type")->add($type);
            $this->success("添加成功！", U("course/type"));
        } else {
            $this->error("非法请求");
        }
    }

    public function type_edit($id)
    {
        $ct = D("course_type")->find($id);
        $this->assign("ct", $ct);
        $this->display();
    }

    public function edit_type()
    {
        if (IS_POST) {
            if (strrpos($_POST["icon"], C("TMPL_PARSE_STRING.__UPLOAD__")) == 0) {
                $icon = $_POST["icon"];
            } else {
                $icon = C("TMPL_PARSE_STRING.__UPLOAD__") . $_POST["icon"];
            }

            $re = D("course_type")->where(array("id" => $_POST["id"]))->save(array("tname" => $_POST["tname"], "tdesc" => $_POST["tdesc"], "tag" => $_POST["tag"], "icon" => $icon, "state" => $_POST["state"]));
            if ($re) {
                $this->success("编辑成功！", U("course/type"));
            } else {
                $this->success("编辑失败！", U("course/type"));
            }
        } else {
            $this->error("非法请求");
        }
    }


    // 删除
    public function delete($id)
    {

        $uc = D("user_card_course")->where(array("courseid" => $id))->count("userid");
        if (intval($uc) == 0 && $this->course_model->delete($id)) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败,该课程有人预约了");
        }
    }

    private function load_menu_lang()
    {
        $default_lang = C('DEFAULT_LANG');

        $langSet = C('ADMIN_LANG_SWITCH_ON', null, false) ? LANG_SET : $default_lang;

        $apps = sp_scan_dir(SPAPP . "*", GLOB_ONLYDIR);
        $error_menus = array();
        foreach ($apps as $app) {
            if (is_dir(SPAPP . $app)) {
                if ($default_lang != $langSet) {
                    $admin_menu_lang_file = SPAPP . $app . "/Lang/" . $langSet . "/admin_menu.php";
                } else {
                    $admin_menu_lang_file = SITE_PATH . "data/lang/$app/Lang/" . $langSet . "/admin_menu.php";
                    if (!file_exists_case($admin_menu_lang_file)) {
                        $admin_menu_lang_file = SPAPP . $app . "/Lang/" . $langSet . "/admin_menu.php";
                    }
                }

                if (is_file($admin_menu_lang_file)) {
                    $lang = include $admin_menu_lang_file;
                    L($lang);
                }
            }
        }
    }

}

