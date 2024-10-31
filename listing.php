<?php
include_once 'common.class.php';

class Realtransac_API_Listing extends Realtransac_API_Common {
    
    public function __construct($instance, $widget_id){
       
        global  $rt_config;
        $this->plugver  = plugin_get_version();
        $this->widget   = $widget_id;
        $this->display  = $instance['displaylistingform']; 
        $this->displaysliderform  = $instance['displaysliderform']; 
        $this->displayqty  = $instance['displayqty']; 
        $this->designoption = get_option('plugindesign');
        $this->pageid   = $instance['pageid'];
        $this->type     = $instance['type'];
        $this->show     = $instance['show'];
        $this->built    = $instance['built'];
        $this->limit    = $instance['limit'];
        $this->wsdl     = $rt_config['wsdl'];
        $this->apikey   = $rt_config['apikey'];
        $this->ip       = $rt_config['ip'];
        $this->client   = $rt_config['client'];
        $this->viewID   = $rt_config['viewdetailid'];
        $this->qtranslate = false;
        
                
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
            'show'     => $instance['show'],
            'built'    => $instance['built'],
            'limit'    => $instance['limit'],
            'displayqty' => $instance['displayqty'],
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

        $this->result = json_decode($result);
        
        /*if(!empty($this->result->Type)){
             update_option('typeoption', serialize($this->result->Type));
        }
        if(!empty($this->result->BuiltDescription)){
             update_option('built_desc', serialize($this->result->BuiltDescription));
        }*/
   }
    
