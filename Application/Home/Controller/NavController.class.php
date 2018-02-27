<?php
namespace Home\Controller;
use Think\Controller;
header('Content-type:text/html;charset=utf-8');
class NavController extends Controller {
   public function __construct(){
       parent::__construct();
       $catModel = D('Admin/Category');
       $catData = $catModel->getNavData();
       $this->assign('catData',$catData);
   }
}