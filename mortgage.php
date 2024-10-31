<?php
class Realtransac_API_Mortgage {
    
   public function __construct($instance, $widget_id){
        global  $rt_config;
        $this->plugver      = plugin_get_version();
        $this->widget       = $widget_id;        
        $this->pageid       = get_option('pageid');
        $this->designoption = get_option('plugindesign');
        $this->display      = $instance['displaymortgageform'];
        $this->wsdl         = $rt_config['wsdl'];
        $this->apikey       = $rt_config['apikey'];
        $this->ip           = $rt_config['ip'];
        $this->client       = $rt_config['client'];
        $this->qtranslate   = false;
      
        
        if ( function_exists( 'qtrans_generateLanguageSelectCode' ) ){					
            $this->qtranslate   = true;  
            $this->lang         = qtrans_getLanguage();
        }else{
            $this->lang         = $rt_config['language'];
        }
        
   } 
   
   public function displayForm(){ 
       
       global  $rt_config;
       
       echo '<div class = "rt_mortage rt_widget_content">'; 
       ?>
       
        <script type="text/javascript">
            
            var ajaxurl = '<?php echo plugins_url('ajaxcall.php' , __FILE__).'/?lang='.$this->lang; ?>';
            var LANGUAGE  = '<?php echo $this->lang; ?>';
            
            jQuery.noConflict();
            
            jQuery(document).ready(function() {
               
                jQuery("#mortgageForm<?php echo $this->widget; ?>").validate({ 
                    submitHandler: function() {
                        
                            if(jQuery('#mortgage_result<?php echo $this->widget; ?>').height() < 50){
                               jQuery('#mortgage_result<?php echo $this->widget; ?>').css("min-height", '50px');
                            }
                            jQuery('#mortgage_result<?php echo $this->widget; ?>').append('<div class="loader"><?php _e("GENERIC_LOADING","realtransac"); ?></div>');
                            jQuery('#mortgage_result<?php echo $this->widget; ?>').find('table').animate({opacity: "0.5"});

                            var loader  = jQuery('#mortgage_result<?php echo $this->widget; ?>').find('.loader');
                            var pos     = jQuery('#mortgage_result<?php echo $this->widget; ?>').position();
                                                        
                            jQuery("#mortgage_result<?php echo $this->widget; ?>").show();
                            jQuery.ajax({
                              type: "POST",
                              url: ajaxurl,
                              dataType: "json",
                              data: {
                                  action: 'martgage',
                                  language: LANGUAGE,
                                  amount:jQuery("#amount").val(),
                                  interestrate:jQuery("#interestrate").val(),
                                  termsofyears:jQuery("#termsofyears").val(),
                                  term:jQuery("#term").val()
                            },
                            success: function( response ){
                                jQuery('#mortgage_result<?php echo $this->widget; ?>').html(response.MortgageResult);
                                jQuery('#mortgage_result<?php echo $this->widget; ?>').animate({opacity: "1"});
                                jQuery.isFunction(function(){setInterval(function(){new ElementMaxHeight();},500)});
                            }
                           });
                        }
                    });
                    
                    jQuery.extend(jQuery.validator.messages, {
                        required: "<?php _e('GENERIC_REQUIRED','realtransac'); ?>",                    
                        email   : "<?php _e('GENERIC_VALID_EMAIL','realtransac'); ?>",
                        number  : "<?php _e('GENERIC_VALID_NUMBER','realtransac'); ?>",
                        min     : "<?php _e('GENERIC_VALID_MIN','realtransac'); ?>",
                        max     : "<?php _e('GENERIC_VALID_MAX','realtransac'); ?>",
                        range   : "<?php _e('GENERIC_VALID_RANGE','realtransac'); ?>"
                    });
           
                jQuery('#mortgageButton<?php echo $this->widget; ?>').click(function(event){                    
                   jQuery("#mortgageForm<?php echo $this->widget; ?>").submit();
                });

            });                  

    </script>
    <?php
            $display_class = 'rt_vertical';
	    if($this->display == '2'){
                $display_class = 'rt_horizontal';
            }
    ?>
     <form id="mortgageForm<?php echo $this->widget; ?>" name="mortgageForm" class="mortgageForm rtForm <?php echo $display_class;?>"  method="post" action="javascript:void(0);">
            
        <div  class="rt_search_wrapper">
             <div  id="horizontal_pane" class="rt_search_frame">
             <div  class="rt_search_row">
                    <div class="label">
                         <?php 

                            _e('MORTGAGE_MORTGAGE_AMOUNT',"realtransac");

                          ?>
                    </div>
                    <div class="interestTerm">
                    <input id="amount" name="amount" type="text" class="required number" value=""/>   
                    </div>
                   
                    
             </div> 
             <div  class="rt_search_row">
                    <div class="label">
                        <?php 

                           _e('MORTGAGE_INTEREST_RATE',"realtransac");

                        ?>
                    </div>
                    <div class="interestTerm">
                    <input id="interestrate" name="interestrate" type="text" class="required min" value=""/>   
                    
                    </div>  
            </div> 
            <div  class="rt_search_row">
                <div class="label">
                    <?php 

                       _e('MORTGAGE_INTEREST_TERM',"realtransac");

                    ?>
               </div>
               <div class="drop_down_list rangemin">
                <select id="term" name="term">
                   <option value="1" selected><?php _e('MORTGAGE_MONTHS',"realtransac");?></option>
                   <option value="2"><?php _e('MORTGAGE_YEARS',"realtransac");?></option>
                </select>
               </div>
               <div class="drop_down_list rangemax">
                 <input id="termsofyears" name="termsofyears" type="text" class="required range" value=""/>                 
                </div>
            </div>

           
            <div  class="rt_search_row">
                    <div class="label">
                        <?php 

                           _e('MORTGAGE_START_DATE',"realtransac");

                        ?>
                    </div>
                    <div class="interestTerm">
                     <?php 
                        /*if($this->qtranslate){

                        $translanguage = qtrans_getLanguage();
                        }else{
                        $translanguage = 'en';
                        }*/
                    ?>
                    <input type="hidden" name="language" value="<?php  echo $this->lang;  ?>"></input>
                    <input name="departure_date" disabled="disabled" id="datepicker<?php echo $this->widget; ?>" type="text" class="required"  value="<?php echo date('d/m/Y');?>" />

                    </div>
                </div>
                <div class="btn-outer">
                    <a class="viewbutton" id="mortgageButton<?php echo $this->widget; ?>">
                        <span class="btnleft"></span>
                        <span class="btncenter">
                            <?php 

                               _e('GENERIC_SEND',"realtransac");

                            ?>
                        </span></span>
                        <span class="btnright"></span>
                    </a> 
                </div>
           </div>
            <div class="clear"></div>
            
         </div>
        </form>	
        <div class="mortgage_result" id="mortgage_result<?php echo $this->widget; ?>"></div>
    
   <?php
        echo '</div>';
       
   } 
 
} 
?>