<?php
if ( ! function_exists( 'safeArrayGet' ) ) {
    function safeArrayGet($array, $key, $default = null) {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}
/**
 * Eprolo Actions
 */
class Eprolo_Actions_New {

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
     * Add the meta box for shipment info on the order page
     */
    public function add_meta_box() {
        add_meta_box( 
            'woocommerce-aftership-new', 
            __( 'Eprolo Shipment Tracking', 'aftership' ), 
            array( $this, 'render_meta_box' ), 
            $this->get_order_admin_screen(), // Changed from $aplugin->get_order_admin_screen()
            'side', 
            'high' 
        );
    }
    /**
 * 创建快递公司下拉选择框
 *
 * @param string $selected_slug 当前选中的快递公司slug
 * @return string 生成的下拉框HTML
 */
public function render_courier_dropdown($selected_slug = '') {
    $couriers = $GLOBALS['AfterShip']->couriers;
    $output = '<select name="aftership_tracking_slug" id="aftership_tracking_slug" class="select short">';
    $output .= '<option value="">' . __('选择快递公司', 'aftership') . '</option>';
    
    foreach ($couriers as $courier) {
        $selected = selected($selected_slug, $courier['slug'], false);
        $output .= sprintf(
            '<option value="%s" %s>%s</option>',
            esc_attr($courier['slug']),
            $selected,
            esc_html($courier['name'])
        );
    }
    
    $output .= '</select>';
    return $output;
}
    /**
     * Get order admin screen based on WC version
     */
    public function get_order_admin_screen() {
        // if(!wc_order_util_method_exists('get_order_admin_screen')) {
        //     return 'shop_order';
        // }
        return call_user_func_array(array('Automattic\WooCommerce\Utilities\OrderUtil', 'get_order_admin_screen'), array());
    }
    // 渲染数据
    public function render_meta_box() {
        // 方法1：通过全局变量获取
        global $theorder;
        // 确保 $theorder 是有效的 WC_Order 对象
        if (!is_a($theorder, 'WC_Order')) {
            echo '<p>Order information cannot be obtained.</p>';
            return;
        }
        // 获取订单ID
        $order_id = $theorder->get_id();
        $migrate = $theorder->get_meta('_eprolo_tracking_items');
        if (!$theorder) {
            echo '<p>Order information cannot be obtained.</p>';
            return;
        }
        $aplugin = new Eprolo_OptionsManager();
        wp_enqueue_script( 
            'startup', 
            $aplugin->getUrl() . 'js/eproloTracking.js', 
            array('jquery'), 
            $aplugin->get_eprolo_version(), 
            true 
        );
        wp_enqueue_script( 'startup', $aplugin->getUrl() . 'js/startup.js', array( 'jquery' ), $aplugin->get_eprolo_version(), true );
        wp_enqueue_style( 'startup', $aplugin->getUrl() . 'css/eproloTracking.css', '', $aplugin->get_eprolo_version(), 'all' );
        wp_localize_script( 'startup', 'ajax_startup', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ) );
        $InfoHtml="<div id='eprolo_tracking_list'>";
        if(is_array($migrate) && !empty($migrate)) {
            foreach($migrate as $index => $tracking ) {
                $eprolo_tracking_link = "";
                $tracking_link=safeArrayGet($tracking,'tracking_link', '');
                if(empty($tracking_link)){
                    $eprolo_tracking_link = sprintf("https://t.17track.net/en#nums=%s", safeArrayGet($tracking, 'tracking_number', ''));	
                }else{
                    $eprolo_tracking_link = sprintf("%s?nums=%s", $tracking_link,safeArrayGet($tracking, 'tracking_number', ''));	
                }
                  $InfoHtml .= sprintf('<div class="_tracking_nkd9j_19"><div class="_title_nkd9j_23"><div class="_title_nkd9j_index">Shipment %s</div><div data-tracking-id="%s" data-order-id="%s"  data-provider_name="%s" data-tracking_number="%s" data-tracking_link="%s"> <a href="#" onclick="return editTraking(this)">Edit</span></a>   <a href="#" onclick="return confirmDeleteTracking(this,2)">Delete</a></div></div><div class="_content_nkd9j_38"><div class="_number_nkd9j_45"><div><strong>%s&nbsp;</strong></div><div><a target="_blank" title="122" href="%s">%s</a></div></div></div></div>'
                 ,$index + 1,esc_html( safeArrayGet($tracking , 'tracking_id', '')),esc_html( safeArrayGet($tracking , 'order_id', '')),esc_html( safeArrayGet($tracking , 'provider_name', '')),esc_html( safeArrayGet($tracking , 'tracking_number', '')),safeArrayGet($tracking , 'tracking_link', ''),esc_html( safeArrayGet($tracking , 'provider_name', '')),$eprolo_tracking_link,esc_html( safeArrayGet($tracking , 'tracking_number', '')));
            }
        }
        $InfoHtml .="</div>";
        ?>
           <?php echo  $InfoHtml; ?>
           <div style="padding: 10px 0;display: flex;justify-content: space-between;">
             <div  class="components-button is-secondary has-text has-icon eprolo-Tracking-Number" onClick="addTracking(this)">Add Tracking Number</div>
             <div id="EproloShipmentTrackingBoxClose" class="woocommerce-message-close notice-dismiss" style="position:relative;display:none" onClick="close_box()"></div>
           </div>
           <div  id="EproloShipmentTrackingBox" style="display:none">
           <div class="form-field">
               <label>Tracking Name:</label>
               <input type="text" id="eprolo_Provider_name" >
            </div>
            <div class="form-field">
               <label>Tracking number:</label>
               <input type="text" id="eprolo_tracking_number" >
            </div>
            <div class="form-field">
               <label>Tracking link:</label>
               <input type="text" id="eprolo_tracking_link" placeholder="http://">
            </div>
            <input style="display:none" type="text" id="eprolo_order_id" >
            <input style="display:none" type="text" id="eprolo_tracking_id" >
            <div style="padding:10px 0;">
              <div id="eprolo_tracking_submit" class="tracking-button" type="button" onclick="saveTrackingData(this, event)">
                <span id="eprolo_tracking_bt">Add Tracking</span><span id="eprolo-loading" class="eprolo-loading" style="display:none"></span>
              </div>
            </div>
           </div>
            
		<?php

	}
public function enqueue_scripts() {
    wp_enqueue_script(
        'aftership-form',
        plugins_url('assets/js/aftership-form.js', dirname(__FILE__)),
        array('jquery'),
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/aftership-form.js'),
        true
    );
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
     * 初始化
     */
    public function init() {
        // 添加到WooCommerce订单状态列表
        add_filter('wc_order_statuses', array($this, 'add_custom_order_statuses'));
        // 添加订单列表列
        add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_shipping_status_column' ), 99 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'show_shipping_status_column' ), 10, 2 );
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_eprolo_save_tracking_data', array($this, 'save_tracking_data'));
        add_action('wp_ajax_eprolo_get_order_info', array($this, 'get_order_info'));
        // add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        // 删除物流信息
        add_action('wp_ajax_eprolo_delete_tracking', array($this, 'delete_tracking_data'));
    }
    // 跟新列表
    public function get_order_info(){
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $order = wc_get_order($order_id);
        if (!$order) {
           wp_send_json_error(array('message' => 'The order does not exist.'));
           return;
        }
        $migrate =  $order->get_meta('_eprolo_tracking_items');
        if(is_array($migrate) && !empty($migrate)) {
            wp_send_json_success(array('message' =>  $migrate));
        }else{
            wp_send_json_error(array('message' => 'fail to save: '. $e->getMessage()));
        }
    }
    // 添加新的删除方法
public function delete_tracking_data() {
    $tracking_id = isset($_POST['tracking_id']) ? sanitize_text_field($_POST['tracking_id']) : '';
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(array('message' => 'The order does not exist.'));
        return;
    }
    
    try {
        $tracking_items = $order->get_meta('_eprolo_tracking_items', true);
        $tracking_items = is_array($tracking_items) ? $tracking_items : [];
        
        // 过滤掉要删除的跟踪项
        $updated_items = array_filter($tracking_items, function($item) use ($tracking_id) {
            return $item['tracking_id'] !== $tracking_id;
        });
        
        $order->update_meta_data('_eprolo_tracking_items', $updated_items);
        $order->save();
        
        wp_send_json_success(array('message' => 'The tracking information has been deleted.'));
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'fail to delete: ' . $e->getMessage()));
    }
}
    /**
     * 将自定义状态添加到WooCommerce订单状态列表
     */
    public function add_custom_order_statuses($order_statuses) {
        $order_statuses['wc-shipped'] = 'Fulfilled';
        return $order_statuses;
    }
    /**
 * 添加订单发货状态列
 */
