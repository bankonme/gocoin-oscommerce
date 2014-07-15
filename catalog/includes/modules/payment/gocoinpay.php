<?php

/* $Id$ */

class gocoinpay {

    var $code, $title, $description, $enabled;
    var $pay_url = 'https://gateway.gocoin.com/merchant/';
    var $domain = '';
    var $baseUrl = '';

    // class constructor
    function gocoinpay() {
        global $order;
        $this->code = 'gocoinpay';
        $this->domain = ($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER;
        $this->baseUrl = $this->domain . DIR_WS_CATALOG;

        $this->title        = MODULE_PAYMENT_GOCOIN_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_GOCOIN_TEXT_PUBLIC_TITLE;
        $this->description  = MODULE_PAYMENT_GOCOIN_TEXT_DESCRIPTION;
        $this->sort_order   = MODULE_PAYMENT_GOCOIN_SORT_ORDER;
       
       
         if (PHP_VERSION < '5.3') {
                $php_version_allowed = false ;
         }
         else{
                $php_version_allowed = true ;
         }
         
        $this->enabled      = ((MODULE_PAYMENT_GOCOIN_STATUS == 'True') ? true : false);
        if($php_version_allowed==false){
            $this->enabled =false;  
        }
        
        // $this->baseUrl        = $this->getBaseUrl();
        if ((int) MODULE_PAYMENT_GOCOIN_DEFAULT_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_GOCOIN_DEFAULT_ORDER_STATUS_ID;
        }

        if (is_object($order))
            $this->update_status();
    }

    // class methods
    function update_status() {
        global $order;

        if (($this->enabled == true) && ((int) MODULE_PAYMENT_GOCOIN_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_GOCOIN_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
            while ($check = tep_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    function javascript_validation() {
        return false;
    }

    function selection() {

        return array('id' => $this->code,
            'module' => $this->public_title);
    }

    function pre_confirmation_check() {
        global $cartID, $cart;
        if (empty($cart->cartID)) {
            $cartID = $cart->cartID = $cart->generate_cart_id();
        }

        if (!tep_session_is_registered('cartID')) {
            tep_session_register('cartID');
        }
    }

    function confirmation() {
        global $order, $cartID, $cart_Gocoin_ID, $customer_id, $languages_id, $order_total_modules;

        $pay_type[] = array('id' => 'BTC', 'text' => 'Bitcoin');
        $pay_type[] = array('id' => 'XDG', 'text' => 'Dogecoin');
        $pay_type[] = array('id' => 'LTC', 'text' => 'Litecoin');
        if (tep_session_is_registered('cartID')) {
            $insert_order = false;
        }
        if (tep_session_is_registered('cart_Gocoin_ID')) {
            $order_id = substr($cart_Gocoin_ID, strpos($cart_Gocoin_ID, '-') + 1);

            $curr_check = tep_db_query("select currency from " . TABLE_ORDERS . " where orders_id = '" . (int) $order_id . "'");
            $curr = tep_db_fetch_array($curr_check);

            if (($curr['currency'] != $order->info['currency']) || ($cartID != substr($cart_Gocoin_ID, 0, strlen($cartID)))) {
                $check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int) $order_id . '" limit 1');

                if (tep_db_num_rows($check_query) < 1) {
                    tep_db_query('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int) $order_id . '"');
                    tep_db_query('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int) $order_id . '"');
                    tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int) $order_id . '"');
                    tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int) $order_id . '"');
                    tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int) $order_id . '"');
                    tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int) $order_id . '"');
                }

