<?php

chdir('../../../../');
require('includes/application_top.php');
if (!defined('MODULE_PAYMENT_GOCOIN_STATUS') || (MODULE_PAYMENT_GOCOIN_STATUS != 'True')) {
    exit;
}

function callback() {
    _paymentStandard();
}

function getNotifyData() {
    $post_data = file_get_contents("php://input");

    if (!$post_data) {
        $response = new stdClass();
        $response->error = 'Post Data Error';
        return $response;
    }
    $response = json_decode($post_data);
    return $response;
}

function _paymentStandard() {
    $sts_default = MODULE_PAYMENT_GOCOIN_DEFAULT_ORDER_STATUS_ID; // Default
    $sts_processing = MODULE_PAYMENT_GOCOIN_ORDER_STATUS_ID; // Processing

    $module_display = 'gocoin';
    $response = getNotifyData();
    $error = 0;
    if (!$response) {
        $error = $error + 1;
        $error_msg[] = ' NotifyData Blank';
        //======================Error=============================     
    }
    if (isset($response->error) && $response->error != '') {
        $error = $error + 1;
        $error_msg[] = $response->error;
    }
    if (isset($response->payload)) {
        //======================IF Response Get=============================     
        $event                  = $response->event;
        $order_id               = (int) $response->payload->order_id;
        $redirect_url           = $response->payload->redirect_url;
        $transction_id          = $response->payload->id;
        $total                  = $response->payload->base_price;
        $status                 = $response->payload->status;

        $currency               = $response->payload->base_price_currency;
        $currency_type          = $response->payload->price_currency;
        $invoice_time           = $response->payload->created_at;
        $expiration_time        = $response->payload->expires_at;
        $updated_time           = $response->payload->updated_at;
        $merchant_id            = $response->payload->merchant_id;
        $btc_price              = $response->payload->price;
        $price                  = $response->payload->base_price;
        $url                    = "https://gateway.gocoin.com/merchant/" . $merchant_id . "/invoices/" . $transction_id;
        $fprint                 =  $response->payload->user_defined_8;
                    
        //=================== Set To Array=====================================//
        //Used for adding in db
        $iArray = array(
            'order_id'          => $order_id,
            'invoice_id'        => $transction_id,
            'url'               => $url,
            'status'            => $event,
            'btc_price'         => $btc_price,
            'price'             => $price,
            'currency'          => $currency,
            'currency_type'     => $currency_type,
            'invoice_time'      => $invoice_time,
            'expiration_time'   => $expiration_time,
            'updated_time'      => $updated_time,
            'fingerprint'       => $fprint);

               $i_id =  getFPStatus($iArray);
               if(!empty($i_id) && $i_id==$transction_id){
                 updateTransaction('payment', $iArray);
                
                 switch($response->event)
                 {
                    case 'invoice_created':
                      break;
                    case 'invoice_payment_received':
                    case 'invoice_ready_to_ship':
                            if (isset($order_id) && is_numeric($order_id) && ($order_id > 0)) {
                                $cur_sts = $sts_processing;
                                $order_query = tep_db_query("select orders_status, currency, currency_value from " . TABLE_ORDERS . " where orders_id = '" . $order_id . "'");
                                if (tep_db_num_rows($order_query) > 0) {
                                    $order = tep_db_fetch_array($order_query);

                                    if ($order['orders_status'] == MODULE_PAYMENT_GOCOIN_DEFAULT_ORDER_STATUS_ID) {
                                        $sql_data_array = array('orders_id' => $order_id,
                                            'orders_status_id' => MODULE_PAYMENT_GOCOIN_DEFAULT_ORDER_STATUS_ID,
                                            'date_added' => 'now()',
                                            'customer_notified' => '0',
                                            'comments' => '');

                                        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

                                        if ($status == 'paid') {
                                            tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . (MODULE_PAYMENT_GOCOIN_ORDER_STATUS_ID > 0 ? (int) MODULE_PAYMENT_GOCOIN_ORDER_STATUS_ID : (int) DEFAULT_ORDERS_STATUS_ID) . "', last_modified = now() where orders_id = '" . (int) $order_id . "'");
                                        }
                                    }

                                    $total_query = tep_db_query("select value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . $order_id . "' and class = 'ot_total' limit 1");
                                    $total = tep_db_fetch_array($total_query);

                                    $comment_status = $status;


                                    $sql_data_array = array('orders_id' => $order_id,
                                        'orders_status_id' => (MODULE_PAYMENT_GOCOIN_ORDER_STATUS_ID > 0 ? (int) MODULE_PAYMENT_GOCOIN_ORDER_STATUS_ID : (int) DEFAULT_ORDERS_STATUS_ID),
                                        'date_added' => 'now()',
                                        'customer_notified' => '0',
                                        'comments' => $comment_status);


                                    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
                                }
                            }
                        break;
                }
               }
    }

    if ($error > 0) {
        $email_body = @implode('<br>', $error_msg);
        //tep_mail('', , 'GoCoin Invalid Process', $email_body, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
    }
}

function getFPStatus($details){
        $query = tep_db_query("SELECT invoice_id FROM  gocoin_ipn where 
            invoice_id  = '". tep_db_prepare_input($details['invoice_id'])."' and   
            fingerprint = '". tep_db_prepare_input($details['fingerprint'])."'       
         ");
        if(tep_db_num_rows($query) > 0){
            $row = tep_db_fetch_array($query);
            if(isset($row['invoice_id'])) {
                return $row['invoice_id'];
            }
        }
 }                    

function updateTransaction($type = 'payment', $details) {
    return tep_db_query("
		update  gocoin_ipn set 
        status       = '". tep_db_prepare_input($details['status'])."',   
        updated_time = '". tep_db_prepare_input($details['updated_time'])."'     where
        invoice_id   = '". tep_db_prepare_input($details['invoice_id'])."' and   
        order_id     = '". tep_db_prepare_input($details['order_id'])."'       
     ");
                    
}

callback();
require('includes/application_bottom.php');
?>