public function add_shipping_status_column($columns) {
    $columns['woocommerce-automizely-eprolo-tracking'] = 'Eprolo Tracking';
	return $columns;
}

/**
 * 显示订单发货状态
 */
public function show_shipping_status_column( $column_name, $order ) {
    if ( 'woocommerce-automizely-eprolo-tracking' === $column_name ) {
        // echo $order;
        // return;
        $tracking_items = $order->get_meta( '_eprolo_tracking_items', true );
        // 确保 $tracking_items 是数组
        $tracking_items = is_array($tracking_items) ? $tracking_items : [];
        $aplugin = new Eprolo_OptionsManager();
        wp_enqueue_script( 
            'startup', 
            $aplugin->getUrl() . 'js/eproloTracking.js', 
            array('jquery'), 
            $aplugin->get_eprolo_version(), 
            true 
        );
        wp_enqueue_style( 'custom', $aplugin->getUrl() . 'css/eproloTracking.css', '', $aplugin->get_eprolo_version(), 'all' );
        wp_localize_script( 'startup', 'ajax_startup', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ) );
        if ( !empty($tracking_items) ) {
            echo '<ul class="wcas-tracking-number-list">';
            foreach ( $tracking_items as $tracking_item ) {
                // 根据 slug，匹配显示的 courier name
                // $provider_courier = $this->get_courier_by_slug(safeArrayGet($tracking_item, 'slug', ''));
                // 根据规则，生成 tracking link
                $eprolo_tracking_link = "";
                $tracking_link=safeArrayGet($tracking_item,'tracking_link', '');
                if(empty($tracking_link)){
                    $eprolo_tracking_link = sprintf("https://t.17track.net/en#nums=%s", safeArrayGet($tracking_item, 'tracking_number', ''));	
                }else{
                    $eprolo_tracking_link = sprintf("%s?nums=%s", $tracking_link,safeArrayGet($tracking_item, 'tracking_number', ''));	
                }
                printf(
                    '<li>
                    <div>
                        <b>%s</b>
                    </div>
                    <a href="%s" title="%s" target="_blank" class=ft11>%s</a>
                    <a href="#" class="eprolo_inline_tracking_delete" data-tracking-id="%s" data-order-id="%s" onclick="return confirmDeleteTracking(this)">
                        <span class="dashicons dashicons-trash"></span>
                    </a>
                </li>',
                    esc_html( safeArrayGet($tracking_item, 'provider_name', '')),
                    esc_url( $eprolo_tracking_link),
                    esc_html( safeArrayGet($tracking_item, 'tracking_number', '')),
                    esc_html( safeArrayGet($tracking_item, 'tracking_number', '')),
                    esc_html( safeArrayGet($tracking_item, 'tracking_id', '')),
                    esc_attr( safeArrayGet($tracking_item, 'order_id', ''))
                );
            }
            echo '</ul>';
        } else {
            echo '–';
        }
    }
}
/**
 * 保存跟踪信息
 */
