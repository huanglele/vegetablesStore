<?php/** * Author: huanglele * Date: 2016/1/28 * Time: 17:49 * Description: */namespace Admin\Controller;use Admin\Controller;class UserController extends CommonController{    /**     * 列出所有的微信用户     */    public function index(){        $map = array();        $uid = I('get.uid');        if($uid){            $map['uid'] = $uid;        }        $this->assign('uid',$uid);        $name = I('get.name');        if($name){            $map['nickname'] = array('like','%'.$name.'%');        }        $this->assign('name',$name);        $type = I('get.type',0,'number_int');        switch($type){            case 1:$map['is_store'] = 0;$map['is_leader'] = 0;break;            case 2:$map['is_store'] = 1;break;            case 3:$map['is_leader'] = 1;break;        }        $this->assign('type',$type);        $phone = I('get.phone');        if($phone){            $map['phone'] = $phone;        }        $this->assign('phone',$phone);        $M = M('user');        $order = 'uid desc';        $this->getData($M,$map,$order);        $this->assign('Status',C('UserStatue'));        $this->display('index');    }    /**     * 用户的详细信息     */    public function userInfo(){        $uid = I('get.uid',0,'number_int');        $info = M('user')->find($uid);        if(!$info){$this->error('用户不存在',U('index'));}        if($info['card'])   $info['card'] = json_decode($info['card'],true);        $this->assign('info',$info);        $this->assign('Status',C('Subscribe'));        $this->display('userInfo');    }    public function addUser(){        if(isset($_POST['submit'])){            $phone = I('post.phone',0,'number_int');            if($phone && !M('user')->where(array('phone'=>$phone))->find() ){                $data = $_POST;                $data['phone'] = $phone;                $nickname= I('post.nickname');                if(!$nickname) $nickname = $phone;                $data['nickname'] = $nickname;                $password = I('post.password');                if(!$password) $this->error('密码错误');                $data['password'] = md5($password);                $card_name = I('post.card_name');                $card_card = I('post.card_card');                $data['card'] = json_encode(array('name'=>$card_name,'card'=>$card_card));                if(M('user')->add($data)){                    $this->success('添加成功');                }else{                    $this->error('添加失败');                }            }else{                $this->error('手机号已存在');            }        }else{            $this->display('adduser');        }    }    /**     * 添加推手自定义财务     */    public function umoney(){        $status = I('post.status',0,'number_int');        $amount = I('post.amount',0,'number_int');        $uid = I('post.uid',0,'number_int');        $note = I('post.note','');        if(!in_array($status,array(4,5)) || $amount==0 )  $this->error('参数错误');        //扣钱 添加财务记录        $User = M('User');        $User->startTrans();        if($status==5){     //扣除            $Type = '扣除';            $r1 = $User->where('uid='.$uid)->setDec('money',$amount);        }else if($status==4){     //添加            $Type = '添加';            $r1 = $User->where('uid='.$uid)->setInc('money',$amount);        }        $da2['time'] = time();        $da2['uid'] = $uid;        $da2['note'] = $note;        $da2['money'] = $amount;        $da2['type'] = $status;        $r2 = M("umoney")->add($da2);        if($r1 && $r2){            $User->commit();            record("操作了".$uid.'号用户的财务，'.$Type.'了'.$amount);            $this->success('添加成功');        }else{            $User->rollback();            $this->error('添加失败');        }    }    /**     * 列出所有的商家用户     */    public function shang(){        $map = array();        $map['role'] = 1;        $aid = I('get.aid');        if($aid){            $map['aid'] = $aid;        }        $this->assign('aid',$aid);        $user = I('get.user');        if($user){            $map['user'] = array('like','%'.$user.'%');        }        $this->assign('user',$user);        $status = I('get.status',-1,'number_int');        if($status>=0){            $map['status'] = $status;        }        $this->assign('status',$status);        $M = M('admin');        $order = 'aid desc';        $this->getData($M,$map,$order);        $this->assign('Status',C('AdminStatus'));        $this->display('shang');    }    public function shangInfo(){        $id = I('get.id');        $info = M('admin')->find($id);        if(!$info) $this->error('用户不存在',U('shang'));        $this->assign('AdminStatus',C('AdminStatus'));        $this->assign('info',$info);        $this->display('shangInfo');    }    /**     * 更新商家状态     */    public function update(){        if(isset($_POST['submit'])){            $M = M('user');            $uid = I('post.uid');            $is_store = I('post.is_store',0,'number_int');            $is_leader = I('post.is_leader',0,'number_int');            $ac = '修改'.$uid.'号用户为';            if($is_store==1){                $ac .= '代理、';                $data['is_store'] = 1;            }else{                $data['is_store'] = 0;                $ac .= '非代理、';            }            if($is_leader==1){                $ac .= '销售总监';                $data['is_leader'] = 1;            }else{                $data['is_leader'] = 0;                $ac .= '非销售总监';            }            $data['uid'] = $uid;            if($M->save($data)){                record($ac);                $this->success('更新成功');            }else{                $this->error('更新失败');            }        }else{            $this->error('参数错误');        }    }    /**     * 重置用户密码     */    public function pwd(){        $id = I('get.id','','number_int');        $map['aid'] = $id;        $M = M('admin');        $password = $M->where($map)->getField('password');        if(!$password) $this->error('用户不存在');        $newPwd = md5(readConf('adminDefaultPwd'));        if(($newPwd)==$password){$this->success('密码已经重置');die;}        if($M->where($map)->setField('password',$newPwd)){            $this->success('更新成功');        }else{            $this->error('修改失败');        }    }    /**     * 添加推手自定义财务     */    public function smoney(){        $status = I('post.status',0,'number_int');        $amount = I('post.amount',0,'number_int');        $aid = I('post.aid',0,'number_int');        $note = I('post.note','');        if(!in_array($status,array(4,5)) || $amount==0 || $aid==0)  $this->error('参数错误');        //扣钱 添加财务记录        $User = M('Admin');        $User->startTrans();        if($status==5){     //扣除            $Type = '扣除';            $r1 = $User->where('aid='.$aid)->setDec('money',$amount);        }else if($status==4){     //添加            $Type = '添加';            $r1 = $User->where('aid='.$aid)->setInc('money',$amount);        }        $da2['time'] = time();        $da2['aid'] = $aid;        $da2['note'] = $note;        $da2['money'] = $amount;        $da2['type'] = $status;        $r2 = M("smoney")->add($da2);        if($r1 && $r2){            $User->commit();            record("操作了".$aid.'号商户的财务，'.$Type.'了'.$amount);            $this->success('添加成功');        }else{            $User->rollback();            $this->error('添加失败');        }    }}