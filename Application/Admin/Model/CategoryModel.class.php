<?php
namespace Admin\Model;
use Think\Model;
class CategoryModel extends Model 
{
	protected $insertFields = array('cat_name','parent_id','is_floor');
	protected $updateFields = array('id','cat_name','parent_id','is_floor');
	protected $_validate = array(
		array('cat_name', 'require', '分类名称不能为空！', 1, 'regex', 3),
		array('cat_name', '1,150', '分类名称的值最长不能超过 150 个字符！', 1, 'length', 3),
		array('parent_id', 'number', '父级分类Id必须是一个整数！', 2, 'regex', 3),
	);
	public function search($pageSize = 20)
	{
		/**************************************** 搜索 ****************************************/
		$where = array();
		/************************************* 翻页 ****************************************/
		$count = $this->alias('a')->where($where)->count();
		$page = new \Think\Page($count, $pageSize);
		// 配置翻页的样式
		$page->setConfig('prev', '上一页');
		$page->setConfig('next', '下一页');
		$data['page'] = $page->show();
		/************************************** 取数据 ******************************************/
		$data['data'] = $this->alias('a')->where($where)->group('a.id')->limit($page->firstRow.','.$page->listRows)->select();
		return $data;
	}

	// 删除前
	protected function _before_delete(&$data, $option)
	{
	    $children = $this->getChild($data['where']['id']);
	    $children[] = $data['where']['id'];
	    $data['where']['id']=array(
	        0=>'IN',
	        1=>implode(',',$children)
	    );
		
	}
	/********** 获取所有子类Id ***********/
	public function getChild($catId){
	    $data = $this->select();
	    return $this->_getChild($data,$catId,TRUE);
	}
	public function _getChild($data,$catId,$isClear=FALSE){
	    static $_ret = array();
	    if($isClear){
	        $_ret = array();
	    }
	    foreach($data as $k=>$v){
	        if($v['parent_id'] == $catId){
	            $_ret[] = $v['id'];
	            $this->_getChild($data,$v['id']);
	        }
	    }
	    return $_ret;
	}
	/********** 重新排序数据打印树形数据 ***********/
	public function getTree(){
	    $data = $this->select();
	    return $this->_getTree($data);
	}
	public function _getTree($data,$parent_id=0,$level=0){
	    static $_ret = array();
	    foreach ($data as $k=>$v){
	        if($v['parent_id'] == $parent_id){
	            $v['level'] = $level;
	            $_ret[] = $v;
	            $this->_getTree($data,$v['id'],$level+1);
	           
	        }
	    }
	    return $_ret;
	}
	
	/**********************************前台方法*******************************************/
	//获取导航条数据
	public function getNavData(){
	    //获取缓存，如没有，重新生成
	    //$catData = S('catData');
	    if(!$catData){
	        $all = $this->select();
	        $ret = array();
	        foreach ($all as $k=>$v){
	            //顶级分类
	            if($v['parent_id'] == 0){
	                //取出二级分类
	                foreach ($all as $k1=>$v1){
	                    if($v1['parent_id'] == $v['id']){
	                        //三级分类
	                        foreach ($all as $k1=>$v2){
	                            if($v2['parent_id'] == $v1['id']){
	                                $v1['children'][] = $v2;
	                            }
	                        }
	                        $v['children'][] = $v1;
	                    }
	                }
	                $ret[] = $v;
	            }
	        }
	        //S('catData',$ret,86400);
	        return $ret;
	    }else{
	        return $catData;
	    }
	}
	
