<?php
include_once 'common.class.php';

class Realtransac_API_RearchList extends Realtransac_API_Common {

     public function __construct($instance, $widget_id){
       
        global  $rt_config;
        $this->plugver  = plugin_get_version();
        $this->widget   = $widget_id;        
        //$this->pageid   = $instance['pageid'];
        $this->designoption = get_option('plugindesign');
        $this->wsdl     = $rt_config['wsdl'];
        $this->apikey   = $rt_config['apikey'];
        $this->ip       = $rt_config['ip'];
        $this->client   = $rt_config['client'];
        $this->qtranslate = false;
        $this->permalink = apply_filters('the_permalink', get_permalink());
      
             
        $this->permalink = get_permalink($this->pageid);
                      
        if ( function_exists( 'qtrans_generateLanguageSelectCode' ) ){					
            $this->qtranslate   = true;  
            $this->lang         = qtrans_getLanguage();
            $this->permalink    = qtrans_convertURL($this->permalink);
          
        }else{
            $this->lang         = $rt_config['language'];
        }
        
        if(isset($_SESSION['RESEARCH'])){
            $post   = array('searchcountry'=> $_SESSION['RESEARCH']['country'], 'type' => $_SESSION['RESEARCH']['type'], 'category' => $_SESSION['RESEARCH']['category']);
        }
        
        $param = array(
            'apikey'   => $this->apikey, 
            'version'  => $this->plugver, 
            'language' => $this->lang,
            'post'     => $post 
        );
        /**
        * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE FORM BASED ON IT */
        if(isset($rt_config['rt_currency']['globalCurrency'])) {
            $param['rtglobal_currency'] =   $rt_config['rt_currency']['globalCurrency'];
        }
        $parameters = array('data' => $param);
        $result     = $this->client->call('getResearchList', $parameters, '', '', false, true);
        
        // Check for a fault
        if ($this->client->fault) {
                 echo '<h6>'.__('GENERIC_SERVER_CONNECTION_FAULT',"realtransac").'</h6>';
        } else {
                $err = $this->client->getError();
                if ($err) {
                        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
                }
        }
        $this->results   =   json_decode($result);
        
    }
    
