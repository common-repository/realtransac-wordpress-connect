<?php
include_once 'common.class.php';

class Realtransac_API_BannerList extends Realtransac_API_Common {
    
    public function __construct($instance, $widget_id){
                              
        global  $rt_config;
        $this->plugver      = plugin_get_version();
        $this->widget       = $widget_id;
        $this->display      = $instance['displaybannerform'];
        $this->RTsale_style = $instance['testimonials_style'];
        $this->designoption = get_option('plugindesign');
        $this->pageid       = $instance['pageid'];
        $this->wsdl         = $rt_config['wsdl'];
        $this->apikey       = $rt_config['apikey'];
        $this->ip           = $rt_config['ip'];
        $this->client       = $rt_config['client'];
        $this->qtranslate   = false;
       
        $this->permalink = get_permalink($this->pageid);
                      
        if ( function_exists( 'qtrans_generateLanguageSelectCode' ) ){					
            $this->qtranslate   = true;  
            $this->lang         = qtrans_getLanguage();
            $this->permalink    = qtrans_convertURL($this->permalink);
          
        }else{
            $this->lang         = $rt_config['language'];
        }    

        $param = array(
            'apikey'   => $this->apikey, 
            'version'  => $this->plugver,
            'type'     => $instance['type'],
            'show'     => $instance['show'],
            'built'    => $instance['built'],
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

        $this->result   =   json_decode($result);

        /*if(!empty($this->result->Type)){
             update_option('typeoption', serialize($this->result->Type));
        }

        if(!empty($this->result->BuiltDescription)){
             update_option('built_desc', serialize($this->result->BuiltDescription));
        }*/
   }
   
   function displayBannerList(){
       
        global  $rt_config; 
       
        echo '<div class = "rt_bannerlist rt_widget_content" style="min-height:250px;">'; 
        
	if($this->result->error){
		echo  '<div class="rt_salelist">';
                    echo '<div class="InvalidSub" style="color: #F4F4F7; font-size: 15px; font-weight: bold; text-align: center;">' .$this->result->error.'</div>';
		echo '</div>';
        }else{
        if ($this->result->products){
                                     
            if($this->display == '1'){ //SIMPLE PAGINATION

                        ?>
                           <div class="banner_salelist">                           
                            <div class="banner_salelist_inner">
                            <ul id="slider<?php echo $this->widget;?>" class="rt_salelist_content_container">
                                <?php
                                foreach($this->result->products as $key => $product){
                               
                                          $url = $this->append_params_page_url($this->permalink, array('id' => $product->id));
                                  
                                ?>
                                <li>
                                    <div class="saleslist_container">
                                    <div class="banleft_img">
                                        <a href="<?php echo $url;?>"><img src="<?php echo $product->picture_zoom;?>" /></a>
                                    </div>
                                    <div class="rt_topcontent">
                                        <span class="sale_siderow sale_siderow_h2">
                                            <?php
                                            if(is_string ($product->category)){
                                                $category = $product->category;
                                            }
                                            if(is_string ($product->type)){
                                                $type = $product->type;
                                            }
                                            if(is_string ($product->city)){
                                                $city = $product->city;
                                            }
                                            echo $type .'  '.$category.'  '.$city .'<br/><span>'.$product->price.'</span>';
                                            ?>                                            
                                        </span>

                                        <div class="merge_data">
                                            <span class="sale_sid RT_New_Img"><span class="sale_element"> <?php echo $product->bedrooms != NULL ? $product->bedrooms .'&nbsp;'.'<img src="'.plugins_url( 'images/bed.png' , __FILE__ ).'">' : ' --  <img src="'.plugins_url( 'images/bed.png' , __FILE__ ).'" />' ;?></span></span>
                                            <span class="sale_sid RT_New_Img"><span class="sale_element"><?php echo $product->bathrooms != NULL ? $product->bathrooms .'&nbsp;'.'<img src="'.plugins_url( 'images/bath.png' , __FILE__ ).'">' : ' --  <img src="'.plugins_url( 'images/bath.png' , __FILE__ ).'" />' ;?></span></span>
                                            <span class="sale_sid RT_New_Img"><span class="sale_element"><b><?php echo $product->area != NULL ?   $product->area.'  '.$product->area_unit  : ' '; ?></b></span></span>
                                        </div>
                                        <div class="view_sidedetails">
                                            <a class="viewbutton" href="<?php echo $url;?>">
                                                <span class="btnleft"></span>
                                                <span class="btncenter">
                                                    <?php 

                                                        _e('GENERIC_VIEW_DETAILS',"realtransac");

                                                    ?>
                                                </span>
                                                <span class="btnright"></span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="sale_description" ><span class="sale_element_description"><p><?php echo $product->VALUE->desc;?></p></span></div>                                                                       
                                    <?php //if( function_exists('ADDTOANY_SHARE_SAVE_KIT') ) { ADDTOANY_SHARE_SAVE_KIT(array('linkurl' => $url)); } ?>
                                    </div>

                                </li>
                                <?php } ?>
                            </ul>

                            <div class="rt_pagination">
                                <div class="previous" id="previous<?php echo $this->widget;?>"></div>
                                <div class="pagination" id="pager<?php echo $this->widget;?>"></div>
                                <div class="next" id="next<?php echo $this->widget;?>"></div>
                            </div>
                            <div class="fb-like RT_fb_like" data-href="http://www.facebook.com/#!/profile.php?id=100002926247020" data-send="false" data-layout="button_count" data-width="20" data-show-faces="false"></div>
                        </div>
                        <script type="text/javascript">
                            jQuery.noConflict();
                            jQuery(function(){
                                jQuery('#slider<?php echo $this->widget;?>').bxSlider({
                                    pager: true,
                                    nextText: '&raquo;',
                                    nextSelector: jQuery('#next<?php echo $this->widget;?>'),
                                    prevText: '&laquo;',
                                    prevSelector: jQuery('#previous<?php echo $this->widget;?>'),
                                    pagerSelector: jQuery('#pager<?php echo $this->widget;?>')
                                });
                            });
                        </script>    
                        </div>
                       
                        <?php 

                        }else if($this->display == '2'){  //SLIDER PAGINATION
       
                        ?>
                            <div class="banner_salelist">
                            <div class="primg" id="slider-prev<?php echo $this->widget;?>"></div>                            
                            <div class="banner_salelist_inner">
                                <ul class="bxslider<?php echo $this->widget;?>" class="rt_salelist_content_container">
                                <?php
                                foreach($this->result->products as $key => $product){
                                 
                                  
                                        $url = $this->append_params_page_url($this->permalink, array('id' => $product->id));
                                      
                                ?>
                                <li>
                                    <div class="saleslist_container">
                                    <div class="banleft_img">
                                        <a href="<?php echo $url;?>"><img src="<?php echo $product->picture_zoom;?>" /></a>
                                    </div>
                                    <div class="rt_topcontent">
                                        <span class="sale_siderow sale_siderow_h2">
                                            <?php
                                                if(is_string ($product->category)){
                                                    $category = $product->category;
                                                }
                                                if(is_string ($product->type)){
                                                    $type = $product->type;
                                                }
                                                if(is_string ($product->city)){
                                                    $city = $product->city;
                                                }
                                                echo $type .'  '.$category.'  '.$city .'<br/><span>'.$product->price.'</span>';
                                            ?>                                            
                                        </span>
                                        <div class="merge_data">
                                            <span class="sale_sid RT_New_Img"><span class="sale_element"> <?php echo $product->bedrooms != NULL ? $product->bedrooms .'&nbsp;'.'<img src="'.plugins_url( 'images/bed.png' , __FILE__ ).'">' : ' --  <img src="'.plugins_url( 'images/bed.png' , __FILE__ ).'" />' ;?></span></span>
                                            <span class="sale_sid RT_New_Img"><span class="sale_element"><?php echo $product->bathrooms != NULL ? $product->bathrooms .'&nbsp;'.'<img src="'.plugins_url( 'images/bath.png' , __FILE__ ).'">' : ' --  <img src="'.plugins_url( 'images/bath.png' , __FILE__ ).'" />' ;?></span></span>
                                            <span class="sale_sid RT_New_Img"><span class="sale_element"><b><?php echo $product->area != NULL ?   $product->area.'  '.$product->area_unit  : ' '; ?></b></span></span>
                                        </div>
                                        <div class="view_sidedetails">
                                            <a class="viewbutton" href="<?php echo $url;?>">
                                                <span class="btnleft"></span>
                                                <span class="btncenter">
                                                <?php 

                                                        _e('GENERIC_VIEW_DETAILS',"realtransac");

                                                ?>
                                                </span>
                                                <span class="btnright"></span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="sale_description" ><span class="sale_element_description"><p><?php echo $product->VALUE->desc;?></p></span></div>                                    
                                    <?php //if( function_exists('ADDTOANY_SHARE_SAVE_KIT') ) { ADDTOANY_SHARE_SAVE_KIT(array('linkurl' => $url)); } ?>
                                    </div>

                                </li>
                                <?php } ?>
                                </ul>                       
                                <div class="fb-like RT_fb_like" data-href="http://www.facebook.com/#!/profile.php?id=100002926247020" data-send="false" data-layout="button_count" data-width="20" data-show-faces="false"></div>
                            </div>
                            <div class="nximg" id="slider-next<?php echo $this->widget;?>"></div>
                            <script type="text/javascript">
                                jQuery.noConflict();

                                jQuery('.bxslider<?php echo $this->widget;?>').bxSlider({
                                    nextSelector: '#slider-next<?php echo $this->widget;?>',
                                    prevSelector: '#slider-prev<?php echo $this->widget;?>',
                                    nextText: '',
                                    prevText: ''
                                });
                            </script>
                        </div>
                        
                        <?php 

                   }
             }else{
                        echo '<div class="InvalidSub">';
                         _e('GENERIC_PROPERTY_NOT_MATCH',"realtransac");
                        echo '</div>';
            }
  } // invalid subscription if End
 
  echo '</div>';
 } // sub class
} // main class 
?>