<?php
// 本类由系统自动生成，仅供测试用途
namespace Home\Controller;
use MyMVC\Controller;
class IndexController extends Controller {
    public function index()
    {
        $model = new \Home\Model\CartModel();
        $data  = $model->getCity();
        $a = array(1,1,2,3);
        $this->a = $a;
        $this->b = array('a'=>array('xxxx'=> 1,'bbb'),'b'=>array('xxxx'=> 1,'bbb'),2,3);
        $this->c = '55555';
        $this->d = '66666';
        $this->display();
    }
}