<?php
class Realtransac_API_ViewDetail {
    
   public function __construct($instance, $widget_id){
       
      
            global  $rt_config;
            $this->plugver      = plugin_get_version();
            $this->urlaction    = plugins_url( 'captcha_code_file.php' , __FILE__ );      
            $this->wsdl         = $rt_config['wsdl'];
            $this->apikey       = $rt_config['apikey'];
            $this->ip           = $rt_config['ip'];
            $this->client       = $rt_config['client'];
            
            // Changed for detail page map
            $rt_config['pageType'] = 3;
        
            $this->qtranslate = false;
      
        
            if ( function_exists( 'qtrans_generateLanguageSelectCode' ) ){					
                $this->qtranslate   = true;  
                $this->lang         = qtrans_getLanguage();
            }else{
                $this->lang         = $rt_config['language'];
            }
          
            $rt_config['viewdetail'] = $instance['id'];
             
            $param = array(
                        'apikey'       => $this->apikey,
                        'version'      => $this->plugver,
                        'productid'    => $instance['id'],
                        'language'     => $this->lang,                            
                    );   
                
            /**
            * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE SEARCH RESULTS BASED ON IT */
            if(isset($rt_config['rt_currency']['globalCurrency'])) {
                $param['rtglobal_currency'] =   $rt_config['rt_currency']['globalCurrency'];
            }
          $parameters = array('data' => $param);
          $result = $this->client->call('getProductDetails', $parameters, '', '', false, true);
           
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
   
   public function enquiry_sumbit(){
       
        global  $rt_config;
              
       $contractsale    = ($_POST['contractsale']) ? $_POST['contractsale'] : 0;
       $inspectproperty = ($_POST['inspectproperty']) ? $_POST['inspectproperty'] : 0;
       $similarproperty = ($_POST['similarproperty']) ? $_POST['similarproperty'] : 0;
       
        $param = array(
                        'apikey'          =>  $this->apikey,
                        'version'         =>  $this->plugver,
                        'productid'       =>  addslashes($rt_config['viewdetail']),
                        'Name'            =>  $_POST['Name'],
                        'Email'           =>  $_POST['email'],
                        'Phone'           =>  $_POST['phone'],                            
                        'Comments'        =>  $_POST['comments'],
                        'About'           =>  $_POST['about'],
                        'Contractsale'    =>  $contractsale,
                        'Inspectproperty' =>  $inspectproperty,
                        'Similarproperty' =>  $similarproperty,
                        'Captcha'         =>  $_POST['view_letters_code'],
                        'sessionCaptcha'  =>  $_SESSION['view_letters_code'],
                        'language'        =>  $this->lang
                    );  
                    


         if($_POST['view_letters_code'] == $_SESSION['view_letters_code']){  
        
         $parameters = array('data' => $param);        

         $result = $this->client->call('getProductEnquiries', $parameters, '', '', false, true);
           
            // Check for a fault
            if ($this->client->fault) {
                     echo '<h6>'.__('GENERIC_SERVER_CONNECTION_FAULT',"realtransac").'</h6>';
            } else {
                    $err = $this->client->getError();
                    if ($err) {
                            echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
                    } 
            }
                             
            $this->enquiryresults   =   json_decode($result);
            
       }else{
                $this->viewcaptchaerror = 1;        
       }
        
        
        return $this->enquiryresults;
    } 
 
    public function view_list(){
        
      global  $rt_config;
     
      echo '<div class = "'.$rt_config['plugin_design'].' rt_viewdetails rt_widget">'; 
       ?>
        
        <script language="javascript">
            jQuery.noConflict();           

            jQuery(document).ready(function(){
                
                
                jQuery("#enquiryForm").validate({
                        rules: {
                            view_letters_code: {
                                required: true,
                                remote: { 
                                    url:'<?php echo plugins_url( 'check.php' , __FILE__ ); ?>',
                                    type:"post",
                                    async:false,
                                    data: {
                                        /* this is the name of the post parameter that PHP will use: $_POST['captcha'] */
                                        view_letters_code: function() {
                                            return jQuery.trim(jQuery("#view_letters_code").val());
                                        }
                                    }
                                }
                            }
                        },
                        messages: {
                            view_letters_code: {
                                required: "<?php _e('GENERIC_CAPTCHA_IS_EMPTY','realtransac'); ?>",
                                remote  : "<?php _e('GENERIC_INVALID_CAPTCHA','realtransac'); ?>"
                            }
                        }
                        
                });
                    

                jQuery('.phonelengthError').hide();
                jQuery('.namelengthError').hide();
                jQuery('.emaillengthError').hide();

                jQuery('.aboutlengthError').hide();
                jQuery('.captchalengthError').hide();

                jQuery('#enquiryButton').click(function(event){
                    jQuery('#enquiryForm').submit();
                });

                jQuery('#enquiryForm').submit(function(event){

                    //PHONE
                    if(jQuery("#phone").val() == ''){

                        jQuery('.phonelengthError').show('');
                        event.preventDefault();
                    }else{
                        jQuery('.phonelengthError').hide();
                    }

                    //NAME   
                    if(jQuery("#Name").val() == ''){

                        jQuery('.namelengthError').show();
                        event.preventDefault();
                    }else{
                        jQuery('.namelengthError').hide();
                    }

                    //EMAIL   
                    if(jQuery("#email").val() == ''){

                        jQuery('.emaillengthError').show();
                        event.preventDefault();
                    }else{
                        jQuery('.emaillengthError').hide();
                    }

                    //ABOUT
                    if(jQuery("#about").val() == ''){

                        jQuery('.aboutlengthError').show();
                        event.preventDefault();
                    }else{
                        jQuery('.aboutlengthError').hide();
                    }

                    //CAPTCHA   

                });  
                
                Galleria.run('#galleria', {
                                transition: 'fade',
                                imageCrop: true,
                                thumbCrop: 'height',
                                autoplay:true,
                                debug:false,
                                //width:850,
                                height:650,
                                showImagenav:true,
                                 extend: function() {
                                    var gallery         =   this,landscape;
                                    var gallerywidth    =   gallery.getOptions('width');
                                    var galleryheight   =   gallery.getOptions('height');
                                    this.bind('image', function(e) {
                                        var imageW      =   e.imageTarget.width;
                                        var imageH      =   e.imageTarget.height;
                                        if(imageW <= gallerywidth && imageH <= galleryheight){
                                           landscape = false;
                                       } else if(imageW > gallerywidth && imageH > galleryheight){
                                           landscape = 'width';
                                       } else if(imageW > gallerywidth || imageH > galleryheight){
                                           landscape = 'height';
                                       }
                                        this.setOptions({ imageCrop: landscape }).refreshImage();
                                        this.setOptions({ thumbCrop: landscape }).refreshImage();
                                    });
                                }
                });
    

            });
            function viewrefreshCaptcha()
            {
                    var img = document.images['viewcaptchaimg'];
                    //img.src = img.src.substring(0,img.src.lastIndexOf("?"))+"?rand="+Math.random()*1000;
                    img.src = img.src.substring(0,img.src.lastIndexOf("?"))+"?type=enquiry&rand="+Math.random()*1000;
            }
        </script>
               
        <?php
        if(!isset($this->results->error)){
                //Product Details
                $productTitle   =   $this->results->ProductInfo->Category .' '.$this->results->ProductInfo->Type;
                if(trim($this->results->ProductInfo->Bedroom)!='' && trim($this->results->ProductInfo->Bedroom)!='0') {
                    $productTitle  .=   ', '.$this->results->ProductInfo->Bedroom.' '.$this->results->ProductInfo->BedroomTitle;
                }
                if(trim($this->results->ProductInfo->Area)!='' && trim($this->results->ProductInfo->Area)!='0') {
                    $productTitle  .=   ', '.$this->results->ProductInfo->Area;
                }
                $productTitle  .=   ' '.$this->results->ProductInfo->City;
                echo '<div class = "rt_property_details">';
                    echo '<div class = "rt_property_header">';
                        echo '<div class="rt_property_header_left">';
                            echo '<span class="rt_detail_header">'.$productTitle.'<span>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
                
                //View Slider
                 echo '<div class = "rt_property_slideshow_frame">';
                      if(count($this->results->Picture) || $this->results->Video != ''){
                          echo '<div id="galleria">';

                                 foreach($this->results->Picture as $result){

                                            $file     = $this->results->Url.$result->idProduct.'/orginal/'.$result->idPicture.'_'.$result->filename;
                                            $srcfile  = $this->results->Url.$result->idProduct.'/map/'.$result->idPicture.'_'.$result->filename;
                                            echo '<a href="'.$file.'"><img src="'.$srcfile.'"></a>';
                                 }
 
                                 if(isset($this->results->Video) && $this->results->Video != ''){                                          
                                      echo '<a href="'.$this->results->Video.'"><span class="video">Watch this video!</span></a>';
                                 }

                          echo '</div>';

                     }else{
                          echo  '<div class="galleria-noimg" align="center"><img src="'.plugins_url( 'images/no-img.png' , __FILE__ ).'" ref="'.plugins_url( 'images/no-img.png' , __FILE__ ).'"/></div>';
                          
                     }
                   
                echo '</div>';
               
               //Listing Details 
               echo '<div class="RT_listview_contant_wrapper">';
               
               if($this->results !=''){
                   echo '<div class="rt_property_details_header">';
                        echo '<div class="rt_property_details_list">'; ?>
                                <div class="rt_property_h2">
                                    <?php _e('VIEWDETAIL_PROPERTY_DETAILS',"realtransac");?>
                                </div>
                                <?php
                                echo '<div class="contant_info">';
                                    if($this->results){
                                        echo '<div class="rt_mlsdetrow">';
                                              //MLD ID REFERENCE  
                                            if($this->results->idMLS) {
                                                echo __('VIEWDETAIL_MLS_ID_REFERENCE',"realtransac").' '.$this->results->idMLS;
                                            }
                                        echo '</div>';
                                        $energyval      =   0;
                                        $emissionval    =   0;
                                        $descriptionTitle   =   '';
                                        $descriptionValue   =   '';
                                        echo '<div style="width: 100%;">';
                                        if($this->results->Listing) {
                                           echo '<div class="listdetails">';
                                            $i = 1 ;
                                            foreach($this->results->Listing as $listDetail) {
                                                if($i%2 == 0){
                                                    $mlsrow = "rt_evenrow";
                                                }else{
                                                    $mlsrow = "rt_oddrow";
                                                }
                                                if($listDetail->Notation == 'ENERGYLABEL'){
                                                    $energyval      =   (int)$listDetail->Value;
                                                }else if($listDetail->Notation == 'EMISSIONGES'){
                                                    $emissionval    =   (int)$listDetail->Value;
                                                }else if($listDetail->Notation == 'DESC'){
                                                    $display_title = $listDetail->Title;
                                                    if($listDetail->Text !=''){
                                                        $display_title = $listDetail->Text;
                                                    }
                                                    $descriptionTitle   =   $display_title;
                                                    $descriptionValue   =   $listDetail->Value;
                                                }else {
                                                    $display_title = $listDetail->Title;
                                                    if($listDetail->Text !=''){
                                                        $display_title = $listDetail->Text;
                                                    }
                                                    echo '<div class="rt_listdetrow '.$mlsrow.'">
                                                            <div class="rt_listname '.$mlsrow.'Name">&nbsp;&nbsp;&nbsp;'.$display_title .' - '.'</div>
                                                            <div class="rt_listval '.$mlsrow.'Val">'. $listDetail->Value.'</div>
                                                          </div>';
                                                }
                                                $i++;
                                            }
                                            echo '</div>';
                                            echo '<div class="contact-detail">
                                                    <div>
                                                        <div class=rt_agencylogo><img src="'.$this->results->ContactDetails->LogoUrl.'"/></div>
                                                        <div class="rt_agencyDetails">';
                                                            echo $this->results->ContactDetails->Agencyname."</br>";
                                                            echo $this->results->ContactDetails->AgencyAddress."</br>";
                                                            if($this->results->ContactDetails->AgencyCity){
                                                                echo '('.$this->results->ContactDetails->AgencyCity.')'."</br>";
                                                            }
                                                            echo $this->results->ContactDetails->AgencyCountry."</br>";
                                                            //echo $this->results->ContactDetails->Email."</br>";
                                                            echo $this->results->ContactDetails->Phone."</br>";
                                                echo '  </div>
                                                        <div class="communication-icons">';
                                                            $siteurl    =   getCurrentUrl();
                                                            $siteTitle  =   get_the_title();
                                                        echo '<div class="shared_icons">
                                                                <a href="http://www.facebook.com/sharer.php?u='.$siteurl.'&t='.$siteTitle.'" target="_blank" title="Share This on Facebook"><img style="width:21px;height:20px;border: 0px none;" title="Share This on Facebook" src="'.plugins_url( 'images/fb.png' , __FILE__ ).'" alt="Share This on Facebook"/></a>
                                                                <a href="http://twitter.com/share?url='.$siteurl.'&text='.$siteTitle.'&via=TWITTER-HANDLE" target="_blank" alt="Tweet This!" title="Tweet This!"><img src="'.plugins_url( 'images/tw.png' , __FILE__ ).'" style="border:0px;width:21px;height:20px;" alt="Tweet This" title="Tweet This"></a>
                                                                <a href="http://plus.google.com/share?url='.$siteurl.'" target="_blank" title="Post it on Google+"><img style="width:21px;height:20px;border: 0px none;" title="Post it on Google+" src="'.plugins_url( 'images/g+.png' , __FILE__ ).'" alt="Post it on Google+"/></a>';
                                                                /*
                                                                <a href="http://digg.com/submit?url='.$siteurl.'&title='.$siteTitle.'" target="_blank" title="Digg This"><img style="width:21px;height:20px;border: 0px none;" title="Digg This" src="'.plugins_url( 'images/di.png' , __FILE__ ).'" alt="Digg This" /></a>
                                                                <a href="http://www.stumbleupon.com/submit?url='.$siteurl.'&title='.$siteTitle.'" target="_blank" title="Submit This to StumbleUpon"><img style="width:21px;height:20px;border: 0px none;" title="Submit This to StumbleUpon" src="'.plugins_url( 'images/su.png' , __FILE__ ).'" alt="Submit This to StumbleUpon"/></a>
                                                                <a href="http://www.linkedin.com/shareArticle?mini=true&url='.$siteurl.'&title='.$siteTitle.'" target="_blank" title="Share This on LinkedIn"><img style="width:21px;height:20px;border: 0px none;" title="Share This on LinkedIn" src="'.plugins_url( 'images/in.png' , __FILE__ ).'" alt="Share This on LinkedIn"/></a>
                                                                 */ 
                                                        echo '</div>
                                                        </div>
                                                    </div>
                                                  </div>
                                                  <div style="clear: both;">
                                                    <div class="property-description">'.$descriptionTitle.'</div>
                                                  </div>
                                                  <div class="description-value" style="clear: both;padding:5px;">'.$descriptionValue.'</div>';
                                                  // if($energyval){
                                                  if($this->results->EnergyGasLevel){
                                                    $EnergyGasLevel = $this->results->EnergyGasLevel;
                                                    echo '<div class="energygaslevel">';
                                                    if($energyval) {
                                                        if($EnergyGasLevel->EnergyRange) {
                                                            $sliderContent .= '<div class="range_left">
                                                                                    <table cellspacing="0" cellpadding="0">
                                                                                        <tr class="elementRow">
                                                                                            <td class="elementValue">Logement econome</td>
                                                                                            <td>Logement</td>
                                                                                        </tr>';
                                                            $i = 1;
                                                            $ranger=range('A','G');
                                                            foreach($EnergyGasLevel->EnergyRange as $rangekey => $range){ 
                                                                $class  = '';
                                                                $secimg = '';
                                                                $ecoVal = '';
                                                                $line   = '';
                                                                $valattr= '';
                                                                $symbol = '';

                                                                if(($range->min == '' && $energyval <= $range->max) || ($energyval > $range->min && $energyval <= $range->max) ||  ($range->max=='' && $energyval > $range->min)){

                                                                    $class  = 'slider_img';
                                                                    $secimg = 'slider_bg';
                                                                    $line   = 'dotline';
                                                                    $symbol = 'kWhm';
                                                                    $ecoVal = $energyval;
                                                                }
                                                                if($range->min==''){
                                                                    $valattr = '&le;'.' '.$range->max;
                                                                }
                                                                if($range->max==''){
                                                                    $valattr = '&gt;'.' '.$range->min;
                                                                }
                                                                if($range->min!='' && $range->max!=''){
                                                                    $valattr = $range->min.' to '.$range->max;
                                                                }
                                                                $sliderContent .=   '   <tr class="elementRow">
                                                                                            <td class="elementRow_left_td_1">
                                                                                                <div class="wrapper">
                                                                                                    <div class="elementValue'.$i.'">'.$valattr.'</div>
                                                                                                    <div class="elementArrow'.$i.'">'.$ranger[$rangekey].'</div>
                                                                                                </div>
                                                                                                <div class="'.$line.'"></div>
                                                                                            </td>
                                                                                            <td>
                                                                                                <div class="'.$class.'"></div>
                                                                                                <div class="'.$secimg.'">'.$ecoVal.'</div>
                                                                                                <div class="symbolval">'.$symbol.'</div>
                                                                                            </td>
                                                                                        </tr>';
                                                                $i++;
                                                            }
                                                            $sliderContent .=   '       <tr><td colspan="2">Logement energivore</td></tr>';
                                                            $sliderContent .=   '   </table>
                                                                                </div>';
                                                        }
                                                    }
                                                    if($emissionval){
                                                        if($EnergyGasLevel->EmissionRange){
                                                            $sliderContent .=   '<div class="range_right">
                                                                                    <table>
                                                                                        <tr class="elementRow"><td class="elementValue">Forte emission de GES</td><td>Logement</td></tr>';
                                                            $i = 1;
                                                            $ranger=range('A','G');

                                                            foreach($EnergyGasLevel->EmissionRange as $emikey => $emi){    

                                                                $class  = '';
                                                                $secimg = '';
                                                                $ecoVal = '';
                                                                $line   = '';
                                                                $valattr= '';
                                                                $symbol = '';
                                                                if(($emi->min=='' && $emissionval <= $emi->max) || ($emissionval > $emi->min && $emissionval <= $emi->max) ||  ($emi->max=='' && $emissionval > $emi->min)){
                                                                    $class  = 'slider_img';
                                                                    $secimg = 'slider_bg';
                                                                    $line   = 'dotline';
                                                                    $symbol = 'kWhmÃ‚Â²';
                                                                    $ecoVal = $emissionval;
                                                                }
                                                                if($emi->min==''){
                                                                    $valattr = '&le;'.' '.$emi->max;
                                                                }
                                                                if($emi->max==''){
                                                                    $valattr = '&gt;'.' '.$emi->min;
                                                                }
                                                                if($emi->min!='' && $emi->max!=''){
                                                                    $valattr = $emi->min.' to '.$emi->max;
                                                                }
                                                                $sliderContent .=   '   <tr class="elementRow">
                                                                                            <td class="elementRow_left_td_2">
                                                                                                <div class="wrapper">
                                                                                                    <div class="fieldValue'.$i.'">'.$valattr.'</div>
                                                                                                    <div class="fieldArrow'.$i.'">'.$ranger[$emikey].'</div>
                                                                                                </div>
                                                                                                <div class="'.$line.'"></div>
                                                                                            </td>
                                                                                            <td>
                                                                                                <div class="'.$class.'"></div>
                                                                                                <div class="'.$secimg.'">'.$ecoVal.'</div>
                                                                                                <div class="symbolval">'.$symbol.'</div>
                                                                                            </td>
                                                                                        </tr>';

                                                                $i++;
                                                            }
                                                            $sliderContent  .=  '       <tr><td colspan="2">Forte emission de GES</td></tr>';
                                                            $sliderContent  .=  '   </table>
                                                                                </div>';
                                                            echo $sliderContent;
                                                        }
                                                    }
                                                    echo '</div>';
                                                }      
                                        }
                                        echo '</div>';
                                    }
                                echo '</div>
                              </div>
                              <div class ="rt_property_viewmore"></div>
                         </div>';
               }
                
       


       //Enquiry Form:
        if(isset($_POST['enquiryButton'])){

                $this->results = $this->enquiry_sumbit();

        }

        ?>
        <div class="rt_enquiry">

        <form name="enquiryForm" id="enquiryForm" action="" method="post" class="enquiryform">
            <div class="rt_property_h2">

            <?php
                 _e('VIEWDETAIL_ENQUIRY_FORM',"realtransac");

            ?>
            </div>
             <div class="listenq">
                  <div class="rt_enquiry_form"><div class="error"><font color="green"><b class="enquireySuccess"><?php echo $this->enquiryresults->success; ?></b></font></div></div>
                  <div class="listenq_inner_left">
                          <div class="rt_enquiry_form">
                               <div class="label ">
                                <?php

                                    _e('GENERIC_NAME',"realtransac");

                                ?>
                                   <span style="color:red"> *</span></div>
                                   <div class="element"><input id="Name" name="Name" type="text" value="<?php if($this->enquiryresults->Name->val) { echo $this->enquiryresults->Name->val; } else { } ?>"/>
                                    <div class="namelengthError" style="color:red">
                                        <?php 

                                            _e('VIEWDETAIL_NAME_IS_EMPTY',"realtransac");

                                         ?>
                                    </div>
                                       <div class="error">
                                           <font color="red"><?php
                                                if($this->enquiryresults->Name->error){
                                                    echo $this->enquiryresults->Name->error;
                                                }?>
                                           </font>
                                        </div>
                                    </div>
                           </div>
                          <div class="rt_enquiry_form">
                             <div class="label ">
                                <?php

                                    _e('GENERIC_PHONE',"realtransac");

                                ?>
                                 <span style="color:red"> *</span></div>
                             <div class="element"><input id="phone" name="phone" type="text" value="<?php if($this->enquiryresults->Phone->val) { echo $this->enquiryresults->Phone->val; } else { } ?>"/>
                           <div class="phonelengthError" style="color:red">
                                <?php

                                    _e('VIEWDETAIL_PLEASE_ENTER_A_VALID_PHONE_NO',"realtransac");

                                ?>
                          </div>
                             <div class="error">
                             <font color="red"><?php
                                    if($this->enquiryresults->Phone->error){
                                      echo $this->enquiryresults->Phone->error;

                                }?></font>
                             </div>
                             </div>
                         </div>
                         <div class="rt_enquiry_form">
                             <div class="rt_enquiry_form_checkbox">
                             <?php

                                    _e('VIEWDETAIL_I_WOULD_LIKE_TO',"realtransac");

                             ?>
                             </div>
                            </div>

                           <div class="rt_enquiry_form">
                                   <div><input type="checkbox" value="1" name="contractsale" /><div class="rt_chkfav">

                                        <?php

                                            _e('VIEWDETAIL_ASK_FOR_THE_CONTACT_OF_THE_SELLER',"realtransac");

                                        ?>
                                       </div> </div>
                           </div>
                           <div class="rt_enquiry_form">
                                   <div><input type="checkbox" value="1" name="inspectproperty" /><div class="rt_chkfav">

                                    <?php

                                        _e('VIEWDETAIL_ASK_FOR_A_VISIT',"realtransac");

                                    ?>
                                   </div> </div>
                           </div>
                           <div class="rt_enquiry_form">
                                   <div><input type="checkbox" value="1" name="similarproperty" /><div class="rt_chkfav">
                                        <?php

                                            _e('VIEWDETAIL_BE_CONTACTED_ABOUT_SIMILAR_PROPERTIES',"realtransac");

                                        ?>
                                    </div> </div>
                            </div>
                  </div>
                  <div class="listenq_inner_right">
                           <div class="rt_enquiry_form">
                                <div class="label ">
                                    <?php 

                                        _e('GENERIC_EMAIL',"realtransac");

                                     ?>
                                    <span style="color:red"> *</span></div>
                                <div class="element"><input id="email" name="email" type="text" value="<?php if($this->enquiryresults->Email->val) { echo $this->enquiryresults->Email->val; } else { } ?>"/>
                                 <div class="emaillengthError" style="color:red">
                                    <?php

                                        _e('GENERIC_EMAIL_IS_EMPTY',"realtransac");

                                    ?>
                                </div>
                               <div class="error">
                                    <font color="red"><?php
                                        if($this->enquiryresults->Email->error){
                                          echo $this->enquiryresults->Email->error;

                                    }?></font>
                                </div>
                                </div>
                            </div>
                            <div class="rt_enquiry_form">
                                <div class="label ">
                                     <?php _e('VIEWDETAIL_ABOUT_ME',"realtransac");?>
                                    <span style="color:red"> *</span></div>
                                <div class="element">
                                        <select id="about" name="about">
                                             <option value="" selected><?php _e('GENERIC_CHOOSE',"realtransac"); ?></option>
                                             <option value="1"><?php _e('VIEWDETAIL_I_ALREADY_OWN_A_PROPERTY',"realtransac"); ?> </option>
                                             <option value="2"><?php _e('VIEWDETAIL_I_AM_RENTING',"realtransac");?></option>
                                             <option value="3"><?php _e('VIEWDETAIL_I_HAVE_RECENTLY_SOLD',"realtransac"); ?></option>
                                             <option value="4"><?php _e('VIEWDETAIL_I_AM_FIRST_HOME_BUYER',"realtransac"); ?></option>
                                             <option value="5"><?php _e('VIEWDETAIL_I_AM_AN_INVESTOR',"realtransac"); ?></option>
                                             <option value="6"><?php _e('VIEWDETAIL_I_AM_MONITORING_THE_MARKET',"realtransac"); ?> </option>
                                         </select>
                                         <div class="aboutlengthError" style="color:red">
                                                 <?php

                                                _e('VIEWDETAIL_PLEASE_CHOOSE_ONE_OPTION',"realtransac");

                                                ?>
                                         </div>
                                    <div class="error">
                                        <font color="red"><?php
                                            if($this->enquiryresults->About->error){
                                              echo $this->enquiryresults->About->error;

                                        }?>
                                       </font>
                                    </div>
                                </div>
                            </div>
                            <div class="rt_enquiry_form">                         
                                <span class="enquire_title">
                                        <?php

                                            _e('VIEWDETAIL_WRITE_BELOW_YOUR_MESSAGE',"realtransac");

                                        ?>
                                </span><br />
                                <textarea id="comments" name="comments" rows="7" cols="64"></textarea>
                         </div>
                  </div>
                  <div class="clear"></div>
                  <div class="listenq_inner_left">
                      <div class="rt_enquiry_form">
                            <p>
                            <img src="<?php echo $this->urlaction; ?>?type=enquiry&rand=<?php echo rand(); ?>" id='viewcaptchaimg' >
                             <a class="captcha-ref" href='javascript: viewrefreshCaptcha();'></a>    
                             <br>
                            
                            <label for='message' class="captcha_label">

                                <?php

                                        _e('VIEWDETAIL_ENTER_THE_ABOVE_CODE_HERE',"realtransac");

                                ?>

                            <span style="color:red"> *</span></label>
                            <input id="view_letters_code" name="view_letters_code" value="" class ="" type="text"><br>
                                 <div class="captchalengthError" style="color:red">
                                        <?php

                                            _e('GENERIC_CAPTCHA_IS_EMPTY',"realtransac");

                                        ?>
                                </div>
                                <div class="error">
                                    <font color="red">
                                    <?php
                                    if($this->viewcaptchaerror){

                                        _e('VIEWDETAIL_INVALID_CAPTCHA',"realtransac");
                                    }
                                    ?>
                                    </font>
                                </div>                        
                      </div>
                    </div>
                      <!-- -->
                      <div class="rt_search_button listenq_inner_right">
                        <input type="hidden" name="enquiryButton" value="Send Email"/>
                        <a class="viewbutton" id="enquiryButton">
                            <span class="btnleft"></span>
                            <span class="btncenter">
                                <?php

                                    _e('GENERIC_SEND_EMAIL',"realtransac");

                                 ?>
                            </span>
                            <span class="btnright"></span>
                        </a>
                        <div class="clear"></div>
                     </div>    
              </div>
            </form>
            </div>
        
    <?php
    echo '</div>';
    }else {
        echo '<div class = "property_notfound">'.$this->results->error.'</div>';
    }

    echo '</div>';
           
   } 
} 
?>