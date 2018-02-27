<?php 
namespace Admin\Model;
use Think\Model;
define('APP_DEBUG', true);
class GoodsModel extends Model{
    
    // 添加时调用create方法允许接收的字段
	protected $insertFields = 'goods_name,market_price,shop_price,is_on_sale,goods_desc,brand_id,cat_id,type_id,promote_price,promote_start_date,promote_end_date,is_new,is_hot,is_best,sort_num,is_floor';
	// 修改时调用create方法允许接收的字段
	protected $updateFields = 'id,goods_name,market_price,shop_price,is_on_sale,goods_desc,brand_id,cat_id,type_id,promote_price,promote_start_date,promote_end_date,is_new,is_hot,is_best,sort_num,is_floor';
    
    //定义create（）接收数据时的验证规则
    protected $_validate = array(
        array('cat_id','require','主分类不能为空！',1),
        array('goods_name','require','商品名称不能为空！',1),
        array('market_price','currency','市场价格必须是货币类型！',1),
        array('shop_price','currency','本店价格必须是货币类型！',1)
    );
    
    //钩子方法，在添加数据之前会自动被调用
    /*
     * 第一个参数：表单中即将要被插入到数据库中的数据->array
     * &引用传递：函数内部要修改函数外表传进来的变量必须按引用传递，除非传递的是一个对象，对象默认按引用传递
     */
    protected function _before_insert(&$data, $options){
        if ($_FILES['logo']['error'] == 0){
            $ret = uploadOne('logo','Goods',array(
                array(50,50),
                array(130,130),
                array(350,350),
                array(700,700),
            ));    
            //把路径放到表单中
            $data['logo'] = $ret['images'][0];
            $data['sm_logo'] = $ret['images'][1];
            $data['mid_logo'] = $ret['images'][2];
            $data['big_logo'] = $ret['images'][3];
            $data['mbig_logo'] = $ret['images'][4];
                
            }
        $data['addtime'] = date('Y-m-d H:i:s',time());
        $data['goods_desc'] = removeXSS($_POST['goods_desc']);
    }
    
    protected function _after_insert(&$data, $options){
        //添加商品属性数据
        $attrValue=I('post.attr_value');
        $gaModel = D('goods_attr');
        foreach ($attrValue as $k=>$v){
            //去重，防止属性重复
            $v=array_unique($v);
            foreach ($v as $k1=>$v1){
                $gaModel->add(array(
                    'goods_id'=>$data['id'],
                    'attr_id'=>$k,
                    'attr_value'=>$v1
                ));
            }
        }
        //添加扩展分类数据
        $exid = I('post.ext_cat_id');
        if($exid){
            $gcModel=D('goods_cat');
            foreach ($exid as $k=>$v){
                if (empty($v)){
                    continue;
                }
                $gcModel->add(array(
                    'cat_id'=>$v,
                    'goods_id'=>$data['id']
                ));
            }
        }
        
        if(isset($_FILES['pic'])){
            $pics = array();
            //整理接收到的图片数据，便于UploadOne()
            foreach ($_FILES['pic']['name'] as $k=>$v){
                $pics[]=array(
                    'name'=>$v,
                    'type'=>$_FILES['pic']['type'][$k],
                    'tmp_name'=>$_FILES['pic']['tmp_name'][$k],
                    'error'=>$_FILES['pic']['error'][$k],
                    'size'=>$_FILES['pic']['size'][$k]
                );
            }
            $_FILES = $pics;
            $gpModel = D('goods_pic');
            foreach($pics as $k=>$v){
                if($v['error'] == 0){
                    $ret = uploadOne($k,'goods',array(
                       array(650,650),
                       array(350,350),
                       array(50,50),
                    ));
                }
                if($ret['ok'] == 1){
                    $gpModel->add(array(
                        'pic'=>$ret['images'][0],
                        'big_pic'=>$ret['images'][1],
                        'mid_pic'=>$ret['images'][2],
                        'sm_pic'=>$ret['images'][3],
                        'goods_id'=>$data['id']
                    ));
                }
            }
        }
        
        $mp = I('post.member_price');
        $mpModel = D('member_price');
        foreach ($mp as $k=>$v){
            $_v = (float)$v;
            if($_v>0){
                $mpModel->add(array(
                    'price'=>$_v,
                    'level_id'=>$k,
                    'goods_id'=>$data['id']
                ));
            }
        }
    }

