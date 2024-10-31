<?php
require_once(dirname(__FILE__).'/search.php');

class Realtransac_API_AdvancedSearch extends Realtransac_API_Search {
    
    public function __construct($instance, $widget_id = ''){
        global  $rt_config;
        $this->plugver  = plugin_get_version();
        $this->widget   = $widget_id;
        $this->display  = $instance['displayasearchform'];
        $this->pageid   = $instance['pageid'];
        $this->designoption   = get_option('plugindesign');
        $this->wsdl     = $rt_config['wsdl'];
        $this->apikey   = $rt_config['apikey'];
        $this->ip       = $rt_config['ip'];
        $this->client   = $rt_config['client'];
        $this->pageType = $rt_config['pageType'];
        $this->viewID   = $rt_config['viewdetailid'];
        $this->qtranslate = false;
        $this->mls_show   = $rt_config['mls_show'];

        $this->permalink = get_permalink($this->pageid);
        $this->detailLink    = get_permalink($this->viewID);
                      
        if ( function_exists( 'qtrans_generateLanguageSelectCode' ) ){					
            $this->qtranslate   = true;  
            $this->lang         = qtrans_getLanguage();
            $this->permalink    = qtrans_convertURL($this->permalink);
            $this->detailLink   = qtrans_convertURL($this->detailLink);
          
        }else{
            $this->lang         = $rt_config['language'];
        }
                 
        $this->setSearchSession();         

    }
    
