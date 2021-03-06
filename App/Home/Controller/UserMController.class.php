<?php
/**
 * Created by PhpStorm.
 * author: huanglele
 * Date: 2016/6/16
 * Time: 17:10
 * Description:
 */
namespace Home\Controller;
use Think\Controller;
use Think\Crypt\Driver\Think;

class UserMController extends Controller
{
    private $uid = null;
    /**
     * 初始化，判断是否登录了
     */
    public function _initialize(){
//        parent::_initialize();
        $this->uid = session('uid');
        if(!$this->uid){
            $this->redirect('mobile/login');die;
        }else{
            C('LAYOUT_NAME','Public/mLayout');
        }
    }
    //个人中心
    public function index(){
        $info = M('user')->find($this->uid);
        $this->assign('info',$info);
        //获取推荐商品
        $Tool = A('Tool');
        $goods = $Tool->getGoods(array('status'=>2),4,'sold_num desc');
        $this->assign('goods',$goods);
        $this->display('index');
    }
    //我的收货地址
    public function myAddress(){
        $Tool = A('Tool');
        $map['uid'] = $this->uid;
        $Tool->getList('address',$map,'id desc');
        $this->display('myAddress');
    }
    //添加收货地址
    public function addAddress(){
        if(isset($_POST['action'])){
            $id = I('post.id',0,'number_int');
            $action = I('post.action');
            if($action=='update' && !$id){
                $action = 'add';
            }
            $data['name'] = I('post.receiverName');
            $data['phone'] = I('post.receiverMobile');
            $data['address'] = I('post.address');
            $code = array('province'=>I('post.province'),'city'=>I('post.city'),'district'=>I('post.district'),'deliveryAddress'=>I('post.deliveryAddress'));
            $data['code'] = json_encode($code);
            $data['uid'] = $this->uid;
            $isDefault = I('post.isDefault');
            $isDefault = ($isDefault=='Y') ? 1:0;
            $data['default'] = $isDefault;
            $M = M('address');
            if($action=='update'){
                $data['id'] = $id;
                if($M->save($data)){
                    if($isDefault){
                        $this->setDefaultAddress($id);
                    }
                    $this->success('修改成功');
                }else{
                    $this->error('修改失败');
                }
            }else{
                $id = $M->add($data);
                if($id){
                    if($isDefault){
                        $this->setDefaultAddress($id);
                    }
                    $this->success('添加成功');
                }else{
                    $this->error('添加失败');
                }
            }
        }else{
            $ac = I('ac');
            if(!$ac){
                $ac = U('UserM/index');
            }
            $this->assign('ac',$ac);
            $this->display('addaddress');
        }
    }
    //设置默认地址
    private function setDefaultAddress($id){
        $map['uid'] = $this->uid;
        $map['id'] = array('neq',$id);
        M('address')->where($map)->setField('default',0);
    }
    //修改收货地址
    public function editAddress(){
        $id = I('get.id');
        $info = M('address')->find($id);
        if($info && $info['uid']==session('uid')){
            $ac = I('ac');
            if(!$ac){
                $ac = U('UserM/myAddress');
            }
            $this->assign('ac',$ac);
            $info['code'] = json_decode($info['code'],true);
            $this->assign('info',$info);
            $this->display('editAddress');
        }else{
            $this->error('页面不存在',U('index'));
        }
    }
    //我的订单
    public function myOrder(){
        $this->display('myOrder');
    }
    /**
     * 我的订单详情
     */
    public function order(){
        $id = I('get.id');
        $info = M('orders')->find($id);
        if($info && $info['uid']==session('uid')){
            $goods = json_decode($info['goods_info'],true);
            foreach($goods as $vo){
                $gidArr[] = $vo['gid'];
            }
            $goodInfo = M('goods')->where(array('gid'=>array('in',$gidArr)))->getField('gid,name,img',true);
            $goodArr = array();
            foreach($goods as $vo){
                $t['gid'] = $vo['gid'];
                $t['name'] = $goodInfo[$vo['gid']]['name'];
                $t['img'] = $goodInfo[$vo['gid']]['img'];
                $t['buy_price'] = $vo['pay_each_price'];
                $t['buy_num'] = $vo['buy_num'];
                $goodArr[] = $t;
            }
            $info['goods'] = $goodArr;
            $info['address'] = json_decode($info['address_info'],true);
            $this->assign('info',$info);
            $this->assign('OrderStatus',C('OrderStatus'));
            $this->assign('OrderPayType',C('OrderPayType'));
            $this->assign('OrderType',C('OrderType'));
            $this->display('order');
        }else{
            $this->error('订单不存在',U('myOrder'));
        }
    }
    public function payOrder(){
        $order = I('get.order');
        $info = M('orders')->field('trade,goods_amount,yunfei,uid,status,uid,pay_type')->find($order);
        if($info && $info['status']==1 && $info['uid']==session('uid')){
            if($info['pay_type']==1){   //微信支付
                $openid = session('openid');
                if(!$openid){
                    $tools = new \Org\Wxpay\JsApi();
                    $openId = $tools->GetOpenid();
                    session('openid',$openId);
                }
                $data['uid'] = session('uid');
                $data['oid'] = $order;
                $data['amount'] = $info['goods_amount']+$info['yunfei'];
                $data['body'] = '消费';
                $data['attach'] = '消费';
                $this->sendPayData($data);
            }elseif($info['pay_type']==2){  //余额支付
                $da['money'] = $info['goods_amount']+$info['yunfei'];
                $da['trade'] = $order;
                $this->cashPayOrder($da);
            }
        }else{
            $this->error('订单不存在');
        }
    }
    //获取订单信息
    public function orderList(){
        $p = I('p',1,'number_int');
        $status = I('get.status');
        if(!in_array($status,array(1,2,3))){
            $status = 1;
        }
        $map['status'] = $status;
        $map['uid'] = $this->uid;
        $Tool = A('Tool');
        $list = $Tool->getList('orders',$map,'trade desc','trade,goods_info,status,goods_amount,create_time');
        if($list){
            $gidArr = array();
            foreach($list as $v){
                $goods = json_decode($v['goods_info'],true);
                foreach($goods as $vo){
                    $gidArr[] = $vo['gid'];
                }
            }
            $goodInfo = M('goods')->where(array('gid'=>array('in',$gidArr)))->getField('gid,name,img',true);
            $OrderStatus = C('OrderStatus');
            //合成数据
            foreach($list as $o){
                $i['trade'] = $o['trade'];
                $i['status'] = $OrderStatus[$o['status']];
                $i['time'] = Mydate($o['create_time']);
                $i['goods_amount'] = $o['goods_amount'];
                $goods = json_decode($v['goods_info'],true);
                $goodArr = array();
                foreach($goods as $vo){
                    $t['name'] = $goodInfo[$vo['gid']]['name'];
                    $t['img'] = $goodInfo[$vo['gid']]['img'];
                    $goodArr[] = $t;
                }
                $i['goods'] = $goodArr;
                $data[] = $i;
            }
        }else{
            $data = array();
        }
        $num = count($data);
        $ret['status'] = 'success';
        $ret['num'] = $num;
        $ret['list'] = $data;
        if($num==16)  $p++;
        $ret['page'] = $p;
        $this->ajaxReturn($ret);
    }
    //我推荐的人
    public function team(){
        if(IS_AJAX){
            $p = I('p',1,'number_int');
            $map['invite_uid'] = session('uid');
            $Tool = A('Tool');
            $list = $Tool->getList('user',$map,'uid desc','nickname as name,phone,coin');
            if($p==1){
                //判断是否是销售总监
                $isLeader = M('user')->where(array('uid'=>session('uid')))->getField('is_leader');
                if($isLeader){
                    $mapTeam['invite_uid|leader'] = $this->uid;
                    $ret['team'] = M('user')->where($mapTeam)->count();
                }
            }
            $num = count($list);
            $ret['status'] = 'success';
            $ret['num'] = $num;
            $ret['list'] = $list;
            if($num==16)  $p++;
            $ret['page'] = $p;
            $this->ajaxReturn($ret);
        }else{
            $this->display('team');
        }
    }
    //我的财务记录
    public function money(){
        if(IS_AJAX){
            $p = I('p',1,'number_int');
            $type = I('get.type',3,'number_int');
            if(in_array($type,array(3,4))){
                $map['uid'] = session('uid');
                $map['type'] = $type;
                $Tool = A('Tool');
                $list = $Tool->getList('money',$map,'mid desc','time,note,amount');
                if(count($list)){
                    foreach($list as $k=>$v){
                        $list[$k]['time'] = date('Y-m-d',$list[$k]['time']);
                    }
                }
            }else if($type==5){  //获取总监的 这个麻烦一点呀
                $map['uid'] = session('uid');
                $map['type'] = 5;
                $M = M('money');
                $count = $M->where($map)->count();
                $Page = new\Think\Page($count,16);
                $field = 'DATE_FORMAT(FROM_UNIXTIME(time),"%Y-%c") as t,SUM(amount) as amount,COUNT(mid) as note';
                $list = $M->where($map)->field($field)->order('t desc')->limit($Page->firstRow,$Page->listRows)->group('t')->select();
                if(count($list)){
                    foreach($list as $k=>$v){
                        $list[$k]['time'] = $list[$k]['t'];
                        $list[$k]['note'] = '共'.$list[$k]['note'].'笔收入';
                    }
                }
            }
            $num = count($list);
            $ret['status'] = 'success';
            $ret['num'] = $num;
            $ret['list'] = $list;
            if($num==16)  $p++;
            $ret['page'] = $p;
            $this->ajaxReturn($ret);
        }else{
            $this->display('money');
        }
    }
    //我的钱包
    public function wallet(){
        $info = M('user')->find($this->uid);
        $this->assign('info',$info);
        $this->display('wallet');
    }
    //我的菜箱
    public function myBox(){
        $info = M('user')->where(array('uid'=>session('uid')))->field('is_store,goods,card,status')->find();
        if($info['is_store']){
            $ac = I('get.ac');
            $gidArr = json_decode($info['goods'],true);
            if(!count($gidArr)){
                $gidArr[] = 0;
            }
            if($ac=='un'){//没有添加
                $this->assign('ac','do');
                $map['gid'] = array('NOTIN',$gidArr);
            }else{//已添加
                $this->assign('ac','un');
                $map['gid'] = array('IN',$gidArr);
            }
            $Tool = new \Home\Controller\ToolController();
            $list = $Tool->getGoods($map,0,'gid desc');
            $this->assign('list',$list);
            $this->display('myBox');
        }else{
            $UserStatus = C('UserStatue');
            $this->assign('info',json_decode($info['card'],true));
            $this->assign('status',$UserStatus[$info['status']]);
            $this->display('noVip');
        }
    }
    //申请代理
    public function applyVip(){
        $info = M('user')->field('is_store,card,use_money')->find($this->uid);
        if(1 || $info['use_money']>1980){   //不需要门槛了，现在后台审核
            $da = array();
            foreach($_POST as $k=>$v){
                if($v==''){
                    $this->error('填写完整银行卡信息');die;
                }else{
                    $da[$k] = $v;
                }
            }
            $data['card'] = json_encode($da);
            $data['uid'] = $this->uid;
            $data['is_store'] = 0;
            $data['status'] = 1;
            if(M('user')->save($data)){
                $this->success('处理成功',U('myBox'));
            }else{
                $this->error('填写完整银行卡信息');
            }
        }else{
            $this->error('你还没有达到要求');
        }
    }
    //处理上下货
    public function doBox(){
        $info = M('user')->where(array('uid'=>session('uid')))->field('is_store,goods')->find();
        if($info['is_store']){
            $ac = I('get.ac');
            $id = I('get.id');
            $gidArr = json_decode($info['goods'],true);
            if($ac=='un'){//删除
                foreach($gidArr as $k=>$v){
                    if($v==$id){
                        unset($gidArr[$k]);break;
                    }
                }
            }else{//添加
                $gidArr[] = $id;
            }
            $gidJson = json_encode(array_unique($gidArr));
            M('user')->where(array('uid'=>session('uid')))->setField('goods',$gidJson);
            $this->redirect('myBox',array('ac'=>$ac));
        }else{
            $this->error('你还不是会员',U('index'));
        }
    }
    //显示我的店铺二维码
    public function myBoxPic(){
        $info = M('user')->where(array('uid'=>session('uid')))->field('is_store,goods')->find();
        if($info['is_store']){
            $url = U('mobile/store',array('from_uid'=>$this->uid),true,true);
            $this->assign('url',$url);
            $this->display('myBoxPic');
        }else{
            $this->error('你还不是会员');
        }
    }
    //确认订单
    public function cart(){
        if(IS_POST){
//            layout(false);
            $gidArr = I('post.gid');
            $numArr = I('post.num');
            $gInfo = M('goods')->where(array('gid'=>array('in',$gidArr)))->getField('gid,name,buy_price as price,status,left_num,img',true);
            $data = array();
            $goodAmount = 0;
            $cart = array();
            foreach($gidArr as $k=>$v){
                if(array_key_exists($v,$gInfo)){
                    $cart[$v] = $numArr[$k];
                    $i['gid'] = $v;
                    $i['num'] = $numArr[$k];
                    $i['name'] = $gInfo[$v]['name'];
                    $i['price'] = $gInfo[$v]['price'];
                    $i['status'] = $gInfo[$v]['status'];
                    $i['left_num'] = $gInfo[$v]['left_num'];
                    $i['img'] = $gInfo[$v]['img'];
                    $data[] = $i;
                    $goodAmount += $i['num']*$i['price'];
                }
            }
            session('cart',$cart);  //更新购物车
            $this->assign('amount',$goodAmount);//商品价格
            $this->assign('yunfei',readConf('yunfei'));//商品价格
            $this->assign('data',$data);
//            var_dump($gInfo,$data);die;
            //查询收货地址
            $address = M('address')->where(array('uid'=>$this->uid))->field('id,address,phone,name')->order('`default` desc')->select();
            $this->assign('address',$address);
            $this->display('cart');
        }else{
            $this->redirect('mobile/index');
        }
    }
    /**
     * 购买商品
     */
    public function buy(){
        $Tool = A('Order');
        $order = $Tool->addOrder();
        if($order['status']){
            if($order['pay_type']==1){      //微信支付
                $data['uid'] = session('uid');
                $data['oid'] = $order['trade'];
                $data['amount'] = $order['money'];
                $data['body'] = '消费';
                $data['attach'] = '消费';
                $this->sendPayData($data);
            }elseif($order['pay_type']==2){ //余额支付
                $this->cashPayOrder($order);
            }
        }else{
            $this->error($order['msg']);
        }
    }
    /**
     * 现金支付订单
     * @param $order['money'] 订单总金额 包含商品和运费
     * @param $order['trade'] 订单号
     */
    private function cashPayOrder($order){
        $cash_money = M('user')->where(array('uid'=>$this->uid))->getField('cash_money');   //查询现金余额
        if($cash_money<$order['money']){
            //现金余额不足
            $this->error('现金余额不足',U('myOrder'));die;
        }
        M('user')->startTrans();
        $r1 = M('user')->where(array('uid'=>$this->uid))->setDec('cash_money',$order['money']);
        $Order = A('Order');
        $r2 = $Order->onOrderPay($order['trade']);
        if($r1 && $r2 ){
            M('user')->commit();
            $this->success('支付成功',U('myOrder'));
        }else{
            M('user')->rollback();
            $this->error('支付失败',U('myOrder'));
        }
    }
    /**
     * 发起微信支付
     * @param $da
     * 包含oid的为订单付款，对应的orders表里面的trade字段。
     */
    private function sendPayData($da){
        layout(false);
        $body = $da['body'];
        $attach = $da['attach'];
        $tag = $da['uid'];
        $trade_no = createWxPayTrade();
        $Pay = A('Wechat');
        $openId = session('openid');
        if(!$openId) {$this->error('请在微信里面打开');die;}
        $order = $Pay->pay($openId,$body,$attach,$trade_no,intval($da['amount']*100),$tag);
        if($order['result_code']=='SUCCESS'){//生成订单信息成功
            $data['mytrade'] = $trade_no;
            $data['uid'] = $da['uid'];
            $data['oid'] = $da['oid'];
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['amount'] = $da['amount'];
            $data['status'] = 1;
            $data['pay_time'] = 0;
            if(M('wxpay')->add($data)){
                $this->assign('money',$da['amount']);
                $this->display('UserM/paySub');die;
            }else{
                $this->error('操作失败请重试');die;
            }
        }else{
            $this->error('操作失败请重试');die;
        }
    }
    /**
     * 显示自己的推广二维码
     */
    public function tgQrCode(){
        $url = U('Mobile/reg',array('from_uid'=>session('uid')),true,true);
        $this->assign('url',$url);
        $this->display('tgQrCode');
    }
    /**
     * 我的收藏
     */
    public function myFav(){
        $favorite = M('user')->where(array('uid'=>session('uid')))->getField('favorite');
        $gidArr = json_decode($favorite,true);
//        var_dump($gidArr);die;
        if(count($gidArr)){
            $map['gid'] = array('IN',$gidArr);
            $Tool = new \Home\Controller\ToolController();
            $list = $Tool->getGoods($map,0,'gid desc');
        }else{
            $list = array();
        }
        $this->assign('list',$list);
        $this->display('myFav');
    }
    //我的会员卡
    public function myCard(){
        $useMoney = M('user')->where(array('uid'=>$this->uid))->getField('use_money');
        if($useMoney<500){
            $this->error('你还不是会员');
        }else if($useMoney<812){
            $card = 'card_500.jpg';
        }elseif($useMoney<1982){
            $card = 'card_812.jpg';
        }elseif($useMoney<3000){
            $card = 'card_1982.jpg';
        }else{
            $card = 'card_3000.jpg';
        }
        $this->assign('card',$card);
        $this->display('card');
    }
    //返利余额提现
    public function getCash(){
        $money = I('post.money',0,'float');
        if(!$money){$this->error('提现金额格式不对');die;}
        $left_money = M('user')->where(array('uid'=>$this->uid))->getField('money');
        if($left_money<$money){$this->error('返利余额不足');die;}
        M('user')->startTrans();
        $r1 = M('user')->where(array('uid'=>$this->uid))->setDec('money',$money);
        $da['uid'] = $this->uid;
        $da['amount'] = $money;
        $da['type'] = 9;
        $da['time'] = time();
        $da['note'] = '';
        $r2 = M('money')->add($da);
        if($r1 && $r2){
            M('user')->commit();
            $this->success('申请成功');
        }else{
            M('user')->rollback();
            $this->error('申请失败');
        }
    }
    //微信充值
    public function wxCz(){
        if(isset($_POST['submit'])){
            $money = I('post.money',0,'float');
            if(!$money){$this->error('充值格式不对');die;}
            $data['uid'] = session('uid');
            $data['oid'] = 0;
            $data['amount'] = $money;
            $data['body'] = '充值';
            $data['attach'] = '充值';
            $this->sendPayData($data);
        }else{
            $openid = session('openid');
            if(!$openid){
                $tools = new \Org\Wxpay\JsApi();
                $openId = $tools->GetOpenid();
                session('openid',$openId);
            }
            $this->display('wxCz');
        }
    }
    //修改头像昵称
    public function myInfo(){
        if(isset($_POST['submit'])){
            $data['nickname'] = I('post.nickname');
            if(!$data['nickname']){
                $this->error('昵称不能为空');
            }
            //判断是否有文件上传
            if($_FILES['img']['error']==0){
                //处理图片
                $upload = new \Think\Upload(C('UploadConfig'));
                $info   =   $upload->upload();
                if($info) {
                    $data['headimgurl'] = $info['img']['savepath'].$info['img']['savename'];
                    $image = new \Think\Image();
                    $image->open('./upload/'.$data['headimgurl']);
                    $image->thumb(80,80,2)->save('./upload/'.$data['headimgurl']);
                }else{
                    $this->error($upload->getError());
                }
            }
            $data['uid'] = $this->uid;
            if(M('user')->save($data)){
                session('nickname',$data['nickname']);
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }else{
            $info = M('user')->where(array('uid'=>$this->uid))->field('phone,nickname,headimgurl')->find();
            $this->assign('info',$info);
            $this->display('myInfo');
        }
    }
}