    public function search($perPage = 15){
        /******排序功能***********************/
        $orderby = 'a.id';
        $orderway = 'desc';
        $odby = I('get.odby');
        
        if($odby){
            if($odby == 'id_asc'){
                $orderway = 'asc';
            }elseif ($odby == 'price_desc'){
                $orderby = 'shop_price';
                $orderway = 'desc';
            }elseif ($odby == 'price_asc'){
                $orderby = 'shop_price';
                $orderway = 'asc';
            }
        }
        
        /*******搜索功能***********************/
        $where = array();
        //所属分类
        $catId = I('get.cat_id');
        if($catId){
            $gids = $this->getGoodsIdByCatId($catId);
            $where['a.id'] = array('IN',$gids);
        }
        //所属品牌
        $brandId = I('get.brand_id');
        if($brandId){
            $where['a.brand_id'] = array('eq',$brandId);//
        }
        //商品名称
        $gn = I('get.gn');
        if($gn){
            $where['a.goods_name'] = array('like',"%$gn%");//WHERE goods_name like '%$gn%';
        }
        //价格区间
        $fp = I('get.fp');
        $tp = I('get.tp');
        if($fp && $tp){
            $where['a.shop_price'] = array('between',array($fp,$tp));
        }elseif ($fp){
            $where['a.shop_price'] = array('egt',$fp);
        }elseif ($tp){
            $where['a.shop_price'] = array('elt',$tp);
        }
        //是否上架
        $ios = I('get.ios');
        if($ios){
            $where['a.is_on_sale'] = array('eq',$ios);
        }
        //时间搜索
        $fa = I('get.fa');
        $ta = I('get.ta');
        if($fa && $ta){
            $where['a.addtime'] = array('between',array($fa,$ta));
        }elseif ($fp){
            $where['a.addtime'] = array('egt',$fa);
        }elseif ($tp){
            $where['a.addtime'] = array('elt',$ta);
        }
        /******分页功能******/
        //获取记录数
        $count = $this->alias('a')->where($where)->count();
        // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $Page = new \Think\Page($count,$perPage);
        //设置分页的样式
        $Page->setConfig('next','下一页');
        $Page->setConfig('prev','上一页');
        // 分页显示输出
        $show = $Page->show();
        //获取数据
        $data = $this->order("$orderby $orderway")
        ->field('a.*,b.brand_name,c.cat_name,GROUP_CONCAT(e.cat_name) ext_cat_name')
        ->alias('a')
        ->join('LEFT JOIN __BRAND__ b ON a.brand_id=b.id 
                LEFT JOIN __CATEGORY__ c ON a.cat_id=c.id
                LEFT JOIN __GOODS_CAT__ d ON a.id=d.goods_id 
                LEFT JOIN __CATEGORY__ e ON d.cat_id=e.id
            ')
        ->where($where)
        ->limit($Page->firstRow.','.$Page->listRows)
        ->group('a.id')
        ->select();
        //返回数据
        return array(
            'data' => $data,
            'page' => $show
        );
    }
    public function getGoodsIdByCatId($catId){
        //取出所有子分类的ID
        $catModel = D('Admin/category');
        $children = $catModel->getChild($catId);
        $children[] = $catId;
        //goods表中取出主分类下的所有商品
        $gids = $this->field('id')->where(array(
            'cat_id'=>array('IN',$children)
        ))->select();
        
        //取出扩展分类下的商品ID
        $gcModel = D('goods_cat');
        $gids1 = $gcModel->field('DISTINCT goods_id id')->where(array(
            'cat_id'=>array('in',$children)
        ))->select();
        //合并ID数组
        if($gids && $gids1){
            $gids = array_merge($gids,$gids1);
        }elseif ($gids1){
            $gids = $gids1;
        }
        $id=array();
        foreach ($gids as $k=>$v){
            if(!in_array($v['id'],$id)){
                $id[] = $v['id'];
            }
        }
        return $id;
    }
    
    protected function _before_update(&$data, $options){
        $id = $options['where']['id'];
        
        $gaid = I('post.goods_attr_id');
        $attrvalue = I('post.attr_value');
        $gaModel = D('goods_attr');
        $_i = 0;
        foreach ($attrvalue as $k=>$v){
            foreach ($v as $k1=>$v1){
                if($gaid[$_i] == ''){
                    $gaModel->add(array(
                        'attr_value'=>$v1,
                        'attr_id'=>$k,
                        'goods_id'=>$id
                    ));
                }else{
                    $gaModel->where(array(
                        'id'=>array('eq',$gaid[$_i])
                    ))->setField(array(
                        'attr_value'=>$v1
                    ));
                }
                $_i++;
            }
        }
        //删除原先分类数据
        $exid = I('post.ext_cat_id');
        $gcModel=D('goods_cat');
        $gcModel->where(array(
            'goods_id'=>array('eq',$id)
        ))->delete();
        if($exid){
            foreach ($exid as $k=>$v){
                if (empty($v)){
                    continue;
                }
                $gcModel->add(array(
                    'cat_id'=>$v,
                    'goods_id'=>$id
                ));
            }
        }
        $mp = I('post.member_price');
        //删除原来的会员价格
        $mpModel = D('member_price');
        $mpModel->where(array(
            'goods_id'=>array('eq',$id),
        ))->delete();
        foreach ($mp as $k=>$v){
            $_v = (float)$v;
            if($_v>0){
                $mpModel->add(array(
                    'price'=>$_v,
                    'level_id'=>$k,
                    'goods_id'=>$id
                ));
            }
        }
        if ($_FILES['logo']['error'] == 0){
            $upload = new \Think\Upload();
            $upload->maxSize   =     1024*1024 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath  =      './Public/Uploads/'; // 设置附件上传目录
            $upload->savePath  =      'Goods/';
            // 上传文件
            $info   =   $upload->upload();
            if(!$info) {
                // 上传错误提示错误信息
                $this->error = $upload->getError();
        
                return false;
            }else{
                // 上传成功
                //生成缩略图，先拼出路径和名称
                 
                $logo = $info['logo']['savepath'].$info['logo']['savename'];
                $smlogo = $info['logo']['savepath'].'sm_'.$info['logo']['savename'];
                $midlogo = $info['logo']['savepath'].'mid_'.$info['logo']['savename'];
                $biglogo = $info['logo']['savepath'].'big_'.$info['logo']['savename'];
                $mbiglogo = $info['logo']['savepath'].'mbig_'.$info['logo']['savename'];
                $image = new \Think\Image();
                //打开要生成缩略图的图片
                $image->open('./Public/Uploads/'.$logo);
                //生成缩略图
                $image->thumb(50, 50)->save('./Public/Uploads/'.$smlogo);
                $image->thumb(130, 130)->save('./Public/Uploads/'.$midlogo);
                $image->thumb(350, 350)->save('./Public/Uploads/'.$biglogo);
                $image->thumb(700, 700)->save('./Public/Uploads/'.$mbiglogo);
                //把路径放到表单中
                $data['logo'] = $logo;
                $data['sm_logo'] = $smlogo;
                $data['mid_logo'] = $midlogo;
                $data['big_logo'] = $biglogo;
                $data['mbig_logo'] = $mbiglogo;
                
                //删除就图片
                $oldLogo = $this->field('logo,sm_logo,mid_logo,big_logo,mbig_logo')->find("$id");
               
                unlink('./Public/Uploads/'.$oldLogo['logo']);
                unlink('./Public/Uploads/'.$oldLogo['sm_logo']);
                unlink('./Public/Uploads/'.$oldLogo['mid_logo']);
                unlink('./Public/Uploads/'.$oldLogo['big_logo']);
                unlink('./Public/Uploads/'.$oldLogo['mbig_logo']);
            }
            
        }
        $data['goods_desc'] = removeXSS($_POST['goods_desc']);
        if(isset($_FILES['pic'])){
            $pics = array();
            //整理接收到的图片数据，便于UploadOne()
            foreach ($_FILES['pic']['name'] as $k=>$v){
                $pics[]=array(
                    'name'=>$v,
                    'type'=>$_FILES['pic']['type'][$k],
                    'tmp_name'=>$_FILES['pic']['tmp_name'][$k],
                    'error'=>$_FILES['pic']['error'][$k],
                    'size'=>$_FILES['pic']['size'][$k]
                );
            }
            $_FILES = $pics;
            $gpModel = D('goods_pic');
            foreach($pics as $k=>$v){
                if($v['error'] == 0){
                    $ret = uploadOne($k,'goods',array(
                        array(650,650),
                        array(350,350),
                        array(50,50),
                    ));
                }
                if($ret['ok'] == 1){
                    $gpModel->add(array(
                        'pic'=>$ret['images'][0],
                        'big_pic'=>$ret['images'][1],
                        'mid_pic'=>$ret['images'][2],
                        'sm_pic'=>$ret['images'][3],
                        'goods_id'=>$id
                    ));
                }
            }
        }
    }
    protected function _before_delete(&$data, $options){
        $id = $data['where']['id'];
        //先删除库存数据
        $gnModel = D('goods_number');
        $gnModel->where(array(
               'goods_id'=>array('eq',$id)
           ))->delete();
        //先删除分类
        $gcModel = D('goods_cat');
        $gcModel->where(array(
            'goods_id'=>array('eq',$id)
        ))->delete();
        //先删除图片
        $gpModel = D('goods_pic');
        $pics = $gpModel->field('pic','big_pic','mid_pic','sm_pic')->where(array(
            'goods_id'=>array('eq',$id),
        ))->select();
        foreach($pics as $k=>$v){
            deleteImage($v);
            $gpModel->where(array(
                'goods_id'=>array('eq',$id)
            ))->delete();
        }
    }
    public function ajaxDelAttr(){
        $gaid = I('get.gaid');
        $goodsId = I('get.goods_id');
        $gaModel = D('goods_attr');
        $gaModel->delete($gaid);
        $gnModel = D('goods_number');
        $gnModel->where(array(
            'goods_id'=>array('EXP',"=$goodsId AND FIND_IN_SET($gaid,goods_attr_id)")
        ))->delete();
    }
    
    /******************************前台方法********************************************/
    public function getPromoteGoods($limit = 5){
        $today = date('Y-m-d H:i');
        return $this->field('id,goods_name,mid_logo,promote_price')
        ->where(array(
            'is_on_sale'=>array('eq','是'),
            'promote_price'=>array('gt',0),
            'promote_start_date'=>array('elt',$today),
            'promote_end_date'=>array('egt',$today),
        ))
        ->limit($limit)
        ->order('sort_num ASC')
        ->select();
    }
    
    public function getRecGoods($recType,$limit = 5){
        $today = date('Y-m-d H:i');
        return $this->field('id,goods_name,mid_logo,promote_price')
        ->where(array(
            'is_on_sale'=>array('eq','是'),
            "$recType"=>array('eq','是')
        ))
        ->limit($limit)
        ->order('sort_num ASC')
        ->select();
    }

	//获取会员价格
	public function getMemberPrice($goodsId){
	    $today = date("Y-m-d H:i");
	    $levelId = session('level_id');
	    //获取促销价格
	    $promotePrice = $this->field('promote_price')
	    ->where(array(
	        'promote_price'=>array('gt',0),
	        'promote_start_date'=>array('elt',$today),
	        'promote_end_date'=>array('egt',$today),
	    ))->find($goodsId);
	    
	    //判断会员有没有登录
	    if($levelId){
	        $mpModel = D('member_price');
	        $mpData = $mpModel->field('price')
	        ->where(array(
	            'goods_id'=>array('eq',$goodsId),
	            'level_id'=>array('eq',$levelId),
	        ))->find();
	        //判断有没有设置会员价格
	        if($mpData['price']){
	            //对比返回最便宜的价格
	            if($promotePrice['promote_price']){
	                return min($promotePrice['promote_price'],$mpData['price']);
	            }else{
	                return $mpData['price'];
	            }
	        }else{
    	        $p = $this->field('shop_price')->find($goodsId);
    	        if($promotePrice['promote_price']){
    	            return min($promotePrice['promote_price'],$p['shop_price']);
    	        }else{
    	            return $p['shop_price'];
    	        }
	        }
	    }else {
	        $p = $this->field('shop_price')->find($goodsId);
	        
	        if($promotePrice['promote_price']){
	            return min($promotePrice['promote_price'],$p['shop_price']);
	        }else{
	            return $p['shop_price'];
	        }
	    }
	    
	}
    
	//计算销量，一件商品在已经支付过的定单中出现的次数
	public function cat_search($catId,$pageSize=60){
	    /********************搜索***************/
	    //拼凑所有搜索条件，商品ID
	    $goodsId = $this->getGoodsIdByCatId($catId);
	    $where['a.id'] = array('in',$goodsId);
	    //品牌ID
	    $brandId = I('get.brand_id');
	    if($brandId){
	        $where['a.brand_id'] = array('eq',(int)$brandId);
	    }
	    //价格
	   $price = I('get.price');
	    if($price){
	        $price = explode('-',$price);
	        $where['a.shop_price'] = array('between',$price);
	    }
	    //取出属性
	    $gaModel = D('goods_attr');
	    $attrGoodsId = NULL;
	    foreach($_GET as $k=>$v){
	        //attr_1/黑色-颜色,以attr_开头
	        if(strpos($k,'attr_') === 0){
	            $attrId = str_replace('attr_','',$k);
	            $attrName = strrchr($v,'-');
	            $attrValue = str_replace($attrName,'',$v);
	            $gids = $gaModel->field("GROUP_CONCAT(goods_id) gids")
	            ->where(array(
	                'attr_id'=>array('eq',$attrId),
	                'attr_name'=>array('eq',$attrName),
	            ))->find();
	            if($gids['gids']){
	                $gids['gids'] = explode(',',$gids['gids']);
	                if($attrGoodsId == NULL){
	                    //空，说明是第一个属性，先存起来
	                    $attrGoodsId = $gids['gids'];
	                }else{
	                    $attrGoodsId = array_intersect($attrGoodsId,$gids['gids']);
	                    //取交集后为空，就不用接下去考虑下个属性了
	                    if(empty($attrGoodsId)){
	                        $where['a.id'] = array('eq',0);
	                        break;
	                    }
	                }
	            }else{
	                //这个属性找不到商品ID，则清空前几次交集
	                $attrGoodsId = array();
	                $where['a.id'] = array('eq',0);
	                break;
	            }
	        }
	    }
	    //循环后如果还有值，说明是满足这些条件的商品id
	    if($attrGoodsId){
	        $where['a.id'] = array('in',$attrGoodsId);
	    }
	    /******分页功能******/
	    //获取记录数
	    $count = $this->alias('a')
	    ->field('COUNT(a.id) goods_count,GROUP_CONCAT(a.id) goods_id')
	    ->where($where)->find();
	    //返回商品ID
	    $data['goods_id'] = explode(',',$count['goods_id']);
	    // 实例化分页类 传入总记录数和每页显示的记录数(25)
	    $Page = new \Think\Page($count['goods_count'],$pageSize);
	    //设置分页的样式
	    $Page->setConfig('next','下一页');
	    $Page->setConfig('prev','上一页');
	    // 分页显示输出
	    $data['page'] = $Page->show();
	    
	    /***********************排序*****************************/
	    $oderby = 'x1';
	    $oderway = 'desc';
	    $odby = I('get.odby');
	    if($odby){
	        if($odby == 'addtime'){
	            $oderby = 'a.addtime';
	        }
	        if(strpos($odby,'price_') === 0){
	            $oderby = 'a.shop_price';
	            if($odby == 'price_asc'){
	                $oderway = 'asc';
	            }
	        }
	    }
	    //取出满足条件的数据
	    $data['data'] = $this->alias('a')
	    ->field('a.id,a.goods_name,a.mid_logo,a.shop_price,SUM(b.goods_number) x1')
	    ->join("LEFT JOIN __ORDER_GOODS__ b ON (a.id=b.goods_id
	        AND b.order_id IN(SELECT id FROM __ORDER__ WHERE pay_status='是'))")
	    ->where($where)
	    ->group('a.id')
	    ->limit($page->firstRow.','.$page->listRows)
	    ->order("$oderby $oderway")
	    ->select();
	    return $data;
	}
	
	/*2*/
    /********************************关键字搜索******************************/
	public function key_search($key,$pageSize=60){
	    /********************搜索***************/
	    //拼凑所有搜索条件，商品ID
	    //$goodsId = $this->getGoodsIdByCatId($catId);
	    $goodsId = $this->alias('a')
	    ->field('GROUP_CONCAT(DISTINCT a.id) gids')
	    ->join('LEFT JOIN __GOODS_ATTR__ b ON a.id=b.goods_id')
	    ->where(array(
	        'a.is_on_sale'=>array('eq','是'),
	        'a.goods_name'=>array('exp',"LIKE '%$key%' OR a.goods_desc LIKE '%$key%' OR attr_value LIKE '%$key%'"),
	    ))->find();
	    
	    $goodsId = explode(',',$goodsId['gids']);
	    var_dump($this->getLastSql());
	    $where['a.id'] = array('in',$goodsId);
	    //品牌ID
	    $brandId = I('get.brand_id');
	    if($brandId){
	        $where['a.brand_id'] = array('eq',(int)$brandId);
	    }
	    //价格
	    $price = I('get.price');
	    if($price){
	        $price = explode('-',$price);
	        $where['a.shop_price'] = array('between',$price);
	    }
	    //取出属性
	    $gaModel = D('goods_attr');
	    $attrGoodsId = NULL;
	    foreach($_GET as $k=>$v){
	        //attr_1/黑色-颜色,以attr_开头
	        if(strpos($k,'attr_') === 0){
	            $attrId = str_replace('attr_','',$k);
	            $attrName = strrchr($v,'-');
	            $attrValue = str_replace($attrName,'',$v);
	            $gids = $gaModel->field("GROUP_CONCAT(goods_id) gids")
	            ->where(array(
	                'attr_id'=>array('eq',$attrId),
	                'attr_name'=>array('eq',$attrName),
	            ))->find();
	            if($gids['gids']){
	                $gids['gids'] = explode(',',$gids['gids']);
	                if($attrGoodsId == NULL){
	                    //空，说明是第一个属性，先存起来
	                    $attrGoodsId = $gids['gids'];
	                }else{
	                    $attrGoodsId = array_intersect($attrGoodsId,$gids['gids']);
	                    //取交集后为空，就不用接下去考虑下个属性了
	                    if(empty($attrGoodsId)){
	                        $where['a.id'] = array('eq',0);
	                        break;
	                    }
	                }
	            }else{
	                //这个属性找不到商品ID，则清空前几次交集
	                $attrGoodsId = array();
	                $where['a.id'] = array('eq',0);
	                break;
	            }
	        }
	    }
	    //循环后如果还有值，说明是满足这些条件的商品id
	    if($attrGoodsId){
	        $where['a.id'] = array('in',$attrGoodsId);
	    }
	    /******分页功能******/
	    //获取记录数
	    $count = $this->alias('a')
	    ->field('COUNT(a.id) goods_count,GROUP_CONCAT(a.id) goods_id')
	    ->where($where)->find();
	    //返回商品ID
	    $data['goods_id'] = explode(',',$count['goods_id']);
	    // 实例化分页类 传入总记录数和每页显示的记录数(25)
	    $Page = new \Think\Page($count['goods_count'],$pageSize);
	    //设置分页的样式
	    $Page->setConfig('next','下一页');
	    $Page->setConfig('prev','上一页');
	    // 分页显示输出
	    $data['page'] = $Page->show();
	     
	    /***********************排序*****************************/
	    $oderby = 'x1';
	    $oderway = 'desc';
	    $odby = I('get.odby');
	    if($odby){
	        if($odby == 'addtime'){
	            $oderby = 'a.addtime';
	        }
	        if(strpos($odby,'price_') === 0){
	            $oderby = 'a.shop_price';
	            if($odby == 'price_asc'){
	                $oderway = 'asc';
	            }
	        }
	    }
	    //取出满足条件的数据
	    $data['data'] = $this->alias('a')
	    ->field('a.id,a.goods_name,a.mid_logo,a.shop_price,SUM(b.goods_number) x1')
	    ->join("LEFT JOIN __ORDER_GOODS__ b ON (a.id=b.goods_id
	        AND b.order_id IN(SELECT id FROM __ORDER__ WHERE pay_status='是'))")
		        ->where($where)
		        ->group('a.id')
		        ->limit($page->firstRow.','.$page->listRows)
		        ->order("$oderby $oderway")
		        ->select();
		        return $data;
	}
    
    
    
    
    
    
    
    
    
}
    

?>