   function displayList(){

        global  $rt_config;
        
        echo '<div class = "rt_salelist rt_widget_content">';
        
	if($this->result->error){		
            echo '<div class="InvalidSub">' .$this->result->error.'</div>';		
	}else{
          if ($this->result->products)
          {        
            if($this->display == '1'){ 
                ?>                                   
                    <div class="rt_vsale">
                       <div class="rt_salelist_inner"> 
                           
                       <?php if($this->displaysliderform == 2){?>            
                           <div class="primg" id="previous<?php echo $this->widget;?>"></div>
                       <?php }?>
                       
                        <ul id="slider<?php echo $this->widget;?>" class="salelist_container">
                          <?php 
                            $totalProducts  =   count($this->result->products);
                            foreach($this->result->products as $key => $product){ 
                                    $url = $this->append_params_page_url($this->detailLink, array('id' => $product->id));
                          ?> 
                            <li>
                            	<div class="saleslist_container">
                                    <div class="saleslist_container_inner">
                                        <div class="left_img">
                                            <a href="<?php echo $url;?>">
                                                <img src="<?php echo $product->picture; ?>"/></a> 
                                        </div>
                                        <span class="sale_siderow"><span class="sale_element_h2"> 
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
                                                echo $type .'  '.$category.'  '.$city
                                                 ?>
                                         </span></span>
                                        
                                        <div class="merge_data">
                                        <span class="sale_siderow"><span class="sale_element bedroom"><?php echo $product->bedrooms != NULL ? $product->bedrooms .'&nbsp;' : ' -- ';?></span></span>
                                        <span class="sale_siderow"><span class="sale_element bathroom"><?php echo $product->bathrooms != NULL ? $product->bathrooms .'&nbsp;' : ' -- ';?></span></span>
                                        </div>
                                        <div class="merge_data">
                                        <span class="sale_siderow"><span class="sale_element sale_element_area"><?php echo $product->area != NULL ?   $product->area.'  '.$product->area_unit  : ' '; ?></span></span>
                                        </div>
                                        <div class="merge_data">
                                        <span class="sale_siderow"><span class="sale_element sale_element_price"><b> <?php echo $product->price;?></b></span></span>
                                        </div>
                                        <?php //if( function_exists('ADDTOANY_SHARE_SAVE_KIT') ) { ADDTOANY_SHARE_SAVE_KIT(array('linkurl' => $url)); } ?>
                                        <div class="sale_description" ><span class="sale_element_description"><p><?php echo $product->VALUE->desc;?></p></span></div>
                                    </div>
                                        <div class="view_sidedetails">
                                            <a class="viewbutton" href="<?php echo $url;?>">
                                                <span class="btnleft"></span>
                                                <span class="btncenter">
                                                    <?php  _e('GENERIC_VIEW_DETAILS',"realtransac");?>
                                                </span>
                                                <span class="btnright"></span>
                                            </a>
                                        </div>
                                </div>
                         </li>
                           <?php } ?> 
                       </ul>
                       <?php if($this->displaysliderform == 2){?>            
                       <div class="nximg" id="next<?php echo $this->widget;?>"></div>
                       <?php }?> 
                       </div>
                </div>              
            <?php } else { ?>            
                <div class="rt_hsale">
                    <div class="rt_salelist_inner">
                        
                       <?php if($this->displaysliderform == 2){?>            
                           <div class="primg" id="previous<?php echo $this->widget;?>"></div>
                       <?php }?>
                           
                        <ul id="slider<?php echo $this->widget;?>" class="salelist_container">
                          <?php 
                          $totalProducts  =   count($this->result->products);
                          foreach($this->result->products as $key => $product){ 
                                $url = $this->append_params_page_url($this->detailLink, array('id' => $product->id));?> 
                            <li>
                            	<div class="saleslist_container">
                                    <div class="left_img">
                                        <a href="<?php echo $url;?>">
                                            <img src="<?php echo $product->picture;?>" /></a> 
                                    </div>
                                    <div class="rt_topcontent">
                                        <span class="sale_siderow"><span class="sale_element_h2"> 

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
                                                echo $type .'  '.$category.'  '.$city
                                                 ?>
                                         </span></span>
                                        <div class="merge_data">
                                        <span class="sale_siderow"><span class="sale_element"> <?php echo $product->bedrooms != NULL ? $product->bedrooms .'&nbsp;'.'<img src="'.plugins_url( 'images/bed.png' , __FILE__ ).'">' : ' --  <img src="'.plugins_url( 'images/bed.png' , __FILE__ ).'" />' ;?></span></span>
                                        <span class="sale_siderow"><span class="sale_element"><?php echo $product->bathrooms != NULL ? $product->bathrooms .'&nbsp;'.'<img src="'.plugins_url( 'images/bath.png' , __FILE__ ).'">' : ' --  <img src="'.plugins_url( 'images/bath.png' , __FILE__ ).'" />' ;?></span></span>
                                        </div>
                                        <div class="merge_data">
                                        <span class="sale_siderow"><span class="sale_element"><?php echo $product->area != NULL ?   $product->area.'  '.$product->area_unit  : ' '; ?></span></span>
                                        </div>
                                        <div class="merge_data">
                                        <span class="sale_siderow"><span class="sale_element"><b> <?php echo $product->price;?></b></span></span>
                                        </div>
                                    </div>
                                    <div class="sale_description" ><span class="sale_element_description"><p><?php echo $product->VALUE->desc;?></p></span></div>
                                    <?php //if( function_exists('ADDTOANY_SHARE_SAVE_KIT') ) { ADDTOANY_SHARE_SAVE_KIT(array('linkurl' => $url)); } ?>
                                    <div class="view_sidedetails">
                                        <a class="viewbutton" href="<?php echo $url;?>">
                                            <span class="btnleft"></span>
                                            <span class="btncenter">
                                            <?php _e('GENERIC_VIEW_DETAILS',"realtransac"); ?>
                                            </span>
                                            <span class="btnright"></span>
                                        </a>
                                     </div>
                                </div>
                               
                            </li>
                           <?php } ?> 
                       </ul>  
                       
                       <?php if($this->displaysliderform == 2){?>            
                           <div class="nximg" id="next<?php echo $this->widget;?>"></div>
                       <?php }?>   
                    </div>
                 </div>                
            <?php }?>

            <?php if($this->displaysliderform == 2){
                    $displayQuantity    =   $this->result->displayqty;
                    if($totalProducts < $this->result->displayqty) {
                        $displayQuantity    =   $totalProducts;
                    }
                ?>
                        
                <script type="text/javascript">
                    jQuery.noConflict();
                    jQuery(function(){

                        jQuery('#slider<?php echo $this->widget;?>').bxSlider({
                            displaySlideQty: <?php echo $displayQuantity; ?>,
                            moveSlideQty: <?php echo $this->result->displayqty; ?>, 
                            pager: false,
                            infiniteLoop: false,
                            nextText: '',
                            nextSelector: jQuery('#next<?php echo $this->widget;?>'),
                            prevText: '',
                            prevSelector: jQuery('#previous<?php echo $this->widget;?>')                        
                        });
                    });
                </script>
               <?php }else{ 
                      if($this->display == '1'){
                          $width = $this->result->displayqty*190;
                      }else{
                          $width = $this->result->displayqty*360;
                      }
                ?>
                
                <div class="rt_pagination" style="width: <?php echo $width;?>px">
                    <div class="rt_pagination_outer">
                        <div class="rt_pagination_inner">
                            <div class="previous" id="previous<?php echo $this->widget;?>"></div>                     
                            <div class="pagination" id="pager<?php echo $this->widget;?>"></div>
                            <div class="next" id="next<?php echo $this->widget;?>"></div>
                        </div>
                    </div>
                </div>
                <script type="text/javascript">
                    jQuery.noConflict();
                    jQuery(function(){

                        jQuery('#slider<?php echo $this->widget;?>').bxSlider({
                            displaySlideQty: <?php echo $this->result->displayqty; ?>,
                            moveSlideQty: <?php echo $this->result->displayqty; ?>, 
                            pager: true,
                            infiniteLoop: false,
                            nextText: '&raquo;',
                            nextSelector: jQuery('#next<?php echo $this->widget;?>'),
                            prevText: '&laquo;',
                            prevSelector: jQuery('#previous<?php echo $this->widget;?>'),
                            pagerSelector: jQuery('#pager<?php echo $this->widget;?>')
                        });
                    });
                </script>
            <?php } ?>
            <div class="clear"></div>
            <?php 
            
        } // main if  
         else{
            echo '<div class="InvalidSub">';          
                _e('GENERIC_PROPERTY_NOT_MATCH',"realtransac");
            echo '</div>';
        }
       } // invalid subscription if End	                
       echo '</div>';
     }
   
