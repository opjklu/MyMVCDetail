<?php
namespace Home\Model;
use MyMVC\Model;

class CartModel extends Model
{
    public function getCity()
    {
       $data = array();
       if (!empty($this->fields))
       {
           foreach ($this->fields as $key => $value)
           {
               if (false === strpos($value, 'PRI')) {
                   $data[$key] = 123456;
               }
           }
       }
      return  $this->insert($data, array('where' => array('goods_name' =>'aaa', 'id' => 2)));
    }
}