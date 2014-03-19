<?php

chdir('../../../../');
require('includes/application_top.php');
if (!defined('MODULE_PAYMENT_GOCOIN_STATUS') || (MODULE_PAYMENT_GOCOIN_STATUS != 'True')) {
    exit;
}

function shoGocoinToken() {
    $merchant_id = MODULE_PAYMENT_GOCOIN_MERCHANT_ID;
    $gocoin_access_key = MODULE_PAYMENT_GOCOIN_ACCESS_KEY;

    $arr = array(
        'client_id' => $merchant_id,
        'client_secret' => $gocoin_access_key,
        'scope' => "user_read_write+merchant_read_write+invoice_read_write",);


    include DIR_WS_INCLUDES . 'gocoinlib/src/client.php';

    $client = new Client($arr);

    $b_auth = $client->authorize_api();
    $result = array();
    if ($b_auth) {
        $result['success'] = true;
        $result['data'] = $client->getToken();
        echo "Copy this Access Token into your GoCoin Module: " . $client->getToken();
    } else {
        $result['success'] = false;
        echo "Error in getting Token: " . $client->getError();
    }
    die();
}

shoGocoinToken();
require('includes/application_bottom.php');
?>
