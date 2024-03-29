<?php
/**
 * 购物车内容类型；商品类型
 * Class ObjectGoods
 */
class Goods{

    public function get_type() {
        return 'goods';
    }
    /**
     * 购物车项参数组织.
     */
    private function _params($object) {
        return array(
            'item' => $object,
            'warning' => false,   //  购物车商品信息提示：购物车商品发生改变的时候的信息提示
        );
    }

    /**
     * @param $object   商品对象
     * @param string $msg  错误提示，引用传递
     * @param bool $append  是否是往购物车追加
     * @param bool $is_fastbuy  boolean 是否是要进行快速购买
     */
    public function add_object($object,&$msg='' , $append= true, $is_fastbuy = false){
        $object = $object['goods'];
        $arr_save = [
            'obj_ident'=>'goods_'.$object['product_id'],
            'obj_type' =>'goods',
            'params'=>json_encode([  // json 格式存入数据库
                $this->_params([    // 购物车项参数组织
                    'product_id' => $object['product_id'],
                ]),
            ]),
            'quantity'=>$object['num'] ? :1,  // 购买数量
            'member_ident' => md5(session_id()) // 用户在没有登录的下时候的购物车
        ];
        if ($is_fastbuy){
            $arr_save['is_fastbuy'] = 'true';
        }
        //  如果用户登录，没登陆也能加入到购物车
        if ($this->member_id){
            $arr_save['member_ident'] = md5($this->member_id);
            $arr_save['member_id'] = $this->member_id;
        }
        // 追加|更新(只有不是快速购买时才行)
        if ($append && !$is_fastbuy){
            // 如果存在相同的商品则追加
            $filter = [
                'object_ident'=>$arr_save['obj_ident'],
                'member_ident' =>$arr_save['member_ident']
            ];
            if ($arr_save['member_id']){
                $filter['member_id'] = $arr_save['member_id'];
            }
            // TODO  $cart_object 购物车查询当前$filter的商品数据是否存在(根据你的实际框架来写)
            if ($cart_object =true){

                $arr_save['quantity'] += $cart_object['quantity'];
                // $_SESSION['CART_DISABLED_IDENT'] 主要用在购物车选中商品的时候,把取消选中的保存至session中
                // 注意：多客户端选择的时候，会造成选中商品不一致，是存在session中而不是数据库中
                // 存储的是：goods_308 数字是 product_id
                if (is_array($_SESSION['CART_DISABLED_IDENT'])){
                    // 作用：如果该商品没有被选中则会被从session中剔除掉
                    // 如果该商品在购物车中已经存在，但是没有被选中，再加入同商品到购物车，会选中该商品
                    $_SESSION['CART_DISABLED_IDENT'] = array_diff($_SESSION['CART_DISABLED_IDENT'],$cart_object['obj_ident']);
                } else {
                    $arr_save['time'] = time(); //纪录首次加入时间
                }
            }
            // 验证
            if ($this->_check($arr_save, $msg)){
                return false;
            }
            // TODO 执行更新购物车操作 save/updae 操作
            $is_save = true;
            if (!$is_save) {
                $msg = '购物车状态保存异常';
                return false;
            }
            return $arr_save['obj_ident'];
        }
    }

    /**
     * @param $ident 例如：goods_1071
     * @param $quantity
     * @param $msg
     * @return bool|mixed
     */
    public function update($ident, $quantity, &$msg) {
        $arr_save = array(
            'obj_ident' => $ident,
            'obj_type' => 'goods',
        );
        $arr_save['member_ident'] = md5(session_id()); // 用户在没有登录的下时候的购物车
        if ($this->member_id) {
            $arr_save['member_ident'] = md5($this->member_id);
            $arr_save['member_id'] = $this->member_id;
        }
        // TODO 查询购物车表，得到购物车数据
        $cart_object = [];

        if (floatval($quantity) == floatval($cart_object['quantity'])) {
            return $arr_save['obj_ident'];
        }
        $cart_object['quantity'] = floatval($quantity);

        if (!$this->_check($cart_object, $msg)) { //验证加入项
            return false;
        }
        // TODO 执行购物车数据表操纵
        $is_save = true;
        if (!$is_save) {
            $msg = ('购物车状态保存异常');
            return false;
        }
        return $cart_object['obj_ident'];
    }
    /**
     * 指定的购物车商品项.
     *
     * @param string $sIdent
     * @param bool   $rich   // 是否只取cart_objects中的数据 还是完整的sdf数据
     *
     * @return array
     */
    public function get($ident = null, $rich = false, $is_fastbuy = false) {
        if (empty($ident)) {
            return $this->getAll($rich, $is_fastbuy);
        }
        $filter = array(
            'obj_ident' => $ident,
            'member_ident' => $this->member_ident,
        );
        if ($is_fastbuy) {
            $filter['is_fastbuy'] = 'true';
        }
        $filter['member_ident'] = md5($this->session->sess_id());
        if ($this->member_id) {
            $filter['member_ident'] = md5($this->member_id);
            $filter['member_id'] = $this->member_id;
        }
        $cart_objects = $this->mdl_cartobjects->getList('*', $filter);
        if (empty($cart_objects)) {
            return array();
        }
        if ($rich) {
            $cart_objects = $this->_get_rich($cart_objects);
        }

        return $cart_objects;
    }

