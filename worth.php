<?php
class Realtransac_API_Worth {

     public function __construct($instance, $widget_id){
       
        global $rt_config;
        $this->plugver      = plugin_get_version();
        $this->widget       = $widget_id;        
        $this->wsdl         = $rt_config['wsdl'];
        $this->apikey       = $rt_config['apikey'];        
        $this->display      = $instance['displayworthform']; 
        $this->client       = $rt_config['client'];
        $this->pageid       = get_option('pageid'); 
        $this->designoption = get_option('plugindesign');
        $this->urlaction    = plugins_url( 'captcha_code_file.php' , __FILE__ );
              
        $this->qtranslate = false;
        
        if ( function_exists( 'qtrans_generateLanguageSelectCode' ) ){					
            $this->qtranslate   =   true;  
            $this->lang         = qtrans_getLanguage();
        }else{
            $this->lang         = $rt_config['language'];
        }
              
        if(isset($_POST['worthform'])){ 
                         
            $param = array(
                'apikey'         => $this->apikey,
                'version'        => $this->plugver,
                'Address'        => $_POST['worthaddress'],
                'Email'          => $_POST['worthemail'],
                'Phone'          => $_POST['worthphone'],
                'City'           => $_POST['worthcity'],
                'Bed'            => $_POST['worthbed'],
                'language'       => $this->lang
            );
                       
            if($_POST['worth_letters_code'] == $_SESSION['worth_letters_code']){  

                  $parameters = array('data' => $param);
                  $result = $this->client->call('getWorthForm', $parameters, '', '', false, true);

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
                $this->captchaerror = 1;        
            }            
        }
    }
 
    public function displayWorthForm(){
        
        global  $rt_config; 
        echo '<div class = "rt_worth rt_widget_content">'; 
        if(isset($this->result->error) && $this->result->error != ''){
           echo '<font color="red"><b>'.$this->result->error.'</b></font>';
        }else if(isset($this->result->result) && $this->result->result != ''){
           echo '<font color="green"><b>'.$this->result->result.'</b></font>';
        }
     ?>
    <script type="text/javascript">
        
        jQuery(document).ready(function(){
            
            jQuery("#worthform<?php echo $this->widget; ?>").validate({
                rules: {
                            worth_letters_code: {
                                required: true,
                                remote: { 
                                    url:'<?php echo plugins_url( 'check.php' , __FILE__ ); ?>',
                                    type:"post",
                                    async:false,
                                    data: {
                                        /* this is the name of the post parameter that PHP will use: $_POST['captcha'] */
                                        worth_letters_code: function() {
                                            return jQuery.trim(jQuery("#worth_letters_code").val());
                                        }
                                    }
                                }
                            }
                        },
                messages: {
                    worth_letters_code: {
                        required: "<?php _e('GENERIC_CAPTCHA_IS_EMPTY','realtransac'); ?>",
                        remote  : "<?php _e('GENERIC_INVALID_CAPTCHA','realtransac'); ?>"
                    }
                }
            });
            
            jQuery('#worthButton<?php echo $this->widget; ?>').click(function(event){
                jQuery('#worthform<?php echo $this->widget; ?>').submit();
            });
            
        });
        
      
        function refreshCaptcha()
        {
                var img = document.images['worthcaptchaimg'];
                img.src = img.src.substring(0,img.src.lastIndexOf("?"))+"?type=worth&rand="+Math.random()*1000;

        }
    </script>
    <?php
        $display_class = 'rt_vertical';
        if($this->display == '2'){
            $display_class = 'rt_horizontal';
        }
    ?>
     <form id="worthform<?php echo $this->widget; ?>" name="worthform" class="worthform rtForm <?php echo $display_class;?>" method="post" action="">
        <div  class="rt_search_wrapper">
           
            <div  class="rt_search_row">	
                <div class="label">
                    <?php _e('WORTH_ADDRESS_OF_YOURP_ROPERTY',"realtransac"); ?>
                <span style="color:red">*</span></div>
                <div class="element">
                        <input id="worthaddress" name="worthaddress" class="required" type="text" value="" />
                </div>
            </div>  
             <div  class="rt_search_row">	
                <div class="label">
                   <?php _e('GENERIC_CITY',"realtransac"); ?>
                <span style="color:red">*</span></div>
                <div class="element">
                        <input id="worthcity" name="worthcity" class="required" type="text" value="" />
                </div>
            </div>  
             <div  class="rt_search_row">	
                <div class="label">
                    <?php _e('WORTH_NUM_OF_BED',"realtransac"); ?>
                <span style="color:red">*</span></div>
                <div class="element">
                        <input id="worthbed" name="worthbed" class="required number" type="text" value="" />
                </div>
            </div>  
              <div  class="rt_search_row">	
                <div class="label">
                    <?php _e('GENERIC_EMAIL',"realtransac"); ?>
                <span style="color:red">*</span></div>
                <div class="element">
                        <input id="worthemail" name="worthemail" class="required email" type="text" value="" />
                </div>
            </div>  
            <div  class="rt_search_row">	
                <div class="required label">
                    <?php _e('GENERIC_PHONE',"realtransac"); ?>
                <span style="color:red">*</span></div>
                <div class="element">
                        <input id="worthphone" name="worthphone" class="required number" type="text" value="" />
                </div>
            </div>  
             <div  class="rt_search_row">	
                <div class="label">
                    <?php _e('GENERIC_CAPTCHA',"realtransac"); ?>
                 <span style="color:red">*</span></div>
               <div class="element">
              
                       <p>
                           <img src="<?php echo $this->urlaction; ?>?type=worth&rand=<?php echo rand(); ?>" id='worthcaptchaimg' /> 
                           <a href='javascript: refreshCaptcha();'><img src="<?php echo plugins_url( 'images/refresh.jpg' , __FILE__ ); ?>" /></a>
                       </p>
                       
                       <input id="worth_letters_code" name="worth_letters_code" class="" value="" type="text"/> 
                      <div id="errMsg"></div>
               </div>
                <div class="error">
                <font color="red">
                    <?php //_e('GENERIC_INVALID_CAPTCHA',"realtransac"); ?>
                </font>
                </div>
           </div>
           <input type="hidden" name="language" value="<?php  echo $this->lang;  ?>"/>
           <input type="hidden" name="worthform" value="1"/>

           <div class="btn-outer">

           <a class="viewbutton" id="worthButton<?php echo $this->widget; ?>">
                <span class="btnleft"></span>
                <span class="btncenter">
                    <?php  _e('GENERIC_SEND',"realtransac"); ?>
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

 