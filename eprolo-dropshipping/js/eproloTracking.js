// 关闭表单
function close_box(element){
    jQuery("#EproloShipmentTrackingBox").hide();
    jQuery("#EproloShipmentTrackingBoxClose").hide();
}
// 增加数据
function addTracking(element){
    jQuery("#EproloShipmentTrackingBox").show();
    jQuery("#EproloShipmentTrackingBoxClose").show();
    jQuery('#eprolo_tracking_bt').text('Add Tracking')
    const boxDom=element.parentNode.parentNode;
    if(boxDom){
     const EproloShipmentTrackingBox = jQuery(boxDom).find('#EproloShipmentTrackingBox')
     if(EproloShipmentTrackingBox){
        EproloShipmentTrackingBox.find('#eprolo_Provider_name').val('');
        EproloShipmentTrackingBox.find('#eprolo_tracking_number').val('');
        EproloShipmentTrackingBox.find('#eprolo_tracking_link').val('');
      }
    }
}
// 保存数据
function saveTrackingData(element) {
    // 添加权限检查
    if (!window.eprolo_is_admin) {
        messageAlert('You do not have permission to perform this action', 0);
        return false;
    }
    // Get the current order ID from WordPress admin
    const orderId = getCurrentOrderId();
    
    // Get form values
    const providerName = document.getElementById('eprolo_Provider_name').value;
    const trackingNumber = document.getElementById('eprolo_tracking_number').value;
    const trackingLink = document.getElementById('eprolo_tracking_link').value;
    // const shipDate = document.getElementById('aftership-ship-date').value;
    // Validate required fields
    if (!providerName || !trackingNumber) {
        messageAlert('Provider Name and Tracking Number are required',0);
        return;
    }
    let data={
        action: 'eprolo_save_tracking_data',
        order_id: orderId,
        provider_name: providerName,
        tracking_number: trackingNumber,
        tracking_link: trackingLink,
    }
    if(jQuery('#eprolo_tracking_bt').text()=='Saving...'){
        return;
    }
    let isEdit = false
    if(jQuery(element).find('#eprolo_tracking_bt').text()=='Edit Tracking'){
        data['tracking_id'] = jQuery('#eprolo_tracking_id').val();
        isEdit = true;
    }
    // 显示loading
    jQuery('#eprolo-loading').show();
    jQuery('#eprolo_tracking_bt').text('Saving...');
    // Send data via AJAX
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: data,
        success: function(response) {
            console.log(response)
            if (response.success) {
                updateTrackingList(orderId,isEdit)
            } else {
                // 隐藏loading
            jQuery('#eprolo-loading').hide();
            jQuery('#eprolo_tracking_bt').text('Add Tracking');
                messageAlert(response.data.message,0)
            }
        },
        error: function(xhr, status, error) {
            // 隐藏loading
            jQuery('#eprolo-loading').hide();
            jQuery('#eprolo_tracking_bt').text('Add Tracking');
            alert('AJAX Error: ' + error);
        }
    });
    return false; // Prevent default form submission;
}
// 跟新物流列表
function updateTrackingList(orderId,isEdit) {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'eprolo_get_order_info',
            order_id: orderId
        },
        success: function(response) {
            if (response.success) {
                messageAlert('Tracking information saved successfully');
                let value = response.data.message;
                let keys = Object.keys(value);
                if(keys.length>0){
                   var InfoHtml="";
                   keys.forEach(function(key, index) {
                    let tracking=value[key];
                     let eprolo_tracking_link = "";
                     if(tracking['tracking_link']==''){
                        eprolo_tracking_link = "https://t.17track.net/en#nums="+tracking['tracking_number'];
                     }else{
                        eprolo_tracking_link = tracking['tracking_link']+"?nums="+tracking['tracking_number'];
                     }
                     InfoHtml += `<div class="_tracking_nkd9j_19"><div class="_title_nkd9j_23"><div class="_title_nkd9j_index">Shipment ${index + 1}</div><div data-tracking-id="${tracking['tracking_id']}" data-order-id="${tracking['order_id']}"  data-provider_name="${tracking['provider_name']}" data-tracking_number="${tracking['tracking_number']}" data-tracking_link="${tracking['tracking_link']}"> <a href="#" onclick="return editTraking(this)">Edit</span></a>   <a href="#" onclick="return confirmDeleteTracking(this,2)">Delete</a></div></div><div class="_content_nkd9j_38"><div class="_number_nkd9j_45"><div><strong>${tracking['provider_name']}&nbsp;</strong></div><div><a target="_blank" title="122" href="${'eprolo_tracking_link'}">${tracking['tracking_number']}</a></div></div></div></div>`

                   })
                   document.getElementById('eprolo_tracking_list').innerHTML = InfoHtml;
                   document.getElementById('eprolo_Provider_name').value='';
                   document.getElementById('eprolo_tracking_number').value='';
                   document.getElementById('eprolo_tracking_link').value='';
                  if(isEdit){
                    close_box();
                  }
                }
            } else {
                messageAlert(response.message, 0);
            }
            // 隐藏loading
            jQuery('#eprolo-loading').hide();
            jQuery('#eprolo_tracking_bt').text('Add Tracking');
        },
        error: function(xhr, status, error) {
            // 隐藏loading
            jQuery('#eprolo-loading').hide();
            jQuery('#eprolo_tracking_bt').text('Add Tracking');
            messageAlert('获取订单信息失败: ' + error, 0);
        }
    });
}
// Helper function to get current order ID
function getCurrentOrderId() {
    // This depends on how your WooCommerce admin is structured
    // Common ways to get order ID:
    // 1. From URL
    const urlParams = new URLSearchParams(window.location.search);
    const postParam = urlParams.get('post');
    
    // 2. From hidden field
    const orderIdInput = document.querySelector('input[name="post_ID"]');
    
    return postParam || (orderIdInput ? orderIdInput.value : 0);
}
function messageAlert($message,type=1) {
    const tempDiv = document.createElement('div');
    if(type==1){
        tempDiv.innerHTML = sprintf('<div  class="el-message"><div class="Eprolo-task__icon"></div><p class="el-message__content">%s</p></div>', $message);
    }else{
        tempDiv.innerHTML = sprintf('<div  class="el-message el-message-error"><div class="Eprolo-task__icon"></div><p class="el-message__content">%s</p></div>', $message);
    }
    document.body.appendChild(tempDiv);
    setTimeout(() => {
        tempDiv.remove();
    }, 5000);
}
// 编辑物流信息
function editTraking(element){
    const boxDom=element.parentNode.parentNode.parentNode.parentNode.parentNode;
    jQuery("#EproloShipmentTrackingBoxClose").show();
    if(boxDom){
     const EproloShipmentTrackingBox = jQuery(boxDom).find('#EproloShipmentTrackingBox')
     if(EproloShipmentTrackingBox){
        EproloShipmentTrackingBox.show()
        EproloShipmentTrackingBox.find('#eprolo_Provider_name').val(jQuery(element.parentNode).data('provider_name'));
        EproloShipmentTrackingBox.find('#eprolo_tracking_number').val(jQuery(element.parentNode).data('tracking_number'));
        EproloShipmentTrackingBox.find('#eprolo_tracking_link').val(jQuery(element.parentNode).data('tracking_link'));
        EproloShipmentTrackingBox.find('#eprolo_order_id').attr('value',jQuery(element.parentNode).data('order-id'));
        EproloShipmentTrackingBox.find('#eprolo_tracking_id').attr('value',jQuery(element.parentNode).data('tracking-id'));
        EproloShipmentTrackingBox.find('#eprolo_tracking_bt').text('Edit Tracking')
        // console.log(element,element.parentNode,jQuery(element.parentNode),jQuery(element.parentNode).data('tracking_id'))
      }
    }
    return false;
}
// 删除物流信息
function confirmDeleteTracking(element,type=1) {
    // 添加权限检查
    if (!window.eprolo_is_admin) {
        messageAlert('You do not have permission to perform this action', 0);
        return false;
    }
    if (confirm('Are you sure you want to delete this tracking information?')) {
        var trackingId = element.getAttribute('data-tracking-id');
        var orderId = element.getAttribute('data-order-id');
        if(type==2){
            var trackingId = element.parentNode.getAttribute('data-tracking-id');
            var orderId = element.parentNode.getAttribute('data-order-id');
        }
        jQuery.ajax({
            url: ajax_startup.ajaxUrl,
            type: 'POST',
            data: {
                action: 'eprolo_delete_tracking',
                tracking_id: trackingId,
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    if(type==2){
                        element.parentNode.parentNode.parentNode.remove()
                    }else{
                        element.closest('li').remove();
                    }
                    messageAlert('successfully')
                } else {
                    messageAlert(response.data.message,2)
                }
            }
        });
    }
    return false;
}