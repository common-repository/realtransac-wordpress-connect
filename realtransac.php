<?php
/*
Plugin Name: Realtransac Wordpress Connect
Description: Connect to your properties listed on the Realtransac CRM Portal.
Author: Realtransac LLC
Version: 2.2
Author URI: http://realtransac.com/
*/
//Global
ob_start();
ini_set('max_execution_time', 8000);
ini_set("soap.wsdl_cache_enabled", "1");
global  $wpdb;

if (!is_admin()){
    if(isset($_POST)){
        extract($_POST);
    }
}
/* Initialize All Js.files*/
add_action('init', 'intial_start');
add_action('init', 'plugin_get_version');
add_action('init', 'intial_scritps',1);
add_action('admin_menu','load_menu');
add_action('admin_init','banner_init');
add_action('plugins_loaded', 'trans_init');
define('NO_OF_PAGINATION_LINK_SHOW', '4'); // Its defined to show how many page numbers to display on middle .
define('NO_OF_PAGE_LEFT_RIGHT', '2'); // Its defined to show how many page numbers to display on  right and right side pagination .

/* Initialize All Js.files*/

require_once('lib/soap/nusoap.php');
require_once('common.class.php');

function loadXML($url) {       
    if (ini_get('allow_url_fopen') == true) {
      return load_fopen($url);
    } else if (function_exists('curl_init')) {
      return load_curl($url);
    } else {
      // Enable 'allow_url_fopen' or install cURL.
      throw new Exception("Can't load data: Enable 'allow_url_fopen' or install cURL");
    }
}
function load_fopen($url) {
    return @simplexml_load_file($url);
}

function load_curl($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    return @simplexml_load_string($result);
}