        // TO DISPLAY FORM 
    public function displaySearchForm() {
        
        global  $rt_config;
        $urlaction    =     home_url('/'); 
        echo '<div class="rt_advanced rt_widget_content">';
        ?> 

        <script>
            jQuery.noConflict();
           
            var apikey         = '<?php echo $this->apikey; ?>';
            var wsdl           = '<?php echo $this->wsdl; ?>';
            var ajax_url       = '<?php echo plugins_url('ajaxcall.php' , __FILE__).'/?lang='.$this->lang; ?>';
            
            var price_min   = '<?php echo $this->params['START']['PRICE']['MIN'] ?>';
            var price_max   = '<?php echo $this->params['START']['PRICE']['MAX'] ?>';
            var price_step  = '<?php echo $this->params['START']['PRICE']['STEP'] ?>';
            
            var bed_min     = '<?php echo $this->params['START']['BED']['MIN'] ?>';
            var bed_max     = '<?php echo $this->params['START']['BED']['MAX'] ?>';
            var bed_step    = '<?php echo $this->params['START']['BED']['STEP'] ?>';
                        
            var distance_min   = '<?php echo $this->params['START']['DISTANCE']['MIN'] ?>';
            var distance_max   = '<?php echo $this->params['START']['DISTANCE']['MAX'] ?>';
            var distance_step  = '<?php echo $this->params['START']['DISTANCE']['STEP'] ?>';
            //var distance_val = '<?php //echo $this->params['START']['DISTANCE']['VAL'] ?>';
            var stepVal        = '<?php echo $this->params['START']['PRICE']['MULTIPLE'] ?>';
            var language       = '<?php echo $this->lang; ?>';
            
            var isLoading = false;
             
            jQuery(document).ready(function(){
                function decimalFloorRounding(number) {
                    if(jQuery.isNumeric(number)) {
                        number          =   jQuery.trim(number);
                        returnNumber    =   number;
                        numberLength    =   number.length;
                        if(numberLength>1) {
                            if(numberLength <= 3) {
                                lastIndex   =   numberLength - 1;
                                count       =   1;
                            } else if(numberLength==4 || numberLength==5) {
                                lastIndex   =   numberLength - 2;
                                count       =   2;
                            } else if(numberLength > 5) {
                                lastIndex   =   numberLength - 3;
                                count       =   3;
                            }
                            returnNumber   =   number.substr(0,lastIndex);
                            for(iteration=1;iteration<=count;iteration++) {
                                returnNumber  +=   '0';
                            }
                        }
                        return returnNumber;
                    }
                    return false;
                }
                function processPriceDropDown(data) {
                    var optionArray     =   new Array();
                    var apminOptions    =   '';  
                    var apmaxOptions    =   '';                            
                    var dMinPrice       =   parseInt(data.defaultPrice.min);
                    var dMaxPrice       =   parseInt(data.defaultPrice.max);
                    var dStepValue      =   parseInt(data.defaultPrice.step);
                    var apminValue      =   parseInt(data.price.min);
                    var apmaxValue      =   parseInt(data.price.max);
                    var apstepValue     =   parseInt(data.price.step);
                    var priceText       =   apminValue;
                    var priceVal        =   dMinPrice;
                    var firstValue      =   new Array();
                    firstValue['value'] =   dMinPrice;
                    firstValue['text']  =   apminValue;

                    optionArray.push(firstValue);
                    while(priceVal < dMaxPrice) {
                        priceText      +=   apstepValue;
                        priceVal       +=   dStepValue;
                        var loopValue   =   new Array();
                        loopValue['value']  =   priceVal;
                        loopValue['text']   =   priceText;
                        optionArray.push(loopValue);
                    }

                    jQuery.each(optionArray, function(index, value) {
                        optionText  =   decimalFloorRounding(value['text']);
                        if(parseInt(data.price.ushow) == 1){
                            apminOptions += '<option value="' + value['value'] + '">' + data.price.uval +' '+ optionText + '</option>';
                        }else{
                            apminOptions += '<option value="' + value['value'] + '">' + optionText +' '+ data.price.uval + '</option>';
                        }
                    });

                    optionArray.shift();    //To remove first indexed value from max drop down
                    jQuery.each(optionArray, function(index, value) {
                        var more     =  '';   
                        var selected =  '';
                        if(value['value'] >= dMaxPrice) {
                            more  =  '+';
                        }
                        if(dMaxPrice == value['value']) {
                            selected = 'selected';
                        }
                        optionText  =   decimalFloorRounding(value['text']);
                        if(parseInt(data.price.ushow) == 1) {
                            apmaxOptions += '<option value="' + value['value'] +'" '+ selected +'>' + data.price.uval +' '+ optionText + more + '</option>';
                        }else{
                            apmaxOptions += '<option value="' + value['value'] +'" '+ selected +'>' + optionText +' '+ data.price.uval + more + '</option>';
                        }
                    });
                    jQuery("select#aprice_min").html(apminOptions);
                    jQuery("select#aprice_max").html(apmaxOptions);
                    jQuery("#apriceMIN").val(dMinPrice);
                    jQuery("#apriceMAX").val(dMaxPrice);
                    jQuery("#aprice_step").val(data.price.step);
                    jQuery("#aprice_uval").val(data.price.uval);
                    jQuery("#aprice_ushow").val(data.price.ushow);
                    jQuery("#aprice_hmax").val("0");
                }
              function ajaxReqcountry(){
                    isLoading = true;
                    jQuery('.rt_search_wrapper .btn-outer').addClass("rt_opacity");
                    jQuery.ajax({ 
                        url: ajax_url,
                        type: "POST",
                        dataType:"json",
                        data: {action: 'dynamicvalues',  id: jQuery('#alocation').val(),countryId: jQuery('#asearchcountry').val(),  typeid:'', category:'', language:language, ajax: 'true', level:6, apikey: apikey, wsdl: wsdl, ignoreAll:1, isPortal:1},

                        success: function(data){
                            var options = '';
                            
                            //TYPE
                            var options = '';
                            jQuery.each(data.type, function(key, val) {
                                options += '<option value="' + key + '">' + val + '</option>';
                            });
                            jQuery("select#atype").html(options);
                            
                            //CATOGERY
                            var options    = '';
                            jQuery.each(data.category, function(key, val) {
                            options += '<option value="' + key + '">' + val + '</option>';
                            });
                            jQuery("select#acategory").html(options);                           
                            
                            //AREA 
                            var area_minOptions    = '';  
                            var area_maxOptions    = '';  
                            var options    = '';  
                            var area_minValue = parseInt(data.area.min);
                            var area_maxValue = parseInt(data.area.max);
                            var area_step     = parseInt(data.area.step);
                            var count = 0;
                          
                           //AREA MIN
                           for(var val=area_minValue;val<=area_maxValue - 1;val+=area_step){

                                var more     = ''; 
                                var selected = '';

                                 if(area_minValue == val){
                                        selected = 'selected';
                                   }

                                area_minOptions += '<option value="' + val + '" '+ selected +'>' + val + data.area.uval + '</option>';
                                count++;
                            }
                            //AREA MAX
                            for(var val=area_minValue;val<=area_maxValue;val+=area_step) {
                                var more     = ''; 
                                var selected = '';
                                if(val == area_maxValue) {
                                    more = '+';
                                }  
                                if(area_maxValue == val) {
                                        selected = 'selected';
                                   }
                                if(val != 0) {
                                   area_maxOptions += '<option value="' + val + '" '+ selected +'>' + val + data.area.uval + more +'</option>';
                                 }
                                count++;
                            }                            

                            jQuery("select#asurface_min").html(area_minOptions);
                            jQuery("select#asurface_max").html(area_maxOptions);
                            jQuery("#asurface_uval").val(data.area.uval);
                            jQuery("#asurface_step").val(data.area.step);
                            jQuery("#asurface_unit").val(data.area.unit);
                            
                            
                            /* PRICE DROP DOWN STARTS */
                            processPriceDropDown(data);
                            /* PRICE DROP DOWN ENDS */
                            
                            jQuery('#atype,#acategory,#asurface_min,#asurface_max,#aprice_min,#aprice_max').removeAttr('disabled');
                            jQuery('.rt_search_wrapper .btn-outer').removeClass("rt_opacity");
                            isLoading = false;
                        }
                    });
                }
            
              function ajaxReqType()
              {
                 isLoading = true;
                 jQuery('.rt_search_wrapper .btn-outer').addClass("rt_opacity");
                 jQuery.ajax({ 
                    url: ajax_url,
                    type: "POST",
                    dataType:"json",
                    data: {action: 'dynamicvalues',  id: jQuery('#alocation').val(), countryId: jQuery('#asearchcountry').val(), typeid: jQuery('#atype').val(), category:jQuery('#acategory').val(), ajax: 'true', language:language, level:6, apikey: apikey, wsdl: wsdl, ignoreAll:1, isPortal:1},
                    success: function(data) {
                            var options = '';

                            jQuery.each(data.category, function(key, val) {
                            options += '<option value="' + key + '">' + val + '</option>';
                            });
                            jQuery("select#acategory").html(options);
                            
                            /* PRICE DROP DOWN STARTS */
                            processPriceDropDown(data);
                            /* PRICE DROP DOWN ENDS */
                            
                            jQuery('#acategory,#aprice_min,#aprice_max').removeAttr('disabled');
                            jQuery('.rt_search_wrapper .btn-outer').removeClass("rt_opacity");
                            isLoading = false;
                    }
                });

                }

            function ashowAreaRange()
            { 

                isLoading = true;
                jQuery('.rt_search_wrapper .btn-outer').addClass("rt_opacity");
                jQuery.ajax({           
                    type: "POST",
                    url: ajax_url,
                    dataType:"json",
                    data: {action: 'dynamicvalues', category: jQuery('#acategory').val(), typeid: jQuery('#atype').val(), id: jQuery('#alocation').val(), countryId: jQuery('#asearchcountry').val(), language:language, ajax: 'true', level:3, apikey: apikey, wsdl: wsdl, ignoreAll:1, isPortal:1},
                    success: function(data){  

                       //AREA 
                            var area_minOptions    = '';  
                            var area_maxOptions    = '';  
                            var options    = '';  
                            var area_minValue = parseInt(data.area.min);
                            var area_maxValue = parseInt(data.area.max);
                            var area_step     = parseInt(data.area.step);
                            var count = 0;
                            
                           //AREA MIN
                           for(var val=area_minValue;val<=area_maxValue - 1;val+=area_step){

                                var more     = ''; 
                                var selected = '';

                                if(area_minValue == val){
                                    selected = 'selected';
                                }
                               
                                area_minOptions += '<option value="' + val + '" '+ selected +'>' + val + data.area.uval +'</option>';
                                count++;
                            }
                            //AREA MAX
                            for(var val=area_minValue;val<=area_maxValue;val+=area_step){

                                var more     = ''; 
                                var selected = '';
                                if(val == area_maxValue){
                                    more = '+';
                                }  
                              
                                if(area_maxValue == val){
                                        selected = 'selected';
                                   }
                               if(val != 0){
                                area_maxOptions += '<option value="' + val + '" '+ selected +'>' + val + data.area.uval + more +'</option>';
                               }
                                count++;
                            }
                             jQuery('#asurface_min').removeAttr('disabled');
                             jQuery('#asurface_max').removeAttr('disabled');
                             
                             jQuery("select#asurface_min").html(area_minOptions);
                             jQuery("select#asurface_max").html(area_maxOptions);
                             jQuery("#asurface_uval").val(data.area.uval);
                             jQuery("#asurface_step").val(data.area.step);
                             jQuery("#asurface_unit").val(data.area.unit);
                             jQuery('.rt_search_wrapper .btn-outer').removeClass("rt_opacity");
                             isLoading = false;
                    }
                }); 

            }
            
            function ashowAreaUnits(val1, val2, element, isrange)
            {                
                var aunit = jQuery('#asurface_uval').val();
                if(isrange){
                    element.html(val1+' '+aunit+' - '+val2+' '+aunit);
                }else{
                    element.html(val1+' '+aunit);
                }
            }
            
            function showArea(area, element, isrange, unit)
            {             
                ele = element;
                range = isrange;                

                jQuery.ajax({ 
                    url: ajax_url,
                    type: "POST",                    
                    data: {action: 'formatarea', area: area, isrange: range, unit: unit, apikey: apikey, wsdl: wsdl},
                    success: function(data) {
                        ele.html(data);
                    }
                });
            }
            
            //VALIDATION START    
            jQuery("#asearchForm<?php echo $this->widget; ?>").validate({
                showErrors: function(errorMap, errorList) {
                    this.defaultShowErrors();
                }
            });

            jQuery.extend(jQuery.validator.messages, {
                required: "<?php _e('GENERIC_REQUIRED','realtransac'); ?>"
            });

            jQuery('#asearchButton<?php echo $this->widget; ?>').click(function(event){                
                  if(!isLoading){
                     jQuery('#asearchForm<?php echo $this->widget; ?>').submit();
                 }
            });



            jQuery("select#asearchcountry").change(function(){
                jQuery('#atype,#acategory,#asurface_min,#asurface_max,#acategory,#aprice_min,#aprice_max').attr('disabled', 'disabled');
                jQuery('#asearchcountry option:selected').text(); 
                jQuery("#acountryname").val(jQuery("#asearchcountry").find("option:selected").text()); // Set country name.
                jQuery("#asurface_hmax").val(0);
                ajaxReqcountry();
            });



            jQuery('select#atype').change(function(){
                jQuery('#acategory,#aprice_min,#aprice_max').attr('disabled', 'disabled');
                ajaxReqType();
            });

            jQuery('select#acategory').change(function(){
                jQuery('#asurface_min,#asurface_max').attr('disabled', 'disabled');
                jQuery("#asurface_hmax").val(0);
                ashowAreaRange();
            });

            jQuery("#abed_min").change(function() {
                var bed = jQuery(this).val();
                var lastIndex = jQuery('option:last', '#abed_min').val();
                if(bed == lastIndex){
                    bed = 0;
                    jQuery("#abed_hmax").val(bed);
                }
                
                if(jQuery('#abed_max').val() < jQuery(this).val()){
                    jQuery('#abed_max').val(jQuery(this).val());
                }
            });

            jQuery("#abed_max").change(function() {
                var bed = jQuery(this).val();
                var lastIndex = jQuery('option:last', '#abed_max').val();
                if(bed == lastIndex){
                bed = 0;
                }
                jQuery("#abed_hmax").val(bed);
                
                if(jQuery('#abed_min').val() > jQuery(this).val()){
                    jQuery('#abed_min').val(jQuery(this).val());
                }
            });

            jQuery("#abath_min").change(function() {
                var bath = jQuery(this).val();
                var lastIndex = jQuery('option:last', '#abath_min').val();
                if(bath == lastIndex){
                    bath = 0;
                    jQuery("#abath_hmax").val(bath);
                }
                
                if(jQuery('#abath_max').val() < jQuery(this).val()){
                    jQuery('#abath_max').val(jQuery(this).val());
                }
            });

            jQuery("#abath_max").change(function() {
                var bath = jQuery(this).val();
                var lastIndex = jQuery('option:last', '#abath_max').val();
                if(bath == lastIndex){
                    bath = 0;
                }
                jQuery("#abath_hmax").val(bath);
                
                if(jQuery('#abath_min').val() > jQuery(this).val()){
                    jQuery('#abath_min').val(jQuery(this).val());
                }
            });

             jQuery("#aprice_min").change(function(){
                /*
                if((parseInt(jQuery(this).val()) >= parseInt(jQuery('#apriceMAX').val())) || (parseInt(jQuery(this).val()) <= parseInt(jQuery('#apriceMIN').val())) || (parseInt(jQuery(this).val()) == parseInt(jQuery('#aprice_max').val())) || (parseInt(jQuery(this).val()) == 0)){

                var selectedIndex = jQuery('option:selected', '#aprice_min').index();
                var newmax = selectedIndex + (stepVal - 1);
                var hmax = 0;

                if(jQuery('#aprice_max option')[newmax]){
                jQuery('#aprice_max option')[newmax].selected = true;
                jQuery('#apriceMAX').val(jQuery('#aprice_max').val());
                jQuery('#apriceMIN').val(jQuery(this).val());

                }else{
                var lastIndex = jQuery('option:last', '#aprice_max').index();
                jQuery('#aprice_max option')[lastIndex].selected = true;
                jQuery('#apriceMAX').val(jQuery('#aprice_max').val());
                jQuery('#apriceMIN').val(jQuery(this).val());
                }

                if(parseInt(jQuery('select#aprice_max').val()) < parseInt(price_max)){
                hmax = jQuery('select#aprice_max').val();
                }
                jQuery('#aprice_hmax').val(hmax);

                }
                */
                var price     = jQuery(this).val();
                var lastIndex = jQuery('option:last', '#aprice_min').val();
                if(price == lastIndex){
                    price = 0;
                    jQuery("#aprice_hmax").val(price);
                }

                if(parseInt(jQuery('#aprice_max').val()) < parseInt(jQuery(this).val())){
                    jQuery('#aprice_max').val(jQuery(this).val());
                }
            });

            jQuery("#aprice_max").change(function(){
                
                /*
                if((parseInt(jQuery(this).val()) <= parseInt(jQuery('#apriceMIN').val())) || (parseInt(jQuery(this).val()) >= parseInt(jQuery('#apriceMAX').val())) || (parseInt(jQuery(this).val()) == parseInt(jQuery('#aprice_min').val())) || (parseInt(jQuery(this).val()) == 0)){

                var selectedIndex = jQuery('option:selected', '#aprice_max').index();
                var newmin = selectedIndex;
                if(selectedIndex){
                newmin = (selectedIndex + 1) - stepVal;
                }

                if(jQuery('#aprice_min option')[newmin]){
                jQuery('#aprice_min option')[newmin].selected = true;
                jQuery('#apriceMIN').val(jQuery('#aprice_min').val());
                jQuery('#apriceMAX').val(jQuery(this).val());

                }else{
                var firstIndex = jQuery('option:first', '#aprice_min').index();
                jQuery('#aprice_min option')[firstIndex].selected = true;
                jQuery('#apriceMIN').val(jQuery('#aprice_min').val());
                jQuery('#apriceMAX').val(jQuery(this).val());
                }
                }
                */
                var price     = jQuery(this).val();
                var lastIndex = jQuery('option:last', '#aprice_max').val();
                if(price == lastIndex){
                    price = 0;
                }
                jQuery("#aprice_hmax").val(price);
                    
                if(parseInt(jQuery('#aprice_min').val()) > parseInt(jQuery(this).val())){
                    jQuery('#aprice_min').val(jQuery(this).val());
                }
            });
            
            jQuery("#asurface_min").change(function() {
                var area = jQuery(this).val();
                var lastIndex = jQuery('option:last', '#asurface_min').val();
                if(area == lastIndex){
                    area = 0;
                    jQuery("#asurface_hmax").val(area);
                }
                
                if(parseInt(jQuery('#asurface_max').val()) < parseInt(jQuery(this).val())){
                    jQuery('#asurface_max').val(jQuery(this).val());
                }

            });

            jQuery("#asurface_max").change(function() {
                var area = jQuery(this).val();
                var lastIndex = jQuery('option:last', '#asurface_max').val();
                if(area == lastIndex){
                    area = 0;
                }
                jQuery("#asurface_hmax").val(area);
                
                if(parseInt(jQuery('#asurface_min').val()) > parseInt(jQuery(this).val())){
                    jQuery('#asurface_min').val(jQuery(this).val());
                }
            });

            jQuery("#aispicture").click(function(){
                    if(jQuery(this).is(':checked')) {
                    jQuery(this).val(1);
                    }else{

                    jQuery(this).val(0);
                    }
                 // startFilter();
            });

          //Distance Slider

            jQuery("#distance-range<?php echo $this->widget;?>").slider({
                min: parseInt(distance_min),
                max: parseInt(distance_max),
                step: parseInt(distance_step),
                value: parseInt(jQuery('#distance').val()*10),
                stop : function(event, ui) {

                    jQuery("#distance").val(ui.value/10);
                    jQuery(".volumeimage_hover").css('width', ui.value*13/5);
                    jQuery("#distanceDiv<?php echo $this->widget;?>").html(ui.value);
                    showArea(ui.value, jQuery("#distanceDiv<?php echo $this->widget;?>"), false, jQuery('#distance_unit').val());
                    updateCircle();
                }
            });

            jQuery(".volumeimage_hover").css('width',jQuery("#distance-range<?php echo $this->widget;?>").slider("value")*13/5);

            
        });
            
        </script>
        
        <?php 

	if($this->error){
             echo "<div  class='rt_search_wrapper'>";
                    echo '<div class="InvalidSub" style="color: #F4F4F7; font-size: 15px; font-weight: bold; text-align: center;">'.$this->error.'</div>';
             echo "</div>";
         }else{
	        $display_class = 'rt_vertical';
	    if($this->display == '2'){
                $display_class = 'rt_horizontal';
            }                                                
       	?>
        <form id="asearchForm<?php echo $this->widget; ?>" name="asearchForm" class="asearchForm rtForm <?php echo $display_class;?>"  method="post" action="<?php echo $this->permalink; ?>">
        <div  class="rt_search_wrapper">
            
            <?php if($this->mls_show == '2'){ ?>
                <div  class="rt_search_row rt_search_down">	                      
                    <div class="label">
                        <?php _e('GENERIC_COUNTRY',"realtransac"); ?>
                    </div>                      
                    <div class="drop_down_list">
                        <select id="asearchcountry" name="asearchcountry"> 
                        <?php                                
                        foreach ($this->params['COUNTRIES'] as $key => $val) {
                            if($this->params['COUNTRYKEY'] == $key){
                                echo '<option value="'.$key.'" selected>'.$val.'</option>';
                            }else{
                                echo '<option value="'.$key.'">'.$val.'</option>';
                            }
                        }
                        ?>
                        </select>
                    </div>
                </div>
            <?php } ?> 
            
            <div class="rt_search_row">	
                        <div class="label">
                            <?php _e('GENERIC_LOCATION',"realtransac"); ?>
                        </div>
                        <div class="element">
                            <?php if(empty($this->params['LOCALBOX'])){ ?>
                                <input id="alocalbox" name="alocalbox"  class="required" type="text" value="" >
                            <?php } else { ?>
                                <input id="alocalbox" name="alocalbox"  type="text" class="required" value="<?php echo $this->params['LOCALBOX'];?>" >
                            <?php }  ?>

                           <input id="alocation" name="alocation" type="hidden" value="<?php echo $this->params['LOCATION'];?>"/>
                        </div>
                       
             </div> 
            
            <div  class="rt_search_row">     
                <div class="label_Perimeter">
                       <?php _e('ADVANCEDSEARCH_PERIMETER',"realtransac"); ?>
                </div>
                <div class="element_Perimeter">
                    <div class="leftimage"></div>
                    <div class="volumeimage">
                        <div class="volumeimage_hover"></div>
                    </div>
                    <div class="demos_distance">
                        <?php
                        
                        $dmin   = $this->params['START']['DISTANCE']['MIN'];
                        $dmax   = $this->params['START']['DISTANCE']['MAX'];
                        $dstep  = $this->params['START']['DISTANCE']['STEP'];
                        $duval  = $this->params['DISTANCE']['UVAL'];
                        $dunit  = $this->params['DISTANCE']['UNIT'];
                        $dval   = $this->params['DISTANCE']['VAL'];
                        $dvalue = $dval*10;
                        ?>
                        <div id="distance-range<?php echo $this->widget;?>"></div>
                        <input type="hidden" id="distance_min" name="distance_min" value="<?php echo $dmin;?>" />
                        <input type="hidden" id="distance_max" name="distance_max" value="<?php echo $dmax;?>" />
                        <input type="hidden" id="distance_step" name="distance_step" value="<?php echo $dstep;?>" />
                        <input type="hidden" id="distance" name="distance" value="<?php echo $dval;?>" />
                        <input type="hidden" id="distance_unit" name="distance_unit" value="<?php echo $dunit;?>" />
                        <input type="hidden" id="distance_uval" name="distance_uval" value="<?php echo $duval;?>" />
                    </div ><!-- End demo -->
                    <div> <span id="distanceDiv<?php echo $this->widget; ?>"><?php echo $dvalue.' '.$duval;?></span></div>
                </div>
            </div> 
            
            <div  class="rt_search_row">	                      
                <div class="label">
                    <?php _e('GENERIC_TYPE',"realtransac"); ?>
                </div>                      
                <div class="drop_down_list">
                    <select id="atype" name="atype"> 
                    <?php                                
                    foreach ($this->params['TYPES'] as $key => $val) {
                        if($this->params['TYPE'] == $key){
                            echo '<option value="'.$key.'" selected>'.$val.'</option>';
                        }else{
                            echo '<option value="'.$key.'">'.$val.'</option>';
                        }
                    }
                    ?>
                    </select>
                </div>
            </div>
            
            <div  class="rt_search_row">
                <div class="label">
                   <?php _e('GENERIC_CATEGORY',"realtransac"); ?>
                </div>                    
                <div class="drop_down_list">
                    <select id="acategory" name="acategory">		
                    <?php
                    foreach ($this->params['CATEGORIES'] as $key=>$val) {
                        if($this->params['CATEGORY'] == $key){
                            echo '<option value="'.$key.'" selected>'.$val.'</option>';
                        }else{
                            echo '<option value="'.$key.'">'.$val.'</option>';
                        }
                    }
                    ?>   
                    </select>
                </div>
            </div>
            
           <div  class="rt_search_row">
                                <!--div class="label_Pictures">Pictures:</div-->
                                <div class="element_Pictures">
                                    <?php
                                    if($this->params['ISPICTURE']){
                                        $checked = 'checked';
                                        $value   = 1;
                                    }
                                    else{
                                        $checked = '';
                                        $value   = 0;
                                    }
                                    ?>
                                    <input type="checkbox" <?php echo $checked; ?> name="aispicture" id="aispicture" value="<?php echo $value; ?>"/>
                                    
                                    <div class="chkpic">
                                        <?php _e('ADVANCEDSEARCH_ONLY_WITH_PICTURES',"realtransac"); ?>
                                    </div>
             </div>
             </div>
            
            <div  class="rt_search_slider">	
                <div class="label">
                     <?php _e('GENERIC_PRICE',"realtransac"); ?>
                </div>
                <div class="element">
                    <div class="demo">
                        <?php
                            $price_hmax     =   '';
                            $dprice_min     =   $this->params['START']['PRICE']['MIN'];
                            $dprice_max     =   $this->params['START']['PRICE']['MAX'];
                            $dprice_step    =   $this->params['START']['PRICE']['STEP'];
                            
                            $defaultPriceMin    =   $this->params['DEFAULT_PRICE']['MIN'];
                            $defaultPriceMax    =   $this->params['DEFAULT_PRICE']['MAX'];
                            $defaultPriceStep   =   $this->params['DEFAULT_PRICE']['STEP'];
                            
                            $price_min      =   $this->params['PRICE']['MIN'];
                            $price_max      =   $this->params['PRICE']['MAX'];
                            $price_uval     =   $this->params['START']['PRICE']['VAL'];
                            $price_ushow    =   $this->params['PRICE']['USHOW'];
                            if(isset($this->params['PRICE']['HMAX'])) {
                                $price_hmax =   $this->params['PRICE']['HMAX'];
                            }
                            $priceVal   =   $dprice_min;
                            $priceIndex =   $defaultPriceMin;
                            $pminVal    =   array('0' => array('value' => '0', 'text' => '0'));
                            while($priceIndex < $defaultPriceMax) {
                                $priceIndex +=  $defaultPriceStep;
                                $priceVal   +=  $dprice_step;
                                $optionText  =  $this->decimalFloorRounding($priceVal);
                                $pminVal[]   =  array('value' => $priceIndex, 'text' => $optionText);
                            }
                        ?>
                        <div id="aprice_range">
                            <div class="advanceMin rangemin">
                                <div id="mm">
                                    <?php _e('GENERIC_MIN',"realtransac"); ?>
                                </div>
                                <div class="drop-down-outer">
                                    <select id="aprice_min" name="aprice_min">                     
                                        <?php
                                            foreach ($pminVal as $key => $val){
                                                $nextIndex  =   $key + 1;
                                                $selected   =   '';
                                                if($price_min == $val['value'] || ($val['value']<$price_min && $price_min<$pminVal[$nextIndex]['value'])) {
                                                    $selected = 'selected';
                                                }
                                                if($price_ushow == 1) {
                                                    echo '<option value="'.$val['value'].'" '.$selected.'>'.$price_uval.' '.$val['text'].'</option>';
                                                } else {
                                                    echo '<option value="'.$val['value'].'" '.$selected.'>'.$val['text'].' '.$price_uval.'</option>';
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="advanceMax rangemax">
                                <div id="mmxx">
                                    <?php _e('GENERIC_MAX',"realtransac"); ?>
                                </div> 
                                <div class="drop-down-outer">
                                    <select id="aprice_max" name="aprice_max">
                                        <?php
                                        unset($pminVal[0]);
                                        foreach ($pminVal as $key => $val) {
                                            $prevIndex  =   $key - 1;
                                            $pmore      =   '';
                                            $selected   =   '';
                                            if($val['value'] >= $defaultPriceMax) {
                                                $pmore  =   '+';
                                            }
                                            $selectMax  =   $price_min + ($dprice_step * $this->params['START']['PRICE']['MULTIPLE']);
                                            if($price_max != ''){
                                                if($price_max == $val['value'] || ($val['value']>$price_max && $price_max>$pminVal[$prevIndex]['value'])) {
                                                    $selected = 'selected';
                                                }
                                                $PRICEMAX = $price_max;
                                            }else{
                                                if($selectMax == $val['value']){
                                                    $selected = 'selected';
                                                }
                                                $PRICEMAX = $selectMax;
                                            }

                                            if($price_ushow == 1){
                                                echo '<option value="'.$val['value'].'" '.$selected.'>'.$price_uval.' '.$val['text'].$pmore.'</option>';
                                            }else{
                                                echo '<option value="'.$val['value'].'" '.$selected.'>'.$val['text'].' '.$price_uval.$pmore.'</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                        </div>
                        <input type="hidden" id="apriceMIN" name="apriceMIN" value="<?php echo $price_min; ?>"/>
                        <input type="hidden" id="apriceMAX" name="apriceMAX" value="<?php echo $PRICEMAX; ?>"/>
                        <?php if($price_hmax != '' || $price_hmax == '0'){ ?>
                                <input type="hidden" id="aprice_hmax" name="aprice_hmax" value="<?php echo $price_hmax; ?>"/>
                        <?php }else { ?>
                                <input type="hidden" id="aprice_hmax" name="aprice_hmax" value="<?php echo $PRICEMAX; ?>"/>
                        <?php } ?>
                        <input type="hidden" id="aprice_uval" name="aprice_uval" value="<?php echo $price_uval; ?>"/>
                        <input type="hidden" id="aprice_ushow" name="aprice_ushow" value="<?php echo $price_ushow; ?>"/>
                        <input type="hidden" id="aprice_step" name="aprice_step" value="<?php echo $dprice_step; ?>"/>
                    </div>
                </div>
            </div> 
            </div>
            <div  class="rt_search_slider">	             
                <div class="label">
                        <?php _e('GENERIC_BEDROOMS',"realtransac"); ?>
                </div>
                <div class="element">                    
                     <div id="abedroom_range">
                        <?php

                            $bed_min    =  $this->params['START']['BED']['MIN'];
                            $bed_max    =  $this->params['START']['BED']['MAX'];
                            $bed_step   =  $this->params['START']['BED']['STEP'];
                            $bed_hmax   =  $this->params['BED']['HMAX'];

                            $bedVal = $bed_min;
                            $minVal = array(0);
                            while($bedVal != $bed_max - $bed_step){
                            $bedVal += $bed_step;
                            $minVal[] = $bedVal;
                            }
                        ?>
                        <div class="advanceMin rangemin">
                            <div id="mm">
                                <?php _e('GENERIC_MIN',"realtransac"); ?>
                            </div>
                            <div class="drop-down-outer">
                                <select id="abed_min" name="abed_min">
                                <?php
                                foreach ($minVal as $key=>$val) {
                                $pmore    = '';
                                $selected = '';

                                $bmin = $val;
                                if($val == '0'){
                                $bmin = 'studio';
                                }
                                if($val == $bed_max){
                                //$pmore = '+';
                                }
                                if($this->params['BED']['MIN'] == $val){
                                $selected = 'selected';
                                }

                                echo '<option value="'.$val.'" '.$selected.'>'.$bmin.'</option>';

                                }
                                ?>
                                </select>
                            </div>
                        </div>
                        <div class="advanceMax rangemax">
                            <div id="mmxx">
                                <?php _e('GENERIC_MAX',"realtransac"); ?>
                            </div>
                            <div class="drop-down-outer">
                                <select id="abed_max" name="abed_max">
                                <?php
                                while($bedVal != $bed_max){
                                $bedVal += $bed_step;
                                $minVal[] = $bedVal;
                                }
                                unset($minVal[0]);
                                foreach ($minVal as $key=>$val) {
                                $pmore    = '';
                                $selected = '';

                                $bmin = $val;
                                if($val == '0'){
                                    $bmin = 'studio';
                                }
                                if($val == $bed_max){
                                    $pmore = '+';
                                }
                                if($this->params['BED']['MAX'] == $val){
                                $selected = 'selected';
                                }
                                    echo '<option value="'.$val.'" '.$selected.'>'.$bmin.$pmore.'</option>';
                                }
                                if($bed_hmax == ''){
                                    $bed_hmax = 0;
                                }
                                ?>
                                </select>
                                <input type="hidden" id="abed_hmax" name="abed_hmax" value="<?php echo $bed_hmax; ?>"/>
                                <input type="hidden" id="abed_step" name="abed_step" value="<?php echo $bed_step;?>"/>
                            </div>
                        </div>
                    </div>        
                </div>
            </div>
            
            <div  class="rt_search_slider">    
                <div class="label">
                    <?php _e('GENERIC_BATHROOMS',"realtransac"); ?>
                </div>
                <div class="element">                   
                        <div id="abathroom-range">
                        <?php                                           
                        $bath_min  =  $this->params['START']['BATH']['MIN'];
                        $bath_max  =  $this->params['START']['BATH']['MAX'];
                        $bath_step =  $this->params['START']['BATH']['STEP'];
                        $bath_hmax =  $this->params['BATH']['HMAX'];
                        //$hidden_bathmax = 0; 
                        
                        $bathVal = $bath_min;
                        $bminVal = array(0);

                        while($bathVal != $bath_max - $bath_step){
                            $bathVal  += $bath_step;
                            $bminVal[] = $bathVal;
                        }
                        ?>
                        <div class="advanceMin rangemin">
                            <div id="mm">
                                 <?php  _e('GENERIC_MIN',"realtransac"); ?>
                            </div>
                        <div class="drop-down-outer">
                            <select id="abath_min" name="abath_min">                     
                            <?php
                            foreach ($bminVal as $key=>$val) {
                            $pmore    = '';
                            $selected = '';
                            if($val == $bath_max){
                               //$pmore = '+';
                            }
                            if($this->params['BATH']['MIN'] == $val){
                               $selected = 'selected';
                            }
                            echo '<option value="'.$val.'" '.$selected.'>'.$val.'</option>';

                            }
                            ?>
                            </select>
                        </div>
                        </div>
                        <div class="advanceMax rangemax">
                            <div id="mmxx">
                                <?php  _e('GENERIC_MAX',"realtransac"); ?>
                            </div>
                        <div class="drop-down-outer">
                            <select id="abath_max" name="abath_max">                     
                            <?php
                            while($bathVal != $bath_max){
                                $bathVal  += $bath_step;
                                $bminVal[] = $bathVal;
                            }
                            unset($bminVal[0]);
                            foreach ($bminVal as $key=>$val) {
                            $pmore    = '';
                            $selected = '';
                            if($val == $bath_max){
                               $pmore = '+';
                            }
                            if($this->params['BATH']['MAX'] == $val){
                               $selected = 'selected';
                            }
                            echo '<option value="'.$val.'" '.$selected.'>'.$val.$pmore.'</option>';

                            }
                            if($bath_hmax == ''){
                                $bath_hmax = 0;
                            }
                            ?>
                            </select>                                
                            <input type="hidden" id="abath_hmax" name="abath_hmax" value="<?php echo $bath_hmax;?>"/>
                            <input type="hidden" id="abath_step" name="abath_step" value="<?php echo $bath_step;?>"/>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            
            <div  class="rt_search_slider">   
                <div class="label">
                        <?php _e('GENERIC_SURFACE',"realtransac"); ?>
                </div>
                <div class="element">                    
                    <div id="asurface-range">
                        <?php
                       
                        $area_min   =  $this->params['START']['SURFACE']['MIN'];
                        $area_max   =  $this->params['START']['SURFACE']['MAX'];                        
                        $area_step  =  $this->params['START']['SURFACE']['STEP'];
                        $area_unit  =  $this->params['START']['SURFACE']['UNIT'];
                        $area_uval  =  $this->params['START']['SURFACE']['UVAL'];
                        $area_hmax  =  $this->params['SURFACE']['HMAX'];
                        //$hiddenamax =  0;
                        
                        $areaVal = $area_min;
                        $aminVal = array(0);
                        while($areaVal < $area_max - $area_step){
                            $areaVal  += $area_step;
                            $aminVal[] = $areaVal; 

                            if(($areaVal + $area_step) > $area_max){                                                        
                               $area_max = $areaVal;
                            }  
                        }
                        ?>
                        <div class="advanceMin rangemin">
                            <div id="mm">
                                 <?php _e('GENERIC_MIN',"realtransac"); ?>
                            </div>
                        <div class="drop-down-outer">
                            <select id="asurface_min" name="asurface_min">                     
                            <?php
                            foreach ($aminVal as $key=>$val) {
                            $pmore    = '';
                            $selected = '';
                            if($val == $area_max){
                               //$pmore = '+';
                            }

                            /*if($this->params['START']['SURFACE']['MIN'] == $val){
                            $selected = 'selected';

                            }else if($_POST['asurface_min'] && $_POST['asurface_max']){
                                if($this->params['SURFACE']['MIN'] == $val ){
                                    $selected = 'selected';
                                } 
                            }*/
                            if($this->params['SURFACE']['MIN'] == $val){
                                $selected = 'selected';
                            }

                            echo '<option value="'.$val.'" '.$selected.'>'.$val.$area_uval.'</option>';

                            }
                            ?>
                            </select>
                        </div>
                        </div>
                        <div class="advanceMax rangemax">
                            <div id="mmxx">
                                 <?php  _e('GENERIC_MAX',"realtransac"); ?>
                            </div>
                        <div class="drop-down-outer">
                            <select id="asurface_max" name="asurface_max">                     
                            <?php    
                             while($areaVal < $area_max){
                                $areaVal  += $area_step;
                                $aminVal[] = $areaVal; 

                                if(($areaVal + $area_step) > $area_max){                                                        
                                   $area_max = $areaVal;
                                }  
                            }
                            unset($aminVal[0]);
                            foreach ($aminVal as $key=>$val) {
                            $pmore = '';
                            $selected = '';
                            if($val == $area_max){
                               $pmore = '+';
                            }

                            /*if($_POST['search_type'] == '2'){
                                if($_POST['asurface_max'] == $val){
                                    $selected = 'selected';

                                }
                            }else if(!$_POST['asurface_max']){
                                if($area_max == $val){
                                     $selected = 'selected';
                                }

                            }*/
                            if($this->params['SURFACE']['HMAX'] > 0){
                                if($this->params['SURFACE']['HMAX'] == $val){
                                    $selected = 'selected';
                                }
                                $hiddenamax = $this->params['SURFACE']['HMAX'];
                            }else{
                                if($this->params['SURFACE']['MAX'] == $val){
                                    $selected = 'selected';
                                }
                                $hiddenamax = 0;
                            }

                            echo '<option value="'.$val.'" '.$selected.'>'.$val.$area_uval.$pmore.'</option>';

                            }
                            ?>
                            </select>                     
                            <input type="hidden" id="asurface_hmax" name="asurface_hmax" value="<?php echo $hiddenamax;?>"/>
                            <input type="hidden" id="asurface_uval" name="asurface_uval" value="<?php echo $area_uval;?>"/>
                            <input type="hidden" id="asurface_step" name="asurface_step" value="<?php echo $area_step;?>"/>
                            <input type="hidden" id="asurface_unit" name="asurface_unit" value="<?php echo $area_unit;?>"/>
                    </div>
                    </div>
                </div>               
              </div>
            </div>
            <input type="hidden" name="search_type" value="2" /> 
            <input id="asorted" name="asorted" type="hidden" value=""/>
            <input type="hidden" name="language" value="<?php echo $this->lang; ?>" />
            <input type="hidden" id="zoom_level" name="zoom_level" value="<?php echo $this->params['ZOOM'];?>"/>
            <input type="hidden" id="map_center" name="map_center" value="<?php echo $this->params['MCENTER'];?>"/>  
            
            <input type="hidden" id="acountryname" name="acountryname" value="<?php echo $this->params['COUNTRY_MAP'] ?>"/>
            <input type="hidden" id="mexpand" name="mexpand" value="0"/>
            <input type="hidden" id="shape" name="shape"/> 
            <input type="hidden" name="asaveForm" value="1" />

            <div class="btn-outer">
                <a class="viewbutton" id="asearchButton<?php echo $this->widget; ?>">
                    <span class="btnleft"></span>
                    <span class="btncenter">
                          <?php _e('ADVANCEDSEARCH_SEARCH',"realtransac"); ?>
                    </span>
                    <span class="btnright"></span>
                </a>
            </div>
            <div class="clear"></div>
            </div>
        </form>	
      
        <?php
         
	} // INVALID SUBSCRIPTIONS END
        echo '</div>';
     }
    
    
    public function displaySearchMap(){                 

        global  $rt_config;
        $mapdata = '{}';
        echo '<div class = "rt_google_map rt_widget_content">'; 
        
         if($this->error){
             echo "<div  class='rt_search_wrapper'>";
                    echo '<div class="InvalidSub" style="color: #F4F4F7; font-size: 15px; font-weight: bold; text-align: center;">'.$this->error.'</div>';
             echo "</div>";
             
         }else{
             
            if($this->pageType == 3 && $rt_config['viewdetail'] != ''){
                
                $param = array(
                    'apikey'         => $this->apikey,
                    'version'        => $this->plugver,
                    'idPRODUCT'      => addslashes($rt_config['viewdetail']),
                    'language'       => $this->lang,
                    'country_id'     => $this->params['COUNTRYKEY'],
                    'limit'          => 1
                );
                /**
                * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE FORM BASED ON IT */
                if(isset($rt_config['rt_currency']['globalCurrency'])) {
                    $param['rtglobal_currency'] =   $rt_config['rt_currency']['globalCurrency'];
                }
                
            $parameters = array('data' => $param, 'ismap' => true);

            $result = $this->client->call('getSearchResults', $parameters, '', '', false, true);

            if ($this->client->fault) {
                     echo '<h6>'.__('GENERIC_SERVER_CONNECTION_FAULT',"realtransac").'</h6>';
            } else {
                    $err = $this->client->getError();
                    if ($err) {
                            echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
                    } 
            }
                             
            $data   =   json_decode($result);

            $mcenter    =   '';
            $mapdata    =   $data->mapresult;
            $mapresult  =   (array)json_decode($data->mapresult);
            
            if(isset($mapresult[0])){
               $mapresult[0]    =   (array)$mapresult[0];
               $mcenter         =   $mapresult[0]['lat'].'#'.$mapresult[0]['lng'];
               $this->pageType  =   0; // is lat and lng set.
            }
            
            }else{
                $data = $rt_config['searchresults']; 
                if($data != ''){
                    $mapdata = $data;
                }
            }

        ?>              
        
            <script type="text/javascript">
                jQuery.noConflict();
                var includePath    = '<?php echo plugins_url( '' , __FILE__ ); ?>'

                jQuery(document).ready(function() {                         
                    try{
                        jQuery('#map_center').val('<?php echo $mcenter; ?>');
                        loadData(<?php echo $mapdata; ?>, false, <?php echo $this->pageType; ?>, true);
                    }catch(e){alert(e)}
                });
            </script>
            
            <div class="rt_search_mapwrapper">
                <div class="map_container">
                    <div id="map-rollover-container" >
                        <div id="map-rollover" ></div>
                        <div id="map-rollover-btm"></div>
                        <div id="liste" class="int_list_tab"></div>
                    </div>
                    <div id="map_realestate" style="height: 375px; margin : 4px;"> </div>
                </div>
                <!--<div class="rt_map_result">
                  <div class="rt_search_map">                    
                      <div class="poly_text">
                        <div> <?php //_e('ADVANCEDSEARCH_DRAW_BOX',"realtransac"); ?></div>
                        <div id="shape_poly" class="unselected" onclick="startShape();"></div>
                     </div>
                  </div>
                  <div class="rt_search_map">
                      <div class="circ_text">
                        <div><?php //_e('ADVANCEDSEARCH_DRAW_CIRCLE',"realtransac"); ?></div>
                        <div id="shape_circ" class="selected" onclick="startCircle();"></div>
                      </div>
                  </div>
                </div>-->
                <input type="hidden" id="shape_poly" class="unselected" onclick="void(0);" />
                <input type="hidden" id="shape_circ" class="selected" onclick="void(0);" />
                <input type="hidden" id="points" name="points" value="" />                
                <input id="alocalbox" name="alocalbox" type="hidden" value="<?php echo $this->params['LOCALBOX']; ?>"/>
                <input type="hidden" id="zoom_level" name="zoom_level" value="<?php echo $this->params['ZOOM'];?>"/>
                <input type="hidden" id="map_center" name="map_center" value="<?php echo $this->params['MCENTER'];?>"/>
                
                <input type="hidden" id="countryname" name="countryname" value="<?php echo $this->params['COUNTRY_MAP']; ?>"/>
                <input type="hidden" id="mexpand" name="mexpand" value="0"/>
                <input type="hidden" id="shape" name="shape"/> 
            </div>


          <?php 
    
           }
             
       echo '</div>';	
    
  } // INVALID SUBSCRIPTIONS END	
}
          
?>