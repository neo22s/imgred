<?php
class i18n{
    
    /**
     * Loads the gettext dropin
     * @param $locale
     * @param $charset
     */
    public static function load($locale=NULL,$charset=NULL)
    {
        mb_internal_encoding(CHARSET);
        mb_http_output(CHARSET);
        mb_http_input(CHARSET);
        mb_language('uni');
        mb_regex_encoding(CHARSET);
        
        //gettext override
        require_once(VENDOR_PATH.'/gettext/gettext.inc');
    
        if ( !function_exists('_') )
        {//check if gettext exists if not use dropin
            T_setlocale(LC_MESSAGES, $locale);
            bindtextdomain(BIND_DOMAIN,LOCALES_PATH);
            bind_textdomain_codeset(BIND_DOMAIN, $charset);
            textdomain(BIND_DOMAIN);
            log::add('i18n::load dropin locale: '.$locale.' charset: '.$charset);
        }
        else
        {//gettext exists using fallback in case locale doesn't exists
            T_setlocale(LC_MESSAGES, $locale);
            T_bindtextdomain(BIND_DOMAIN,LOCALES_PATH);
            T_bind_textdomain_codeset(BIND_DOMAIN, $charset);
            T_textdomain(BIND_DOMAIN);
            log::add('i18n::load locale: '.$locale.' charset: '.$charset);
        }
        //end language locales
    }
}

/**
 * Echoes a text and tries to translate it
 * @param $text
 */
function _e($text)
{
    if (function_exists('T_'))
    {    
        echo T_($text);
    }
    else
    {
        echo $text;
    }
}
    