function trans_init(){  
    load_plugin_textdomain( 'realtransac', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function intial_start(){
        global  $rt_config;
        $mlsArray = array();
        //SESSION START
        if(!session_id()){
                session_start();

        }                
        
        /* IP CONFIGURATION */
        if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
        {
            $ip=$_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
        {
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
            $ip=$_SERVER['REMOTE_ADDR'];
        }
       
       
        if(get_option('apiwsdl')){
             $rt_wsdl        =   get_option('apiwsdl');
        }else{
             $rt_wsdl        =   'http://realtransac.com/api?wsdl';
        }
        $rt_apikey      =   get_option('apikey');
        $rt_client      =   '';
        $reset_flag     =   false;
         
        $rt_viewdetail_id  =   get_option('viewdetail');
        $mlsArray          =   get_option('mls_show');
        if(!is_array($mlsArray)){
            $mlsArray = array();
        }
        $rt_mls_show       =   0;
        
        if(!empty($mlsArray)){
            foreach($mlsArray as $key => $val){
                if($val == 1){
                    $rt_mls_show = $val;
                }else if($val == 2){
                    $rt_mls_show = $val;
                }
            }
        }
        
        if($rt_wsdl != ''){
            try{
                if(!loadXML($rt_wsdl)){
                    throw new Exception('No WSDL found at ' . $rt_wsdl);                    
                }
                $rt_client  =   new nusoap_client($rt_wsdl, 'wsdl'); // true is for WSDL

                $err = $rt_client->getError();
                if ($err) {
                    echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
                }

            }catch(Exception $ex){
                //echo "<h4>".'Message: ' .$ex->getMessage()."</h4>";
                if($ex->faultcode){
                   echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';  
                }
            }
        }
        
        /**
        * GETTING THE CURRENCY VALUES GIVEN IN ADMIN SIDE TEXT BOX,
        * GENERATING CURRENCY ARRAY BASED ON IT */
        $currencyList   =   array();
        $globalcurrency =   get_option('globalcurrency');
        $gCurrencyArray =   explode(',', $globalcurrency);
        if(is_array($gCurrencyArray) && count($gCurrencyArray)>0) {
            $gCurrencyArray =   array_filter($gCurrencyArray);
            foreach($gCurrencyArray as $currency) {
                $currencyList[$currency]    =   array("currencyCode" => trim($currency), "IsDefault" => '0');
            }
        }
        
        /**
        * IF API KEY CHANGED IN ADMIN OR DEFAULT CURRENCY NOT SET OR DEFAULT CURRENCY IS EMPTY, THEN WE NEED TO REFRESH THE DEFAULT CURRENCY USING API SERVICE CALL */
        if(!isset($_SESSION['RT_CURRENCY']['DEFAULT_CURRENCY']) || empty($_SESSION['RT_CURRENCY']['DEFAULT_CURRENCY']) || $_SESSION['RT_CURRENCY']['API_KEY']!=$rt_apikey) {
            $param       =  array('apikey' => $rt_apikey);  
            $parameters  =  array('data' => $param);
            $result      =  $rt_client->call('getAgencyCurrency', $parameters, '', '', false, true);
            $result      =  json_decode($result);
            $reset_flag  =  true;
            unset($_SESSION['RT']);
            $_SESSION['RT_CURRENCY']['API_KEY']          =  $rt_apikey;
            $_SESSION['RT_CURRENCY']['OLD_CURRENCY']     =  '';
            $_SESSION['RT_CURRENCY']['CURRENCY']         =  trim($result->currencyCode);
            $_SESSION['RT_CURRENCY']['DEFAULT_CURRENCY'] =  trim($result->currencyCode);
        }
        
        /**
        * PUSHING DEFAULT CURRENCY INTO GLOBAL CURRENCY LIST */
        $defaultCurrency    =   $_SESSION['RT_CURRENCY']['DEFAULT_CURRENCY'];
        $currencyList[$defaultCurrency]    =   array("currencyCode" => $defaultCurrency, "IsDefault" => '1');
        
        // Set RT cglobal config
        $rt_config  =   array('client' => $rt_client, 'wsdl' => $rt_wsdl, 'apikey' => $rt_apikey, 'ip' => $ip, 'pageType' => 2, 'mapresults' => '', 'language' => 'en', 'plugin_design' => get_plugindesign(), 'viewdetail' => '', 'viewdetailid' => $rt_viewdetail_id ,'mls_show' => $rt_mls_show, 'reset_flag' => $reset_flag);
        
        $rt_config['rt_currency']['currency_list']   =  $currencyList;
        $rt_config['rt_currency']['globalCurrency']  =  $_SESSION['RT_CURRENCY']['CURRENCY'];
        $rt_config['rt_currency']['defaultCurrency'] =  $_SESSION['RT_CURRENCY']['DEFAULT_CURRENCY'];
        if($_SESSION['RT_CURRENCY']['OLD_CURRENCY']!='') {
            $rt_config['rt_currency']['globalOldCurrency']  =   $_SESSION['RT_CURRENCY']['OLD_CURRENCY'];
        }
}

function get_plugindesign() {
        $plugin_design      =   get_option('plugindesign');
        $pclass = 'rt_normal rt_widget';
        if($plugin_design == 2){
            $pclass = 'rt_lighter rt_widget';
        }else if($plugin_design == 3){
            $pclass = 'rt_darker rt_widget';
        }else if($plugin_design == 4){
            $pclass = 'rt_custom rt_widget';
        }
        
        return $pclass;
}
function plugin_get_version() {

    if ( ! function_exists( 'get_plugins' ) )
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
    $plugin_file = basename( ( __FILE__ ) );
    
    return $plugin_folder[$plugin_file]['Version'];

}

function getCurrentUrl() {
  $url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
  $url .= ( $_SERVER["SERVER_PORT"] !== 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
  $url .= $_SERVER["REQUEST_URI"];
  if(isset($_REQUEST['lang'])) {
      $url .= '&lang='.$_REQUEST['lang'];
  }
  return $url;
}

function intial_scritps(){

       $CurrentPage = get_current_file_name ();

       if (!is_admin() && $CurrentPage != 'wp-login.php'){
           
       //Wp jquery removed
        wp_deregister_script('jquery');  
        
        wp_enqueue_style('plugin', plugins_url( 'css/plugin.css' , __FILE__ ));
        wp_enqueue_style('libjquery', plugins_url( 'css/jquery-ui-1.7.2.custom.css' , __FILE__ ));
        wp_enqueue_style('map', plugins_url( 'css/map.css' , __FILE__ ));
        wp_enqueue_style('galleriacss', plugins_url( 'galleria/css/galleria.classic.css' , __FILE__ ));
        
        //SCRIPTS
        wp_enqueue_script( 'gmaps', 'http://maps.googleapis.com/maps/api/js?sensor=false');
      
        wp_enqueue_script( 'libjquery', plugins_url( 'lib/jquery/1.8.3/jquery-1.8.3.js' , __FILE__ ));        
        wp_enqueue_script( 'ui.core', plugins_url( 'lib/jquery/1.8.3/jquery.ui.core.min.js' , __FILE__ ));
        wp_enqueue_script( 'ui.widget', plugins_url( 'lib/jquery/1.8.3/jquery.ui.widget.min.js' , __FILE__ ));
        wp_enqueue_script( 'ui.mouse', plugins_url( 'lib/jquery/1.8.3/jquery.ui.mouse.min.js' , __FILE__ ));
        wp_enqueue_script( 'ui.slider', plugins_url( 'lib/jquery/1.8.3/jquery.ui.slider.min.js' , __FILE__ ));
        wp_enqueue_script( 'jquery.cycle', plugins_url( 'js/jquery.cycle.all.2.72.js' , __FILE__ ));
      
        wp_enqueue_script( 'maxheight', plugins_url( 'js/maxheight.js' , __FILE__ ));
        wp_enqueue_script( 'rtmaps', plugins_url( 'js/map.js' , __FILE__ ));
        wp_enqueue_script( 'bxslider', plugins_url( 'js/jquery.bxSlider.js' , __FILE__ ));                      
        wp_enqueue_script( 'rtscript', plugins_url( 'js/all_jscript.js' , __FILE__ ));
        wp_enqueue_script( 'datepicker', plugins_url( 'js/jquery.ui.datepicker.js' , __FILE__ ));
        wp_enqueue_script( 'validate', plugins_url( 'js/jquery.validate.js' , __FILE__ ));
        wp_enqueue_script( 'galleria', plugins_url( 'galleria/galleria-1.2.8.min.js' , __FILE__ ));
        wp_enqueue_script( 'galleriaclassic', plugins_url( 'galleria/galleria.classic.min.js' , __FILE__ ));
        wp_enqueue_script( 'quickpager', plugins_url( 'js/quickpager.jquery.js' , __FILE__ ));
        wp_enqueue_script( 'slideshow', plugins_url( 'js/slideshow.js' , __FILE__ ));
        wp_enqueue_script( 'pagination', plugins_url( 'js/jquery.pagination.js' , __FILE__ ));
        
        
     }
  
     /* PLUGIN STYLES CALL BASED ON THEME, LIGHT, DARK, CUSTOM */
      $plugindesign  = get_option('plugindesign');
      if (!is_admin()){

           if($plugindesign == 2){
                // LIGHT STYLES
                   wp_enqueue_style('lighter', plugins_url( 'css/lighter.css' , __FILE__ ));

           }else if($plugindesign == 3){
                // DARK STYLES
                   wp_enqueue_style('darker', plugins_url( 'css/darker.css' , __FILE__ ));

           }else if($plugindesign == 4){
                //CUSTOM STYLES
                   wp_enqueue_style('custom', plugins_url( 'css/custom.css' , __FILE__ ));

           }

      }      
   
}

function get_current_file_name () {
    $Exploded_Data = explode('/', $_SERVER['PHP_SELF']); //explode the path to current file to array
    $Exploded_Data = array_reverse ($Exploded_Data); //reverse the arrays order cos php file name is always after last /
    $CurrentPage = $Exploded_Data[0]; // assign the php file name to the currentpage variable
    return $CurrentPage;
}

function load_menu(){
    
   //API FORM OPTIONS 
    add_submenu_page('options-general.php', 'Registration Forms', 'Realtransac settings', 'manage_options', 'registration_settings', 'registration_settings');
   
    register_setting ('registration_options', 'apikey', '');
    register_setting ('registration_options', 'apiwsdl', '');
    register_setting ('registration_options', 'viewdetail', '');
    register_setting ('registration_options', 'globalcurrency', '');
    register_setting ('registration_options', 'mls_show','');
    register_setting ('registration_options', 'plugindesign', '');
    
    //BANNER POST OPTIONS
    add_options_page('Realtranasc Banner Post', 'Realtransac Banner Post', 10, 'rtbannerpost_settings', 'rtbannerpost_settings');
     
    register_setting ('rtbannerpost_options', 'rtstyletype', '');
    register_setting ('rtbannerpost_options', 'rtsorttype', '');
    register_setting ('rtbannerpost_options', 'rtordertype', '');
    register_setting ('rtbannerpost_options', 'rtbannerwidth', '');
    register_setting ('rtbannerpost_options', 'rtbannerheight', '');
    register_setting ('rtbannerpost_options', 'rtbannerimgwidth', '');
    register_setting ('rtbannerpost_options', 'rtbannerimgheight', '');
  
}
function registration_settings(){
    global  $rt_config;
    $mlsArray = array();
    
    $profile_fields = get_option('api_fields');  // Check and removed
    
    ?>
    
    <div class="wrap">
        <h2>API Form Settings</h2>
        <form method="post" action="options.php" onsubmit="return ValidateCompleteForm(this)" id="api-settings" name="apiform">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key: </th>
                    <td><input type="text" name="apikey" value="<?php echo get_option('apikey'); ?>" style="width:340px" class="required"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">WSDL: </th>
                    <?php
                      $apiwsdl = get_option('apiwsdl');
                      $default_apiwsdl = "http://realtransac.com/api?wsdl";
                    ?>
                    <td><input type="text" name="apiwsdl" value="<?php if($apiwsdl){ echo $apiwsdl; } else { echo $default_apiwsdl; }?>" style="width:340px" class="required"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Product Detail Page (ID) : </th>
                    <td><input type="text" name="viewdetail" value="<?php echo get_option('viewdetail'); ?>" style="width:340px" class="required"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Global Currencies : </th>
                    <td><input type="text" id="globalcurrency" name="globalcurrency" value="<?php echo get_option('globalcurrency'); ?>" style="width:340px" class="required"/></td>
                </tr>
                <?php
                    
                    $mlsArray = get_option('mls_show');
                    if(!is_array($mlsArray)){
                        $mlsArray = array();
                    }
                    if(!empty($mlsArray)){
                        foreach($mlsArray as $key => $val){
                            if($val == 1){
                                $intional = "checked";
                            }else if($val == 2){
                                $interNational = "checked";
                            }
                        }
                    }
                ?>
                <tr valign="top">
                    <th scope="row">National Property</th>
                    <td>
                        <input type="checkbox" name="mls_show[]" <?php echo $intional; ?> value="1"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">InterNational Property</th>
                    <td>
                        <input type="checkbox" name="mls_show[]" <?php echo $interNational; ?> value="2"/>
                    </td>
                </tr>
                
                 <?php
                 
                        /* GET TYPE, BUILT DESCRIPTION FOR WIDGET OPTIONS */
                 
                        if ( function_exists( 'qtrans_generateLanguageSelectCode' ) ){
                            $language = qtrans_getLanguage();

                        }else{
                            $language = WPLANG;
                        }
                        
                        $post   =   array();
                        /**
                        * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE FORM BASED ON IT */
                        if(isset($rt_config['rt_currency']['globalCurrency'])) {
                            $post['rtglobal_currency']  =   $rt_config['rt_currency']['globalCurrency'];
                        }
                        $param = array(
                            'apikey'   => get_option('apikey'),
                            'version'  => plugin_get_version(),
                            'language' => $language,
                            'post'     => $post
                        );


                        $checksub   =   0;
                        $subscrurl  =   '';
                                
                        $parameters = array('data' => $param);
                        $result     = $rt_config['client']->call('getDefaultSearchForm', $parameters, '', '', false, true);

                        // Check for a fault
                        if ($rt_config['client']->fault) {
                            echo '<h6>'.__('GENERIC_SERVER_CONNECTION_FAULT',"realtransac").'</h6>';
                        }else{
                            $err = $rt_config['client']->getError();
                            if ($err) {
                                echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
                            }
                        }

                        $formdata     = json_decode($result);

                        //ACCESS BUILT DESCRIPTION VALUES
                        if(isset($formdata->Type)){
                            update_option('typeoption', serialize($formdata->Type));
                        }

                        if(isset($formdata->BuiltDescription)){
                            update_option('built_desc', serialize($formdata->BuiltDescription));
                        }
                                    
                        /* CHECK VALID SUBSCRIPTION */

                        $param = array(
                              'apikey' => $rt_config['apikey'],
                        );

                        $parameters = array('data' => $param);
                        $checksub   = $rt_config['client']->call('getValidSubscription', $parameters, '', '', false, true);
                        $subscrurl  = $rt_config['client']->call('getUrl', '', '', '', false, true);

                        // Check for a fault
                        if ($rt_config['client']->fault) {
                            echo '<h6>'.__('GENERIC_SERVER_CONNECTION_FAULT',"realtransac").'</h6>';
                        } else {
                            $err = $rt_config['client']->getError();
                            if ($err) {
                                echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
                            }
                        }

                        if($checksub == '0'){

                            echo '<tr valign="top">';
                            echo '<th scope="row">AWS subscription: </th>';
                            echo '<td><font color="red">You are using free trial. Please <a href="'.$subscrurl.'/agency/index/subscriptions'.'" target="_blank">Subscribe</a>  to unlock more features!</font></td>';
                            echo '</tr>';
                        }
                                    
                        /* PLUGIN DESIGN OPTIONS */
                                    
                        $check = get_option('plugindesign');
                        if($check){
                            $checked = array('1' => false, '2' => false, '3' => false, '4' => false);
                            $checked[$check] = true;

                            foreach($checked As $key =>$chk){
                                if($chk){
                                $checked[$key] = 'checked';
                                }
                            }
                        }else{
                            update_option('plugindesign', 1);
                            $checked[1] = 'checked';
                        }

                        echo '<tr>';
                        echo '<tr><td><h3>Plugin Design Settings </h3></td></tr>';
                        echo '<tr><td><input type="radio" name="plugindesign" '.$checked[1].' value="1"/> Theme style (Default)</td></tr>';
                        echo '<tr><td><input type="radio" name="plugindesign" '.$checked[2].' value="2"/> Light style</td></tr>';
                        echo '<tr><td><input type="radio" name="plugindesign" '.$checked[3].' value="3"/> Dark style</td></tr>';
                        echo '<tr><td><input type="radio" name="plugindesign" '.$checked[4].' value="4"/> Custom style</td></tr>';
                        echo '</tr>';
                                            
                   ?>
                
             
            </table>  
            <?php settings_fields('registration_options'); ?>
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('GENERIC_SAVE','realtransac'); ?>" />
            </p>
        </form>
      
        <h3>Result Pages</h3>
        <p>
            First you have to create a new page. In title for example put "Your results of research?.
In the content of this page, put the shortcode <code>[rt_search]</code>. Then Save it, this is give you the Wordpress page-ID  of this new created page (ex: ?page_id=x). Copy and paste the ID name of this new page, then go to in the plugin section of WP.
Put the "Realtransac property search? or "Realtransac property advance search? or "Realtransac Featured properties? or "Realtransac Property List? or Realtransac Banner list? widget in your word press wherever you want and paste your Result page ID in the field "Page ID? of this widget, and save it.

        </p>
        <h3>Multilinguage<span style="color:#D8202C"><b>(*)</b></span></h3>
         <p>1. Plugin Requires <a href="http://wordpress.org/extend/plugins/qtranslate" target="_blank">Qtranslate</a> to be Installed inorder for Translation to Work its mandatory. </b></p>
         <p>2. If you installed Qtranslate plugin please add <code>&lt;?php echo qtrans_generateLanguageSelectCode('image'); ?&gt;</code> to your "header.php" at theme page.</p>
         <p>3. To allow Multilanguage auto translation on your widgets Titles, put in titles of Widgets the following tag :  <code>[:en]texten[:fr]textfr[:es]textes</code>
        (replace texten, textfr, textes by the correct words or sentencies in each language).
        </p>
        <h3>Social Media links<span style="color:#D8202C"><b>(*)</b></span></h3>
        <p>1.  Plugin Requires <a href="http://wordpress.org/extend/plugins/add-to-any/" target="_blank">Share Buttons by Lockerz / AddToAny</a> to be Installed for Social media links to Work its mandatory. </b></p>
        <h3>Shortcode For Widgets</h3>
        <p>Allows inclusion of any widget within a page for any theme using the following Shortcodes and Parameters.</p>
        <p>1. Realtransac property search</p>
                <p class="left"> <code>[RT_Widget widget_name="Search" instance="title=RT Property Search&pageid=41&displaysearchform=1"]</code></p>
                <b class="left">Parameters</b>
                <p class="left">title->Widget Title, pageid, displaysearchform ->( Vertical - 1, Horizontal - 2)</p>
        2. Realtransac property advance search
                <p class="left"><code>[RT_Widget widget_name="AdvancedSearch" instance="title=RT Advance Search&pageid=41&displayasearchform=1"]</code></p>
                <b class="left">Parameters</b>
                <p class="left">title->Widget Title, pageid, displayasearchform ->( Vertical - 1, Horizontal - 2)</p>
        3. Realtransac Featured properties
                <p class="left"><code>[RT_Widget widget_name="TopListing" instance="title=RT Property Listing&type=1&show=Best&built=213&pageid=4&limit=5&displaylistingform=2&displaysliderform=2&displayqty=4"]</code></p>
                <b class="left">Parameters</b>
                <p class="left">title->Widget Title, type->Sales = 1 / Rental = 2 / Room for Rent = 3 / Short Time Lease = 4, show-> (Best-Best, Random-Random, Latest-Latest), built->Resale = 213 / New = 212 / Under construction = 214, pageid, limit, displaylistingform -> (Vertical - 1, Horizontal - 2), displaysliderform -> (Simple - 1, Slider - 2), displayqty -> 4</p>
                
        4. Realtransac Property List
                <p class="left"><code>[RT_Widget widget_name="PropertyList" instance="title=RT Property List&type=1&limit=6&pageid=4&displayoption=Best"]</code></p>
                <b class="left">Parameters</b>
                <p class="left">title->Widget Title, type->Sales = 1 / Rental = 2 / Room for Rent = 3 / Short Time Lease = 4, limit, pageid, displayoption ->( Best - Best, Random - Random, Latest - Latest)</p>
        5. Realtransac Google Map
                <p class="left"><code>[RT_Widget widget_name="MapSearch" instance="title=RT Google Map"]</code></p>
                <b class="left">Parameters</b>
                <p class="left">title->Widget Title</p>
        6. Realtransac Mortgage Calculator
                <p class="left"><code>[RT_Widget widget_name="Mortgage" instance="title=RT Mortgage Calculator"]</code></p>
                <b class="left">Parameters</b>
                <p class="left">title->Widget Title</p>
       7. Realtransac Banner list
                <p class="left"><code>[RT_Widget widget_name="BannerList" instance="title=RT Banner List&type=1&show=Best&pageid=4&built=213&limit=5&displaybannerform=1"]</code></p>
                <b class="left">Parameters</b>
                <p class="left">title->Widget Title, type->Sales = 1 / Rental = 2 / Room for Rent = 3 / Short Time Lease = 4, show-> (Best-Best, Random-Random, Latest-Latest), pageid, built->Resale = 213 / New = 212 / Under construction = 214, limit, displaybannerform -> (Vertical - 1, Horizontal - 2)</p>
       8. Realtransac Contact Form
               <p class="left"><code>[RT_Widget widget_name="Contactus" instance="title=Contact Form&displaycontactform=1"]</code></p>
               <b class="left">Parameters</b>
               <p class="left">title->Widget Title, displaycontactform->( Vertical - 1, Horizontal - 2)</p>
       9. How much is my worth Form
               <p class="left"><code>[RT_Widget widget_name="Worth" instance="title=How much is my worth&displayworthform=1"]</code></p>
               <b class="left">Parameters</b>
               <p class="left">title->Widget Title, displayworthform->( Vertical - 1, Horizontal - 2)</p><br>
       
      <h3>Shortcodes</h3> 
              1. Property list (in each page/post) 
                <p class="left"> <code>[RT_Listing pageid="4" title="RT Listing" type="1" built="213" limit="5"]</code></p>
                <b class="left">Parameters</b>
                <p class="left">title->Title, type->Sales = 1 / Rental = 2 / Room for Rent = 3 / Short Time Lease = 4, built->Resale = 213 / New = 212 / Under construction = 214, limit</p><br>
              2. Viewdetail (in each page/post)
                <p class="left"> <code>[rt_viewdetail id=45612]</code></p>
                <b class="left">Parameters</b>
                <p class="left">id->productid</p><br>
     
    </div>
<?php 
}


function rtbannerpost_settings(){
    ?>

    <div class="wrap">
            <h2>Realtransac Banner Post Settings</h2>
                  
                         <form method="post" action="options.php" id="rtbannerpost-settings" name="rtbannerpost-settings">
                            <div class="inside">
                                    <table class="form-table">
                                            <tr>
                                                    <th><label for="styletype">Style</label></th>
                                                    <td>
                                                            <select name="rtstyletype">
                                                                    <option value="1" <?php if(get_option('rtstyletype') == "1") {echo "selected=selected";} ?>>Vertical</option>
                                                                    <option value="2" <?php if(get_option('rtstyletype') == "2") {echo "selected=selected";} ?>>Horizontal</option>

                                                            </select>
                                                    </td>
                                            </tr>
                                            <tr>
                                                    <th><label for="rtsorttype">Sort by Posts/Pages</label></th>
                                                    <td>
                                                            <select name="rtsorttype">
                                                                    <option value="post_date" <?php if(get_option('rtsorttype') == "post_date") {echo "selected=selected";} ?>>Date</option>
                                                                    <option value="title" <?php if(get_option('rtsorttype') == "title") {echo "selected=selected";} ?>>Title</option>
                                                                    <option value="rand" <?php if(get_option('rtsorttype') == "rand") {echo "selected=selected";} ?>>Random</option>
                                                            </select>
                                                    </td>
                                            </tr>
                                            <tr>
                                                    <th><label for="rtordertype">Order by Posts/Pages</label></th>
                                                    <td>
                                                            <select name="rtordertype">
                                                                    <option value="ASC" <?php if(get_option('rtordertype') == "ASC") {echo "selected=selected";} ?>>Ascending</option>
                                                                    <option value="DESC" <?php if(get_option('rtordertype') == "DESC") {echo "selected=selected";} ?>>Descending</option>
                                                            </select>
                                                    </td>
                                            </tr>
                                            <tr>
                                                    <th><label for="rtbannerwidth">Banner Width</label></th>
                                                    <td><input type="text" name="rtbannerwidth" value="<?php $width = get_option('rtbannerwidth'); if(!empty($width)) {echo $width;} else {echo "960";}?>"></td>
                                            </tr>
                                            <tr>
                                                    <th><label for="rtbannerheight">Banner Height</label></th>
                                                    <td><input type="text" name="rtbannerheight" value="<?php $height = get_option('rtbannerheight'); if(!empty($height)) {echo $height;} else {echo "268";}?>"></td>
                                            </tr>

                                            <tr>
                                                    <th><label for="rtbannerimgwidth">Banner Image Width</label></th>
                                                    <td><input type="text" name="rtbannerimgwidth" value="<?php $img_width = get_option('rtbannerimgwidth'); if(!empty($img_width)) {echo $img_width;} else {echo "600";}?>"></td>
                                            </tr>
                                            <tr>
                                                    <th><label for="rtbannerimgheight">Banner Image Height</label></th>
                                                    <td><input type="text" name="rtbannerimgheight" value="<?php $height = get_option('rtbannerimgheight'); if(!empty($height)) {echo $height;} else {echo "270";}?>"></td>
                                            </tr>
                                           

                                    </table>
                                 
                            </div>
                            <input type="hidden" name="action" value="update" />
                            
                            <?php settings_fields('rtbannerpost_options'); ?>
                            <p class="submit">
                                <input type="submit" value="<?php _e('GENERIC_UPDATE_OPTIONS',"realtransac") ?>" class="button-primary" />
                            </p>
                            <b class="left">Banner Setting Options</b>
                            
                            <ul>
                                <li>Style -> ( Vertical format, Horizontal format )</li>
                                <li>Sort by posts/pages ->( Sort by date, title and Random )</li>
                                <li>Order by posts/pages -> ( Ascending format, Descending format )</li>
                                <li>Banner width -> Size of Banner maximum width</li>
                                <li>Banner height -> Size of Banner maximum height</li>
                                <li>Banner Image width -> Size of Banner image maximum width</li>
                                <li>Banner Image height -> Size of Banner image maximum height</li>
                            </ul>
                                
                            <h3>Shortcode for bannerpost (posts/pages)</h3> 
                            <p>Allows inclusion of any page/post for Bannerpost - Slider using the following Shortcodes.</p>
                            <p class="left"> <code>[rt_bannerpost]</code></p>
                            
                            <h3>Shortcode for bannerpost viewdetail (posts/pages)</h3> 
                            <p class="left"> <code>[rt_viewdetail id=2245]</code></p>
                            <b class="left">Parameters</b>
                            <p class="left">id->product id</p>
                          
                    </form>
            
    </div>

<?php
    
}
function banner_init(){
    add_meta_box("rtbannerpost", "Realtranasc Banner Post", "rtbannerpost_add", "post", "normal", "high");
    add_meta_box("rtbannerpost", "Realtranasc Banner Post", "rtbannerpost_add", "page", "normal", "high");
}

function rtbannerpost_add(){
    global $post;
    $custom = get_post_custom($post->ID);
    $addrtbannerpost = $custom["rtbannerpost"][0];
?>
	<div class="inside">
		<table class="form-table">
			<tr>
				<th><label for="addrtbannerpost">Add to Banner?</label></th>
				<td><input type="checkbox" name="addrtbannerpost" value="1" <?php if($addrtbannerpost == 1) { echo "checked='checked'";} ?></td>
			</tr>
		</table>
	</div>
<?php
}


function save_rtbannerpost(){
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){
        return $post_id;
    }
    global $post;
    if($post->post_type == "post" || $post->post_type == "page") {
	update_post_meta($post->ID, "rtbannerpost", $_POST["addrtbannerpost"]);
    }
}
add_action('save_post', 'save_rtbannerpost');

$img_width = get_option('rtbannerimgwidth');

if(empty($img_width)) {
	$img_width = 300;
}

$img_height = get_option('rtbannerimgheight');

if(empty($img_height)) {
	$img_height = 250;
}

if (function_exists('add_image_size')) { 
	add_image_size( 'rtbannerpost', $img_width, $img_height, true ); 
}

//Check for Post Thumbnail Support

add_theme_support( 'post-thumbnails' );

function showrtbannerpost($atts) {
        
    require_once(dirname(__FILE__).'/bannerpost-list.php');        
}   

add_shortcode("rt_bannerpost", "showrtbannerpost");

function bannerthumb($position) {
	$thumb = get_the_post_thumbnail($post_id, $position);
	$thumb = explode("\"", $thumb);
	return $thumb[5];
}
function treatTitle($text, $chars, $points = "...") {
	$length = strlen($text);
	if($length <= $chars) {
		return $text;
	} else {
		return substr($text, 0, $chars)." ".$points;
	}
}



// Realtransac Normal Search 
class WP_Search extends WP_Widget {
    
    // Constructor
    function __construct() {        
        $widget_ops         = array('description' => __('REALTRANSAC_SEARCH_DESCRIPTION', 'realtransac'), 'classname' => get_plugindesign());
        $this->WP_Widget('rtsearch', __('REALTRANSAC_PROPERTY_SEARCH','realtransac'), $widget_ops);
        $this->default_settings = array(
            'title' => 'Normal search',        
        );
        $this->displaysearchform  = array("1" => "Vertical", "2" => "Horizantal");
        //$this->mls_show  = array("0" => "Choose", "1" => "National", "2" => "International");
    }
    
    function widget( $args, $instance ) {       
        extract($args);

        $title = apply_filters('widget_title', $instance['title']);

        echo $before_widget;
        
        echo $before_title . $title . $after_title;
        
        global  $rt_config;                
        if (!is_admin() && $rt_config['client']) {
            require_once(dirname(__FILE__).'/search.php');
            $search = new Realtransac_API_Search($instance, $widget_id);
            $search->displaySearchForm();
            unset($search);
        }else{
            echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
        }

        echo $after_widget;
    }
        
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        
        $new_instance = wp_parse_args((array) $new_instance, array( 'title' => '','pageid' => '','detailpageid' => '','displaysearchform' => ''));
        $instance['title']             = strip_tags($new_instance['title']);
        $instance['pageid']            = strip_tags($new_instance['pageid']);
        $instance['displaysearchform'] = strip_tags($new_instance['displaysearchform']);
         
        if ($new_instance['reset_to_defaults']) {
            $instance = array_merge($instance, $this->default_settings); // $defaults overrides $instance
        }

        return $instance;
    }

    function form( $instance ) {

        $instance            = wp_parse_args((array) $instance, $this->default_settings);
        $instance            = wp_parse_args( (array) $instance, array( 'title' => '','pageid' => '','detailpageid' => '','displaysearchform' => '') );
        $title               = $instance['title'];                
        $pageid              = $instance['pageid'];
        $displaysearchform   = $instance['displaysearchform'];

        update_option('pageid', $pageid );
        //update_option('detailpageid', $detailpageid );
        ?>  
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('GENERIC_TITLE',"realtransac"); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            <label><?php _e( 'GENERIC_PAGE_ID','realtransac' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('pageid'); ?>" name="<?php echo $this->get_field_name('pageid'); ?>" type="text" value="<?php echo esc_attr($pageid); ?>" />
              
            <fieldset>
                <label for="<?php echo $this->get_field_id('displaysearchform'); ?>"><?php _e( 'GENERIC_DISPLAY_FORMAT',"realtransac"); ?></label>
                <select id="<?php echo $this->get_field_id('displaysearchform'); ?>" name="<?php echo $this->get_field_name('displaysearchform'); ?>">
                <?php foreach($this->displaysearchform as $key => $val) { ?>
                    <option value="<?php echo $key; ?>"<?php selected($key, $displaysearchform); ?>><?php echo $val; ?></option>;
                <?php } ?>
                </select>
            </fieldset>
        
            <input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
        </p>
    <?php
    }
}


// Realtransac Advanced Search 
class WP_AdvancedSearch extends WP_Widget {

    // Constructor
    function __construct() {        
        $widget_ops         = array('description' => __('REALTRANSAC_ADVANCED_SEARCH_DESCRIPTION','realtransac'), 'classname' => get_plugindesign());
        
        $this->WP_Widget('rtadvancerealsearch', __('REALTRANSAC_PROPERTY_ADVANCED_SEARCH','realtransac'), $widget_ops);
        $this->default_settings = array(
            'title' => 'Advance search',       
        );
        
        $this->displayasearchform = array("1" => "Vertical", "2" => "Horizantal");
    }

    function widget( $args, $instance ) {

        extract($args);

        $title = apply_filters('widget_title', $instance['title']);

        echo $before_widget;
        
        echo $before_title . $title . $after_title;
        
        global  $rt_config;
        if (!is_admin() && $rt_config['client']) {
            require_once(dirname(__FILE__).'/advanced_search.php');
            $adsearch = new Realtransac_API_AdvancedSearch($instance, $widget_id);
            $adsearch->displaySearchForm();               
            unset($adsearch);
        }else{
            echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
        }

        echo $after_widget;
    }
    
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $new_instance = wp_parse_args((array) $new_instance, array( 'title' => '','pageid' => '','displayasearchform' => ''));
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['pageid'] = strip_tags($new_instance['pageid']);  
        $instance['displayasearchform'] = strip_tags($new_instance['displayasearchform']);
        
        if ($new_instance['reset_to_defaults']) {
            $instance = array_merge($instance, $this->default_settings); // $defaults overrides $instance
        }

        return $instance;
    }


    function form( $instance ) {
        
        $instance = wp_parse_args((array) $instance, $this->default_settings);        
        $instance = wp_parse_args( (array) $instance, array( 'title' => '','pageid' => '','displayasearchform' => '') );
        $title    = $instance['title'];
        $pageid   = $instance['pageid'];  
        $displayasearchform = $instance['displayasearchform'];
        
        update_option('pageid', $pageid );
        ?>                 
    
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('GENERIC_TITLE',"realtransac"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            <label><?php _e( 'GENERIC_PAGE_ID',"realtransac" ); ?> </label>
            <input class="widefat" id="<?php echo $this->get_field_id('pageid'); ?>" name="<?php echo $this->get_field_name('pageid'); ?>" type="text" value="<?php echo esc_attr($pageid); ?>" />           
            <fieldset>
                <label for="<?php echo $this->get_field_id('displayasearchform'); ?>"><?php _e('GENERIC_DISPLAY_FORMAT',"realtransac"); ?></label>        
                <select id="<?php echo $this->get_field_id('displayasearchform'); ?>" name="<?php echo $this->get_field_name('displayasearchform'); ?>">
                <?php foreach($this->displayasearchform as $key => $val) { ?>
                    <option value="<?php echo $key; ?>"<?php selected($key, $displayasearchform); ?>><?php echo $val; ?></option>;
                <?php } ?>
                </select>            
            </fieldset>
        
            <input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
        </p>
<?php
    }
}

function displayResults($args){
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/search.php');
        $search = new Realtransac_API_Search($instance, $widget_id);
        return $search->displaySearchResults();
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
}

add_shortcode('rt_search', 'displayResults');


function displayCurrency(){
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/search.php');
        $search = new Realtransac_API_Search($instance, $widget_id);
        return $search->displayCurrencyDrop();
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
}

add_shortcode('rt_currency', 'displayCurrency');

function displayLanguageFlage($script = true){
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/search.php');
        $search = new Realtransac_API_Search($instance, $widget_id);
        return $search->languageFlag($script);
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
}

add_shortcode('rt_language', 'displayLanguageFlage');

 //////////////////////////////LISTING PLUGIN               
class WP_TopListing extends WP_Widget {

  // Constructor
  function __construct() {
    $widget_ops = array('description' => __('REALTRANSAC_DETAILS_PROPERTY_LIST_DESCRIPTION','realtransac'), 'classname' => get_plugindesign());
    $this->WP_Widget('rttoplisting', __('REALTRANSAC_DETAILS_PROPERTY_LIST','realtransac'), $widget_ops);
    $this->default_settings = array(
        'title' => __('REALTRANSAC_LISTING','realtransac'),        
        'type'  => 'Sales',
        'show'  => 'Latest',
        'built' => 'Resale',
        'limit' => '5',  
        'displayqty' => '1'
    );
    
   $types = array();
   $built_desc = array();
   
   if(get_option('typeoption')){                     
        $types = unserialize(get_option('typeoption'));       
   }
   
   if(get_option('built_desc')){                     
        $built_desc = unserialize(get_option('built_desc'));       
   }
    
    $this->type = $types;
    $this->show = array("Best" => "Best", "Random" => "Random",  "Latest" => "Latest");
    $this->built_description  = $built_desc;
    $this->displaylistingform = array("1" => "Vertical", "2" => "Horizontal");
    $this->displaysliderform = array("1" => "Simple", "2" => "Slider");
    

  }

  function widget($args, $instance){

    // $args is an array of strings that help widgets to conform to
    // the active theme: before_widget, before_title, after_widget,
    // and after_title are the array keys. Default tags: li and h2.
    extract($args);
   

    $title = $instance['title'];
    // These lines generate our output. Widgets can be very complex
    // but as you can see here, they can also be very, very simple.
    echo $before_widget;
     
    echo $before_title . $title . $after_title;
    
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/listing.php');
        $listing = new Realtransac_API_Listing($instance, $widget_id);
        $listing->displayList();
        unset($listing);
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }

    echo $after_widget;
  }

  function update($new_instance, $old_instance) {

    if (!isset($new_instance['submit'])) {
      return false;
    }
    $instance = $old_instance;

    $instance['title']          = strip_tags(stripslashes($new_instance['title']));        
    $instance['type']           = strip_tags(stripslashes($new_instance['type']));
    $instance['show']           = strip_tags(stripslashes($new_instance['show']));
    $instance['built']          = strip_tags(stripslashes($new_instance['built']));
    $instance['limit']          = strip_tags(stripslashes($new_instance['limit']));
    $instance['displaylistingform']    = strip_tags($new_instance['displaylistingform']);
    $instance['displaysliderform']    = strip_tags($new_instance['displaysliderform']);
    $instance['displayqty']    = strip_tags($new_instance['displayqty']);
    $instance['pageid'] = strip_tags($new_instance['pageid']);
            
    if ($new_instance['reset_to_defaults']) {
      $instance = array_merge($instance, $this->default_settings); // $defaults overrides $instance
    }
    
    return $instance;
  }

  function form($instance) {
    $instance = wp_parse_args((array) $instance, $this->default_settings);

    if ($instance['version'] != $this->version){
      $instance['version'] = $this->version;
    }
  
    // Be sure you format your options to be valid HTML attributes.
    $title = htmlspecialchars($instance['title'], ENT_QUOTES);      
    $type = htmlspecialchars($instance['type'], ENT_QUOTES);
    $show = htmlspecialchars($instance['show'], ENT_QUOTES);
    $built = htmlspecialchars($instance['built'], ENT_QUOTES);
    $limit = htmlspecialchars($instance['limit'], ENT_QUOTES);
    $displaylistingform = htmlspecialchars($instance['displaylistingform'], ENT_QUOTES);   
    $displaysliderform = htmlspecialchars($instance['displaysliderform'], ENT_QUOTES); 
    $displayqty = htmlspecialchars($instance['displayqty'], ENT_QUOTES); 
    $pageid = htmlspecialchars($instance['pageid'], ENT_QUOTES);
    
    ?>
            
        <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('GENERIC_TITLE',"realtransac"); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />

        <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('REALTRANSAC_PRODUCT_SHOW',"realtransac"); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" />
        
        <label><?php _e( 'GENERIC_DETAIL_PAGE_ID',"realtransac" ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('pageid'); ?>" name="<?php echo $this->get_field_name('pageid'); ?>" type="text" value="<?php echo esc_attr($pageid); ?>" />  
        
        <label><?php _e( 'REALTRANSAC_DISPLAY_QUANTITY',"realtransac" ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('displayqty'); ?>" name="<?php echo $this->get_field_name('displayqty'); ?>" type="text" value="<?php echo esc_attr($displayqty); ?>" />              

        <fieldset>
            <label for="<?php echo $this->get_field_id('displaylistingform'); ?>"><?php _e('GENERIC_DISPLAY_FORMAT',"realtransac"); ?></label>     
            <select id="<?php echo $this->get_field_id('displaylistingform'); ?>" name="<?php echo $this->get_field_name('displaylistingform'); ?>">
            <?php foreach($this->displaylistingform as $key => $val) { ?>
                <option value="<?php echo $key; ?>"<?php selected($key, $displaylistingform); ?>><?php echo $val; ?></option>;
            <?php } ?>
            </select>        
        </fieldset>
        
        <fieldset>
            <label for="<?php echo $this->get_field_id('displaysliderform'); ?>"><?php _e('REALTRANSAC_SLIDER_FORMAT',"realtransac"); ?></label>     
            <select id="<?php echo $this->get_field_id('displaysliderform'); ?>" name="<?php echo $this->get_field_name('displaysliderform'); ?>">
            <?php foreach($this->displaysliderform as $key => $val) { ?>
                <option value="<?php echo $key; ?>"<?php selected($key, $displaysliderform); ?>><?php echo $val; ?></option>;
            <?php } ?>
            </select>        
        </fieldset>
       
        <fieldset>
        <label for="<?php echo $this->get_field_id('show'); ?>"><?php _e('GENERIC_SHOW',"realtransac"); ?></label>
            <select id="<?php echo $this->get_field_id('show'); ?>" name="<?php echo $this->get_field_name('show'); ?>">
                <?php foreach($this->show as $key => $val) { ?>
                    <option value="<?php echo $key; ?>"<?php selected($key, $show); ?>><?php echo $val; ?></option>;
                <?php } ?>
            </select>        
        </fieldset>

        <fieldset>
            <label for="<?php echo $this->get_field_id('type'); ?>"><?php _e('GENERIC_TYPE',"realtransac"); ?></label>
            <select id="<?php echo $this->get_field_id('type'); ?>" name="<?php echo $this->get_field_name('type'); ?>">
                <?php foreach($this->type as $key => $val) { ?>
                <option value="<?php echo $key; ?>"<?php selected($key, $type); ?>><?php echo $val; ?></option>;
                <?php } ?>
            </select>            
        </fieldset>
         
        <fieldset>
            <label for="<?php echo $this->get_field_id('built'); ?>"><?php _e('REALTRANSAC_BUILT_DESCRIPTION',"realtransac"); ?></label>
            <select id="<?php echo $this->get_field_id('built'); ?>" name="<?php echo $this->get_field_name('built'); ?>">
                <?php foreach($this->built_description as $key => $val) { ?>
                <option value="<?php echo $key; ?>"<?php selected($key, $built); ?>><?php echo $val; ?></option>;
                <?php } ?>
            </select>            
        </fieldset>
       
        <input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
      <?php
  }

}
// end widget code

//MAP WIDGET
class WP_MapSearch extends WP_Widget {

  // Constructor
  function __construct() {
    $widget_ops = array('description' => __('REALTRANSAC_GOOLE_MAP_DESCRIPTION','realtransac'),'classname' => get_plugindesign());
    $this->WP_Widget('rtmapsearchlist', __('REALTRANSAC_GOOGLE_MAP','realtransac'), $widget_ops);
    $this->default_settings = array(
        'title' => 'Google Map',
       
    );
   
  }

    function widget( $args, $instance ) {
        extract($args);

        $title = apply_filters('widget_title', $instance['title']);

        echo $before_widget;
        
        echo $before_title . $title . $after_title;

        global  $rt_config;
        if (!is_admin() && $rt_config['client']) {
            require_once(dirname(__FILE__).'/advanced_search.php');
            $mapsearch = new Realtransac_API_AdvancedSearch($instance, $widget_id);
            $mapsearch->displaySearchMap();
            unset($mapsearch);
        }else{
            echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
        }

        echo $after_widget;
    }
    
    
    function update( $new_instance, $old_instance ) {
        
           
            $instance = $old_instance;

            $new_instance = wp_parse_args((array) $new_instance, array( 'title' => ''));
            $instance['title'] = strip_tags($new_instance['title']);
            
            if ($new_instance['reset_to_defaults']) {
                $instance = array_merge($instance, $this->default_settings); // $defaults overrides $instance
            }
            
            return $instance;
    }


    function form( $instance ) {
        $instance = wp_parse_args((array) $instance, $this->default_settings);
        $instance = wp_parse_args( (array) $instance, array( 'title' => '') );
        $title = $instance['title'];                
        ?>                 
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('GENERIC_TITLE',"realtransac"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
    <?php
    }

}

//PROPERTY LIST
class WP_PropertyList extends WP_Widget {

  // Constructor
  function __construct() {
    $widget_ops = array('description' => __('REALTRANSAC_PROPERTY_LIST_DESCRIPTION','realtransac'),'classname' => get_plugindesign());
    $this->WP_Widget('rtpropertylist', __('REALTRANSAC_PROPERTY_LIST','realtransac'), $widget_ops);
    $this->default_settings = array(
        'title' => 'Property List',        
        'limit' => '5',       
    );
    $this->displayoption = array("Latest" => "Latest", "Best" => "Best", "Random" => "Random");
    
   $types = array();
   if(get_option('typeoption')){                     
        $types = unserialize(get_option('typeoption'));       
   }
   $this->type = $types;
     
  }

  function widget($args, $instance) {

    // $args is an array of strings that help widgets to conform to
    // the active theme: before_widget, before_title, after_widget,
    // and after_title are the array keys. Default tags: li and h2.
    extract($args);

    $title = $instance['title'];

    // These lines generate our output. Widgets can be very complex
    // but as you can see here, they can also be very, very simple.
    echo $before_widget;
      
    echo $before_title . $title . $after_title;  
    
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/productlist.php');
        $property = new Realtransac_API_PropertyList($instance, $widget_id);
        $property->displayList();
        unset($property);
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }

    echo $after_widget;
  }

  function update($new_instance, $old_instance) {

    if (!isset($new_instance['submit'])) {
      return false;
    }
    $instance = $old_instance;

    $instance['title']         = strip_tags(stripslashes($new_instance['title']));
    $instance['limit']         = strip_tags(stripslashes($new_instance['limit']));
    $instance['type']          = strip_tags(stripslashes($new_instance['type']));
    $instance['displayoption'] = strip_tags(stripslashes($new_instance['displayoption']));
    $instance['pageid'] = strip_tags($new_instance['pageid']);
  
    if ($new_instance['reset_to_defaults']) {
      $instance = array_merge($instance, $this->default_settings); // $defaults overrides $instance
    }

    return $instance;
  }

  function form($instance) {
      
    $instance = wp_parse_args((array) $instance, $this->default_settings);

    // Be sure you format your options to be valid HTML attributes.
    $title = htmlspecialchars($instance['title'], ENT_QUOTES);    
    $limit = htmlspecialchars($instance['limit'], ENT_QUOTES);
    $type = htmlspecialchars($instance['type'], ENT_QUOTES);
    $displayoption = htmlspecialchars($instance['displayoption'], ENT_QUOTES);
    $pageid = htmlspecialchars($instance['pageid'], ENT_QUOTES);
    

    ?>
    
    <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('GENERIC_TITLE','realtransac'); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />

    <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('REALTRANSAC_PRODUCT_SHOW','realtransac'); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" />
    
    <label><?php _e( 'GENERIC_DETAIL_PAGE_ID','realtransac' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('pageid'); ?>" name="<?php echo $this->get_field_name('pageid'); ?>" type="text" value="<?php echo esc_attr($pageid); ?>" />            
    
    <fieldset>
            <label for="<?php echo $this->get_field_id('type'); ?>"><?php _e('REALTRANSAC_TYPE','realtransac'); ?></label>
            <select id="<?php echo $this->get_field_id('type'); ?>" name="<?php echo $this->get_field_name('type'); ?>">
                <?php foreach($this->type as $key => $val) { ?>
                <option value="<?php echo $key; ?>"<?php selected($key, $type); ?>><?php echo $val; ?></option>;
                <?php } ?>
            </select>            
    </fieldset>
    <fieldset>
        <label for="<?php echo $this->get_field_id('displayoption'); ?>"><?php _e('GENERIC_OPTION','realtransac'); ?></label>        
        <select id="<?php echo $this->get_field_id('displayoption'); ?>" name="<?php echo $this->get_field_name('displayoption'); ?>">
        <?php foreach($this->displayoption as $key => $val) { ?>
            <option value="<?php echo $key; ?>"<?php selected($key, $displayoption); ?>><?php echo $val; ?></option>
        <?php } ?>
        </select>
    </fieldset>

    <input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
    <?php
  }

}


//HOW MUCH IS MY WORTH FORM


class WP_Worth extends WP_Widget {

  // Constructor
  function __construct() {
    $widget_ops = array('description' => __('REALTRANSAC_WORTH_FORM_DESCRIPTION','realtransac'),'classname' => get_plugindesign());
    $this->displayworthform  = array("1" => "Vertical", "2" => "Horizantal");
    $this->WP_Widget('rtworth', __('REALTRANSAC_WORTH_FORM','realtransac'), $widget_ops);
    $this->default_settings = array('title' => 'How much is my worth');    
    
  }

  function widget($args, $instance){

    // $args is an array of strings that help widgets to conform to
    // the active theme: before_widget, before_title, after_widget,
    // and after_title are the array keys. Default tags: li and h2.
    extract($args);

    $title = $instance['title'];

    // These lines generate our output. Widgets can be very complex
    // but as you can see here, they can also be very, very simple.
    echo $before_widget;
      
    echo $before_title . $title . $after_title;

    
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/worth.php');
        $worthform = new Realtransac_API_Worth($instance, $widget_id);
        $worthform->displayWorthForm();
        unset($worthform);
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }

    echo $after_widget;
  }

  function update($new_instance, $old_instance) {

    if (!isset($new_instance['submit'])) {
      return false;
    }
    $instance = $old_instance;

    $instance['title']            = strip_tags(stripslashes($new_instance['title']));
    $instance['displayworthform'] = strip_tags($new_instance['displayworthform']);
   
  
    if ($new_instance['reset_to_defaults']) {
      $instance = array_merge($instance, $this->default_settings); // $defaults overrides $instance
    }

    return $instance;
  }
  

  function form($instance) {
      
    $instance = wp_parse_args((array) $instance, $this->default_settings);

    // Be sure you format your options to be valid HTML attributes.
    $title = htmlspecialchars($instance['title'], ENT_QUOTES);  
    $displayworthform = htmlspecialchars($instance['displayworthform'], ENT_QUOTES);  
   

    ?>
    
    <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('GENERIC_TITLE','realtransac'); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
    <fieldset>
                <label for="<?php echo $this->get_field_id('displayworthform'); ?>"><?php _e( 'GENERIC_DISPLAY_FORMAT','realtransac' ); ?></label>
                <select id="<?php echo $this->get_field_id('displayworthform'); ?>" name="<?php echo $this->get_field_name('displayworthform'); ?>">
                <?php foreach($this->displayworthform as $key => $val) { ?>
                    <option value="<?php echo $key; ?>"<?php selected($key, $displayworthform); ?>><?php echo $val; ?></option>;
                <?php } ?>
                </select>
    </fieldset>  

    <input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
    <?php
  }

}

//CONTACT FORM

class WP_Contactus extends WP_Widget {

  // Constructor
  function __construct() {
    $widget_ops = array('description' => __('REALTRANSAC_CONTACT_FORM_DESCRIPTION','realtransac'),'classname' => get_plugindesign());
    $this->WP_Widget('rtcontactus', __('REALTRANSAC_CONTACT_FORM','realtransac'), $widget_ops);
    $this->displaycontactform  = array("1" => "Vertical", "2" => "Horizantal");
    $this->default_settings = array(
        'title' => 'Contact form'
    );   
        
  }

  function widget($args, $instance) {

    // $args is an array of strings that help widgets to conform to
    // the active theme: before_widget, before_title, after_widget,
    // and after_title are the array keys. Default tags: li and h2.
    extract($args);

    $title = $instance['title'];

    // These lines generate our output. Widgets can be very complex
    // but as you can see here, they can also be very, very simple.
    echo $before_widget;
      
    echo $before_title . $title . $after_title;
    
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/contactus.php');
        $contact = new Realtransac_API_Contactus($instance, $widget_id);
        $contact->contactform();
        unset($contact);
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }

    echo $after_widget;
  }

  function update($new_instance, $old_instance) {

    if (!isset($new_instance['submit'])) {
      return false;
    }
    $instance = $old_instance;

    $instance['title']              = strip_tags(stripslashes($new_instance['title']));
    $instance['displaycontactform'] = strip_tags($new_instance['displaycontactform']);
   
  
    if ($new_instance['reset_to_defaults']) {
      $instance = array_merge($instance, $this->default_settings); // $defaults overrides $instance
    }

    return $instance;
  }

  function form($instance) {
      
    $instance = wp_parse_args((array) $instance, $this->default_settings);

    // Be sure you format your options to be valid HTML attributes.
    $title = htmlspecialchars($instance['title'], ENT_QUOTES);
    $displaycontactform = htmlspecialchars($instance['displaycontactform'], ENT_QUOTES);
    
  
    ?>
    
    <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('GENERIC_TITLE','realtransac'); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
    <fieldset>
                <label for="<?php echo $this->get_field_id('displaycontactform'); ?>"><?php _e( 'GENERIC_DISPLAY_FORMAT','realtransac' ); ?></label>
                <select id="<?php echo $this->get_field_id('displaycontactform'); ?>" name="<?php echo $this->get_field_name('displaycontactform'); ?>">
                <?php foreach($this->displaycontactform as $key => $val) { ?>
                    <option value="<?php echo $key; ?>"<?php selected($key, $displaycontactform); ?>><?php echo $val; ?></option>;
                <?php } ?>
                </select>
    </fieldset>

    <input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
    <?php
  }

}

class WP_Mortgage extends WP_Widget {

  // Constructor
  function __construct() {
    $widget_ops = array('description' => __('REALTRANSAC_MORTGAGE_DESCRIPTION','realtransac'),'classname' => get_plugindesign());
    $this->WP_Widget('rtmortgage', __('REALTRANSAC_MORTGAGE_CALCULATER','realtransac'), $widget_ops);
    $this->displaymortgageform  = array("1" => "Vertical", "2" => "Horizantal");
    $this->default_settings = array( 'title' => 'Mortgage Calculator'   );
  
  }

  function widget($args, $instance) {

    // $args is an array of strings that help widgets to conform to
    // the active theme: before_widget, before_title, after_widget,
    // and after_title are the array keys. Default tags: li and h2.
    extract($args);

    $title = $instance['title'];
    

    // These lines generate our output. Widgets can be very complex
    // but as you can see here, they can also be very, very simple.
    echo $before_widget;
    
    echo $before_title . $title . $after_title;
    
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/mortgage.php');   
        $mortgage = new Realtransac_API_Mortgage($instance, $widget_id);
        $mortgage->displayForm();
        unset($mortgage);
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
    
    echo $after_widget;
  }

  function update($new_instance, $old_instance) {

    if (!isset($new_instance['submit'])) {
      return false;
    }
    $instance = $old_instance;

    $instance['title']       = strip_tags(stripslashes($new_instance['title']));  
    $instance['displaymortgageform'] = strip_tags($new_instance['displaymortgageform']);
  
    if ($new_instance['reset_to_defaults']) {
      $instance = array_merge($instance, $this->default_settings); // $defaults overrides $instance
    }
    
    return $instance;
  }

  function form($instance) {
      
    $instance = wp_parse_args((array) $instance, $this->default_settings);

    // Be sure you format your options to be valid HTML attributes.
    $title  = htmlspecialchars($instance['title'], ENT_QUOTES);   
    $displaymortgageform  = htmlspecialchars($instance['displaymortgageform'], ENT_QUOTES);   
   
    ?>
    
    <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('GENERIC_TITLE','realtransac'); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
     <fieldset>
                <label for="<?php echo $this->get_field_id('displaymortgageform'); ?>"><?php _e( 'GENERIC_DISPLAY_FORMAT','realtransac' ); ?></label>
                <select id="<?php echo $this->get_field_id('displaymortgageform'); ?>" name="<?php echo $this->get_field_name('displaymortgageform'); ?>">
                <?php foreach($this->displaymortgageform as $key => $val) { ?>
                    <option value="<?php echo $key; ?>"<?php selected($key, $displaymortgageform); ?>><?php echo $val; ?></option>;
                <?php } ?>
                </select>
    </fieldset>  
    <input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
    <?php
  }

}

 //////////////////////////////BANNER LIST WIDGET               
class WP_BannerList extends WP_Widget {

  // Constructor
  function __construct() {
    $widget_ops = array('description' => __('REALTRANSAC_BANNER_DESCRIPTION','realtransac'),'classname' => get_plugindesign());
    $this->WP_Widget('rtbannerlist', __('REALTRANSAC_BANNER_LIST','realtransac'), $widget_ops);
    $this->default_settings = array(
        'title' => 'Banner List',       
        'type' => 'Sales',
        'show' => 'Best',
        'built' => 'Resale',
        'limit' => '5'  
    );
    
   $types = array();
   $built_desc = array();
    
   if(get_option('typeoption')){

         $types = unserialize(get_option('typeoption'));

   }
    if(get_option('built_desc')){
                     
         $built_desc = unserialize(get_option('built_desc'));
       
   }
   
    $this->type = $types;
    $this->show = array("Best" => "Best", "Random" => "Random", "Latest" => "Latest");
    $this->built_description = $built_desc;
    $this->displaybannerform = array("1" => "Simple", "2" => "Slider");
   
   
  }

  function widget($args, $instance) {

    // $args is an array of strings that help widgets to conform to
    // the active theme: before_widget, before_title, after_widget,
    // and after_title are the array keys. Default tags: li and h2.
    extract($args);
   

    $title = $instance['title'];
    // These lines generate our output. Widgets can be very complex
    // but as you can see here, they can also be very, very simple.
    echo $before_widget . $before_title . $title . $after_title;
    
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/banner_list.php');
        $listing = new Realtransac_API_BannerList($instance, $widget_id);
        $listing->displayBannerList();
        unset($listing);
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
    
    echo $after_widget;
  }

  function update($new_instance, $old_instance) {

    if (!isset($new_instance['submit'])) {
      return false;
    }
    $instance = $old_instance;

    $instance['title']              = strip_tags(stripslashes($new_instance['title']));        
    $instance['type']               = strip_tags(stripslashes($new_instance['type']));
    $instance['show']               = strip_tags(stripslashes($new_instance['show']));
    $instance['built']              = strip_tags(stripslashes($new_instance['built']));
    $instance['limit']              = strip_tags(stripslashes($new_instance['limit']));
    $instance['displaybannerform']  = strip_tags($new_instance['displaybannerform']);  
    $instance['pageid'] = strip_tags($new_instance['pageid']);
        
    if ($new_instance['reset_to_defaults']) {
      $instance = array_merge($instance, $this->default_settings); // $defaults overrides $instance
    }

    return $instance;
  }

  function form($instance) {
    $instance = wp_parse_args((array) $instance, $this->default_settings);

    if ($instance['version'] != $this->version){
      $instance['version'] = $this->version;
    }
   
    // Be sure you format your options to be valid HTML attributes.
    $title = htmlspecialchars($instance['title'], ENT_QUOTES);      
    $type = htmlspecialchars($instance['type'], ENT_QUOTES);
    $show = htmlspecialchars($instance['show'], ENT_QUOTES);
    $built = htmlspecialchars($instance['built'], ENT_QUOTES);
    $limit = htmlspecialchars($instance['limit'], ENT_QUOTES);
    $displaybannerform = htmlspecialchars($instance['displaybannerform'], ENT_QUOTES);
    $pageid = htmlspecialchars($instance['pageid'], ENT_QUOTES);
    
    ?>
        
    <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('GENERIC_TITLE','realtransac'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />

    <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('REALTRANSAC_PRODUCT_SHOW','realtransac'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" />
        
    <label><?php _e( 'GENERIC_DETAIL_PAGE_ID','realtransac' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('pageid'); ?>" name="<?php echo $this->get_field_name('pageid'); ?>" type="text" value="<?php echo esc_attr($pageid); ?>" />            

       <fieldset>
                <label for="<?php echo $this->get_field_id('displaybannerform'); ?>"><?php _e('REALTRANSAC_PAGINATION_FORMAT','realtransac'); ?></label>
                    <select id="<?php echo $this->get_field_id('displaybannerform'); ?>" name="<?php echo $this->get_field_name('displaybannerform'); ?>">
                    <?php foreach($this->displaybannerform as $key => $val) { ?>
                        <option value="<?php echo $key; ?>"<?php selected($key, $displaybannerform); ?>><?php echo $val; ?></option>;
                    <?php } ?>
                    </select>        
       </fieldset>
        <fieldset>
    <label for="<?php echo $this->get_field_id('show'); ?>"><?php _e('GENERIC_SHOW','realtransac'); ?></label>
            <select id="<?php echo $this->get_field_id('show'); ?>" name="<?php echo $this->get_field_name('show'); ?>">
                <?php foreach($this->show as $key => $val) { ?>
                    <option value="<?php echo $key; ?>"<?php selected($key, $show); ?>><?php echo $val; ?></option>;
                <?php } ?>
            </select>        
        </fieldset>

        <fieldset>
        <label for="<?php echo $this->get_field_id('type'); ?>"><?php _e('REALTRANSAC_TYPE','realtransac'); ?></label>
            <select id="<?php echo $this->get_field_id('type'); ?>" name="<?php echo $this->get_field_name('type'); ?>">
                <?php foreach($this->type as $key => $val) { ?>
                <option value="<?php echo $key; ?>"<?php selected($key, $type); ?>><?php echo $val; ?></option>;
                <?php } ?>
            </select>            
        </fieldset>
         <fieldset>
        <label for="<?php echo $this->get_field_id('built'); ?>"><?php _e('REALTRANSAC_BUILT_DESCRIPTION','realtransac'); ?></label>
            <select id="<?php echo $this->get_field_id('built'); ?>" name="<?php echo $this->get_field_name('built'); ?>">
                <?php foreach($this->built_description as $key => $val) { ?>
                <option value="<?php echo $key; ?>"<?php selected($key, $built); ?>><?php echo $val; ?></option>;
                <?php } ?>
            </select>            
        </fieldset>
        <input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
      <?php
  }

}
// end widget code

function view_results(){
    
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/viewdetail.php');
        $instance['id'] = addslashes($_GET['id']);
        $viewdetail = new Realtransac_API_ViewDetail($instance, $widget_id);
        $viewdetail->view_list();
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
    
}
if(isset($_GET['id'])){      
    add_shortcode('rt_search', 'view_results');
}

function view_detail($instance){
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        extract(shortcode_atts(array('id' => ''), $instance));
        require_once(dirname(__FILE__).'/viewdetail.php');
        $viewdetail = new Realtransac_API_ViewDetail($instance, $widget_id);
        $viewdetail->view_list();
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
}
add_shortcode('rt_viewdetail', 'view_detail');



//REGISTERING WIDGETS
function widget_realsearch_init(){

        register_widget('WP_Search');
        register_widget('WP_AdvancedSearch');
        register_widget('WP_TopListing');
        register_widget('WP_MapSearch');
        register_widget('WP_PropertyList');
        register_widget('WP_Mortgage');
        register_widget('WP_BannerList');
        register_widget('WP_Worth');
        register_widget('WP_Contactus');
        //register_widget('WP_AgentList');
        //register_widget('WP_ResearchList');
        //register_widget('WP_Partners');
 
}
add_action('widgets_init', 'widget_realsearch_init');

//SHORTCODE FOR WIDGETS
function load_widget($atts) {

    global $wp_widget_factory;
    global $rt_config;


    extract(shortcode_atts(array(
       'widget_name' => FALSE,
       'instance'    => ''
    ), $atts));

    $instance = str_ireplace(array("&amp;", "#038;"), '&' ,$instance);
    $widget_name = wp_specialchars($widget_name);
    $wp_class = 'WP_'.ucwords($widget_name);


    if (is_a($wp_widget_factory->widgets[$wp_class], 'WP_Widget')){
        $widget = $wp_widget_factory->widgets[$wp_class];

        ob_start();
        the_widget($wp_class, $instance, array('widget_id'=>'arbitrary-instance-'.$widget->id,
            'before_widget' => '<div class="'.$rt_config['plugin_design'].' page_widget">',
            'after_widget'  => '</div>',
            'before_title'  => '<h2 class="widgettitle">',
            'after_title'   => '</h2>'
        ));
        $output = ob_get_contents();
        ob_end_clean();
        return $output;

    }else{
        return '<p>'.sprintf(__(""),'<strong>'.$wp_class.'</strong>').'</p>';
    }


}
add_shortcode('RT_Widget','load_widget'); 
    
    

//SHORTCODE FOR PROPERTY LIST
function load_propertylisting($instance){

    extract(shortcode_atts(array(
            'title'  => '',
            'type'   => '',
            'built'  => '',
            'pageid' => ''
    ), $instance));
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/listing.php');
        $propertylist = new Realtransac_API_Listing($instance, $widget_id);
        return $propertylist->displayPropertyList();
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }

}
add_shortcode('RT_Listing', 'load_propertylisting');

//SHORTCODE FOR AGENT LISTING
function agent_listing($instance){
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        extract(shortcode_atts(array('page_id' => ''), $instance));
        require_once(dirname(__FILE__).'/listingagent.php');
        $AgentList = new Realtransac_API_AgentList($instance, $widget_id);
        return $AgentList->displayAgentList();

    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
}
add_shortcode('rt_listingagent', 'agent_listing');

//SHORTCODE FOR PARTNERS LISTING
function partners_listing($instance){
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/listingpartners.php');
        $PartnersList = new Realtransac_API_ListingPartners($instance, $widget_id);
        return $PartnersList->displayPartnerList();
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
}
add_shortcode('rt_partnersagency', 'partners_listing');

//SHORTCODE FOR RESEARCH LISTING
function research($instance){
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/research.php');
        $researcg = new Realtransac_API_RearchList($instance, $widget_id);
        return $researcg->displayRearchtList();
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
}
add_shortcode('rt_research', 'research');

//SHORTCODE FOR BROKER LISTING FILTER
function broker_listing_filter($instance){ 
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/listfilter.php');
        $stateList = new Realtransac_API_AgentListFilter($instance, $widget_id);
        return $stateList->agentListFilterForm();
        //unset($stateList);
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
}
add_shortcode('rt_agentfilter', 'broker_listing_filter');

//SHORTCODE FOR AGENT INFORMATION
function agent_information(){
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/listingagent.php');
        $instance['id'] = addslashes($_GET['id']);
        $agentinfo = new Realtransac_API_AgentList($instance, $widget_id);
        return $agentinfo->agentInformation();
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
    
}
if(isset($_GET['id'])){      
    add_shortcode('rt_agentinfo', 'agent_information');
}

//SHORTCODE FOR MAP FILTER
function map_filter($instance){ 
    global  $rt_config;
    if (!is_admin() && $rt_config['client']) {
        require_once(dirname(__FILE__).'/listfilter.php');
        $stateList = new Realtransac_API_AgentListFilter($instance, $widget_id);
        return $stateList->mapFilter();
        //unset($stateList);
    }else{
        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
    }
}
add_shortcode('rt_mapfilter', 'map_filter');


add_action('rt_ajax', 'rt_ajax_callback');

function rt_ajax_callback() {
	global $wpdb, $rt_config; // this is how you get access to the database

        ob_clean();
        
        $data = '';
        if($action != ''){

            try {

                $client        =   new nusoap_client($wsdl, 'wsdl'); // true is for WSDL
                $common_class  =   new Realtransac_API_Common();

                switch ($action) {

                   case 'formatarea':

                        $parameters = array('data' => array( 'area'    => $area, 'isrange' =>$isrange,  'unit' =>$unit  ));
                        $data = $client->call('getFormatArea', $parameters, '', '', true, false);

                        // Check for a fault
                        if($client->fault){
                            echo '<h6>'.__('GENERIC_SERVER_CONNECTION_FAULT',"realtransac").'</h6>';
                        }else{
                            $err = $client->getError();
                            if ($err){
                                    echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
                            }
                        }

                        break;

                   case 'dynamicvalues':
                        $param      =   array(
                                              'apikey' => $apikey,
                                              'wsdl' => $wsdl,
                                              'id' => $id,
                                              'typeid' => $typeid,
                                              'language' => $language,
                                              'category' => $category,
                                              'ajax' => $ajax,
                                              'level' => $level,
                                              'ignoreAll' => $ignoreAll,
                                              'isPortal' => $isPortal
                                             );
                        /**
                        * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE FORM BASED ON IT */
                        if(isset($rt_config['rt_currency']['globalCurrency'])) {
                            $param['rtglobal_currency'] =   $rt_config['rt_currency']['globalCurrency'];
                        }
                        $parameters =   array('data' => $param);
                        $data = $client->call('getDynamicvalues', $parameters, '', '', true, false);

                        // Check for a fault
                        if($client->fault){
                            echo '<h6>'.__('GENERIC_SERVER_CONNECTION_FAULT',"realtransac").'</h6>';
                        }else{
                            $err = $client->getError();
                            if ($err){
                                    echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
                            }
                        }

                        break;
                  case 'searchresults':

                        //SOME TRANSLATION TEXT
                        $sortdata = array(
                            __('SORT_PRICE_UP','realtransac') =>'PRICE#DESC',
                            __('SORT_PRICE_DOWN','realtransac') =>'PRICE#ASC',
                            __('SORT_DATE_UP','realtransac')  =>'CREATION_DATE#DESC',
                            __('SORT_DATE_DOWN','realtransac')  =>'CREATION_DATE#ASC'
                        );

                       $param = array(

                            'apikey'         => $apikey,
                            'version'        => $version,
                            'DISTANCE'       => $distance,
                            'TYPE'           => $type,
                            'CATEGORY'       => $category,
                            'PICTURE'        => $picture,
                            'PRICE'          => json_decode($price),
                            'BEDROOM'        => json_decode($bed),
                            'BATHROOM'       => json_decode($bath),
                            'AREA'           => json_decode($area),
                            'localbox'       => $localbox,
                            'localisation'   => $localisation,
                            'sorted'         => $sorted,
                            'language'       => $language,
                            'ip'             => $ip,
                            'page'           => $page
                        );
                        /**
                        * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE SEARCH RESULT BASED ON IT */
                        if(isset($rt_config['rt_currency']['globalCurrency'])) {
                            $param['rtglobal_currency'] =   $rt_config['rt_currency']['globalCurrency'];
                        }
                    $parameters = array('data' => $param, 'ismap' => false);
                    $result = $client->call('getSearchResults', $parameters, '', '', false, true);

                    // Check for a fault
                    if ($client->fault) {
                             echo "<h6>".'Message: ' ."Server Connection Fault"."</h6>";
                    } else {
                            // Check for errors
                            $err = $client->getError();
                            if ($err) {
                                    // Display the error
                                    echo '<h2>Error</h2><pre>' . $err . '</pre>';
                                    echo "<h6>".'Message: ' ."Server Connection Error"."</h6>";
                            }
                    }

                    $searchdata     =   json_decode($result);
                    $latLngArray    =   array();
                     $data = '<script type="text/javascript">';
                     $data.= 'jQuery.noConflict();';
                     $data.= 'jQuery(document).ready(function() {';
                     $data.= 'try{';
                     $data.= 'var ele  = "map_realestate"'.$widgetid.';';
                     $data.= 'loadData('.$searchdata->mapresult.', false,'.$pagetype.', true, ele);';
                     $data.= '}catch(e){alert(e)}';
                     $data.= '});';
                     $data.= '</script>';

                    if ($searchdata->searchresult){

                             $data.= '<h2>'.__('GENERIC_TOTAL_RESULT','realtransac').count($searchdata->searchresult).'</h2>';
                             $data.= '<span>';
                             $data.= '<select name="asortedby" id="asortedby" onchange="sortResults();">';


                             foreach($sortdata as $key => $val){
                                    if(isset($sorted)&&($sorted == $val)){

                                          $data.= '<option selected value="'.$val.'">';
                                          $data.= $key;
                                          $data.= '</option>';
                                    }else {

                                $data.= '<option value="'.$val.'">';
                                $data.= $key;
                                $data.= '</option>';

                                 }
                              }
                             $data.= '</select>';
                             $data.= '</span>';

                            $markCount= 0;
                            $data.= '<table id="results" celpadding="0" cellspacing="0" width="100%">';
                            $data.= '<th></th>';
                            foreach ($searchdata->searchresult as $product){
                                $url = $common_class->append_params_page_url($permalink, array('id' => $product->idPRODUCT));
                                $data.= '<tr>';
                                $data.= '<th>';
                                $data.= '<fieldset>';
                                $data.= '<div class="rt_listing_rows">';
                                    $data.= '<div class="rt_listing_wrapper">';
                                        $data.= '<div class="rt_listing_marker">';
                                            if($product->lat != '' && $product->lng != ''){
                                                $isExists   =   false;
                                                if(is_array($latLngArray) && count($latLngArray)>0) {
                                                    foreach($latLngArray as $latLngValue) {
                                                        if($product->lat==$latLngValue['Latitude'] && $product->lng==$latLngValue['Longitude']) {
                                                            $imageName  =   $latLngValue['ImageName'];
                                                            $isExists   =   true;
                                                        }
                                                    }
                                                }
                                                if(!$isExists) {
                                                    $latLngArray[$markCount]['Latitude']    =   trim($product->lat);
                                                    $latLngArray[$markCount]['Longitude']   =   trim($product->lng);
                                                    $latLngArray[$markCount]['ImageName']   =   chr(65 + $markCount);
                                                    $imageName  =   chr(65 + $markCount);
                                                    $markCount++;
                                                }
                                            }
                                            $data.= '<img src="'.$pluginurl.'/images/markerimage/'. $imageName.'_Hover.png'.'"/>';
                                        $data.= '</div>';
                                        $data.= '<div class="rt_listing_details">';
                                            $data.= '<div class="rt_listing_title">';
                                                $data.= '<span>'.$product->TITLE.'</span>';
                                            $data.= '</div>';
                                        $data.= '</div>';
                                        $data.= '<div style="clear:both;">';
                                            //PRODUCT DETAIL LEFT CONTENT
                                            $data.= '<div class="rt_listing_content_left">';
                                                $data.= ' <div class="rt_listing_iconset">';
                                                    if($product->AREA != NULL){
                                                        // $data.= $product->AREA.'<img src="'.plugins_url( 'images/car.png' , __FILE__ ).'" />';
                                                    }
                                                    if($product->BEDROOM != NULL){
                                                        $data.= '<span class="count">'.$product->BEDROOM.'</span>';
                                                    }
                                                    if($product->BATHROOM != NULL){
                                                        $data.= '<span class="count">' .$product->BATHROOM.'</span><br/>';
                                                    }
                                                $data.= '</div>';
                                                $data.= ' <div class="rt_listing_price">';
                                                    $data.= $product->PRICE->PRICE_UNIT.$product->PRICE.'<br/>';
                                                $data.= '</div>' ;
                                                $data.= '<div class="rt_listing_description">';
                                                    $data.= $product->DESC.'<br/>';
                                                $data.= '</div>';
                                                $data.= '<div class="rt_listing_viewdetails">';
                                                    $data.= '<a class="viewbutton" href="'.$url.'"><span class="btnleft"></span><span class="btncenter">'.__("GENERIC_VIEW_DETAILS","realtransac").'</span><span class="btnright"></span></a>';
                                                    $data.= '<div class="clear"></div>';
                                                $data.= '</div>';
                                            $data.= '</div>';

                                            //PRODUCT IMAGES
                                            $data.= '<div class="rt_listing_content_right">';
                                                $data.= ' <div class="rt_listing_imgwrapper">';
                                                    $data.= ' <a href="'.$url.'"><img src="'.$product->PICTURE.'" /></a>';
                                                $data.= '</div>';
                                            $data.= '</div>';
                                        $data.= '</div>';
                                        $data.= '<div style="clear:both;"></div>';
                                    $data.= '</div>';
                                $data.= '</div>';
                                $data.= '</fieldset>';
                                $data.= '</th>';
                                $data.= '</tr>';
                            }
                        $data.= '</table>';

                 }else{

                      $data.= '<div class="property_notfound">'.$searchdata->error.'</div>';

                }

                break;

            }


            }catch(Exception $ex){
                //echo "<h4>".'Message: ' .$ex->getMessage()."</h4>";
                if($ex->faultcode){
                   echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
               }
            }
        }

        echo $data;
	die(); // this is required to return a proper result
}

?>