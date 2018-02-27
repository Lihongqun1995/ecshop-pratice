<?php
namespace Home\Controller;
use Think\Controller;
class CartController extends Controller{
    public function add(){
        if(IS_POST){
           //var_dump($_POST);die;
            $cartModel = D('Cart');
            if($cartModel->create(I('post.'))){
                if($cartModel->add()){
                    $this->success('添加成功！', U('/'));
                    
                    exit;
                } else{
                    
                }
            }else{
                
            }
           $this->error('添加失败,原因'.$cartModel->getError());
        }
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