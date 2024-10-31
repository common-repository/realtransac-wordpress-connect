<?php
include_once 'common.class.php';

class Realtransac_API_PropertyList extends Realtransac_API_Common {

     public function __construct($instance, $widget_id){
       
        global  $rt_config;
        $this->plugver  = plugin_get_version();
        $this->widget   = $widget_id;        
        $this->pageid   = $instance['pageid'];
        $this->designoption = get_option('plugindesign');
        $this->wsdl     = $rt_config['wsdl'];
        $this->apikey   = $rt_config['apikey'];
        $this->ip       = $rt_config['ip'];
        $this->client   = $rt_config['client'];
        $this->viewID   = $rt_config['viewdetailid'];
        
        $this->qtranslate = false;
        //$this->permalink = apply_filters('the_permalink', get_permalink());
      
             
        $this->permalink  = get_permalink($this->pageid);
        $this->detailLink = get_permalink($this->viewID);
                      
        if ( function_exists( 'qtrans_generateLanguageSelectCode' ) ){					
            $this->qtranslate   = true;  
            $this->lang         = qtrans_getLanguage();
            $this->permalink    = qtrans_convertURL($this->permalink);
            $this->detailLink   = qtrans_convertURL($this->detailLink);
          
        }else{
            $this->lang         = $rt_config['language'];
        }    
        
        $param = array(
            'apikey'   => $this->apikey, 
            'version'  => $this->plugver, 
            'type'     => $instance['type'],
            'built'    => '_empty_',            
            'show'     => $instance['displayoption'],            
            'limit'    => $instance['limit'],            
            'language' => $this->lang
        );
        /**
        * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE SEARCH RESULT BASED ON IT */
        if(isset($rt_config['rt_currency']['globalCurrency'])) {
            $param['rtglobal_currency'] =   $rt_config['rt_currency']['globalCurrency'];
        }
        $parameters = array('data' => $param);
        $result = $this->client->call('getPropertyList', $parameters, '', '', false, true);

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
    
     public function displayList(){
         
     global  $rt_config;  
     echo '<div class = "rt_productlist rt_widget_content">'; 
       
        echo '<div class="rt_linklist_Wrapper">';
            echo '<div class="rt_linklist_frame">';  
            if($this->results->error){
                 echo '<div class="InvalidSub">' .$this->results->error.'</div>';
            }else{
                if ($this->results->products){                    
                    ?>
                      <ul id="proresults<?php echo $this->widget; ?>" class="list">                        
                        <?php         
                        foreach($this->results->products as $product){
                              
                                $url = $this->append_params_page_url($this->detailLink, array('id' => $product->id));
                                                     
                            echo '<li><a href="'.$url.'">'.$product->title.'</a></li>';
                            
                        } ?>                            
                    </ul>
                    <?php
                       $arr = $this->widget;
                       $are = explode('-', $arr);
                       $res = $are[0].$are[1];
                       
                    ?>                   
                    
                    <script type="text/javascript">
                        jQuery.noConflict();
                        jQuery(function(){
                            jQuery("#proresults<?php echo $this->widget; ?>").quickPager({pageSize: 5});
                        });
                    </script>
                 <?php
                    
                    }else{           
                      // echo '<ul>';
                         echo '<div class="InvalidSub">';
                            
                            _e('GENERIC_PROPERTY_NOT_MATCH',"realtransac");
                       
                         echo '</div>';
                        //echo '</ul>';
                    }
            }
     
            echo '</div>';
        echo '</div>';
        echo '<div class="clear"></div>';
     echo '</div>';
  
   }
   
} 
?>

 