    // 购物车里的所有商品项
    public function getAll($rich = false, $is_fastbuy = false) {
        $filter = array(
            'obj_type' => 'goods',
        );
        if ($is_fastbuy) {
            $filter['is_fastbuy'] = 'true';
        }
        $filter['member_ident'] = md5($this->session->sess_id());
        if ($this->member_id) {
            $filter['member_ident'] = md5($this->member_id);
            $filter['member_id'] = $this->member_id;
        }
        $cart_objects = $this->mdl_cartobjects->getList('*', $filter);
        if (!$rich) {
            return $cart_objects;
        }

        return $this->_get_rich($cart_objects);
    }
    // 删除购物车中指定商品项
    public function delete($sIdent = null, $is_fastbuy = false) {
        if (!$sIdent || empty($sIdent)) {
            return $this->deleteAll();
        }
        $filter = array(
            'obj_ident' => $sIdent,
            'obj_type' => 'goods',
        );
        if ($is_fastbuy) {
            $filter['is_fastbuy'] = 'true';
        }
        $filter['member_ident'] = md5(session_id());
        if ($this->member_id) {
            $filter['member_ident'] = md5($this->member_id);
            $filter['member_id'] = $this->member_id;
        }
        // 只想数据库删除操作，删除购物车表里面的数据
        $result = true;
        return $result;
    }

    // 清空购物车中商品项数据
    public function deleteAll($is_fastbuy = false) {
        $filter = array(
            'obj_type' => 'goods',
        );
        if ($is_fastbuy) {
            $filter['is_fastbuy'] = 'true';
        }
        $filter['member_ident'] = md5(session_id());
        if ($this->member_id) {
            $filter['member_ident'] = md5($this->member_id);
            $filter['member_id'] = $this->member_id;
        }
        // 只想数据库删除操作，删除购物车表里面的数据
        $result = true;
        return $result;
    }

    // 小计购物车
    public function count(&$cart_result) {
        if (empty($cart_result['objects']['goods'])) {
            return false;
        }
        $cart_result['object_count'] = count($cart_result['objects']['goods']);
        //[objects]['goods']['item']['product']
        foreach ($cart_result['objects']['goods'] as &$cart_object) {
            if ($cart_object['disabled'] == 'true') {
                $cart_result['object_count'] -= 1;
                continue;
            } //该项被禁用

            $item_product = $cart_object['item']['product'];

            //购物车重量
            $count_weight = $this->obj_math->number_multiple(array(
                $item_product['weight'],
                $cart_object['quantity'],
            ));
            $cart_result['weight'] = $this->obj_math->number_plus(array($cart_result['weight'], $count_weight));
            //购物车单项小记
            $count_cart_amount = $this->obj_math->number_multiple(array(
                $item_product['price'],
                $cart_object['quantity'],
            ));
            $cart_result['cart_amount'] = $this->obj_math->number_plus(array(
                $cart_result['cart_amount'],
                $count_cart_amount,
            ));



            //会员身份优惠合计
            $minus_member_discount = $this->obj_math->number_minus(array(
                $item_product['price'],
                $item_product['member_lv_price'],
            ));
            $count_member_discount_amount = $this->obj_math->number_multiple(array(
                $minus_member_discount,
                $cart_object['quantity'],
            ));
            $cart_result['member_discount_amount'] = $this->obj_math->number_plus(array(
                $cart_result['member_discount_amount'],
                $count_member_discount_amount,
            ));

            $cart_result['goods_count'] += $cart_object['quantity'];
        }
    }

    // 加入/更新购物车时验证
    private function _check($object, &$msg){
        // 这里在这里做购物车的验证，可以做出一个验证规则，然后在这获取验证
        // 1. 每个人限购数量
        // 2. 该商品的购买时间
        // 3. 不能重复购买
        // 4。需要预约
        return true;
    }
    /**
     * 购物车商品可售卖验证
     * @param &$cart_objects rich 购物车商品项
     */
    private function _warning(&$cart_objects) {
        foreach ($cart_objects as &$object) {
            if ($object['item']['product']['marketable'] != 'true') {
                $object['warning'] = '已下架';
                $object['app_warning'] = '已下架';
                $object['disabled'] = 'true'; //不能参与结算
                continue;
            }
            if ($object['item']['product']['nostore_sell'] != '1' && !vmc::singleton('b2c_goods_stock')->is_available_stock(
                    $object['item']['product']['bn'], $object['quantity'], $abs_stock)) {
                $object['warning'] = '库存不足,当前最多可售数量:' . $abs_stock;
                $object['app_warning'] = '库存不足,当前最多可售数量:' . $abs_stock;
                $object['abs_stock'] = $abs_stock;
                $object['disabled'] = 'true'; //不能参与结算
                continue;
            }
        }
    }
}