     public function displayRearchtList(){
         global  $rt_config;
     ?>
        <script>
        var PER_PAGE  = '<?php echo $this->results->perpage; ?>';
        var MAX_COUNT = '<?php echo $this->results->totalcount; ?>';
        var filterurl = '<?php echo plugins_url('ajaxcall.php' , __FILE__).'/?lang='.$this->lang; ?>';
        var APIKEY    = '<?php echo $this->apikey; ?>';
        var WSDL      = '<?php echo $this->wsdl; ?>';
        var VERSION   = '<?php echo $this->plugver; ?>';
        var LANGUAGE  = '<?php echo $this->lang; ?>';
        var PLUGIN_URL = '<?php echo plugins_url( '' , __FILE__ ); ?>';
        var PERMALINK = '<?php echo $this->permalink; ?>';
        var PAGETYPE  = '<?php echo $this->pageType; ?>';
        //var category   = '';
        //var type       = '';
        //var country    = '';
        //var isLoading  = false;
        
        jQuery.noConflict();
        
        function ajaxReqcountry(){
            isLoading = true;
            jQuery.ajax({ 
                url: filterurl,
                type: "POST",
                dataType:"json",
                data: {action: 'dynamicvalues',  id:'',countryId: jQuery('#country').val(),  typeid:'', category:'', language:LANGUAGE, ajax: 'true', level:6, apikey: APIKEY, wsdl: WSDL, ignoreAll:0, isPortal:0},

                success: function(data){
                    var options = '';

                    //TYPE
                    var options = '';
                    jQuery.each(data.type, function(key, val) {
                        options += '<option value="' + key + '">' + val + '</option>';
                    });
                    jQuery("select#type").html(options);

                    //CATOGERY
                    var options    = '';
                    jQuery.each(data.category, function(key, val) {
                    options += '<option value="' + key + '">' + val + '</option>';
                    });
                    jQuery("select#category").html(options);                           

                    jQuery('#type,#category').removeAttr('disabled');
                    isLoading = false;
                    jQuery('#rt_research_button').removeClass("rt_opacity");
                }
            });
        }
        function ajaxReqType(){
            isLoading = true;
            jQuery.ajax({ 
                url: filterurl,
                type: "POST",
                dataType:"json",
                data: {action: 'dynamicvalues',  id:'',countryId: jQuery('#country').val(),  typeid:jQuery('#type').val(), category:'', language:LANGUAGE, ajax: 'true', level:6, apikey: APIKEY, wsdl: WSDL, ignoreAll:0, isPortal:0},
                success: function(data){
                    //CATOGERY
                    var options    = '';
                    jQuery.each(data.category, function(key, val) {
                    options += '<option value="' + key + '">' + val + '</option>';
                    });
                    jQuery("select#category").html(options);                           

                    jQuery('#category').removeAttr('disabled');
                    isLoading = false;
                    jQuery('#rt_research_button').removeClass("rt_opacity");
                }
            });
        }
        
        jQuery(document).ready(function(){
            jQuery("select#country").change(function(){
                jQuery('#type,#category').attr('disabled', 'disabled');
                jQuery('#rt_research_button').addClass("rt_opacity");
                ajaxReqcountry();
            });
            jQuery("select#type").change(function(){
                jQuery('#category').attr('disabled', 'disabled');
                jQuery('#rt_research_button').addClass("rt_opacity");
                ajaxReqType();
            });
        });
        
        // Create pagination element with options from form
            var options = {
                items_per_page: PER_PAGE,
                num_display_entries : '<?php echo NO_OF_PAGINATION_LINK_SHOW;?>',
                num_edge_entries : '<?php echo NO_OF_PAGE_LEFT_RIGHT;?>',
                prev_text : '<?php _e('PRV_TEXT', 'realtransac'); ?>',
                next_text : '<?php _e('NEXT_TEXT', 'realtransac'); ?>',
                current_page:0,
                callback: pageselectCallback
            };

            jQuery(document).ready(function(){
                jQuery(".rt_result_pagination").pagination(MAX_COUNT, options);
            });

            jQuery(document).ready(function() {
                jQuery('#rt_research_button').click(function(){
                    if(!isLoading){
                        //category    = jQuery("#category").val();
                        //type        = jQuery("#type").val();
                        //country     = jQuery("#country").val();
                        options.current_page = 0;
                        loadResults(0);
                        return false;
                    }
                });
            });

            function loadResults(page)
            {    
                if(jQuery('#rt_research_container').height() < 50){
                   jQuery('#rt_research_container').css("min-height", '50px');
                }
                jQuery('#rt_research_container').append('<div class="loader"><?php _e("GENERIC_LOADING","realtransac"); ?></div>');
                jQuery('#rt_research_container').find('table').animate({opacity: "0.5"});

                var loader  =jQuery('#rt_research_container').find('.loader');
                var pos     =jQuery('#rt_research_container').position();
                var top     = Math.max(0, pos.top + (jQuery('#rt_research_container').height()/ 2)) - (loader.height()/2) + "px";
                var left    = Math.max(0, pos.left + (jQuery('#rt_research_container').width() / 2)) - (loader.width()/2) + "px";

                loader.css("top", top);
                loader.css("left", left);

                jQuery.ajax({
                  type: "POST",
                  url: filterurl, 
                  dataType: "json",
                  data: {
                      action: 'research',
                      pluginurl: PLUGIN_URL,
                      permalink: PERMALINK,
                      apikey: APIKEY,
                      wsdl: WSDL,
                      version: VERSION,
                      language: LANGUAGE,
                      category: jQuery("#category").val(),
                      typeid:jQuery("#type").val(),
                      country_id:jQuery("#country").val(),
                      page: page
                },
                success: function( response ){

                    jQuery('#totalval').val(response.total);
                    jQuery('#rt_research_container').html(response.htmlcontent);
                    jQuery('#rt_research_container').animate({opacity: "1"});
                    options.current_page;
                    options.items_per_page = response.perpage;
                    jQuery(".rt_result_pagination").pagination(response.total, options);
                }
               });
            }
            
            function pageselectCallback(page, jq)
            {
                options.current_page = page;
                loadResults(page);
                return false;
            }
        // Pagination End    

        </script>
     
        <div class = "rt_research rt_widget_content">
            <div class="rt_research_Wrapper" id="agentlist<?php echo $this->widget; ?>">
                <div class="rt_research_frame">
                    <!-- Filter form start -->
                    <div class="rt_research_filter_container">
                        <div class="secondtitle-bg">
                            <div class="second-title"><?php _e("GENERIC_FILTER","realtransac");?>&nbsp;<strong>:</strong></div>
                            <div class="rt_research_filter">
                                <div class="rt_search_row">
                                    <div class="label"><?php _e('GENERIC_COUNTRY',"realtransac"); ?></div>
                                    <div class="drop_down_list">
                                        <select id="country" name="country">
                                        <?php
                                        foreach ($this->results->country as $key=>$val) {
                                            
                                            if(isset($_SESSION['RESEARCH']['country']) && $_SESSION['RESEARCH']['country'] != ''){
                                                $selected = $_SESSION['RESEARCH']['country'];
                                            }else if($this->results->countryID){
                                                $selected = $this->results->countryID;
                                            }
                                            
                                            if($selected == $key){
                                                echo '<option value="'.$key.'" selected>'.$val.'</option>';
                                            }else{
                                                echo '<option value="'.$key.'">'.$val.'</option>';
                                            }
                                            
                                        }
                                        ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="rt_search_row">
                                    <div class="label"><?php _e('GENERIC_TYPE',"realtransac"); ?></div>
                                    <div class="drop_down_list">
                                        <select id="type" name="type">
                                        <?php
                                        foreach ($this->results->type as $key=>$val) {
                                            if($_SESSION['RESEARCH']['type'] == $key){
                                                echo '<option value="'.$key.'" selected>'.$val.'</option>';
                                            }else{
                                                echo '<option value="'.$key.'">'.$val.'</option>';
                                            }
                                        }
                                        ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="rt_search_row">
                                    <div class="label"><?php _e('GENERIC_CATEGORY',"realtransac"); ?></div>
                                    <div class="drop_down_list">
                                        <select id="category" name="category">
                                        <?php
                                        foreach ($this->results->category as $key=>$val) {
                                            if($_SESSION['RESEARCH']['category'] == $key){
                                                echo '<option value="'.$key.'" selected>'.$val.'</option>';
                                            }else{
                                                echo '<option value="'.$key.'">'.$val.'</option>';
                                            }
                                        }
                                        ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="rt_research_btn">
                                    <a class="viewbutton" id="rt_research_button">
                                        <span class="btnleft"></span>
                                        <span class="btncenter"><?php  _e('LISTING_FILTER_SEARCH',"realtransac"); ?></span>
                                        <span class="btnright"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Filter form end -->
                    <!-- Resear result start -->
                    <div id="rt_research_container" class="">
                        
                        <?php
                        if($this->results->error){ ?>
                            <div class="no_result"><?php echo $this->results->error; ?></div>
                        <?php }else{ ?>
                        
                            <table cellpadding="1" border="0" cellspacing="0" width="100%">
                                <tbody>
                                    <tr class=rt_research_header>
                                        <th width = "13%" style="padding-left: 10px;"><?php _e('GENERIC_CATEGORY','realtransac'); ?></th>
                                        <th width = "10%"> <?php _e('GENERIC_TYPE','realtransac'); ?> </th>
                                        <th width = "45%"> <?php _e('GENERIC_DESCRIPTION','realtransac'); ?></th>
                                        <th width = "22%"> <?php _e('GENERIC_BUDGET','realtransac'); ?></th>
                                        <th width = "10%"> </th>
                                    </tr>

                                <?php
                                if ($this->results->research){    
                                    foreach($this->results->research as $key => $agency){
                                        
                                        if($agency->deal_type == '1'){
                                            $type = __('GENERIC_SALE','realtransac');
                                        }else {
                                            $type = __('GENERIC_RENT','realtransac');
                                        }
                                        
                                    ?>
                                        <tr class="rt_research_rows">
                                            <td class="rt_research_val first rt_research_cat_<?php echo $agency->property_type; ?>"></td>
                                            <td class="rt_research_val"><?php echo $type; ?></td>
                                            <td class="rt_research_val"><?php echo $agency->RE_DESCRIPTION; ?></td>
                                            <td class="rt_research_val"><?php echo $agency->RE_BUDGET; ?></td>
                                            <td class="rt_search-button"> 
                                                <a class="rt_research_mail" href="mailto:<?php echo $agency->ContactEmail; ?>">
                                                    <!--<span class="btnleft"></span>
                                                    <span class="btncenter"><?php //_e('GENERIC_EMAIL',"realtransac"); ?></span>
                                                    <span class="btnright"></span> -->
                                                </a>
                                            </td>
                                        </tr>
                                        <tr><td></td></tr>
                                    <?php
                                    }
                                }
                                ?>
                                </tbody>
                            </table>
                        <?php    
                        }
                        ?>
                    </div>
                    <!-- Resear result end -->
                    <div class="rt_result_pagination"></div>
                </div>
            </div>
            <div class="clear"></div>
        </div>
  <?php
   }
} 
?>

 