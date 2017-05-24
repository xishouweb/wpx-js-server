<?php
/**
 * 后台首页
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
use Org\Util\String;

class CardController extends AdminbaseController
{

    private $card_model;
    private $course_type_model;


    public function _initialize()
    {
        parent::_initialize();
        $this->card_model = D("Card");
        $this->course_type_model = D("course_type");
    }

    public function card_times()
    {
        $cards = $this->card_model->where(array("ctype" => 1))->select();
        $newCards = array();
        foreach ($cards as $card) {
            $type = $this->course_type_model->find($card["cftype"]);
            $card["cftype"] = $type["tname"];
            $card["expire_time"] = date("Y-m-d",strtotime( $card["expire_time"]));
            array_push($newCards, $card);
        }
        $this->assign("cards", $newCards);
        $this->display();
    }

    public function card_month()
    {
        $cards = $this->card_model->where(array("ctype" => 0))->select();
        $newCards = array();
        foreach ($cards as $card) {
            $type = $this->course_type_model->find($card["cftype"]);
            $card["cftype"] = $type["tname"];
            array_push($newCards, $card);
        }
        $this->assign("cards", $newCards);
        $this->display();
    }

    public function card_type()
    {
        $cards = $this->card_model->select();
        $this->assign("cards", $cards);
        $this->display();
    }

    public function card_times_add()
    {
        $ctype = $this->course_type_model->select();
        $this->assign("cftypes", $ctype);
        $this->display();
    }

    public function card_times_edit($id)
    {
        $card = $this->card_model->find($id);
        $ctype = $this->course_type_model->find($card["cftype"]);
        $card["cftype"] = array("id" => $ctype["id"], 'name' => $ctype['tname']);
        $ctype = $this->course_type_model->select();
        $this->assign("cftypes", $ctype);
        $this->assign("card", $card);
        $this->display();
    }

    public function card_month_add()
    {
        $ctype = $this->course_type_model->select();
        $this->assign("cftypes", $ctype);
        $this->display();
    }

    public function card_month_edit($id)
    {
        $card = $this->card_model->find($id);
        $ctype = $this->course_type_model->find($card["cftype"]);
        $card["cftype"] = array("id" => $ctype["id"], 'name' => $ctype['tname']);
        $ctype = $this->course_type_model->select();
        $this->assign("cftypes", $ctype);
        $this->assign("card", $card);
        $this->display();
    }


    public function addTimes()
    {
        if (IS_POST) {
            $card = array("id"=>"cd".String::uuid(),"cname" => $_POST['cname'], "cdesc" => $_POST['cdesc'], "cftype" => $_POST['cftype'], "ctimes" => $_POST['ctimes'], "cdays" => $_POST['cdays'], "cprice" => $_POST['cprice'], "ctype" => 1, "create_time" => date("Y-m-d H:i:s"),"old_price" => $_POST['old_price'],"expire_time" => $_POST['expire_time']);
            $re = $this->card_model->add($card);
            if ($re) {
                $this->success("添加成功！", U("card/card_times"));
            } else {
                $this->success("添加失败");
            }
        } else {
            $this->error("非法请求");
        }
    }

    public function editTimes($id)
    {
        if (IS_POST) {
            $card = array("cname" => $_POST['cname'],"state" => $_POST['state'], "cdesc" => $_POST['cdesc'], "cftype" => $_POST['cftype'], "ctimes" => $_POST['ctimes'], "cdays" => $_POST['cdays'], "cprice" => $_POST['cprice'],"old_price" => $_POST['old_price'],"expire_time" => $_POST['expire_time'], "ctype" => 1, "create_time" => date("Y-m-d H:i:s"));
            $re = $this->card_model->where(array("id" => $id))->save($card);
            if ($re) {
                $this->success("编辑成功！", U("card/card_times"));
            } else {
                $this->success("编辑失败");
            }
        } else {
            $this->error("非法请求");
        }
    }

    //时长卡 0
    public function addMonth()
    {
        if (IS_POST) {
            $card = array("id"=>"cd".String::uuid(),"cname" => $_POST['cname'], "cdesc" => $_POST['cdesc'], "cftype" => $_POST['cftype'],"old_price" => $_POST['old_price'], "cdays" => $_POST['cdays'], "cprice" => $_POST['cprice'], "ctype" => 0, "create_time" => date("Y-m-d H:i:s"));
            $re = $this->card_model->add($card);
            if ($re) {
                $this->success("添加成功！", U("card/card_month"));
            } else {
                $this->success("添加失败");
            }
        } else {
            $this->error("非法请求");
        }
    }

    public function editMonth($id)
    {
        if (IS_POST) {
            $card = array("cname" => $_POST['cname'],"old_price" => $_POST['old_price'],"state" => $_POST['state'], "cdesc" => $_POST['cdesc'], "cftype" => $_POST['cftype'], "cdays" => $_POST['cdays'], "cprice" => $_POST['cprice'], "ctype" => 0, "create_time" => date("Y-m-d H:i:s"));
            $re = $this->card_model->where(array("id" => $id))->save($card);
            if ($re) {
                $this->success("编辑成功！", U("card/card_month"));
            } else {
                $this->success("编辑失败");
            }
        } else {
            $this->error("非法请求");
        }
    }

    public function addType()
    {
        $cards = $this->card_model->select();
        $this->assign("cards", $cards);
        $this->display();
    }


    // 删除
    public function deleteTimes($id)
    {

        $ucc = D("user_card")->where(array("cardid" => $id))->count("id");
        if (!$ucc && $this->card_model->delete($id) !== false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败,已经在使用了");
        }
    }
    // 删除
    public function deleteMonth($id)
    {
        $ucc = D("user_card")->where(array("cardid" => $id))->count();
        if (!$ucc && $this->card_model->delete($id) !== false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败,已经在使用了");
        }
    }

}

