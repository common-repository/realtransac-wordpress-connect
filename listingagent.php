<?php
include_once 'common.class.php';

class Realtransac_API_AgentList extends Realtransac_API_Common {

     public function __construct($instance, $widget_id){
       
        global  $rt_config;
        $this->plugver  = plugin_get_version();
        $this->widget   = $widget_id;                
        $this->wsdl     = $rt_config['wsdl'];
        $this->apikey   = $rt_config['apikey'];
        $this->ip       = $rt_config['ip'];
        $this->client   = $rt_config['client'];
        $this->pageid   = $instance['page_id']; // Displayed agent/partner information on different page.
        $this->agentId  = $instance['id']; // agentId/partnerId
        $this->qtranslate   = false;
        $this->designoption = get_option('plugindesign');
        
        $this->permalink = get_permalink($this->pageid);
        //$this->permalink    = apply_filters('the_permalink', get_permalink());        
                      
        if ( function_exists( 'qtrans_generateLanguageSelectCode' ) ){					
            $this->qtranslate   = true;  
            $this->lang         = qtrans_getLanguage();
            $this->permalink    = qtrans_convertURL($this->permalink);
          
        }else{
            $this->lang         = $rt_config['language'];
        }
        if(isset($this->agentId) && $this->agentId == ''){
            $this->agentId = '';
        }
        $param = array(
            'apikey'   => $this->apikey, 
            'version'  => $this->plugver, 
            'language' => $this->lang,
            'agentId'  => $this->agentId
        );
        
        $parameters = array('data' => $param);
        $result     = $this->client->call('getListingBroker', $parameters, '', '', false, true);
        
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
    
     public function displayAgentList(){
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
            var stateid   = '';
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

            jQuery.noConflict();
            
            jQuery(document).ready(function(){
                jQuery(".rt_result_pagination").pagination(MAX_COUNT, options);
            });
            
            jQuery(document).ready(function() {
                jQuery('.rt_filter_buttton').click(function(){
                    stateid   = jQuery("#rt_agent_state").val();
                    MAX_COUNT = jQuery("#totalval").val();
                    jQuery(".rt_result_pagination").pagination(MAX_COUNT, options);
                    options.current_page = 0;
                    loadResults(0);
                    return false;
                });
                
                /*jQuery('#rt_map_state').change(function(){
                    alert("ssss");
                    stateid   = jQuery("#rt_map_state").val();
                    MAX_COUNT = jQuery("#totalval").val();
                    jQuery(".rt_result_pagination").pagination(MAX_COUNT, options);
                    options.current_page = 0;
                    loadResults(0);
                    return false;
                });*/
            });
        
        function loadResults(page)
        {    
            if(jQuery('#rt_agent_container').height() < 50){
               jQuery('#rt_agent_container').css("min-height", '50px');
            }
            jQuery('#rt_agent_container').append('<div class="loader"><?php _e("GENERIC_LOADING","realtransac"); ?></div>');
            jQuery('#rt_agent_container').find('table').animate({opacity: "0.5"});

            var loader  =jQuery('#rt_agent_container').find('.loader');
            var pos     =jQuery('#rt_agent_container').position();
            var top     = Math.max(0, pos.top + (jQuery('#rt_agent_container').height()/ 2)) - (loader.height()/2) + "px";
            var left    = Math.max(0, pos.left + (jQuery('#rt_agent_container').width() / 2)) - (loader.width()/2) + "px";

            loader.css("top", top);
            loader.css("left", left);

            jQuery.ajax({
              type: "POST",
              url: filterurl, 
              dataType: "json",
              data: {
                  action: 'agentresult',
                  pluginurl: PLUGIN_URL,
                  permalink: PERMALINK,
                  apikey: APIKEY,
                  wsdl: WSDL,
                  version: VERSION,
                  language: LANGUAGE,
                  filter: stateid,
                  page: page
            },
            success: function( response ){
                
                jQuery('#totalval').val(response.Title);
                jQuery('#rt_agent_container').html(response.htmlcontent);
                jQuery('#rt_agent_container').animate({opacity: "1"});
                options.current_page
                jQuery(".rt_result_pagination").pagination(response.Title, options);
                jQuery.isFunction(function(){setInterval(function(){new ElementMaxHeight();},500)});
            }
           });
        }
        
        function pageselectCallback(page, jq)
        {
            options.current_page = page;
            loadResults(page);
            return false;
        }
        
        </script>
         <?php
         global  $rt_config;
         $html = '';
         $html .= '<div class = "rt_agentlist rt_widget_content">'; 
            $html .= '<div class="rt_agentlist_Wrapper" id="agentlist'.$this->widget.'">';
                $html .= '<div class="rt_agentlist_frame">';
                
                    $html .= '<div class="rt_agent_header outer-block">
                                <div class="secondtitle-bg">
                                    <div class="second-title">'.__('GENERIC_LISTING_AGENT_TITLE',"realtransac").'</div>
                                    <div class="rt_result_pagination"></div>
                                </div>
                                <div id="rt_countval"><input type="hidden" name="totalval" id="totalval" value=""/></div>
                             </div>';
                    
                    $html .= '<div id="rt_agent_container" class="outer-block">';
                                if($this->results->error){
                                     $html .= '<div class="no_result">' .$this->results->error.'</div>';
                                }else{
                          $html .= '<table cellpadding="3" border="0" cellspacing="0" width="100%">  
                                        <tbody>';
                                        if ($this->results->agentlist){
                                            foreach($this->results->agentlist as $key => $agency){
                                                
                                                //$url = $this->append_params_page_url($this->permalink, array('id' => $agency->idUSERS)); // pending information
                                                if($agency->Website != ''){
                                                    $url        =   $agency->Website;
                                                    $urlLabel   =   'GENERIC_WEBSITE';
                                                }else{
                                                    $url        =   "mailto:".$agency->Email;
                                                    $urlLabel   =   'GENERIC_CONTACT';
                                                }
                                                $html .= '<tr>  
                                                            <td class="rt_agency-image"><img src="'.$agency->url.'" /></td>
                                                            <td class="rt_agency-name">'.$agency->FirstName.' '.$agency->Name.'</td>
                                                            <td class="rt_polygon"></td>
                                                            <td class="rt_agency-link"></td>  
                                                            <td class="rt_agency-link-desc">'. $agency->city.'</td>
                                                            <td class="rt_polygon1"></td>
                                                            <td class="rt_agency-home"></td>
                                                            <td class="rt_agency-address">'.$agency->Phone.'</td>
                                                            <td class="rt_search-button"> 
                                                                <a class="rt_viewbuttonagency viewbutton" href="'.$url.'" target="_blank">
                                                                    <span class="btnleft"></span>
                                                                    <span class="btncenter">'.__($urlLabel,"realtransac").'</span>
                                                                    <span class="btnright"></span>
                                                               </a>
                                                           </td>
                                                        </tr>
                                                        <tr><td colspan="8"></td></tr>';
                                            }
                                        }
                             $html .= '</tbody>';
                          $html .= '</table>';  
                                }
                   $html .= '</div>';             
                $html .= '</div>';
            $html .= '</div>';
        $html .= '<div class="clear"></div>';
        $html .= '</div>';
        
        return $html;
   }
   
   
   // Displayed agent/partner information
   public function agentInformation(){
         
         $html = '';
         $html = '<div class="rt_agent rt_widget_content">
                    <div  class="rt_agent_row rt_agent_down">';
                   if($this->results->error){
                         $html .= '<div class="no_result">' .$this->results->error.'</div>';
                   }else if($this->results->agent){
                       $html .= '<br><br> AGENT INFORMATION <br><br>'; 
                       foreach ($this->results->agent as $key => $val) {
                                $html .= $key .' => '.$val;
                                $html .= '<br>';
                       }
                       $html .= '<br><br> AGENCY OR BROKER INFORMATION (apikey) <br><br>'; 
                       foreach ($this->results->Broker as $key => $val) {
                                $html .= $key .' => '.$val;
                                $html .= '<br>';
                       }
                   }else{
                       
                   }
                       
         $html .= '</div> </div>';
       
       return   $html;        
     }
} 
?>

 