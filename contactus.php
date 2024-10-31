<?php
class Realtransac_API_Contactus {

     public function __construct($instance, $widget_id){
       
        global $rt_config;
        $this->plugver    = plugin_get_version();
        $this->widget     = $widget_id;        
        $this->wsdl       = $rt_config['wsdl'];
        $this->display    = $instance['displaycontactform'];
        $this->apikey     = $rt_config['apikey'];        
        $this->client     = $rt_config['client'];
        $this->pageid     = get_option('pageid'); 
        $this->designoption = get_option('plugindesign');
        $this->urlaction  = plugins_url('captcha_code_file.php' , __FILE__ );
        $this->qtranslate = false;
      
        
        if ( function_exists( 'qtrans_generateLanguageSelectCode' ) ){					
            $this->qtranslate   =   true;  
            $this->lang         = qtrans_getLanguage();
        }else{
            $this->lang         = $rt_config['language'];
        }
     
        if(isset($_POST['contactform'])){ 
                           
            $param = array(
                'apikey'         => $this->apikey,
                'version'        => $this->plugver,
                'ContactName'    => $_POST['contactname'],
                'ContactEmail'   => $_POST['contactemail'],
                'ContactPhone'   => $_POST['contactphone'],
                'Contactcity'    => $_POST['contactcity'],
                'Comments'       => $_POST['contactcomment'],
                'language'       => $this->lang
            );

            if(!empty($_POST)){  

                if($_POST['contact_letters_code'] == $_SESSION['contact_letters_code']){ 
                    
                    $parameters = array('data' => $param);
                    $result = $this->client->call('getContactForm', $parameters, '', '', false, true);

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

                }else{
                        $this->contactcaptchaerror = 1;        

                }
            }          
              
        }
    }
    
    public function contactform(){
        
       global  $rt_config; 
       echo '<div class = "rt_contact rt_widget_content">'; 
       
       if(isset($this->result->error) && $this->result->error != ''){
           echo '<font color="red"><b>'.$this->result->error.'</b></font>';
       }else if(isset($this->result->result) && $this->result->result != ''){
           echo '<font color="green"><b>'.$this->result->result.'</b></font>';
       }
       ?>
        <script type="text/javascript">                
                jQuery(document).ready(function(){            
                    jQuery("#contactform<?php echo $this->widget; ?>").validate({
                        rules: {
                            contact_letters_code: {
                                required: true,
                                remote: { 
                                    url:'<?php echo plugins_url( 'check.php' , __FILE__ ); ?>',
                                    type:"post",
                                    async:false,
                                    data: {
                                        /* this is the name of the post parameter that PHP will use: $_POST['captcha'] */
                                        contact_letters_code: function() {
                                            return jQuery.trim(jQuery("#contact_letters_code").val());
                                        }
                                    }
                                }
                            }
                        },
                        messages: {
                            contact_letters_code: {
                                required: "<?php _e('GENERIC_CAPTCHA_IS_EMPTY','realtransac'); ?>",
                                remote  : "<?php _e('GENERIC_INVALID_CAPTCHA','realtransac'); ?>"
                            }
                        }
                        
                    });
                    
                    jQuery.extend(jQuery.validator.messages, {
                        required: "<?php _e('GENERIC_REQUIRED','realtransac'); ?>",                    
                        email   : "<?php _e('GENERIC_VALID_EMAIL','realtransac'); ?>",
                        number  : "<?php _e('GENERIC_VALID_NUMBER','realtransac'); ?>"
                    });
          
                    jQuery('#contactButton<?php echo $this->widget; ?>').click(function(event){                                       
                        jQuery('#contactform<?php echo $this->widget; ?>').submit();
                    });

                });
              
                
                function refreshcontactCaptcha()
                {
                        var img = document.images['contactcaptchaimg'];
                        img.src = img.src.substring(0,img.src.lastIndexOf("?"))+"?type=contact&rand="+Math.random()*1000;

                }
        </script>
        <?php
     
                $display_class = 'rt_vertical';
	    if($this->display == '2'){
                $display_class = 'rt_horizontal';
            }
        ?>
        <form id="contactform<?php echo $this->widget; ?>" name="contactform" class="contactform rtForm <?php echo $display_class;?>" method="post" action="">
        <div  class="rt_search_wrapper">
           
            <div  class="rt_search_row">	
                <div class="required label">
                    <?php 

                        _e('GENERIC_NAME',"realtransac");

                    ?>
                <span style="color:#D8202C">*</span></div>
                <div class="element">                                  
                    <input id="contactname" name="contactname" class="required" type="text" value="" >
                </div>
              
            </div>  
            
            
            <div  class="rt_search_row">	
                <div class="required label">
                    <?php 

                        _e('GENERIC_PHONE',"realtransac");

                    ?>
                <span style="color:#D8202C">*</span></div>
                <div class="element">
                    <input id="contactphone" name="contactphone" class="required number" type="text" value="" >
                </div>
              
            </div>  
            
            
            <div  class="rt_search_row">	
                <div class="label">
                   <?php 

                        _e('GENERIC_CITY',"realtransac");

                    ?>
                <span style="color:#D8202C">*</span></div>
                <div class="element">              
                    <input id="contactcity" name="contactcity" class="required" type="text" value="" >
                </div>
              
            </div>  
            
             <div  class="rt_search_row">	
                <div class="label">
                    <?php 

                        _e('GENERIC_EMAIL',"realtransac");

                    ?>
                <span style="color:#D8202C">*</span></div>
                <div class="element">
                    <input id="contactemail" name="contactemail" class="required email" type="text" value="" >
                </div>
              
            </div>  
            
             <div  class="rt_search_row">	
                <div class="label">
                    <?php 

                        _e('GENERIC_COMMENT',"realtransac");

                    ?>
                </div>
                <div class="element">              
                    <textarea id="contactcomment" name="contactcomment" class="required" rows="10" cols="20"></textarea>
                </div>
            </div>
            
             <div  class="rt_search_row">	
                <div class="label">
                    <?php 

                        _e('GENERIC_CAPTCHA',"realtransac");

                    ?>
              <span style="color:#D8202C">*</span>  </div>
                <div class="element">             
                   <p>
                       <img src="<?php echo $this->urlaction; ?>?type=contact&rand=<?php echo rand(); ?>" id='contactcaptchaimg' />
                       <a href='javascript: refreshcontactCaptcha();'><img src="<?php echo plugins_url( 'images/refresh.jpg' , __FILE__ ); ?>" /></a>
                   </p>

                   <input id="contact_letters_code" name="contact_letters_code" class="" value="" type="text"/>
                   <div id="errMsg"></div>                       
               </div>
<!--                <div class="error">
                <font color="red">
                    <?php 

                        //_e('CONTACTUS_ENTER_THE_ABOVE_CODE_HERE',"realtransac");

                    ?>
                </font>
                </div>-->
              
            </div>
            <input type="hidden" name="language" value="<?php  echo $this->lang;  ?>"/>
            <input type="hidden" name="contactform" value="1"/>
            <div class="btn-outer">
            <a class="viewbutton" id="contactButton<?php echo $this->widget; ?>">
                <span class="btnleft"></span>
                <span class="btncenter">
                    <?php 

                        _e('GENERIC_SEND',"realtransac");

                    ?>
                 </span>
                <span class="btnright"></span>
            </a>
            </div>
            <div class="clear"></div>
        </div>
        </form>
     
    <?php
        echo '</div>';
       
   }
   
} 
?>

 