   function displayPropertyList(){
       
       global  $rt_config;
       
       $html = '';
       $html.= '<div class = "rt_shortcodelist '.$rt_config['plugin_design'].' rt_widget_content">';
       
       if($this->result->products){
        ?>
        <script type="text/javascript">
            //ajax.php/?lang='.qtrans_getLanguage()
            var PER_PAGE  = '<?php echo $this->result->perpage; ?>';
            var MAX_COUNT = '<?php echo $this->result->totalcount; ?>';
            var searchurl = '<?php echo plugins_url('ajaxcall.php' , __FILE__).'/?lang='.$this->lang; ?>';
            var APIKEY    = '<?php echo $this->apikey; ?>';
            var WSDL      = '<?php echo $this->wsdl; ?>';
            var VERSION   = '<?php echo $this->plugver; ?>';
            var LANGUAGE  = '<?php echo $this->lang; ?>';
            var PERMALINK = '<?php echo $this->permalink; ?>';
            var DETAILLINK = '<?php echo $this->detailLink; ?>';
            var WIDGETID  = '<?php echo $this->widget; ?>';
            var PLUGIN_URL = '<?php echo plugins_url( '' , __FILE__ ); ?>';
            var IP        = '<?php echo $this->ip; ?>';

            var TYPE  = '<?php echo  $this->type; ?>';
            var SHOW  = '<?php echo  $this->show; ?>';
            var BUILT = '<?php echo  $this->built; ?>';
            var LIMIT = '<?php echo  $this->limit; ?>';

            jQuery.noConflict();
            jQuery(document).ready(function(){

                // Create pagination element with options from form
                var options = {
                    items_per_page: PER_PAGE,
                    num_display_entries : '<?php echo NO_OF_PAGINATION_LINK_SHOW;?>',
                    num_edge_entries : '<?php echo NO_OF_PAGE_LEFT_RIGHT;?>',
                    prev_text : '<?php _e('PRV_TEXT', 'realtransac'); ?>',
                    next_text : '<?php _e('NEXT_TEXT', 'realtransac'); ?>',
                    callback: pageselectCallback
                };
                jQuery(".rt_result_pagination").pagination(MAX_COUNT, options);
            });

            function loadResults(page)
            {
                if(jQuery('#PropertySearchresult').height() < 50){
                   jQuery('#PropertySearchresult').css("min-height", '50px');
                }
                jQuery('#PropertySearchresult').append('<div class="loader"><?php _e("GENERIC_LOADING","realtransac"); ?></div>');
                jQuery('#PropertySearchresult').find('table').animate({opacity: "0.5"});

                var loader  = jQuery('#PropertySearchresult').find('.loader');
                var pos     = jQuery('#PropertySearchresult').position();
                var top     = Math.max(0, pos.top + (jQuery('#PropertySearchresult').height()/ 2)) - (loader.height()/2) + "px";
                var left    = Math.max(0, pos.left + (jQuery('#PropertySearchresult').width() / 2)) - (loader.width()/2) + "px";

                loader.css("top", top);
                loader.css("left", left);

                jQuery.ajax({
                  type: "POST",
                  url: searchurl,
                  dataType: "json",
                  data: {
                      action: 'productsearchresults',
                      pluginurl: PLUGIN_URL,
                      permalink: PERMALINK,
                      detaillink: DETAILLINK,
                      apikey: APIKEY,
                      widget: WIDGETID,
                      wsdl: WSDL,
                      version: VERSION,
                      type: TYPE,
                      show: SHOW,
                      built: BUILT,
                      limit: LIMIT,
                      language: LANGUAGE,
                      ip: IP,
                      page: page
                },
                success: function( response ){
                    loadData(response.jsoncontent, false, 3, true);
                    jQuery('.search_head').html(response.Title);
                    jQuery('#PropertySearchresult').html(response.htmlcontent);
                    jQuery('#PropertySearchresult').animate({opacity: "1"});
                    jQuery.isFunction(function(){setInterval(function(){new ElementMaxHeight();},500)});
                }
               });
            }

            function pageselectCallback(page, jq)
            {
                loadResults(page);
                return false;
            }
        </script>
        <?php
        $from = $page*$this->result->perpage;
        $from = $from + 1;
        $to   = $from+$this->result->perpage;
        $to   = $to - 1 ;
        if($to > $this->result->totalcount){
            $to  = $this->result->totalcount;
        }
        if($to > 0){
            $title =   __('GENERIC_TOTAL_RESULT','realtransac').' '.$from.' - '.$to.' '.__('GENERIC_RESULT_OF','realtransac').' '.$this->result->totalcount;
        }
        $html.= '<div class="search_header_container"> <div class="search_head">'.$title.'</div>  <div class="rt_result_pagination"></div> </div>';
        
        $html.= '<div id="PropertySearchresult">';
        
        $html.= '<table id="results" celpadding="0" cellspacing="0" width="100%">';
        $html.= '<th></th>';
        
        foreach ($this->result->products as $product){

            $url = $this->append_params_page_url($this->detailLink, array('id' => $product->id));
            $html.= '<tr>';
            $html.= '<th>';
                $html.= '<fieldset>';
                    $html.= '<div class="rt_listing_rows">';
                        $html.= '<div class="rt_listing_wrapper">';
                                $html.= ' <div class="rt_listing_marker">';
                                $html.= '</div>';
                                $html.= ' <div class="rt_listing_details">';
                                    $html.= '<div class="rt_listing_title">';
                                    $html.= $product->title;
                                    $html.= '</div>';
                                $html.= '</div>';
                                $html.= '<div class="rt_listing_content_left">';
                                $html.= ' <div class="rt_listing_iconset">';
                                 if($product->area != NULL){
                                      // $html.= $product->AREA.'<img src="'.plugins_url( 'images/car.png' , __FILE__ ).'" />';
                                 }
                                 if($product->bedrooms != NULL){
                                       $html.= '<span class="count bedroom">'.$product->bedrooms.'</span>';
                                 }
                                 if($product->bathrooms != NULL){
                                       $html.= '<span class="count bathroom">' .$product->bathrooms.'</span><br/>';
                                 }
                                 $html.= '</div>';
                                 $html.= ' <div class="rt_listing_price">';
                                 $html.= $product->price.'<br/>';
                                 $html.= '</div>' ;
                                 $html.= '<div class="rt_listing_description">';
                                 $html.= $product->desc.'<br/>';
                                 $html.= '</div>';
                                 $html.= '<div class="rt_listing_viewdetails">';
                                 $html.= '<a class="viewbutton" href="'.$url.'"><span class="btnleft"></span><span class="btncenter">'.__("GENERIC_VIEW_DETAILS","realtransac").'</span><span class="btnright"></span></a>';
                                 $html.= '<div class="clear"></div>';
                                 $html.= '</div>';
                                 $html.= '</div>';
                                 $html.= '<div class="rt_listing_content_right">';
                                 $html.= ' <div class="rt_listing_imgwrapper">';
                                    $html.= ' <a href="'.$url.'"><img src="'.$product->picture.'" /></a>';
                                 $html.= '</div>';
                             $html.= '</div>';
                             $html.= '<div style="clear:both;"></div>';
                        $html.= '</div>';
                 $html.= '</div>';
                 $html.= '</fieldset>';
            $html.= '</th>';
            $html.= '</tr>';

           }
           $html.= '</table>';
           $html.=  '</div>';
           $html.=  '<div class="search_bottom_container"> <div class="search_head">'.$title.'</div> <div class="rt_result_pagination"></div> </div>';
        }else{
            $html.= '<div class="property_notfound">'.$this->result->error.'</div>';
        }
        
        $html.=  '</div>';
        
        return $html;
    }
 } // main class 
?>