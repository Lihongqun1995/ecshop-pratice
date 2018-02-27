<?php
namespace Admin\Model;
use Think\Model;
header('Content-type:text/html;charset=utf-8');
class PrivilegeModel extends Model 
{
	protected $insertFields = array('pri_name','moudle_name','controller_name','action_name','parent_id');
	protected $updateFields = array('id','pri_name','moudle_name','controller_name','action_name','parent_id');
	protected $_validate = array(
		array('pri_name', 'require', '权限名称不能为空！', 1, 'regex', 3),
		array('pri_name', '1,30', '权限名称的值最长不能超过 30 个字符！', 1, 'length', 3),
		array('moudle_name', '1,30', '模块名称的值最长不能超过 30 个字符！', 2, 'length', 3),
		array('controller_name', '1,30', '控制器名称的值最长不能超过 30 个字符！', 2, 'length', 3),
		array('action_name', '1,30', '方法名称的值最长不能超过 30 个字符！', 2, 'length', 3),
		array('parent_id', 'number', '上级权限ID必须是一个整数！', 2, 'regex', 3),
	);
	/************************************* 递归相关方法 *************************************/
	public function getTree()
	{
		$data = $this->select();
		return $this->_reSort($data);
	}
	private function _reSort($data, $parent_id=0, $level=0, $isClear=TRUE)
	{
		static $ret = array();
		if($isClear)
			$ret = array();
		foreach ($data as $k => $v)
		{
			if($v['parent_id'] == $parent_id)
			{
				$v['level'] = $level;
				$ret[] = $v;
				$this->_reSort($data, $v['id'], $level+1, FALSE);
			}
		}
		return $ret;
	}
	public function getChildren($id)
	{
		$data = $this->select();
		return $this->_children($data, $id);
	}
	private function _children($data, $parent_id=0, $isClear=TRUE)
	{
		static $ret = array();
		if($isClear)
			$ret = array();
		foreach ($data as $k => $v)
		{
			if($v['parent_id'] == $parent_id)
			{
				$ret[] = $v['id'];
				$this->_children($data, $v['id'], FALSE);
			}
		}
		return $ret;
	}
	/************************************ 其他方法 ********************************************/
	public function _before_delete($option)
	{
		// 先找出所有的子分类
		$children = $this->getChildren($option['where']['id']);
		// 如果有子分类都删除掉
		if($children)
		{
			$this->error = '有下级数据无法删除';
			return FALSE;
		}
	}
	
	//检查当前管理员是否有权限访问这个页面
	public function chkPri(){
	    //获取当前管理员正要访问的模型、控制器、方法名称
	    //TP中自带三个常量MODULE_NAME,CONTROLLER_NAME,ACTION_NAME
	    $adminId = session('id');
	    if($adminId==1){
	        return TRUE;
	    }
	    $arModel = D('admin_role');
	    $has = $arModel->alias('a')
	    ->join("LEFT JOIN __ROLE_PRI__ b ON a.role_id=b.role_id
	            LEFT JOIN __PRIVILEGE__ c ON b.pri_id=c.id")
	    ->where(array(
	        'a.admin_id'=>array('eq',$adminId),
	        'c.module_name'=>array('eq',MODULE_NAME),
	        'c.controller_name'=>array('eq',CONTROLLER_NAME),
	        'c.action_name'=>array('eq',ACTION_NAME)
	    ))->count();
	    return ($has>0);
	}
	//获取当前管理员所拥有的前两级的权限
	public function getBtns(){
	    //先取出当前管理员的所有权限
	    $adminId = session('id');
	    if($adminId == 1){
	        $priModel = D('privilege');
	        $priData = $priModel->select();
	    }else{
	        $arModel = D('admin_role');
	        $priData = $arModel->alias('a')
    	    ->field("DISTINCT c.id,c.pri_name,c.module_name,c.controller_name,c.action_name,c.parent_id")
    	    ->join("LEFT JOIN __ROLE_PRI__ b ON a.role_id=b.role_id
    	            LEFT JOIN __PRIVILEGE__ c ON b.pri_id=c.id")
    	    ->where(array(
    	        'a.admin_id'=>array('eq',$adminId),
    	    ))->select();
	    }
	    $btns = array();
	    foreach($priData as $k=>$v){
	        if($v['parent_id'] == 0){
	            //找最高的子级
	            foreach ($priData as $k1=>$v1){
	                if($v1['parent_id'] == $v['id']){
	                    $v['children'][] = $v1;
	                }
	            }
	            $btns[] = $v;
	        }
	    }
	    return $btns;
	}
	
	
	
	
}