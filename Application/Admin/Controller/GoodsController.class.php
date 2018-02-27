<?php
namespace Admin\Controller;
use Think\Controller;
use Org\Util\String;
header('Content-type:text/html;charset=utf-8');
class GoodsController extends BaseController {
   /**********添加模块**********/
   public function add(){
       //判断是否有数据传入
       
       if (IS_POST){
           //var_dump($_POST);die;
           //脚本执行时间【默认PHP一个脚本只能执行30秒】
           set_time_limit(0);
           $model = D('goods');
           //create接收数据，I函数过滤，“1”都要检验
           if($model->create(I('post.',1))){
               //添加数据到数据库
               if($model->add()){
                   $this->success('操作成功！',U('lst'));
                   exit;
               }
           }
           //从模型中获取错误信息
           $error = $model->getError();
           //控制器中输出错误信息
           $this->error();
       }
       //取出会员数据
       $mlModel = D('member_level');
       $mlData = $mlModel->select();
       //取出分类数据
       $model = D('Category');
       $catData = $model->getTree();
       //模版显示
       $this->assign(array(
           'catData'=>$catData,
           'mlData'=>$mlData,
           '_page_title'=>'添加商品',
           '_page_btn_name'=>'商品列表',
           '_page_btn_link'=>U('lst')
       ));
       $this->display();
   }
   
   /**********显示模块**********/
   public function lst(){
       $model = D('goods');
       //获取数据
       $data  = $model->search();
       $this->assign($data);
       //var_dump($data);die;
       $model = D('Category');
       $catData = $model->getTree();
       $this->assign(array(
           'catData'=>$catData,
           '_page_title'=>'商品列表',
           '_page_btn_name'=>'添加新商品',
           '_page_btn_link'=>U('add')
       ));
       $this->display();
   }
   
   /**********修改模块**********/
   public function edit(){
   $id = I('get.id');  // 要修改的商品的ID
		$model = D('goods');
		if(IS_POST)
		{
		    //var_dump($_POST);die;
			if($model->create(I('post.'), 2))
			{
				if(FALSE !== $model->save())  // save()的返回值是，如果失败返回false,如果成功返回受影响的条数【如果修改后和修改前相同就会返回0】
				{
					$this->success('操作成功！', U('lst'));
					exit;
				}
			}
			$error = $model->getError();
			$this->error($error);
		}
       $data = $model->find(I('get.id'));
       //获得会员等级数据
       $mlModel = D('member_level');
       $mlData = $mlModel->select();
       //获得会员价格数据
       $mpModel = D('member_price');
       $mpData = $mpModel->where(array(
           'goods_id'=>array('eq',$id),
       ))->select();
       foreach ($mpData as $k=>$v){
           $_mpData[$v['level_id']] = $v['price']; 
       }
       //获取图片信息
       $gpModel = D('goods_pic');
       $gpData = $gpModel->field('id,mid_pic')->where(array(
           'goods_id'=>array('eq',$id)
       ))->select();
       //取出分类数据
       $model = D('Category');
       $catData = $model->getTree();
       //取出扩展分类数据
       $gcmodel = D('goods_cat');
       $gcData = $gcmodel->where(array(
           'goods_id'=>$id
       ))->select();
       //取出类型数据并关联属性数据
       $gaModel = D('Attribute');
       $gaData = $gaModel->field('a.id attr_id,a.attr_name,a.attr_name,a.attr_type,a.attr_option_values,b.attr_value,b.id')->alias('a')
       ->join('LEFT JOIN __GOODS_ATTR__ b ON (b.attr_id=a.id AND b.goods_id='.$data['id'].')')
       ->where(array(
           'type_id'=>array('eq',$data['type_id'])
       ))->select();
       //var_dump($gaData);die;
       //模版显示
       $this->assign('data',$data);
       $this->assign(array(
           'mlData'=>$mlData,
           '_mpData'=>$_mpData,
           'gpData'=>$gpData,
           'catData'=>$catData,
           'gcData'=>$gcData,
           'gaData'=>$gaData,
           '_page_title'=>'修改商品',
           '_page_btn_name'=>'商品列表',
           '_page_btn_link'=>U('lst')
       ));
       $this->display();
   }
   
   /**********删除模块**********/
   public function delete(){
       $id = I('get.id');
       $model = D('goods');
       if(false !== $model->delete($id)){
           $this->success("删除成功！",U('lst'));
       }else{
           $this->error("删除失败！",$model->getError());
       }
   }
   /**********Ajax删除图片模块**********/
   public function ajaxDelPic(){
       $picId = I('get.picid');
       $gpModel = D('goods_pic');
       $pic = $gpModel->field('pic,sm_pic,mid_pic,big_pic')->find($picId);
       deleteImage($pic);
       $gpModel->delete($picId);
   }
   /**********Ajax获取类型属性模块**********/
   public function ajaxGetAttr(){
       $typeId = I('get.type_id');
       $attrModel = D('attribute');
       $attrData = $attrModel->where(array(
           'type_id'=>array('eq',$typeId),
       ))->select();
       echo json_encode($attrData);
   }
   
   public function goods_number(){
       $id = I('get.id');
       //接收提交的库存数据
       $gnModel = D('goods_number');
       if(IS_POST){
           $gnModel->where(array(
               'goods_id'=>array('eq',$id)
           ))->delete();
           $gaid = I('post.goods_attr_id');
           $gn = I('post.goods_number');
           
           //计算商品属性ID和库存量比
           $gaidCount = count($gaid);
           $gnCount = count($gn);
           $rate = $gaidCount/$gnCount;
           //循环插入数据
           $_i = 0;
           foreach($gn as $k=>$v){
               $_goodsAttrId = array();
               for($i=0;$i<$rate;$i++){
                   $_goodsAttrId[]=$gaid[$_i];
                   $_i++;
               }
               //升序排列，防止前后冲突
               sort($_goodsAttrId,SORT_NUMERIC);
               //数组转化为字符串存入
               $_goodsAttrId = (string)implode(',',$_goodsAttrId);
              $gnModel->add(array(
                   'goods_id'=>$id,
                   'goods_attr_id'=>$_goodsAttrId,
                   'goods_number'=>$v
               ));
               
           }
           $this->success('操作成功！',U('lst'));
           exit;
       }
       //取出库存量
       $gnData = $gnModel->where(array(
           'goods_id'=>array('eq',$id)
       ))->select();
        
       $gaModel = D('goods_attr');
       $gaData = $gaModel->alias(a)
       ->field("a.*,b.attr_name")
       ->join("LEFT JOIN __ATTRIBUTE__ b ON a.attr_id=b.id")
       ->where(array(
           'a.goods_id'=>array('eq',$id),
           'b.attr_type'=>array('eq','可选')
       ))->select();
       $_gaData = array();
       foreach ($gaData as $k=>$v){
           $_gaData[$v['attr_name']][] = $v;
       }
       //var_dump($_gaData);
       $this->assign(array(
           'gaData'=>$_gaData,
           'gnData'=>$gnData,
           '_page_title'=>'库存量',
           '_page_btn_name'=>'返回列表',
           '_page_btn_link'=>U('lst')
       ));
       $this->display();
   }
   
   
   
   
   
   
   
   
   
   
   
   
   
   
}


