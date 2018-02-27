<?php
namespace Admin\Controller;
use Think\Controller;
class LoginController extends Controller {
    
   public function chkcode(){
       $Verify = new \Think\Verify(array(
           'fontSize' => 30,//验证码大小
           'length' => 2,//验证码位数
           'userNoise' => TRUE,//关闭验证码杂点
       ));
       $Verify->entry();
   }
   public function login(){
       if(IS_POST){
           $model = D('Admin');
           //接收并验证
           if($model->validate($model->_login_validate)->create()){
               if($model->login()){
                   $this->success('登录成功',U('Index/index'));
                   exit;
               }
           }
           $this->error($model->getError());
       }
       $this->display();
   }
   public function logout(){
       $model = D('Admin');
       $model->logout();
       redirect('login');
   }
}
?>