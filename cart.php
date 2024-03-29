<?php
/**
 * 加入购物车
 */
class Cart{

    public function __construct()
    {
        $this->cart_stage = new CartStage();
    }

    //购物车主页
    public function index()
    {
        $result = $this->cart_stage->result();
        if ($this->cart_stage->is_empty($result)) {
            // TODO 购物车如果是空的就返回;
        }
        // TODO 把购物车数据带回页面
    }
    /**
     * 快速购买
     * @param $product_id  商品id
     * @param $num  购买数量
     */
    public function fastbuy($product_id,$num){

        // TODO 验证是否登录
        // TODO 获取参数 - 过滤参数
        $post_data =[];
        $product_id = $product_id?:$post_data['product_id'];
        $num = $num?:$post_data['num'];
        if (!$num) $num = 1;
        if (!$product_id) die('缺少商品id');
        $extends = null;
        $object = [
            'goods'=>[
                'product_id'=>$product_id,
                'num'=>$num,
                'extends'=>$extends,  // 自定义扩展
            ]
        ];

        $ident = $this->cart_stage->add('goods',$object,$msg,true);
        if (!$ident){
            die($msg);
        }
        // TODO 加入购物车成功之后跳转到订单确认页
    }

    // 向购物车添加商品
    public function add($product_id,$num){
        // TODO 获取参数 - 过滤参数
        $post_data =[];
        $product_id = $product_id?:$post_data['product_id'];
        $num = $num?:$post_data['num'];
        if (!$num) $num = 1;
        if (!$product_id) die('缺少商品id');
        $extends = null;
        $object = [
            'goods'=>[
                'product_id'=>$product_id,
                'num'=>$num,
                'extends'=>$extends,  // 自定义扩展
            ]
        ];
        $ident = $this->cart_stage->add('goods',$object,$msg);
        if (!$ident){
            die($msg);
        }
        // TODO 判断是否来自于ajax请求
        /* if ($this->_request->is_ajax()) {
             //异步加入购物车反馈,放回当前购物车的数据
             return $this->cart_stage->currency_result();
         }*/
        die('加入购车成功');
    }

    /**
     * 更新购物车,在购物车中更新商品数量等。。。
     * @param $ident 货品id(记住不是商品id) 如：goods_1071
     * @param $num   数量
     * @param string $object 代表是商品
     */
    public function update($ident, $num,$object='goods')
    {
        // TODO 获取参数 - 过滤参数
        $params = [];

        $obj_ident = ($ident ? $ident : $params['ident']);
        $num = ($num ? $num : $params['num']);
        if($params['object_type']){
            $object = $params['object_type'];
        }
        $cart_result = $this->cart_stage->update($object, $obj_ident, $num, $msg);

        // 购物车界面url
        $cart_url = 'cart.html';
        if (!$cart_result) {
            // TODO 如果没有修改成功返回错误信息和错误码
        }
        $cart_result = $this->cart_stage->currency_result($filter = null, $cart_result);
        // TODO 购物车界面，并且把更新好的购物车数据返回去
    }

    /**
     * 删除&购物车商品、清空购物车
     * @param bool $ident
     * @param bool $obj_name
     */
    public function remove($ident = false,$obj_name = false)
    {
        // TODO 获取参数 - 过滤参数
        $params =[];
        $obj_ident = ($ident ? $ident : $params['ident']);
        $obj = ($obj_name ? $obj_name : $params['obj_name']);
        if (is_array($obj_ident)) {
            foreach ($obj_ident as $key=>$ident) {
                $this->cart_stage->delete($obj[$key]?$obj[$key]:'goods', $ident);
            }
        } else {
            $this->cart_stage->delete($obj?$obj:'goods', $ident);
        }
        $cart_result = $this->cart_stage->currency_result();
        if ($this->cart_stage->is_empty($cart_result)) {
            die('购物车为空');
        }
    }

    /**
     * 禁用购物车项
     * @param bool $ident 单个商品如：goods_1071；数组是：array('goods_1071','goods_1072')
     */
    public function disabled($ident = false)
    {
        if($ident && !is_array($ident)){
            $ident = array($ident);
        }
        // TODO 获取传进来的参数，过滤
        $params = [];
        if ($ident && !isset($params['ident'])) {
            $params['ident'] = $ident;
        }
        $_SESSION['CART_DISABLED_IDENT'] = array_merge($_SESSION['CART_DISABLED_IDENT'], $params['ident']);

        $cart_result = $this->cart_stage->currency_result();

        // TODO 返回购物车，带上最新购物车数据
    }
    //激活购物车项
    public function enabled($ident = false)
    {
        if($ident && !is_array($ident)){
            $ident = array($ident);
        }
        // TODO 获取传进来的参数，过滤
        $params = [];
        if ($ident && !isset($params['ident'])) {
            $params['ident'] = $ident;
        }
        $_SESSION['CART_DISABLED_IDENT'] = array_diff($_SESSION['CART_DISABLED_IDENT'], $params['ident']);

        $cart_result = $this->cart_stage->currency_result();

        // TODO 返回购物车，带上最新购物车数据
    }
}

/*
 * 购物车数据表结构
CREATE TABLE `vmc_b2c_cart_objects` (
  `obj_ident` varchar(255) NOT NULL COMMENT '对象ident',
  `member_ident` varchar(50) NOT NULL COMMENT '会员ident,会员信息和serssion生成的唯一值',
  `member_id` int(8) NOT NULL DEFAULT '-1' COMMENT '会员 id',
  `obj_type` varchar(20) NOT NULL COMMENT '购物车对象类型',
  `params` longtext NOT NULL COMMENT '购物车对象参数',  // 以json对象存储在数据库中
  `quantity` float unsigned NOT NULL COMMENT '数量',
  `time` int(10) unsigned DEFAULT NULL COMMENT '时间',
  PRIMARY KEY (`obj_ident`,`member_ident`,`member_id`),
  KEY `ind_member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;*/