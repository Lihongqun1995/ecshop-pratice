<?php
namespace Admin\Controller;
use Think\Controller;
class CategoryController extends BaseController 
{
    public function add()
    {
        $model = D('Category');
    	if(IS_POST)
    	{
    		
    		if($model->create(I('post.'), 1))
    		{
    			if($id = $model->add())
    			{
    				$this->success('添加成功！', U('lst?p='.I('get.p')));
    				exit;
    			}
    		}
    		$this->error($model->getError());
    	}
        $catData = $model->getTree();
		// 设置页面中的信息
		$this->assign(array(
		    'catData'=>$catData,
			'_page_title' => '添加分类',
			'_page_btn_name' => '分类列表',
			'_page_btn_link' => U('lst'),
		));
		$this->display();
    }
    public function edit()
    {
    	$id = I('get.id');
    	// M:生成的是父类模型\Think\Model
    	$model = D('Category');
    	if(IS_POST)
    	{
    		if($model->create(I('post.'), 2))
    		{
    			if($model->save() !== FALSE)
    			{
    				$this->success('修改成功！', U('lst', array('p' => I('get.p', 1))));
    				exit;
    			}
    		}
    		$this->error($model->getError());
    	}
    	$data = $model->find($id);
    	
    	// 取出所有的分类做下拉框
    	$catData = $model->getTree();
    	// 取出当前分类的子分类
    	$children = $model->getChild($id);
    	
    	$this->assign(array(
    		'children' => $children,
    		'data' => $data,
    		'catData' => $catData,
    	));

		// 设置页面中的信息
		$this->assign(array(
			'_page_title' => '修改分类',
			'_page_btn_name' => '分类列表',
			'_page_btn_link' => U('lst'),
		));
		$this->display();
    }
    public function delete()
    {
    	$model = D('Category');
    	if($model->delete(I('get.id', 0)) !== FALSE)
    	{
    		$this->success('删除成功！', U('lst', array('p' => I('get.p', 1))));
    		exit;
    	}
    	else 
    	{
    		$this->error($model->getError());
    	}
    }
    public function lst()
    {
    	$model = D('category');
    	$data = $model->getTree();
		// 设置页面中的信息
		$this->assign(array(
		    'data'=>$data,
			'_page_title' => '分类列表',
			'_page_btn_name' => '添加分类',
			'_page_btn_link' => U('add'),
		));
    	$this->display();
    }
}