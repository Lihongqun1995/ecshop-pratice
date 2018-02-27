<?php
namespace Admin\Controller;
use Think\Controller;
class BaseController extends Controller {
   public function __construct(){
       //先调用父类构造函数
       parent::__construct();
       //登录判断
       if(!session('id')){
           $this->error('必须先登录！',U('Login/login'));
       }
       //所有管理员都能进入index界面
       if(CONTROLLER_NAME == "Index"){
           return true;
       }
       $priModel = D('privilege');
       if(!$priModel->chkPri()){
           $this->error('无权访问！');
       }
   }
   
}
?>