	//获取推荐楼层数据
	public function floorData(){
	    //获取缓存，如没有，重新生成
	    //$floorData = S('floorData');
	    if($floorData){
	        return $floorData;
	    }else{
	        //先取出推荐楼层的顶级分类
	        $ret = $this->where(array(
	            'parent_id'=>array('eq',0),
	            'is_floor'=>array('eq','是')
	        ))->select();
	        $goodsModel = D('Admin/Goods');
	        foreach ($ret as $k=>$v){
	            //取出未推荐的二级分类并保存
	            $ret[$k]['subCat']= $this->where(array(
	                'parent_id'=>array('eq',$v['id']),
	                'is_floor'=>array('eq','否')
	            ))->select();
	            //取出推荐的二级分类并保存
	            $ret[$k]['recSubCat']= $this->where(array(
	                'parent_id'=>array('eq',$v['id']),
	                'is_floor'=>array('eq','是')
	            ))->select();
	            //循环推荐的二级分类取出8件推荐的商品
	            foreach ( $ret[$k]['recSubCat'] as $k1=>&$v1){
	                //通过分类取出所有的商品ID返回数组
	                $gids = $goodsModel->getGoodsIdByCatId($v1['id']);
	                //根据商品ID取出商品数据 
	                $v1['goods'] = $goodsModel
	                ->field('id,mid_logo,goods_name,shop_price')
	                ->where(array(
	                    'is_on_sale'=>array('eq','是'),
	                    'is_floor'=>array('eq','是'),
	                    'id'=>array('in',$gids)
	                ))
	                ->order("sort_num ASC")
	                ->limit(8)
	                ->select();
	            }
	            //取出商品所用的的品牌
	            $ret[$k]['brand'] = $goodsModel->alias('a')
	            ->field('DISTINCT brand_id,b.brand_name,b.logo')
	            ->join("LEFT JOIN __BRAND__ b ON a.brand_id=b.id")
	            ->where(array(
	                'a.id'=>array('in',$gids),
	                'a.brand_id'=>array('neq',0)
	            ))
	            ->limit(9)
	            ->select();
	            
	        }
	        
	        //S('floorData',$ret,86400);
	        
	    }
	    
	    return $ret;
	}
	
	public function parentPath($catId){
	    static $ret = array();
	    $info = $this->field("id,cat_name,parent_id")->find($catId);
	    //var_dump($info);
	    $ret[] = $info;
	    if($info['parent_id']>0){
	        $this->parentPath($info['parent_id']);
	    }
	    return $ret;
	}
	
	public function getSearchConditionByCatId($goodsId){
	    // 取出所有的商品Id
	    $ret = array();
	    $goodsModel = D('Admin/Goods');
	   
	    //取出所有品牌
	    $ret['brand'] = $goodsModel->alias('a')
	    ->field('DISTINCT brand_id,b.brand_name')
	    ->join("LEFT JOIN __BRAND__ b ON a.brand_id=b.id")
	    ->where(array(
	        'a.id'=>array('in',$goodsId),
	        'a.brand_id'=>array('neq',0)
	    ))
	    ->limit(9)
	    ->select();
	    //价格区间,默认6段
	    $sectionCount = 6;
	    //取出价格最大和最小值
	    $priceInfo = $goodsModel->where(array(
	        'id'=>array('in',$goodsId)
	    ))
	    ->field("MAX(shop_price) max_price,MIN(shop_price) min_price")
	    ->find();
	    $priceSection = $priceInfo['max_price']-$priceInfo['min_price'];
	    
	    //取出商品数量
	    $goodsCount = count($goodsId);
	    if($goodsCount >1){
	        if($priceSection < 100){
	            $sectionCount = 2;
	        }elseif ($priceSection < 1000){
	            $sectionCount = 4;
	        }elseif ($priceSection < 10000){
	            $sectionCount = 6;
	        }else{
	            $sectionCount = 7;
	        }
	    }
	    //区间大小
	    $pricePerSection = ceil($priceSection/$sectionCount);
	    $price = array();
	    $firstPrice = 0;
	    for($i=0;$i<$sectionCount;$i++){
	        $_tmpEnd = $firstPrice+$pricePerSection;
	        $price[] = $firstPrice.'-'.$_tmpEnd;
	        $firstPrice = $_tmpEnd+1;
	    }
	    $ret['price'] = $price;
	                 
	    //查询商品属性
	    $gaModel = D('goods_attr');
	    $gaData = $gaModel->alias('a')
	    ->field('DISTINCT a.attr_id,a.attr_value,b.attr_name')
	    ->where(array(
	        'a.goods_id'=>array('in',$goodsId),
	    ))
	    ->join('LEFT JOIN __ATTRIBUTE__ b ON b.id=a.attr_id')
	    ->select();
	    $_gaData = array();
	    foreach ($gaData as $k=>$v){
	        $_gaData[$v['attr_name']][] = $v;
	    }
	    $ret['gaData'] = $_gaData;
	    
	    return $ret;
	    
	    
	    
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}