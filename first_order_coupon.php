<?php
/*
  Plugin Name: second order discount
  
  Description: Pull your customers back for the second order.
 */
add_action('woocommerce_thankyou', 'generate_coupon_after_first_order', 10, 1); //place an order后给用户coupon
function generate_coupon_after_first_order()
{   
    function bill_is_one() //检测是否正好一单
    {
        // Get all customer orders
        $customer_orders = get_posts(array(
            'meta_key'    => '_customer_user',
            'meta_value'  => get_current_user_id(),
            'post_type'   => 'shop_order', // WC orders post type
            'post_status' => array(
                'wc-completed',
                'wc-on-hold',
                'wc-processing',
                'wc-shipped',
                'wc-refunded'
            ),
            'fields'      => 'ids',
        ));
        return (count($customer_orders) == 1);
    };
    if (is_user_logged_in()) { //检测用户是否登入
        $current_user = wp_get_current_user();
        //获取fullname
        $fullname = get_user_meta($current_user->ID, 'billing_first_name', true) . " " . get_user_meta($current_user->ID, 'billing_last_name', true);
        if (!$current_user)
            return;
        //调用检测是否只有一个订单， 检测coupon是否已存在，避免刷新订单页面导致获取多个coupon
        if (bill_is_one() && (new WC_Coupon($fullname))->usage_limit !== 1) {
            //建立新coupon，开始
            $_newCoupon = array(
                'post_title' => $fullname,
                'post_content' => '',
                'post_status' => 'publish',
                'post_author' => 1,
                'post_type'     => 'shop_coupon',
            );
            $new_coupon_id = wp_insert_post($_newCoupon);
            update_post_meta($new_coupon_id, 'discount_type', "percent");
            update_post_meta($new_coupon_id, 'coupon_amount', 20);
            update_post_meta($new_coupon_id, 'individual_use', 'yes');
            update_post_meta($new_coupon_id, 'usage_limit', '1');
            update_post_meta($new_coupon_id, 'expiry_date', '');
            update_post_meta($new_coupon_id, 'apply_before_tax', 'yes');
            update_post_meta($new_coupon_id, 'free_shipping', 'no');
            //提示用户获得了coupon
            wc_print_notice(__('You received a coupon on your first order:' . $fullname, 'woocommerce'), 'notice');
        }
    }
};
