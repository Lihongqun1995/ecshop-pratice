<?php
namespace Admin\Model;
use Think\Model;
class OrderModel extends Model 
{
	// 下单时允许表单的字段
	protected $insertFields = array('shr_name','shr_tel','shr_province','shr_city','shr_area','shr_address');
	// 下单时的表单验证规则
	protected $_validate = array(
		array('shr_name', 'require', '收货人姓名不能为空！', 1, 'regex', 3),
		array('shr_tel', 'require', '收货人电话不能为空！', 1, 'regex', 3),
		array('shr_province', 'require', '所在省不能为空！', 1, 'regex', 3),
		array('shr_city', 'require', '所在城市不能为空！', 1, 'regex', 3),
		array('shr_area', 'require', '所在地区不能为空！', 1, 'regex', 3),
		array('shr_address', 'require', '详细地址不能为空！', 1, 'regex', 3),
	);
	public function search($pageSize = 20)
	{
		/**************************************** 搜索 ****************************************/
		$memberId = session('m_id');
		$where['member_id'] = array('eq', $memberId);
		$noPayCount = $this->where(array(
			'member_id' => array('eq', $memberId),
			'pay_status' => array('eq', '否'),
		))->count();
		/************************************* 翻页 ****************************************/
		$count = $this->alias('a')->where($where)->count();
		$page = new \Think\Page($count, $pageSize);
		// 配置翻页的样式
		$page->setConfig('prev', '上一页');
		$page->setConfig('next', '下一页');
		$data['page'] = $page->show();
		/************************************** 取数据 ******************************************/
		$data['data'] = $this->alias('a')
		->field('a.id,a.shr_name,a.total_price,a.addtime,a.pay_status,GROUP_CONCAT(DISTINCT c.sm_logo) logo')
		->join('LEFT JOIN __ORDER_GOODS__ b ON a.id=b.order_id 
		        LEFT JOIN __GOODS__ c ON b.goods_id=c.id')
		->where($where)
		->group('a.id')
		->limit($page->firstRow.','.$page->listRows)
		->select();
		$data['noPayCount'] = $noPayCount;
		return $data;
	}
	protected function _before_insert(&$data, &$option)
	{
		$memberId = session('m_id');
		/********************* 下单前的检查 **************************/
		// 是否登录
		if(!$memberId){
		    $this->error('请先登录！',U('Login/login'));
		    return FALSE;
		}
		
		// 购物车中是否有商品
		$cartModel = D('Home/Cart');
		// 获取购物车中的商品,并保存到 $option变量中，这个$option会被传到 _after_insert中
		 $option['goods'] = $goods = $cartModel->cartList();
		if(!$goods){
		    $this->error('没有商品！');
		    return FALSE;
		}
		// 读库存之前加锁,注意：把锁赋给这个模型，这样这个锁可以一直保存到下单结束，否则如果是局部变量这个锁在_before_insert函数执行完之后注释放了
		$this->fp = fopen("./order.lock");
		flock($this->fp,LOCK_EX);
		// 循环购物车中的商品检查库存量并且计算商品总价
		$gnModel = D('goods_number');
		$total_price = 0;
		// 检查库存量
		foreach ($goods as $k=>$v){
		    $gnNumber = $gnModel->field('goods_number')->where(array(
		        'goods_id'=>$v['goods_id'],
		        'goods_attr_id'=>$v['goods_attr_id'],
		    ))->find();
		    if($gnNumber['goods_number'] < $v['goods_number']){
		        $this->error('下单失败！原因：商品<strong>'.$v['goods_name'].'</strong>库存量不足!');
		        return FALSE;
		    }
		    // 统计总价
		    $total_price += $v['price'] * $v['goods_number'];
		}
		// 把其他信息补到定单中
		$data['total_price'] = $total_price;
		$data['member_id'] = $memberId;
		$data['addtime'] = time();
		// 为了确定三张表的操作都能成功：定单基本信息表，定单商品表，库存量表
		$this->startTrans();
	}	
		
		
	// 定单基本信息生成之后, $data['id']就是新生成的定单的id
	protected function _after_insert($data, $option)
	{
		// 从$option中取出购物车中的商品并循环插入到定单商品表中并且减少库存
        $ogModel = D('order_goods');
	    $gnModel = D('goods_number');
	    foreach ($option['goods'] as $k=>$v){
	        $ret = $ogModel->add(array(
	            'order_id'=>$data['id'],
	            'goods_id'=>$v['goods_id'],
	            'goods_attr_id'=>$v['goods_attr_id'],
	            'goods_number'=>$v['goods_number'],
	            'price'=>$v['price'],
	        ));
	        if(!$ret){
	            $this->rollback();
	            return FALSE;
	        }
	        // 减库存
	        $ret = $gnModel->where(array(
	            'goods_id'=>$v['goods_id'],
	            'goods_attr_id'=>$v['goods_attr_id'],
	        ))->setDec('goods_number',$v['goods_number']);
	        if($ret === FALSE){
	            $this->rollback();
	            return FALSE;
	        }
	    }
	    // 所有操作都成功提交事务
		$this->commit();
		
		// 释放锁
		flock($this->fp,LOCK_UN);
		fclose($this->fp);
		
		// 清空购物车
		$cartModel = D('Cart');
		$cartModel->where('1')->delete();
	}
	/**
	 * 设置为已支付的状态 
	 *
	 * @param unknown_type $orderId
	 */
	public function setPaid($orderId)
	{
		/**
		 * ************　更新定单的支付状态　＊＊＊＊＊＊＊＊＊＊＊＊＊／
		 */
		
		/************ 更新会员积分 *******************/
		
	}
}


