                $insert_order = true;
            }
        } else {
            $insert_order = true;
        }

        if ($insert_order == true) {
            $order_totals = array();
            if (is_array($order_total_modules->modules)) {
                reset($order_total_modules->modules);
                while (list(, $value) = each($order_total_modules->modules)) {
                    $class = substr($value, 0, strrpos($value, '.'));
                    if ($GLOBALS[$class]->enabled) {
                        for ($i = 0, $n = sizeof($GLOBALS[$class]->output); $i < $n; $i++) {
                            if (tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->output[$i]['text'])) {
                                $order_totals[] = array('code' => $GLOBALS[$class]->code,
                                    'title' => $GLOBALS[$class]->output[$i]['title'],
                                    'text' => $GLOBALS[$class]->output[$i]['text'],
                                    'value' => $GLOBALS[$class]->output[$i]['value'],
                                    'sort_order' => $GLOBALS[$class]->sort_order);
                            }
                        }
                    }
                }
            }

            $sql_data_array = array('customers_id' => $customer_id,
                'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                'customers_company' => $order->customer['company'],
                'customers_street_address' => $order->customer['street_address'],
                'customers_suburb' => $order->customer['suburb'],
                'customers_city' => $order->customer['city'],
                'customers_postcode' => $order->customer['postcode'],
                'customers_state' => $order->customer['state'],
                'customers_country' => $order->customer['country']['title'],
                'customers_telephone' => $order->customer['telephone'],
                'customers_email_address' => $order->customer['email_address'],
                'customers_address_format_id' => $order->customer['format_id'],
                'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                'delivery_company' => $order->delivery['company'],
                'delivery_street_address' => $order->delivery['street_address'],
                'delivery_suburb' => $order->delivery['suburb'],
                'delivery_city' => $order->delivery['city'],
                'delivery_postcode' => $order->delivery['postcode'],
                'delivery_state' => $order->delivery['state'],
                'delivery_country' => $order->delivery['country']['title'],
                'delivery_address_format_id' => $order->delivery['format_id'],
                'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                'billing_company' => $order->billing['company'],
                'billing_street_address' => $order->billing['street_address'],
                'billing_suburb' => $order->billing['suburb'],
                'billing_city' => $order->billing['city'],
                'billing_postcode' => $order->billing['postcode'],
                'billing_state' => $order->billing['state'],
                'billing_country' => $order->billing['country']['title'],
                'billing_address_format_id' => $order->billing['format_id'],
                'payment_method' => $order->info['payment_method'],
                'cc_type' => $order->info['cc_type'],
                'cc_owner' => $order->info['cc_owner'],
                'cc_number' => $order->info['cc_number'],
                'cc_expires' => $order->info['cc_expires'],
                'date_purchased' => 'now()',
                'orders_status' => $order->info['order_status'],
                'currency' => $order->info['currency'],
                'currency_value' => $order->info['currency_value']);

            tep_db_perform(TABLE_ORDERS, $sql_data_array);

            $insert_id = tep_db_insert_id();

            for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
                $sql_data_array = array('orders_id' => $insert_id,
                    'title' => $order_totals[$i]['title'],
                    'text' => $order_totals[$i]['text'],
                    'value' => $order_totals[$i]['value'],
                    'class' => $order_totals[$i]['code'],
                    'sort_order' => $order_totals[$i]['sort_order']);

                tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
            }

            for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
                $sql_data_array = array('orders_id' => $insert_id,
                    'products_id' => tep_get_prid($order->products[$i]['id']),
                    'products_model' => $order->products[$i]['model'],
                    'products_name' => $order->products[$i]['name'],
                    'products_price' => $order->products[$i]['price'],
                    'final_price' => $order->products[$i]['final_price'],
                    'products_tax' => $order->products[$i]['tax'],
                    'products_quantity' => $order->products[$i]['qty']);

                tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

                $order_products_id = tep_db_insert_id();

                $attributes_exist = '0';
                if (isset($order->products[$i]['attributes'])) {
                    $attributes_exist = '1';
                    for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++) {
                        if (DOWNLOAD_ENABLED == 'true') {
                            $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                           from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                           left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                           on pa.products_attributes_id=pad.products_attributes_id
                                           where pa.products_id = '" . $order->products[$i]['id'] . "'
                                           and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                           and pa.options_id = popt.products_options_id
                                           and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                           and pa.options_values_id = poval.products_options_values_id
                                           and popt.language_id = '" . $languages_id . "'
                                           and poval.language_id = '" . $languages_id . "'";
                            $attributes = tep_db_query($attributes_query);
                        } else {
                            $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
                        }
                        $attributes_values = tep_db_fetch_array($attributes);

                        $sql_data_array = array('orders_id' => $insert_id,
                            'orders_products_id' => $order_products_id,
                            'products_options' => $attributes_values['products_options_name'],
                            'products_options_values' => $attributes_values['products_options_values_name'],
                            'options_values_price' => $attributes_values['options_values_price'],
                            'price_prefix' => $attributes_values['price_prefix']);

                        tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

                        if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
                            $sql_data_array = array('orders_id' => $insert_id,
                                'orders_products_id' => $order_products_id,
                                'orders_products_filename' => $attributes_values['products_attributes_filename'],
                                'download_maxdays' => $attributes_values['products_attributes_maxdays'],
                                'download_count' => $attributes_values['products_attributes_maxcount']);

                            tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
                        }
                    }
                }
            }

            $cart_Gocoin_ID = $cartID . '-' . $insert_id;
            tep_session_register('cart_Gocoin_ID');
        }

        $confirmation = array('fields' => array(
                array('title' => MODULE_PAYMENT_GOCOIN_PAYTYPE,
                    'field' => tep_draw_pull_down_menu('pay_type', $pay_type)),
        ));


        return $confirmation;
    }

    function process_button() {

        return False;
    }

    function before_process() {
        global $customer_id, $cartID, $HTTP_POST_VARS, $customer_id, $cart_Gocoin_ID, $order, $sendto, $currency;

        $coin_currency = isset($_POST['pay_type']) && !empty($_POST['pay_type']) ? $_POST['pay_type'] : '';

        $customer = $order->billing['firstname'] . ' ' . $order->billing['lastname'];
        $callback_url = $this->baseUrl . "ext/modules/payment/gocoinpay/callback_url.php";

        include DIR_WS_INCLUDES . 'gocoinlib/src/GoCoin.php';
        $return_url = $this->baseUrl . "checkout_success.php";
        $options = array(
            'price_currency'        => $coin_currency,
            'base_price'            => $order->info['total'],
            'base_price_currency'   => "USD", //$order_info['currency_code'],
            'callback_url'          => $callback_url,
            'redirect_url'          => $return_url,
            'order_id'              => substr($cart_Gocoin_ID, strpos($cart_Gocoin_ID, '-') + 1),
            'customer_name'         => $customer,
            'customer_address_1'    => $order->billing['street_address'],
            'customer_address_2'    => '',
            'customer_city'         => $order->delivery['city'],
            'customer_region'       => $order->delivery['state'],
            'customer_postal_code'  => $order->customer['postcode'],
            'customer_country'      => $order->billing['country']['title'],
            'customer_phone'        => $order->customer['telephone'],
            'customer_email'        => $order->customer['email_address'],);

        $key = $this->getGUID();
        $signature = $this->getSignatureText($options, $key);
        $options['user_defined_8'] = $signature;

        //$data_string = json_encode($options);
        $client_id      = MODULE_PAYMENT_GOCOIN_MERCHANT_ID;
        $client_secret  = MODULE_PAYMENT_GOCOIN_ACCESS_KEY;
        $access_token   = MODULE_PAYMENT_GOCOIN_TOKEN;
        $gocoin_url     = $this->pay_url;

 

        if (empty($access_token)) {  //-----------If  Token not found 
            $result = 'error';
            $json['error'] = 'GoCoin Payment Paramaters not Set. Please report this to Site Administrator.';
        } else {  //-----------If  Token  found 
            try {
                $user = GoCoin::getUser($access_token); //----------- If no Error in user creation from token
                if ($user) {
                    $merchant_id = $user->merchant_id;
                    if (!empty($merchant_id)) { //----------- If merchant_id Variable is not blank 
                        $invoice = GoCoin::createInvoice($access_token, $merchant_id, $options);
                        if (isset($invoice->errors)) { //----------- if $invoice->errors found 
                            $result = 'error';
                            $errormsg = isset($invoice->errors->currency_code[0])? $invoice->errors->currency_code[0] : '';
                            $json['error'] = "Error in Processing Order using GoCoin:". $errormsg;
                        } elseif (isset($invoice->error)) { //----------- if $invoice->error found 
                            $result = 'error';
                            $json['error'] = "Error in Processing Order using GoCoin ".$invoice->error;
                        } elseif (isset($invoice->merchant_id) && $invoice->merchant_id != '' && isset($invoice->id) && $invoice->id != '') {
                            $url = $gocoin_url . $invoice->merchant_id . "/invoices/" . $invoice->id;
                            $result = 'success';
                            $messages = 'success';
                            $json['success'] = $url;
                        }else{
                           //-----------  if $invoice is balnk 
                            $result = 'error';
                            $json['error'] = "Error in Processing Order using GoCoin ";
                        }
                    }
                    else {
                        //----------- If merchant_id Variable is blank 
                        $result = 'error';
                        $json['error'] =  'Error in Processing Order using GoCoin, please try selecting other payment options';
                    }
                }
                else{
                     //----------- If user Variable is blank 
                        $result = 'error';
                        $json['error'] =  'Error in Processing Order using GoCoin, please try selecting other payment options';
               }
            } 
            catch (Exception $e) {
                //----------- If  error in user creation from token
                $result = 'error';
                $json['error'] = 'Error in Processing Order using GoCoin, please try selecting other payment options';
            }
        }


        if (isset($json['error']) && $json['error'] != '') {
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error=' . base64_encode($json['error']), 'SSL'));
        } else {
            $json_array = array(
                'order_id' => $invoice->order_id,
                'invoice_id' => $invoice->id,
                'url' => $url,
                'status' => 'invoice_created',
                'btc_price' => $invoice->price,
                'price' => $invoice->base_price,
                'currency' => $invoice->base_price_currency,
                'currency_type' => $invoice->price_currency,
                'invoice_time' => $invoice->created_at,
                'expiration_time' => $invoice->expires_at,
                'updated_time' => $invoice->updated_at,
                'fingerprint' => $signature,
            );
            $this->addTransaction_v1('payment', $json_array);
            tep_redirect($url);
        }
    }

    function after_process() {
        return false;
    }

    function get_error() {
        global $HTTP_GET_VARS;
        $error_message = MODULE_PAYMENT_GOCOIN_ERROR_GENERAL;
        $error = array('title' => MODULE_PAYMENT_GOCOIN_ERROR_TITLE,
            'error' => base64_decode($HTTP_GET_VARS['error']));
        return $error;
    }

    function check() {
        if (!isset($this->_check)) {
            $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_GOCOIN_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        return $this->_check;
    }

    function install() {
        $btn_code = 'you can click button to get access token from gocoin.com <br><button style="" onclick="get_api_token(); return false;" class="scalable " title="Get API Token" id="btn_get_token"><span><span><span>Get API Token</span></span></span></button>';
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable GoCoin Method', 'MODULE_PAYMENT_GOCOIN_STATUS', 'False', 'Do you want to accept GoCoin Method payments?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Client ID', 'MODULE_PAYMENT_GOCOIN_MERCHANT_ID', '', '', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Client Secret Key', 'MODULE_PAYMENT_GOCOIN_ACCESS_KEY', '', '', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Access Token', 'MODULE_PAYMENT_GOCOIN_TOKEN', '', '', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_GOCOIN_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Deafult Order Status', 'MODULE_PAYMENT_GOCOIN_DEFAULT_ORDER_STATUS_ID', '0', 'Set the status of prepared orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set GoCoin Acknowledged Order Status', 'MODULE_PAYMENT_GOCOIN_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('', 'MODULE_PAYMENT_GOCOIN_CREATE_TOKEN', '', '', '6', '0', 'tep_call_function(\'create_gocoin_token\', \'\',  ', now())");

        $this->getDbTable();
    }

    function remove() {
        tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
        return array('MODULE_PAYMENT_GOCOIN_STATUS',
            'MODULE_PAYMENT_GOCOIN_MERCHANT_ID',
            'MODULE_PAYMENT_GOCOIN_ACCESS_KEY',
            'MODULE_PAYMENT_GOCOIN_TOKEN',
            'MODULE_PAYMENT_GOCOIN_DEFAULT_ORDER_STATUS_ID',
            'MODULE_PAYMENT_GOCOIN_ORDER_STATUS_ID',
            'MODULE_PAYMENT_GOCOIN_SORT_ORDER',
            'MODULE_PAYMENT_GOCOIN_CREATE_TOKEN',
        );
    }

    // format prices without currency formatting
    function format_raw($number, $currency_code = '', $currency_value = '') {
        global $currencies, $currency;

        if (empty($currency_code) || !$this->is_set($currency_code)) {
            $currency_code = $currency;
        }

        if (empty($currency_value) || !is_numeric($currency_value)) {
            $currency_value = $currencies->currencies[$currency_code]['value'];
        }

        return number_format(tep_round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }

    public function getDbTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `gocoin_ipn` (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `order_id` int(10) unsigned DEFAULT NULL,
                    `invoice_id` varchar(200) NOT NULL,
                    `url` varchar(400) NOT NULL,
                    `status` varchar(100) NOT NULL,
                    `btc_price` decimal(16,8) NOT NULL,
                    `price` decimal(16,8) NOT NULL,
                    `currency` varchar(10) NOT NULL,
                    `currency_type` varchar(10) NOT NULL,
                    `invoice_time` datetime NOT NULL,
                    `expiration_time` datetime NOT NULL,
                    `updated_time` datetime NOT NULL,
                    `fingerprint`  varchar(250) NOT NULL,
                    PRIMARY KEY (`id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

        $query = tep_db_query($sql);
    }

    public function addTransaction_v1($type = 'payment', $details) {
        return tep_db_query("
              INSERT INTO gocoin_ipn (order_id, invoice_id, url, status, btc_price,
              price, currency, currency_type, invoice_time, expiration_time, updated_time,fingerprint)
              VALUES ( 
                  '" .  tep_db_prepare_input($details['order_id']) . "',
                  '" .  tep_db_prepare_input($details['invoice_id']) . "',
                  '" .  tep_db_prepare_input($details['url']) . "',
                  '" .  tep_db_prepare_input($details['status']) . "',
                  '" .  tep_db_prepare_input($details['btc_price']) . "',
                  '" .  tep_db_prepare_input($details['price']) . "',
                  '" .  tep_db_prepare_input($details['currency']) . "',
                  '" .  tep_db_prepare_input($details['currency_type']) . "',
                  '" .  tep_db_prepare_input($details['invoice_time']) . "',
                  '" .  tep_db_prepare_input($details['expiration_time']) . "',
                  '" .  tep_db_prepare_input($details['updated_time']) . "',
                  '" .  tep_db_prepare_input($details['fingerprint']) . "'  )");
    }

    public function getGUID() {
        if (function_exists('com_create_guid')) {
            $guid = com_create_guid();
            $guid = str_replace("{", "", $guid);
            $guid = str_replace("}", "", $guid);
            return $guid;
        } else {
            mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = substr($charid, 0, 8) . $hyphen
                    . substr($charid, 8, 4) . $hyphen
                    . substr($charid, 12, 4) . $hyphen
                    . substr($charid, 16, 4) . $hyphen
                    . substr($charid, 20, 12); // .chr(125) //"}"
            return $uuid;
        }
    }

    public function getSignatureText($data, $uniquekey) {
        $query_str = '';
        $include_params = array('price_currency', 'base_price', 'base_price_currency', 'order_id', 'customer_name', 'customer_city', 'customer_region', 'customer_postal_code', 'customer_country', 'customer_phone', 'customer_email');
        if (is_array($data)) {
            ksort($data);
            $querystring = "";
            foreach ($data as $k => $v) {
                if (in_array($k, $include_params)) {
                    $querystring = $querystring . $k . "=" . $v . "&";
                }
            }
        } else {
            if (isset($data->payload)) {
                $payload_obj = $data->payload;
                $payload_arr = get_object_vars($payload_obj);
                ksort($payload_arr);
                $querystring = "";
                foreach ($payload_arr as $k => $v) {
                    if (in_array($k, $include_params)) {
                        $querystring = $querystring . $k . "=" . $v . "&";
                    }
                }
            }
        }
        $query_str = substr($querystring, 0, strlen($querystring) - 1);
        $query_str = strtolower($query_str);
        $hash2 = hash_hmac("sha256", $query_str, $uniquekey, true);
        $hash2_encoded = base64_encode($hash2);
        return $hash2_encoded;
    }

}

function create_gocoin_token() {
        if (PHP_VERSION < '5.3') {
                $php_version_allowed = false ;
         }
         else{
                $php_version_allowed = true ;
         }
            
         
    $domain = ($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER;
    $baseUrl = $domain . DIR_WS_CATALOG;
    $str = '<input type="hidden" id="cid" value="'.MODULE_PAYMENT_GOCOIN_MERCHANT_ID.'"/>
                <input type="hidden" id="csec" value="'.MODULE_PAYMENT_GOCOIN_ACCESS_KEY.'"/><b>you can click button to get access token from gocoin.com</b><input type="button" value="Get API TOKEN" onclick="return get_api_token();">';
   
    if($php_version_allowed==false){
       $str.='<br><div style="color: #ff0000;font-weight: bold;">The minimum PHP version required for GoCoin plugin is 5.3.0 </div>'; 
    }
    
    $str.= '<script type="text/javascript">
            var base ="' . $baseUrl . '";
            function get_api_token()    
            {
                    var client_id = "";
                     var client_secret ="";
                        var elements = document.forms["modules"].elements;
                        for (i=0; i<elements.length; i++){
                            if(elements[i].name=="configuration[MODULE_PAYMENT_GOCOIN_MERCHANT_ID]"){
                                client_id = elements[i].value;
                            }
                            if(elements[i].name=="configuration[MODULE_PAYMENT_GOCOIN_ACCESS_KEY]"){
                                client_secret =  elements[i].value;
                            }

                        }

                    if (!client_id) {
                        //alert("Please input "+mer_id+" !");
                        alert("Please input Client Id !");
                        return false;
                    }
                    if (!client_secret) {
                       // alert("Please input "+access_key+" !");
                        alert("Please input Client Secret Key !");
                        return false;
                    }
                    

                    var cid = document.getElementById("cid").value;
                    var csec = document.getElementById("csec").value;
                    if (client_id != cid || client_secret != csec) {
                       alert("Please save changed Client Id and Client Secret Key first!");
                       return;
                    }

                    var currentUrl =  base+ "ext/modules/payment/gocoinpay/showtoken.php"; 
                    //alert(currentUrl);
                    var url = "https://dashboard.gocoin.com/auth?response_type=code"
                                + "&client_id=" + client_id
                                + "&redirect_uri=" + currentUrl
                                + "&scope=user_read+invoice_read_write";
                                

                    var strWindowFeatures = "location=yes,height=570,width=520,scrollbars=yes,status=yes";
                    var win = window.open(url, "_blank", strWindowFeatures);
                    return false;
                }</script>';
    return $str;
}

?>