public function save_tracking_data() {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $provider_name = isset($_POST['provider_name']) ? sanitize_text_field($_POST['provider_name']) : '';
    $tracking_number = isset($_POST['tracking_number']) ? sanitize_text_field($_POST['tracking_number']) : '';
    $tracking_link = isset($_POST['tracking_link']) ? sanitize_text_field($_POST['tracking_link']) : '';
    $ship_date = isset($_POST['ship_date']) ? sanitize_text_field($_POST['ship_date']) : '';
    $tracking_id = isset($_POST['tracking_id']) ? sanitize_text_field($_POST['tracking_id']) : '';
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(array('message' => 'The order does not exist.'));
        return;
    }
    try {
        // 保存跟踪信息
        $tracking_item                      = array();
        $tracking_item['order_id']   = wc_clean( $order_id);
		$tracking_item['provider_name']              = wc_clean( $provider_name  );
		$tracking_item['tracking_number']   = wc_clean( $tracking_number );
        $tracking_item['tracking_link']    = wc_clean( $tracking_link );
		$tracking_item['tracking_id']       = md5( "{$tracking_item['provider_name']}-{$tracking_item['tracking_number']}" );
        $tracking_item['metrics']           = array(
			'created_at' => current_time( 'Y-m-d\TH:i:s\Z' ),
			'updated_at' => current_time( 'Y-m-d\TH:i:s\Z' ),
		);
        $tracking_items = [];
        if($order->get_meta('_eprolo_tracking_items')){
            $tracking_items = $order->get_meta('_eprolo_tracking_items', true);
        }
        // 检查tracking_number是否已存在
        foreach($tracking_items as $item) {
          if($item['tracking_number'] === $tracking_number&&$item['tracking_id'] !== $tracking_id) {
            wp_send_json_error(array('message' => 'This tracking number already exists.'));
            return;
          } 
        }
        if(!empty($tracking_id)){
            foreach($tracking_items as &$item) {
                if($item['tracking_id'] == $tracking_id) {
                    $item['provider_name'] = $provider_name;
                    $item['tracking_number'] = $tracking_number;
                    $item['tracking_link'] = $tracking_link;
                    break;
                }
            }
            unset($item); // 解除引用
        } else {
            array_push($tracking_items, $tracking_item);
        }
        // 保存快递信息到订单meta
        $order->update_meta_data('_eprolo_tracking_items', $tracking_items);
        $order->save();
        
        wp_send_json_success(array('message' => 'The tracking information has been saved.'));
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'fail to save: ' . $e->getMessage()));
    }
}
}

// 初始化
Eprolo_Actions_New::get_instance()->init();


