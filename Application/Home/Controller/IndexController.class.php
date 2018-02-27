<?php
namespace Home\Controller;
use Think\Controller;
header('Content-type:text/html;charset=utf-8');
class IndexController extends NavController {
    public function index(){
        $goodsModel = D("Admin/Goods");
        $goods1 = $goodsModel->getPromoteGoods();
        $goods2 = $goodsModel->getRecGoods('is_new');
        $goods3 = $goodsModel->getRecGoods('is_hot');
        $goods4 = $goodsModel->getRecGoods('is_best');
        $catModel = D('Admin/Category');
        $floorData = $catModel->floorData();
        
        //var_dump($floorData);die;
        //var_dump($goods1);die;
        $this->assign(array(
            'goods1'=>$goods1,
            'goods2'=>$goods2,
            'goods3'=>$goods3,
            'goods4'=>$goods4,
            'floorData'=>$floorData,
        ));
        $this->assign(array(
            '_show_nav'=>'1',
            '_page_title'=>'首页',
            '_page_keywords'=>'首页',
            '_page_description'=>'首页'
        ));
        $this->display();
        
    }
    
    public function goods(){
        //$_COOKIE['cart'];die;
        $id = I('get.id');
        //获取商品数据
        $gModel = D('Goods');
        $info = $gModel->find($id);
        //var_dump($info);
        //面包屑导航条
        $catModel = D('Admin/Category');
        $catPath = $catModel->parentPath($info['cat_id']);
        //图片地址
        $picModel = D('goods_pic');
        $picData = $picModel->where(array(
            'goods_id'=>array('eq',$id),
        ))->select();
        //获取属性
        $attrModel = D('goods_attr');
        $attrData = $attrModel->alias('a')
        ->field("a.*,b.attr_name,b.attr_type")
        ->where(array(
            'goods_id'=>array('eq',$id)
        ))
        ->join("LEFT JOIN __ATTRIBUTE__ b ON b.id=a.attr_id")
        ->select();
        $uniArr = array();
        $mulArr = array();
        foreach ($attrData as $k=>$v){
            if($v['attr_type'] == '唯一'){
                $uniArr[] = $v;
            }elseif ($v['attr_type'] == '可选'){
                $mulArr[$v['attr_name']][] = $v;
            }
        }
        //取出会员价格数据
        $mpModel = D('member_price');
        $mpData = $mpModel->alias('a')
        ->field("a.price,b.level_name")
        ->where(array(
            'goods_id'=>array('eq',$id),
        ))
        ->join("LEFT JOIN __MEMBER_LEVEL__ b ON b.id=a.level_id")
        ->select();
        //var_dump($mpData);
      
        
       
        $viewPath = C('IMAGE_CONFIG');
        $this->assign(array(
            'info'=>$info,
            'catPath'=>$catPath,
            'picData'=>$picData,
            'uniArr'=>$uniArr,
            'mulArr'=>$mulArr,
            'mpData'=>$mpData,
            'viewPath'=>$viewPath['viewPath'],
            '_show_nav'=>'0',
            '_page_title'=>'商品详情页',
            '_page_keywords'=>'商品详情页',
            '_page_description'=>'商品详情页'
        ));
        $this->display();
    }
    
    public function displayHistory()
    	{
    		$id = I('get.id');
    		// 先从COOKIE中取出浏览历史的ID数组
    		$data = isset($_COOKIE['display_history']) ? unserialize($_COOKIE['display_history']) : array();
    		// 把最新浏览的这件商品放到数组中的第一个位置上
    		array_unshift($data, $id);
    		// 去重
    		$data =	array_unique($data);
    		// 只取数组中前6个
    		if(count($data) > 6)
    			$data = array_slice($data, 0, 6);
    		// 数组存回COOKIE,加前缀会导致只能读到一个值
    		setcookie('display_history', serialize($data), time() + 30 * 86400);
    		// 再根据商品的ID取出商品的详细信息
    		$goodsModel = D('Goods');
    		$data = implode(',', $data);
    		$gData = $goodsModel->field('id,mid_logo,goods_name')->where(array(
    			'id' => array('in', $data),
    			'is_on_sale' => array('eq', '是'),
    		))->order("FIELD(id,$data)")->select();
    		echo json_encode($gData);
    	}
    
        public function ajaxGetMemberPrice(){
            $goodsId = I('get.goods_id');
            $gModel = D('Admin/Goods');
            echo $gModel->getMemberPrice($goodsId);
        }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}