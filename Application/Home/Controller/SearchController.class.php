<?php
namespace Home\Controller;
use Think\Controller;
header('Content-type:text/html;charset=utf-8');
class SearchController extends NavController {
    public function cat_search(){
        $catId = I('get.cat_id');
        //取出商品和翻页
        $goodsModel = D('Admin/Goods');
        $data = $goodsModel->cat_search($catId);
        //根据上面搜索出来的商品计算筛选条件
        $catModel = D('Admin/Category');
        $searchFilter = $catModel->getSearchConditionByCatId($data['goods_id']);
        //var_dump($searchFilter);
        
        $this->assign(array(
            'page'=>$data['page'],
            'data'=>$data['data'],
            'searchFilter'=>$searchFilter,
            '_page_title'=>'分类搜索页',
            '_page_keywords'=>'分类搜索页',
            '_page_description'=>'分类搜索页'
        ));
        $this->display();
        
    }
    public function key_search(){
        $key = I('get.key');
        //取出商品和翻页
        $goodsModel = D('Admin/Goods');
        $data = $goodsModel->key_search($key);
        var_dump($data);
        //根据上面搜索出来的商品计算筛选条件
        $catModel = D('Admin/Category');
        $searchFilter = $catModel->getSearchConditionByCatId($data['goods_id']);
        //var_dump($searchFilter);
    
        $this->assign(array(
            'page'=>$data['page'],
            'data'=>$data['data'],
            'searchFilter'=>$searchFilter,
            '_page_title'=>'分类搜索页',
            '_page_keywords'=>'分类搜索页',
            '_page_description'=>'分类搜索页'
        ));
        $this->display();
    
    }
    
   
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}