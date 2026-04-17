<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    | The default gateway to use when no gateway name is specified.
    | Set via TRUEPOS_GATEWAY in your .env file.
    |
    */

    'default' => env('TRUEPOS_GATEWAY'),

    /*
    |--------------------------------------------------------------------------
    | Transaction Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, all transactions are saved to the database.
    | Run `php artisan vendor:publish --tag=truepos-migrations` first.
    |
    */

    'transaction_logging' => env('TRUEPOS_LOG_TRANSACTIONS', false),

    /*
    |--------------------------------------------------------------------------
    | Request Logging
    |--------------------------------------------------------------------------
    |
    | Log all gateway requests/responses via PSR-3 logger.
    | Can be overridden per-gateway.
    |
    */

    'logging' => env('TRUEPOS_LOG_REQUESTS', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Settings
    |--------------------------------------------------------------------------
    */

    'http_timeout' => env('TRUEPOS_HTTP_TIMEOUT', 30),
    'verify_ssl' => env('TRUEPOS_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | 3D Secure Settings
    |--------------------------------------------------------------------------
    */

    'threed_callback_route' => env('TRUEPOS_3D_CALLBACK_ROUTE', '/truepos/3d/callback'),
    'threed_success_url' => env('TRUEPOS_3D_SUCCESS_URL', '/'),
    'threed_failure_url' => env('TRUEPOS_3D_FAILURE_URL', '/'),
    'threed_mapping_ttl' => 3600,

    'allowed_callback_ips' => [],

    /*
    |--------------------------------------------------------------------------
    | Gateways
    |--------------------------------------------------------------------------
    |
    | Each key is a gateway name you reference in code:
    |   TruePos::gateway('akbank')->purchase(...)
    |
    | The 'driver' key maps to the gateway type (nestpay, garanti, posnet, etc.)
    | Multiple entries can share the same driver with different credentials.
    |
    */

    'gateways' => [

        // ─── NestPay (EST) Banks ─────────────────────────────

        'akbank' => [
            'driver' => 'nestpay',
            'client_id' => env('AKBANK_CLIENT_ID'),
            'username' => env('AKBANK_USERNAME'),
            'password' => env('AKBANK_PASSWORD'),
            'store_key' => env('AKBANK_STORE_KEY'),
            'store_type' => '3d', // 3d | 3d_pay | 3d_pay_hosting
            'payment_url' => env('AKBANK_PAYMENT_URL', 'https://www.sanalakpos.com/fim/api'),
            'threed_gateway_url' => env('AKBANK_3D_URL', 'https://www.sanalakpos.com/fim/est3Dgate'),
            'lang' => 'tr',
            'logging' => false,
            'retry' => false,
        ],

        'isbank' => [
            'driver' => 'nestpay',
            'client_id' => env('ISBANK_CLIENT_ID'),
            'username' => env('ISBANK_USERNAME'),
            'password' => env('ISBANK_PASSWORD'),
            'store_key' => env('ISBANK_STORE_KEY'),
            'store_type' => '3d',
            'payment_url' => env('ISBANK_PAYMENT_URL', 'https://sanalpos.isbank.com.tr/fim/api'),
            'threed_gateway_url' => env('ISBANK_3D_URL', 'https://sanalpos.isbank.com.tr/fim/est3Dgate'),
            'lang' => 'tr',
            'logging' => false,
            'retry' => false,
        ],

        'ziraat' => [
            'driver' => 'nestpay',
            'client_id' => env('ZIRAAT_CLIENT_ID'),
            'username' => env('ZIRAAT_USERNAME'),
            'password' => env('ZIRAAT_PASSWORD'),
            'store_key' => env('ZIRAAT_STORE_KEY'),
            'store_type' => '3d',
            'payment_url' => env('ZIRAAT_PAYMENT_URL', 'https://sanalpos2.ziraatbank.com.tr/fim/api'),
            'threed_gateway_url' => env('ZIRAAT_3D_URL', 'https://sanalpos2.ziraatbank.com.tr/fim/est3Dgate'),
            'lang' => 'tr',
            'logging' => false,
            'retry' => false,
        ],

        'halkbank' => [
            'driver' => 'nestpay',
            'client_id' => env('HALKBANK_CLIENT_ID'),
            'username' => env('HALKBANK_USERNAME'),
            'password' => env('HALKBANK_PASSWORD'),
            'store_key' => env('HALKBANK_STORE_KEY'),
            'store_type' => '3d',
            'payment_url' => env('HALKBANK_PAYMENT_URL', 'https://sanalpos.halkbank.com.tr/fim/api'),
            'threed_gateway_url' => env('HALKBANK_3D_URL', 'https://sanalpos.halkbank.com.tr/fim/est3Dgate'),
            'lang' => 'tr',
            'logging' => false,
            'retry' => false,
        ],

        'teb' => [
            'driver' => 'nestpay',
            'client_id' => env('TEB_CLIENT_ID'),
            'username' => env('TEB_USERNAME'),
            'password' => env('TEB_PASSWORD'),
            'store_key' => env('TEB_STORE_KEY'),
            'store_type' => '3d',
            'payment_url' => env('TEB_PAYMENT_URL', 'https://sanalpos.teb.com.tr/fim/api'),
            'threed_gateway_url' => env('TEB_3D_URL', 'https://sanalpos.teb.com.tr/fim/est3Dgate'),
            'lang' => 'tr',
            'logging' => false,
            'retry' => false,
        ],

        'denizbank' => [
            'driver' => 'nestpay',
            'client_id' => env('DENIZBANK_CLIENT_ID'),
            'username' => env('DENIZBANK_USERNAME'),
            'password' => env('DENIZBANK_PASSWORD'),
            'store_key' => env('DENIZBANK_STORE_KEY'),
            'store_type' => '3d',
            'payment_url' => env('DENIZBANK_PAYMENT_URL', 'https://sanalpos.denizbank.com/fim/api'),
            'threed_gateway_url' => env('DENIZBANK_3D_URL', 'https://sanalpos.denizbank.com/fim/est3Dgate'),
            'lang' => 'tr',
            'logging' => false,
            'retry' => false,
        ],

        // ─── Garanti BBVA ────────────────────────────────────

        'garanti' => [
            'driver' => 'garanti',
            'terminal_id' => env('GARANTI_TERMINAL_ID'),
            'merchant_id' => env('GARANTI_MERCHANT_ID'),
            'provision_user' => env('GARANTI_PROV_USER', 'PROVAUT'),
            'terminal_user' => env('GARANTI_TERMINAL_USER'),
            'provision_password' => env('GARANTI_PROV_PASSWORD'),
            'store_key' => env('GARANTI_STORE_KEY'),
            'test_mode' => env('GARANTI_TEST_MODE', false),
            'payment_url' => env('GARANTI_PAYMENT_URL', 'https://sanalposprov.garanti.com.tr/VPServlet'),
            'threed_gateway_url' => env('GARANTI_3D_URL', 'https://sanalposprov.garanti.com.tr/servlet/gt3dengine'),
            'logging' => false,
            'retry' => false,
        ],

        // ─── Yapı Kredi (PosNet) ─────────────────────────────

        'yapikredi' => [
            'driver' => 'posnet',
            'merchant_id' => env('YAPIKREDI_MERCHANT_ID'),
            'terminal_id' => env('YAPIKREDI_TERMINAL_ID'),
            'posnet_id' => env('YAPIKREDI_POSNET_ID'),
            'enc_key' => env('YAPIKREDI_ENC_KEY'),
            'payment_url' => env('YAPIKREDI_PAYMENT_URL', 'https://posnet.yapikredi.com.tr/PosnetWebService/XML'),
            'threed_gateway_url' => env('YAPIKREDI_3D_URL', 'https://posnet.yapikredi.com.tr/3DSWebService/YKBPaymentService'),
            'logging' => false,
            'retry' => false,
        ],

        // ─── QNB Finansbank (PayFor) ─────────────────────────

        'qnbfinansbank' => [
            'driver' => 'payfor',
            'mbr_id' => env('QNB_MBR_ID', '5'),
            'merchant_id' => env('QNB_MERCHANT_ID'),
            'merchant_pass' => env('QNB_MERCHANT_PASS'),
            'user_code' => env('QNB_USER_CODE'),
            'user_pass' => env('QNB_USER_PASS'),
            'payment_url' => env('QNB_PAYMENT_URL', 'https://vpos.qnbfinansbank.com/Gateway/XMLGate.aspx'),
            'threed_gateway_url' => env('QNB_3D_URL', 'https://vpos.qnbfinansbank.com/Gateway/3DHost.aspx'),
            'lang' => 'TR',
            'logging' => false,
            'retry' => false,
        ],

        // ─── Vakıfbank (VPOS 7/24) ──────────────────────────

        'vakifbank' => [
            'driver' => 'vakifbank',
            'merchant_id' => env('VAKIFBANK_MERCHANT_ID'),
            'merchant_pass' => env('VAKIFBANK_MERCHANT_PASS'),
            'terminal_no' => env('VAKIFBANK_TERMINAL_NO'),
            'payment_url' => env('VAKIFBANK_PAYMENT_URL', 'https://onlineodeme.vakifbank.com.tr:4443/VposService/v3/Vposreq.aspx'),
            'threed_gateway_url' => env('VAKIFBANK_3D_URL', 'https://3dsecure.vakifbank.com.tr/MPIAPI/MPI_Enrollment.aspx'),
            'logging' => false,
            'retry' => false,
        ],

        // ─── Kuveyt Türk ────────────────────────────────────

        'kuveytturk' => [
            'driver' => 'kuveytturk',
            'merchant_id' => env('KUVEYTTURK_MERCHANT_ID'),
            'customer_id' => env('KUVEYTTURK_CUSTOMER_ID'),
            'username' => env('KUVEYTTURK_USERNAME'),
            'password' => env('KUVEYTTURK_PASSWORD'),
            'payment_url' => env('KUVEYTTURK_PAYMENT_URL', 'https://boa.kuveytturk.com.tr/sanalposservice/Home/ThreeDModelPayGate'),
            'threed_gateway_url' => env('KUVEYTTURK_3D_URL', 'https://boa.kuveytturk.com.tr/sanalposservice/Home/ThreeDModelPayGate'),
            'logging' => false,
            'retry' => false,
        ],

        // ─── PayTR ──────────────────────────────────────────

        'paytr' => [
            'driver' => 'paytr',
            'merchant_id' => env('PAYTR_MERCHANT_ID'),
            'merchant_key' => env('PAYTR_MERCHANT_KEY'),
            'merchant_salt' => env('PAYTR_MERCHANT_SALT'),
            'test_mode' => env('PAYTR_TEST_MODE', false),
            'payment_url' => env('PAYTR_PAYMENT_URL', 'https://www.paytr.com/odeme/api/get-token'),
            'threed_gateway_url' => env('PAYTR_3D_URL', 'https://www.paytr.com/odeme/api/get-token'),
            'lang' => 'tr',
            'debug' => false,
            'logging' => false,
            'retry' => false,
        ],

        // ─── Ödeme Kuruluşları ──────────────────────────────

        'iyzico' => [
            'driver' => 'iyzico',
            'api_key' => env('IYZICO_API_KEY'),
            'secret_key' => env('IYZICO_SECRET_KEY'),
            'locale' => 'tr',
            'payment_url' => env('IYZICO_PAYMENT_URL', 'https://api.iyzipay.com/payment/auth'),
            'threed_gateway_url' => env('IYZICO_3D_URL', 'https://api.iyzipay.com/payment/3dsecure/initialize'),
            'logging' => false,
            'retry' => false,
        ],

        'moka' => [
            'driver' => 'moka',
            'dealer_code' => env('MOKA_DEALER_CODE'),
            'username' => env('MOKA_USERNAME'),
            'password' => env('MOKA_PASSWORD'),
            'payment_url' => env('MOKA_PAYMENT_URL', 'https://service.moka.com/PaymentDealer/DoDirectPayment'),
            'threed_gateway_url' => env('MOKA_3D_URL', 'https://service.moka.com/PaymentDealer/DoDirectPaymentThreeD'),
            'logging' => false,
            'retry' => false,
        ],

        'sipay' => [
            'driver' => 'sipay',
            'merchant_key' => env('SIPAY_MERCHANT_KEY'),
            'app_key' => env('SIPAY_APP_KEY'),
            'app_secret' => env('SIPAY_APP_SECRET'),
            'payment_url' => env('SIPAY_PAYMENT_URL', 'https://app.sipay.com.tr/ccpayment/api/paySmart2D'),
            'threed_gateway_url' => env('SIPAY_3D_URL', 'https://app.sipay.com.tr/ccpayment/api/paySmart3D'),
            'logging' => false,
            'retry' => false,
        ],

        'param' => [
            'driver' => 'param',
            'client_code' => env('PARAM_CLIENT_CODE'),
            'client_username' => env('PARAM_CLIENT_USERNAME'),
            'client_password' => env('PARAM_CLIENT_PASSWORD'),
            'guid' => env('PARAM_GUID'),
            'payment_url' => env('PARAM_PAYMENT_URL', 'https://posws.param.com.tr/turkpos.ws/service_turkpos_prod.asmx'),
            'threed_gateway_url' => env('PARAM_3D_URL', 'https://posws.param.com.tr/turkpos.ws/service_turkpos_prod.asmx'),
            'logging' => false,
            'retry' => false,
        ],

        'tosla' => [
            'driver' => 'tosla',
            'client_id' => env('TOSLA_CLIENT_ID'),
            'api_user' => env('TOSLA_API_USER'),
            'api_pass' => env('TOSLA_API_PASS'),
            'payment_model' => env('TOSLA_PAYMENT_MODEL', '3d_pay'), // 3d | 3d_pay
            'base_url' => env('TOSLA_BASE_URL', 'https://entegrasyon.tosla.com'),
            'callback_url' => env('TOSLA_CALLBACK_URL'),
            'logging' => false,
            'retry' => false,
        ],

        'craftgate' => [
            'driver' => 'craftgate',
            'api_key' => env('CRAFTGATE_API_KEY'),
            'secret_key' => env('CRAFTGATE_SECRET_KEY'),
            'base_url' => env('CRAFTGATE_BASE_URL', 'https://api.craftgate.io'),
            'callback_url' => env('CRAFTGATE_CALLBACK_URL'),
            'logging' => false,
            'retry' => false,
        ],

        'esnekpos' => [
            'driver' => 'esnekpos',
            'merchant' => env('ESNEKPOS_MERCHANT'),
            'merchant_key' => env('ESNEKPOS_MERCHANT_KEY'),
            'base_url' => env('ESNEKPOS_BASE_URL', 'https://posservice.esnekpos.com'),
            'callback_url' => env('ESNEKPOS_CALLBACK_URL'),
            'logging' => false,
            'retry' => false,
        ],

        'paratika' => [
            'driver' => 'paratika',
            'merchant' => env('PARATIKA_MERCHANT'),
            'merchant_user' => env('PARATIKA_MERCHANT_USER'),
            'merchant_password' => env('PARATIKA_MERCHANT_PASSWORD'),
            'base_url' => env('PARATIKA_BASE_URL', 'https://vpos.paratika.com.tr'),
            'callback_url' => env('PARATIKA_CALLBACK_URL'),
            'logging' => false,
            'retry' => false,
        ],

        'lidio' => [
            'driver' => 'lidio',
            'api_key' => env('LIDIO_API_KEY'),
            'merchant_code' => env('LIDIO_MERCHANT_CODE'),
            'merchant_key' => env('LIDIO_MERCHANT_KEY'),
            'base_url' => env('LIDIO_BASE_URL', 'https://lidio.com'),
            'notification_url' => env('LIDIO_NOTIFICATION_URL'),
            'pos_account_id' => env('LIDIO_POS_ACCOUNT_ID', 1),
            'callback_url' => env('LIDIO_CALLBACK_URL'),
            'logging' => false,
            'retry' => false,
        ],

    ],

];
