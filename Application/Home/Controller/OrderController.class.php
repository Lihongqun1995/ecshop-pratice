<?php
namespace Home\Controller;
use Think\Controller;
class OrderController extends Controller{
    public function add(){
        $memberId = session('m_id');
        if(!$memberId){
            session('returnUrl',U('Order/add'));
            redirect(U('Member/login'));
        }
        if(IS_POST){
           //var_dump($_POST);die;
            $orderModel = D('Admin/Order');
            if($orderModel->create(I('post.'))){
                if($orderModel->add()){
                    $this->success('添加成功！', U('/'));
                    
                    exit;
                } else{
                    
                }
            }else{
                
            }
           $this->error('添加失败,原因'.$orderModel->getError());
        }
        // 先取出购物车中所有的商品
		$cartModel = D('Cart');
		$data = $cartModel->cartList();
		
		// 设置页面信息
    	$this->assign(array(
    		'data' => $data,
    		'_page_title' => '定单确认页',
    		'_page_keywords' => '定单确认页',
    		'_page_description' => '定单确认页',
    	));
    	$this->display();
    }
    
    public function lst(){
        $cartModel = D('Cart');
        $data = $cartModel->cartList();
        $this->assign(array(
            'data'=>$data,
            '_page_title'=>'购物车列表',
            '_page_keywords'=>'购物车列表',
            '_page_description'=>'购物车列表'
        ));
        $this->display();
    }
    public function ajaxCartList(){
        $cartModel = D('Cart');
        $data = $cartModel->cartList();
        echo json_encode($data);
    }
}