<?php
if ( ! function_exists( 'safeArrayGet' ) ) {
    function safeArrayGet($array, $key, $default = null) {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}
/**
 * Eprolo Actions
 */
class Eprolo_Actions_New_api {

    /**
     * Instance of this class.
     *
     * @var object Class Instance
     */
    private static $instance;

    /**
     * Get the class instance
     *
     * @return Eprolo_Actions_New
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 初始化
     */
    public function init() {
         // 注册自定义订单状态
         add_action('init', array($this, 'register_custom_order_status'));
         // 添加到WooCommerce订单状态列表
         add_filter('wc_order_statuses', array($this, 'add_custom_order_statuses'));
        // 添加API端点
        add_action('rest_api_init', function() {
            register_rest_route('eprolo/v1', '/ship-order/(?P<id>\d+)', array(
                'methods' => 'POST',
                'callback' => array($this, 'handle_ship_order_request'),
                'permission_callback' => '__return_true'
            ));
        });
    }
    /**
     * 注册自定义订单状态 - 已经发货
     */
    public function register_custom_order_status() {
        register_post_status('wc-shipped', array(
            'label'                     => '已经发货',
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('已经发货 <span class="count">(%s)</span>', '已经发货 <span class="count">(%s)</span>')
        ));
    }
    /**
     * 将自定义状态添加到WooCommerce订单状态列表
     */
    public function add_custom_order_statuses($order_statuses) {
        $order_statuses['wc-shipped'] = 'Fulfilled';
        return $order_statuses;
    }
     /**
     * Initialize the class
     */
    /**
     * 通过订单ID将订单状态修改为发货
     * 
     * @param int $order_id 订单ID
     * @return array 返回操作结果
     */
    public function update_order_to_shipped($order_id,$tracking_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return array(
                'code' => false,
                'message' => 'The order does not exist.'
            );
        }
        
        try {
            $order->update_status('shipped', 'Ship goods through API interface');
            return array(
                'success' => true,
                'tracking_id'=>$tracking_id,
                'date_shipped'=>current_time( 'Y-m-d H:i:s' ),
                'message' => 'The order status has been updated to "Shipped".'
            );
        } catch (Exception $e) {
            return array(
                'code' => false,
                'message' => 'Failed to update the order status: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * 处理发货请求
     */
    public function handle_ship_order_request(WP_REST_Request $request) {
        $aplugin = new Eprolo_OptionsManager();
        $order_id = $request->get_param('id');
        $request_body = $request->get_body();
        $request_data = json_decode($request_body, true);
        
        $courier_name = isset($request_data['tracking_provider']) ? trim($request_data['tracking_provider']) : '';
        $tracking_number = isset($request_data['tracking_number']) ? trim($request_data['tracking_number']) : '';
        $tracking_link = isset($request_data['tracking_link']) ? $request_data['tracking_link'] : '';
        $eprolo_store_token_request = isset($request_data['eprolo_store_token']) ? $request_data['eprolo_store_token'] : '';
        if (empty($tracking_number)) {
            return new WP_Error('invalid_params', 'Tracking number cannot be empty', array('status' => 200));
        }
        $order = wc_get_order($order_id);
        $eprolo_store_token = $aplugin->getOption( 'eprolo_store_token' );	
        if (empty($eprolo_store_token) || empty($eprolo_store_token_request) || $eprolo_store_token != $eprolo_store_token_request) {
            return new WP_Error('invalid_token', 'Token is not configured', array('status' => 200));	
        }
        if (!$order) {
            return new WP_Error('invalid_order', '订单不存在', array('status' => 200));
        }
        $tracking_item                      = array();
        $tracking_item['order_id']   = wc_clean( $order_id);
		$tracking_item['provider_name']     = wc_clean(  $courier_name  );
		$tracking_item['tracking_number']   = wc_clean(  $tracking_number  );
        $tracking_item['tracking_link']    = wc_clean(  $tracking_link );
        $eproloTracking_id = md5( "{$tracking_item['provider_name']}-{$tracking_item['tracking_number']}" );
		$tracking_item['tracking_id']       = $eproloTracking_id;
		$tracking_item['metrics']           = array(
			'created_at' => current_time( 'Y-m-d\TH:i:s\Z' ),
			'updated_at' => current_time( 'Y-m-d\TH:i:s\Z' ),
		);
        $tracking_items = [];
        if($order->get_meta('_eprolo_tracking_items')){
            $tracking_items = $order->get_meta('_eprolo_tracking_items', true);
            
            // 检查tracking_number是否已存在
            foreach($tracking_items as $item) {
                if($item['tracking_number'] === $tracking_number) {
                    return new WP_Error('duplicate_tracking', 'This tracking number already exists.', array('status' => 200));
                }
            }
        }
        array_push($tracking_items, $tracking_item);
        // 保存快递信息到订单meta
        $order->update_meta_data('_eprolo_tracking_items', $tracking_items);

        // 保存快递信息到WooCommerce订单
        $wc_item                      = array();
        $wc_item['tracking_provider']   = $courier_name;
        $wc_item['custom_tracking_provider']   = "";
        $wc_item['custom_tracking_link']   = $tracking_link;
        // 检查tracking_link ,根据条件跳转到不同的链接,默认跳转17track
        if (empty($tracking_link)){
            $wc_item['custom_tracking_link']   =  'https://t.17track.net/en#nums=' . $tracking_number;
        }
        $wc_item['tracking_number']   = wc_clean(  $tracking_number  );
        $wc_item['source']   = "edit_order";
        $wc_item['tracking_product_code']   = "";
        $wc_item['date_shipped']   = current_time('Y-m-d H:i:s');  // 改为当前日期时间戳
        $wc_item['status_shipped']   = "1";
        $wc_item['tracking_id']   =  md5( "{$tracking_item['provider_name']}-{$tracking_item['tracking_number']}" );
        $wc_item['user_id']   = "1";
        $wc_items = [];
        if($order->get_meta('_wc_shipment_tracking_items')){
            $wc_items = $order->get_meta('_wc_shipment_tracking_items', true);
            // 检查tracking_number是否已存在
            foreach($wc_items as $item) {
                if($item['tracking_number'] === $tracking_number) {
                    return new WP_Error('duplicate_tracking', 'This tracking number already exists.', array('status' => 200));
                }
            }
        }
        array_push($wc_items, $wc_item);
        // 保存快递信息到订单meta
        $order->update_meta_data('_wc_shipment_tracking_items', $wc_items);
        // end保存快递信息到WooCommerce订单
		
        $order->save();
        
        return $this->update_order_to_shipped($order_id,$eproloTracking_id);
    }
    
}

// 初始化
Eprolo_Actions_New_api::get_instance()->init();


