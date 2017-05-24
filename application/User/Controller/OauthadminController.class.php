<?php
/**
 * 参    数：
 * 作    者：lht
 * 功    能：OAth2.0协议下第三方登录数据报表
 * 修改日期：2013-12-13
 */
namespace User\Controller;

use Common\Controller\AdminbaseController;

class OauthadminController extends AdminbaseController
{

    // 后台第三方用户列表
    public function index()
    {
        $tel=$_POST['tel'];
        $oauth_user_model = M('OauthUser');
        if (!$tel) {
            $count = $oauth_user_model->where(array("status" => 1))->count();
            $page = $this->page($count, 20);
            $lists = $oauth_user_model
                ->where(array("status" => 1))
                ->order("create_time DESC")
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
            $this->assign("page", $page->show('Admin'));
        } else {
            $lists = $oauth_user_model->where(array("status" => 1, "tel" => $tel))
                ->select();
            $this->assign("page", "");
        }

        $this->assign('lists', $lists);
        $this->display();
    }

    // 后台删除第三方用户绑定
    public function delete()
    {
        $id = I('get.id', 0, 'intval');
        if (empty($id)) {
            $this->error('非法数据！');
        }
        $result = M("OauthUser")->where(array("id" => $id))->delete();
        if ($result !== false) {
            $this->success("删除成功！", U("oauthadmin/index"));
        } else {
            $this->error('删除失败！');
        }
    }

    public function card($id)
    {
        $ou = D("oauth_user")->find($id);
        $cards = D("user_card")->where(array('openid' => $ou['openid']))->select();
        $allCards = array();
        foreach ($cards as $card) {
            $c = D("card")->find($card['cardid']);
            $card['cname'] = $c['cname'];
            $card['cftype'] = $c['cname'];
            array_push($allCards, $card);
        }
        $this->assign('cards', $allCards);
        $this->assign('userid', $id);
        $this->display();
    }

    public function card_add($id)
    {
        $cards = D("card")->order("cftype desc")->select();
        $newCards = array();
        foreach ($cards as $card) {
            $type = D("course_type")->find($card["cftype"]);
            $card["cftype"] = $type["tname"];
            array_push($newCards, $card);
        }
        $this->assign('cards', $newCards);
        $this->assign('userid', $id);
        $this->display();
    }

    public function addCards($userId, $cardId)
    {
        $card = D("card")->find($cardId);
        $user = D("oauth_user")->find($userId);
        if ($card && $user) {
            $openid = $user['openid'];
            $cashFee = $card['cprice'];
            $days = $card['cdays'];
            $expire = date("Y-m-d", strtotime("+" . $days . " day"));
            //用户累积消费
            $ou = D("oauth_user")->where(array("openid" => $openid))->find();
            $sum_fee = intval($ou["sum_fee"]) + intval($cashFee);
            D("oauth_user")->where(array("openid" => $openid))->save(array("sum_fee" => $sum_fee));
            //用户消费记录 卡关联
            D("user_card")->add(array("openid" => $openid, "cashfee" => $cashFee, "cardid" => $cardId, "buy_time" => date('Y-m-d H:i:s'), 'use_number' => $card['ctimes'], 'expire_time' => $expire));

            $this->success("添加成功！", U("oauthadmin/card", array('id' => $userId)));
        } else {
            $this->error('非法ID');
        }
    }

    public function deleteCard($id, $userId)
    {
        $ucmodel = D("user_card");
        $uc = $ucmodel->find($id);
        if ($uc) {
            $uc['delete_time'] = date("Y-m-d H:i:s");
            D("user_card_rubbish")->save($uc);
            $ucmodel->delete($id);
            $this->success("删除成功！", U("oauthadmin/card", array('id' => $userId)));
        } else {
            $this->error('非法ID');
        }
    }

}