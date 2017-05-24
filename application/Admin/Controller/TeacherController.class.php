<?php
/**
 * 后台首页
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
use Org\Util\String;


class TeacherController extends AdminbaseController
{

    private $teacher_model;
    public function _initialize()
    {

        parent::_initialize();
        $this->teacher_model = D("Teacher");
    }

    /**
     * 全部教练
     */
    public function index()
{
    $count = $this->teacher_model->count();
    $page = $this->page($count, 20);
    $teachers = $this->teacher_model
        ->order("create_time DESC")
        ->limit($page->firstRow, $page->listRows)
        ->select();
    $this->assign("teachers", $teachers);
    $this->display();
}
    public function searchUser($tel)
    {
        $user =D("oauth_user")->where(array("tel"=>$tel))->find();
        $this->ajaxReturn(array("data" => $user, "status" => true), "JSON");
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
        $this->assign("types", $ct);
        $this->assign("teachers", $teachers);
        $this->display();
    }


    public function add_teacher()
    {
        if (IS_POST) {
            $_POST["headimg"]= C("TMPL_PARSE_STRING.__UPLOAD__").$_POST["headimg"];
            $res=D("teacher")->add($_POST);
            if($res){
                $this->success("添加成功！", U("teacher/index"));
            }else{
                $this->success("添加失败！", U("teacher/index"));
            }
        } else {
            $this->error("非法请求");
        }
    }

    // 管理员编辑
    public function edit($id)
    {
        $teacher=D("teacher")->find($id);
        $this->assign("teacher",$teacher);
        $this->display();
    }
    public function edit_teacher()
    {
        if (IS_POST) {
            $_POST["headimg"]= C("TMPL_PARSE_STRING.__UPLOAD__").$_POST["headimg"];
            $res=D("teacher")->save($_POST);
            if($res){
                $this->success("编辑成功！", U("teacher/index"));
            }else{
                $this->success("编辑失败！", U("teacher/index"));
            }
        } else {
            $this->error("非法请求");
        }
    }


    // 删除
    public function delete($id)
    {

        $uc = D("user_teacher")->where(array("teacherid" => $id))->count("userid");
        if (intval($uc) == 0 && D("teacher")->delete($id)) {
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

