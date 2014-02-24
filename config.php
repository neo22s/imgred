<?php
//Initial defines
define('BASE_PATH',         __DIR__.'/');
define('CORE_PATH',         BASE_PATH.'core/');
define('VENDOR_PATH',       CORE_PATH.'vendor/');
define('APP_PATH',          BASE_PATH.'app/');
define('PLUGINS_PATH',      BASE_PATH.'plugins/');
define('LOCALES_PATH',      APP_PATH.'locales/');
define('CLASSES_PATH',      APP_PATH.'classes/');
define('MODELS_PATH',       APP_PATH.'models/');
define('CONTROLLERS_PATH',  APP_PATH.'controllers/');
define('VIEWS_PATH',        APP_PATH.'views/default/');//change 'default' for your own theme

define('VERSION','0.1');



//Image configuration
define('IMG_TYPES','jpeg,jpg,gif,png');//file types that are allowed
define('IMG_UPLOAD_DIR',APP_PATH.'/images/');
define('IMG_MAX_SIZE',500000);//max image size we allow
define('IMG_MAX_WIDTH',480);//max image width for watermark
define('IMG_MAX_HEIGHT',480);//max image height for watermark
define('IMG_EXPIRE',60*60*24*30);//image expires in 1 month
//define('IMG_WATERMARK',IMG_UPLOAD_DIR.'watermark.png');//watermark
define('IMG_WATERMARK',FALSE);//watermark
define('IMG_ERROR',IMG_UPLOAD_DIR.'error.png');//watermark


//environment settings
define('ENV','PRO');

if (ENV=='DEV')
{
    define('DEBUG',TRUE);
    define('EMAIL_ERROR', 'neo22s@gmail.com');//email in case fatal error report

    //DB config
    define('DB_HOST','localhost');
    define('DB_USER','root');
    define('DB_PASS','');
    define('DB_NAME','oc');
    define('DB_CHARSET','utf8');
    define('TABLE_PREFIX','oc_');
    
    //cache settings
    define('CACHE_ACTIVE',TRUE);
    define('CACHE_TYPE','apc');
    define('CACHE_DATA_FILE',APP_PATH.'cache/');
    define('CACHE_EXPIRE','86400');

    //i18n config
    define('CHARSET','UTF-8');//html charset
    define('LOCALE','en_EN');
    define('BIND_DOMAIN','messages');
    date_default_timezone_set('Europe/Madrid');
}


if (ENV=='PRO')
{
    define('DEBUG',FALSE);
    define('EMAIL_ERROR', 'neo22s@gmail.com');//email in case fatal error report

    //DB config

define('DB_HOST','localhost');
define('DB_USER','');
define('DB_PASS','');
define('DB_NAME','');
define('DB_CHARSET','utf8');
define('TABLE_PREFIX','oc_');


    
    //cache settings
    define('CACHE_ACTIVE',TRUE);
    define('CACHE_TYPE','filecache');
    define('CACHE_DATA_FILE',APP_PATH.'cache/');
    define('CACHE_EXPIRE','86400');

    //i18n config
    define('CHARSET','UTF-8');//html charset
    define('LOCALE','en_EN');
    define('BIND_DOMAIN','messages');
    date_default_timezone_set('Europe/Madrid');
}
