<?php

/**
 * Class CartStage 购物车扩展
 */
class CartStage{

    public function __construct()
    {
        // 主要更具不同的東西如：商品：goods；优惠劵：coupon；等，可扩展性强
        $this->service_apps= [
            new Goods()
        ];

        // 购物车的商品促销之类的扩展；
        $this->cart_process_apps= [
            //
        ];
    }

    /**
     *  向购物车加入内容，内部会自动判断应该加入到数据库还是临时购物车，还是快速购买购物车.
     * @param $obj_type   购物车内容类型： goods 商品类型；coupon 优惠劵
     * @param $data   不同类型购物车内容，所需数据不同
     * @param $msg    错误提示，引用传递
     * @param bool $is_fastbuy   boolean 是否是要进行快速购买
     */
    public function add($obj_type , $data , &$msg ,$is_fastbuy = false){
        $ready_object = false;

        foreach ($this->service_apps as $obj) {
            if ($obj_type && $obj_type == $obj->get_type()){
                $ready_obj = $obj;
            }
        }


        if (!$ready_obj || !method_exists($ready_obj, 'add_object')) {
            $msg = '未知购物车内容类型';
            return false;
        }

        return $ready_obj->add_object($data, $msg ,true, $is_fastbuy);
    }
    // 获取购物车数据
    public function currency_result($filter, $cart_result = null)
    {
        if (!$cart_result) {
            $cart_result = $this->result($filter);
        }
        $f1 = app::get('ectools')->getConf('site_decimal_digit_count'); //小数位数
        $f2 = app::get('ectools')->getConf('site_decimal_type_count'); //进位方式
        $ecmath = vmc::singleton('ectools_math');
        foreach ($cart_result as $i => $v1) {
            switch ($i) {
                case 'cart_amount':
                case 'promotion_discount_amount':
                case 'member_discount_amount':
                case 'goods_promotion_discount_amount':
                case 'order_promotion_discount_amount':
                    if ($v1 > 0) {
                        $cart_result[$i] = $ecmath->formatNumber($v1, $f1, $f2);
                    }
                    break;
                case 'objects':
                    foreach ($v1['goods'] as $j => $v2) {
                        foreach ($v2['item']['product'] as $k => $v3) {
                            switch ($k) {
                                case 'buy_price':
                                case 'member_lv_price':
                                case 'mktprice':
                                case 'mktprice':
                                    if ($v3 > 0) {
                                        $cart_result['objects']['goods'][$j]['item']['product'][$k] = $ecmath->formatNumber($v3, $f1, $f2);
                                    }
                                    break;
                            }
                        }
                        $cart_result['objects']['goods'][$j]['amount'] = $ecmath->formatNumber($cart_result['objects']['goods'][$j]['item']['product']['buy_price'] * $cart_result['objects']['goods'][$j]['quantity'], $f1, $f2);
                    }
                    break;
            }
        }
        $cart_result['finally_cart_amount'] = $ecmath->formatNumber($cart_result['cart_amount'] - $cart_result['member_discount_amount'] - $cart_result['promotion_discount_amount'], $f1, $f2);

        return $cart_result;
    }
    /**
     * 辅助判断购物车详情数组是否是没有商品信息的.
     *
     * @param $cart_result  购物车结果数组
     */
    public function is_empty($cart_result)
    {
        if (!is_array($cart_result)) {
            return true;
        }
        if (empty($cart_result['objects'])) {
            return true;
        }
        $keys = array_keys($cart_result['objects']);
        foreach ($keys as $key) {
            if ($key == 'coupon') {
                continue;
            }
            if (!empty($cart_result['objects'][$key])) {
                return false;
            }
        }
        return true;
    }


    /**
     * 购物车商品购买数量更新.
     *
     * @param $obj_type string 购物车内容类型。 e.g. goods \ coupon
     * @param $ident string 购物车内容obj_ident 货品id(记住不是商品id) 如：goods_1071
     * @param $quantity number 更新到数量
     * @param &$msg string 错误消息
     */
    public function update($obj_type, $ident, $quantity, &$msg)
    {
        foreach ($this->service_apps as $obj) {
            if ($obj_type == $obj->get_type()) {
                $obj->update($ident, $quantity, $msg);
                break;
            }
        }
        if ($msg) {
            return false;
        }
        return $this->result();
    }
    /**
     * 删除购物车内容.
     *
     * @param $obj_type string  购物车内容类型。 e.g. goods \ coupon
     * @param $ident string  购物车内容obj_ident
     * @param $is_fastbuy 在快速购买过程中进行购物车内容删除，如在快速购买时，进行优惠券删除操作
     */
    public function delete($obj_type, $ident, $is_fastbuy = false)
    {
        foreach ($this->service_apps as $obj) {
            if ($obj_type == $obj->get_type()) {
                $obj->delete($ident, $is_fastbuy);
                break;
            }
        }
    }

    /**
     * 得到购物车当前详情信息.
     * @param $filter array 过滤条件，e.g.  $filter = array('is_fastbuy'=>'true');
     */
    public function result($filter, $cart_result = null){
        foreach ($this->cart_process_apps as $object){
            if (!is_object($object)) {
                continue;
            }
            // 根据权重调整先后执行顺序
            $processer[$object->get_order()] = $object;
            // CartProcessGet  购物车首次数据填充
            // CartProcessPrefilter 商品促销计算
            // CartProcessPostfilter 订单促销计算
        }
        // 整理购物车中，禁用的商品
        if (!$_SESSION['CART_DISABLED_IDENT'] || !is_array($_SESSION['CART_DISABLED_IDENT'])){
            $_SESSION['CART_DISABLED_IDENT'] = array();
        }
        // 把传进来条件中的禁用商品和session中的禁用商品合并
        if (count($_SESSION['CART_DISABLED_IDENT']) > 0 || isset($filter)){
            // 存在购物车禁用项目
            if (isset($filter['disabled_ident']) && is_array($filter['disabled_ident'])){
                $filter['disabled_ident'] = array_merge($filter['disabled_ident'],$_SESSION['CART_DISABLED_IDENT']);
            }else{
                $filter['disabled_ident'] = $_SESSION['CART_DISABLED_IDENT'];
            }
        }
        // 根据权重调整先后执行顺序，对数组按照键名逆向排序
        krsort($processer);
        foreach ($processer as $pro){
            //  $cart_result 是引用传递
            $pro->process($filter ,$cart_result ,$config);
        }
        return $cart_result;
    }
}