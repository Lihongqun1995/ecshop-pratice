<?php
namespace Admin\Model;
use Think\Model;
class CommentModel extends Model{
    protected $insertFields = 'star,content,goods_id';
    protected $_validate = array(
        array('goods_id','require','参数错误！',1),
        array('star','1,2,3,4,5','分值只能是1-5之间的数字！',1,'in'),
        array('content','1,200','内容必须是1-200个字符！',1,'length')
    );
    protected function _before_insert(&$data,$option){
        $memberId = session('m_id');
        if(!$memberId){
            $this->error = '必须先登录！';
            return FALSE;
        }
        $data['member_id'] = $memberId;
        $data['addtime'] = date('Y-m-d H:i:s');
        $yxId = I('post.yx_id');//选择已有的印象
        $yxName = I('post.yx_name');
        $yxModel = D('Yinxiang');
        if($yxId){
            foreach($yxId as $k=>$v){
                $yxModel->where(array('id'=>$v))->setInc('yx_count');
            }
        }
        //新添的印象
        if($yxName){
            $yxName = str_replace('，',',',$yxName);
            $yxName = explode(',',$yxName);
            foreach ($yxName as $k=>$v){
                $v = trim($v);//去除空值
                if(empty($v)){
                    continue;
                }
                $has = $yxModel->where(array(
                    'goods_id'=>$data['goods_id'],
                    'yx_name'=>$v,
                ))->find();
                if($has){
                    //印象已存在
                    $yxModel->where(array(
                        'goods_id'=>$data['goods_id'],
                        'yx_name'=>$v,
                    ))->setInc('yx_count');
                }else{
                    $yxModel->add(array(
                        'goods_id'=>$data['goods_id'],
                        'yx_name'=>$v,
                        'yx_count'=>1,
                    ));
                }
            }
        }
    }
    public function search($goodsId,$pageSize = 5){
        $where['a.goods_id'] = array('eq',$goodsId);
        //翻页数据
        $count = $this->alias('a')->where($where)->count();
        
        $pageCount = ceil($count / $pageSize);
        $currentPage = max(1,(int)I('get.p',1));
        $offset = ($currentPage -1) * $pageSize;
        //取数据
        $data = $this->alias('a')
        ->field('a.id,a.content,a.addtime,a.star,a.click_count,b.face,b.username,COUNT(c.id) reply_count')
        ->join('LEFT JOIN __MEMBER__ b ON a.member_id=b.id
                LEFT JOIN __COMMENT_REPLY__ c ON a.id=c.comment_id')
        ->where($where)
        ->order('a.id DESC')
        ->limit("$offset,$pageSize")
        ->group('a.id')
        ->select();
        //取出数据，在第一页计算好评率
        if($currentPage == 1){
            $stars = $this->field('star')->where(array(
                'goods_id'=>array('eq',$goodsId),
            ))->select();
            $hao = $zhong = $cha = 0;
            foreach ($stars as $k=>$v){
                if($v['star'] == 3){
                    $zhong++;
                }elseif($v['star'] > 3){
                    $hao++;
                }else{
                    $cha++;
                }
            }
            $total = $cha + $zhong + $hao;
            $hao = round(($hao / $total) * 100,2);
            $zhong = round(($zhong / $total) * 100,2);
            $cha = round(($cha / $total) * 100,2);
            //取出印象数据
            $yxModel = D('Yinxiang');
            $yxData = $yxModel->where(array(
                'goods_id'=>array('eq',$goodsId),
            ))->select();
        }
        $crModel = D('comment_reply');
        foreach($data as $k=>&$V){
            $v['reply'] = $crModel->alias('a')
                          ->field('a.content,a.addtime,b.username,b.face')
                          ->join("LEFT JOIN __MEMBER__ b ON a.member_id=b.id")
                          ->where(array(
                              'a.comment_id'=>$v['id'],
                          ))
                          ->order('a.id ASC')
                          ->select();
        }
        
        return array(
            'hao'=>$hao,
            'zhong'=>$zhong,
            'cha'=>$cha,
            'yxData'=>$yxData,
            'memberId'=>(int)session('m_id'),
            'data'=>$data,
            'pageCount'=>$pageCount,
        );
    }
}

















