<?php

include_once 'common.class.php';

class Realtransac_API_Search extends Realtransac_API_Common{
    
    public $widget;    
    public $pageid;
    public $wsdl;
    public $apikey;
    public $client;
    public $params;
    public $mls_show;
    
    public function __construct($instance, $widget_id){

        global  $rt_config;     
        $this->plugver  = plugin_get_version();
        $this->widget   = $widget_id;       
        $this->display  = $instance['displaysearchform'];
        $this->pageid   = $instance['pageid'];
        $this->wsdl     = $rt_config['wsdl'];
        $this->apikey   = $rt_config['apikey'];
        $this->ip       = $rt_config['ip'];
        $this->client   = $rt_config['client'];
        $this->pageType = $rt_config['pageType'];
        $this->viewID   = $rt_config['viewdetailid'];
        $this->qtranslate = false;
        $this->designoption  = get_option('plugindesign');
        $this->mls_show      = $rt_config['mls_show'];
        $this->detailpageid  = $instance['detailpageid'];
        
        $this->permalink     = get_permalink($this->pageid);
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
   
    protected function setSearchSession(){
        global  $rt_config;
        try{
            /**
            * IF POST ACTION HAVE DONE AND API DOESNT CHANGE IN MEAN WHILE, THEN ONLY THE BELOW POST PART WILL EXECUTE */
            if((isset($_POST["asaveForm"]) || isset($_POST["saveForm"]) || isset($_POST["asorted"])) && !$rt_config['reset_flag'] ){
                if($_POST['search_type'] == 1){ 
                    $post   = array('location' => $_POST['location'],'searchcountry'=> $_POST['searchcountry'], 'type' => $_POST['type'], 'category' => $_POST['category'], 'mls_show' => $this->mls_show);
                }else{
                    $post   = array('location' => $_POST['alocation'],'searchcountry'=> $_POST['asearchcountry'], 'type' => $_POST['atype'], 'category' => $_POST['acategory'], 'mls_show' => $this->mls_show);
                }                
            }else{            
                if(isset($_SESSION['RT'])){
                    $post   = array('location' => $_SESSION['RT']['LOCATION'],'searchcountry'=> $_SESSION['RT']['COUNTRYKEY'], 'type' => $_SESSION['RT']['TYPE'], 'category' => $_SESSION['RT']['CATEGORY'], 'mls_show' => $this->mls_show);
                }
            }
            if(isset($_SESSION['RT']['PRICE'])) {
                $post['session_price']  =   $_SESSION['RT']['PRICE'];
            }
            if($rt_config['rt_currency']['globalOldCurrency']!='') {
                $post['rtglobal_old_currency']  =   $rt_config['rt_currency']['globalOldCurrency'];
            }
            /**
            * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE FORM BASED ON IT */
            if(isset($rt_config['rt_currency']['globalCurrency'])) {
                $post['rtglobal_currency']  =   $rt_config['rt_currency']['globalCurrency'];
            }
            
            $param = array(                
                'apikey'   => $this->apikey,
                'version'  => $this->plugver, 
                'language' => $this->lang,
                'post'     => $post
            );
            $parameters = array('data' => $param);

            $result = $this->client->call('getDefaultSearchForm', $parameters, '', '', true, false);
                  
            // Check for a fault
            if ($this->client->fault) {
                echo '<h6>'.__('GENERIC_SERVER_CONNECTION_FAULT',"realtransac").'</h6>';
            } else {
                $err = $this->client->getError();
                if ($err) {
                    echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
                } 
            }
            
            $formdata     = json_decode($result);
            
            //ACCESS BUILT DESCRIPTION VALUES
           
            /*if(!empty($formdata->Type)){
                update_option('typeoption', serialize($formdata->Type)); 
            }

            if(!empty($formdata->BuiltDescription)){
                update_option('built_desc', serialize($formdata->BuiltDescription)); 
            }*/
            
            if($formdata->error){
                
                $this->error = $formdata->error;
               
            }
            
            /**
            * IF INVALID CURRENCY HAS BEEN GIVEN IN ADMIN, THEN WE WILL SWITCH TO DEFAULT CURRENCY */
            if($formdata->SwitchToDefault) {
                $_SESSION['RT_CURRENCY']['CURRENCY']        =   $rt_config['rt_currency']['defaultCurrency'];
                $rt_config['rt_currency']['globalCurrency'] =   $rt_config['rt_currency']['defaultCurrency'];
            }
            /**
            * IF POST ACTION HAVE DONE AND API DOESNT CHANGE IN MEAN WHILE, THEN ONLY THE BELOW POST PART WILL EXECUTE */
            if( (isset($_POST["asaveForm"]) || isset($_POST["saveForm"]) || isset($_POST["asorted"])) && !$rt_config['reset_flag']) {

                if($_POST['location']){
                   $location = $_POST['location'];
                }else{
                   $location = $_POST['alocation'];
                }

                $param = array(                
                    'apikey'        => $this->apikey,
                    'version'       => $this->plugver,
                    'language'      => $this->lang,
                    'localisation'  => $location
                );


                $_SESSION['RT']['TYPES']   = $formdata->Type;                   

                $param = array(                
                    'apikey'        =>  $this->apikey, 
                    'version'       =>  $this->plugver,
                    'language'      =>  $this->lang,                  
                    'localisation'  =>  $location,
                    'type'          =>  $_POST['type']
                );

                $_SESSION['RT']['CATEGORIES']   = $formdata->Category; 


                // For Map
                $_SESSION['RT']['ZOOM']    = $_POST['map_zoom'];
                $_SESSION['RT']['MCENTER'] = $_POST['map_center'];


                //NORMAL SEARCH FROM
                if($_POST['search_type'] == 1){
                    
                    if($_SESSION['RT']['CATEGORY'] != $_POST['category'] || $_SESSION['RT']['COUNTRYKEY'] != $_POST['searchcountry']){
                        $_SESSION['RT']['SURFACE']      = array('MIN' => $formdata->Surface->min, 'MAX' => $formdata->Surface->max, 'STEP' => $formdata->Surface->step, 'UNIT' => $formdata->Surface->unit, 'UVAL' => $formdata->Surface->uval);
                    }
                    
                    $_SESSION['RT']['LANGUAGE']     = $_POST['language'];
                    $_SESSION['RT']['ISPICTURE']    = $_POST['aispicture'];
                    $_SESSION['RT']['LOCALBOX']     = $_POST['localbox'];
                    $_SESSION['RT']['LOCATION']     = $_POST['location'];                
                    $_SESSION['RT']['TYPE']         = $_POST['type'];
                    $_SESSION['RT']['STEP']         = $_POST['step'];
                    $_SESSION['RT']['CATEGORY']     = $_POST['category'];
                    $_SESSION['RT']['COUNTRY_MAP']  = $_POST['countryname'];
                    $_SESSION['RT']['COUNTRYKEY']   = $_POST['searchcountry'];
                    
                    $_SESSION['RT']['PRICE']        = array('MIN' => $_POST['price_min'], 'MAX' => $_POST['price_max'], 'STEP' => $_POST['price_step'], 'UVAL' => $_POST['price_uval'], 'USHOW' => $_POST['price_ushow'], 'HMAX' => $_POST['hmaxprice']);                            
                    $_SESSION['RT']['BED']          = array('MIN' => $_POST['bed_min'], 'MAX' => $_POST['bed_max'], 'STEP' => $_POST['bed_step'], 'HMAX' => $_POST['bed_hmax']);
                    
                    $_SESSION['RT']['START']        = array(
                                                        'PRICE'     => array('MIN' => $formdata->PriceRange->min, 'MAX' => $formdata->PriceRange->max, 'STEP' => $formdata->PriceRange->step, 'VAL' => $formdata->PriceRange->uval, 'MULTIPLE' => $formdata->Step_value), 
                                                        'BED'       => array('MIN' => $formdata->Bedrooms->min, 'MAX' => $formdata->Bedrooms->max, 'STEP' => $formdata->Bedrooms->step),
                                                        'BATH'      => array('MIN' => $formdata->Bathrooms->min, 'MAX' => $formdata->Bathrooms->max, 'STEP' => $formdata->Bathrooms->step),
                                                        'SURFACE'   => array('MIN' => $formdata->Surface->min, 'MAX' => $formdata->Surface->max, 'STEP' => $formdata->Surface->step ,'UNIT' => $formdata->Surface->unit, 'UVAL' => $formdata->Surface->uval),
                                                        'DISTANCE'  => array('MIN' => $formdata->Distance->min, 'MAX' => $formdata->Distance->max, 'STEP' => $formdata->Distance->step,'VAL' => $formdata->Distance->val),
                                                      );
                    $_SESSION['RT']['DEFAULT_PRICE']    =   array('MIN' => $formdata->DefaultPriceRange->min, 'MAX' => $formdata->DefaultPriceRange->max, 'STEP' => $formdata->DefaultPriceRange->step, 'VAL' => $formdata->DefaultPriceRange->uval);
                 //ADVANCE SEARCH FROM  
                }else{     

                    $_SESSION['RT']['LANGUAGE']     = $_POST['language'];
                    $_SESSION['RT']['ISPICTURE']    = $_POST['aispicture'];
                    $_SESSION['RT']['ASORTED']      = $_POST['asorted'];
                    $_SESSION['RT']['LOCALBOX']     = $_POST['alocalbox'];
                    $_SESSION['RT']['LOCATION']     = $_POST['alocation'];                
                    $_SESSION['RT']['TYPE']         = $_POST['atype'];
                    $_SESSION['RT']['STEP']         = $_POST['step'];
                    $_SESSION['RT']['CATEGORY']     = $_POST['acategory'];
                    $_SESSION['RT']['COUNTRY_MAP']  = $_POST['acountryname'];
                    $_SESSION['RT']['COUNTRYKEY']   = $_POST['asearchcountry'];
                    
                    $_SESSION['RT']['PRICE']        = array('MIN' => $_POST['aprice_min'], 'MAX' => $_POST['aprice_max'], 'STEP' => $_POST['aprice_step'], 'UVAL' => $_POST['aprice_uval'], 'USHOW' => $_POST['aprice_ushow'],'HMAX'  => $_POST['aprice_hmax']);                            
                    $_SESSION['RT']['BED']          = array('MIN' => $_POST['abed_min'], 'MAX' => $_POST['abed_max'], 'STEP' => $_POST['abed_step'],'HMAX'  => $_POST['abed_hmax']); 
                    $_SESSION['RT']['BATH']         = array('MIN' => $_POST['abath_min'], 'MAX' => $_POST['abath_max'], 'STEP' => $_POST['abath_step'],'HMAX'  => $_POST['abath_hmax']);                            
                    $_SESSION['RT']['SURFACE']      = array('MIN' => $_POST['asurface_min'], 'MAX' => $_POST['asurface_max'], 'STEP' => $_POST['asurface_step'], 'UNIT' => $_POST['asurface_unit'], 'UVAL' => $_POST['asurface_uval'],'HMAX'  => $_POST['asurface_hmax']);                            
                    $_SESSION['RT']['DISTANCE']     = array('MIN' => $_POST['distance_min'], 'MAX' => $_POST['distance_max'], 'STEP' => $_POST['distance_step'], 'VAL' => $_POST['distance'], 'UNIT' => $_POST['distance_unit'], 'UVAL' => $_POST['distance_uval']);                            
                    $_SESSION['RT']['START']        = array(
                                                        'PRICE'     => array('MIN' => $formdata->PriceRange->min, 'MAX' => $formdata->PriceRange->max, 'STEP' => $formdata->PriceRange->step, 'VAL' => $formdata->PriceRange->uval,'MULTIPLE' => $formdata->Step_value), 
                                                        'BED'       => array('MIN' => $formdata->Bedrooms->min, 'MAX' => $formdata->Bedrooms->max, 'STEP' => $formdata->Bedrooms->step),
                                                        'BATH'      => array('MIN' => $formdata->Bathrooms->min, 'MAX' => $formdata->Bathrooms->max, 'STEP' => $formdata->Bathrooms->step),
                                                        'SURFACE'   => array('MIN' => $formdata->Surface->min, 'MAX' => $formdata->Surface->max, 'STEP' => $formdata->Surface->step ,'UNIT' => $formdata->Surface->unit, 'UVAL' => $formdata->Surface->uval),
                                                        'DISTANCE'  => array('MIN' => $formdata->Distance->min, 'MAX' => $formdata->Distance->max, 'STEP' => $formdata->Distance->step,'VAL' => $formdata->Distance->val),
                                                      );
                    $_SESSION['RT']['DEFAULT_PRICE']    =   array('MIN' => $formdata->DefaultPriceRange->min, 'MAX' => $formdata->DefaultPriceRange->max, 'STEP' => $formdata->DefaultPriceRange->step, 'VAL' => $formdata->DefaultPriceRange->uval);
                }

            }else{
                // On initial page load
                if(!isset($_SESSION['RT'])){ 
                    
                    // For Map  
                    $_SESSION['RT']['ZOOM']         = $formdata->Zoom;
                    $_SESSION['RT']['MCENTER']      = $formdata->Center;
                    // For Default
                    $_SESSION['RT']['LOCATION']     = $formdata->Location;  
                    $_SESSION['RT']['COUNTRY_MAP']  = $formdata->Country;
                    $_SESSION['RT']['BED']          = array('MIN' => $formdata->Bedrooms->min, 'MAX' => $formdata->Bedrooms->max, 'STEP' => $formdata->Bedrooms->step);                            
                    $_SESSION['RT']['BATH']         = array('MIN' => $formdata->Bathrooms->min, 'MAX' => $formdata->Bathrooms->max, 'STEP' => $formdata->Bathrooms->step);                            
                    $_SESSION['RT']['SURFACE']      = array('MIN' => $formdata->Surface->min, 'MAX' => $formdata->Surface->max, 'STEP' => $formdata->Surface->step, 'UNIT' => $formdata->Surface->unit, 'UVAL' => $formdata->Surface->uval);
                    $_SESSION['RT']['DISTANCE']     = array('MIN' => $formdata->Distance->min, 'MAX' => $formdata->Distance->max, 'STEP' => $formdata->Distance->step, 'VAL' => $formdata->Distance->val, 'UNIT' => $formdata->Distance->unit, 'UVAL' => $formdata->Distance->uval);
                    $_SESSION['RT']['ISPICTURE']    = 0;
                    $_SESSION['RT']['LOCALBOX']     = $formdata->Localbox;
                    $_SESSION['RT']['START']        = array(
                                                            'BED'       => array('MIN' => $formdata->Bedrooms->min, 'MAX' => $formdata->Bedrooms->max, 'STEP' => $formdata->Bedrooms->step),
                                                            'BATH'      => array('MIN' => $formdata->Bathrooms->min, 'MAX' => $formdata->Bathrooms->max, 'STEP' => $formdata->Bathrooms->step),
                                                            'SURFACE'   => array('MIN' => $formdata->Surface->min, 'MAX' => $formdata->Surface->max, 'STEP' => $formdata->Surface->step ,'UNIT' => $formdata->Surface->unit, 'UVAL' => $formdata->Surface->uval),
                                                            'DISTANCE'  => array('MIN' => $formdata->Distance->min, 'MAX' => $formdata->Distance->max, 'STEP' => $formdata->Distance->step,'VAL' => $formdata->Distance->val),
                                                           );
                }
                
                /**
                * PLEASE DONT CHANGE THIS LOGIC AT ANY CAUSE
                * START : TO MATCH THE SELECTED MAX PRICE VALUE WITH MAXIMUM VALUE OF THE PRICE DROP DOWN */
                $hMaxValue  =   $formdata->SessionPriceRange->max;
                $sessionMax =   $formdata->SessionPriceRange->max;
                $sessionMin =   $formdata->SessionPriceRange->min;
                
                $firstMax   =   $formdata->DefaultPriceRange->max;
                $secondMax  =   ($formdata->DefaultPriceRange->max - $formdata->DefaultPriceRange->step);
                $priceIndex =   $formdata->DefaultPriceRange->min;
                $priceStep  =   $formdata->DefaultPriceRange->step;
                if($sessionMax == $firstMax || ($firstMax>$sessionMax && $sessionMax>$secondMax)) {
                    $hMaxValue  =   '0';
                    $sessionMax =   $firstMax;
                }
                
                /**
                * START : WE NEED TO CHECK THE SELECTED MIN PRICES WITH THE LOOP VALUES AS WELL AS THE INTERMEDIATE ONES
                *         TO ASSIGN THE SELECTED MIN VALUE TO THE HIDDENS*/
                $loopPriceVal   =   $formdata->PriceRange->min;
                $dropPriceMax   =   $formdata->PriceRange->max;
                $dropPriceStep  =   $formdata->PriceRange->step;
                $selPriceMin    =   $formdata->SessionPriceRange->min;
                $selPriceMax    =   $formdata->SessionPriceRange->max;
                $priceArray     =   array('0' => array('value' => '0', 'text' => '0'));
                while($loopPriceVal < $dropPriceMax) {
                    $priceIndex    +=   $priceStep;
                    $loopPriceVal  +=   $dropPriceStep;
                    $priceArray[]   =   array('value' => $priceIndex, 'text' => $loopPriceVal);
                }
                foreach ($priceArray as $key=>$val){
                    $nextIndex  =   $key + 1;
                    if($selPriceMin == $val['value'] || ($val['value']<$selPriceMin && $selPriceMin<$priceArray[$nextIndex]['value'])) {
                        $sessionMin  =  $val['value'];
                    }
                }
                /**
                * START : IF THE SELECTED MAX PRICE DOESNT MATCH WITH MAXIMUM VALUE OF THE PRICE FROM DROP DOWN,
                *         THEN, WE NEED TO CHECK THE SELECTED MAX PRICES WITH THE LOOP VALUES AS WELL AS THE INTERMEDIATE ONES
                *         TO ASSIGN THE SELECTED VALUE TO THE HIDDENS*/
                if($hMaxValue!='0') {
                    unset($priceArray[0]);
                    foreach ($priceArray as $key => $val) {
                        $prevIndex  =   $key - 1;
                        if($selPriceMax != ''){
                            if($selPriceMax == $val['value'] || ($val['value']>$selPriceMax && $selPriceMax>$priceArray[$prevIndex]['value'])) {
                                $sessionMax  =  $val['value'];
                            }
                        }
                    }
                }
                $_SESSION['RT']['PRICE']                =   array('MIN' => $sessionMin, 'MAX' => $sessionMax, 'STEP' => $formdata->PriceRange->step, 'UVAL' => $formdata->PriceRange->uval, 'USHOW' => $formdata->PriceRange->ushow, 'HMAX' => $hMaxValue);
                $_SESSION['RT']['START']['PRICE']       =   array('MIN' => $formdata->PriceRange->min, 'MAX' => $formdata->PriceRange->max, 'STEP' => $formdata->PriceRange->step, 'VAL' => $formdata->PriceRange->uval, 'MULTIPLE' => $formdata->Step_value);
                $_SESSION['RT']['DEFAULT_PRICE']        =   array('MIN' => $formdata->DefaultPriceRange->min, 'MAX' => $formdata->DefaultPriceRange->max, 'STEP' => $formdata->DefaultPriceRange->step, 'VAL' => $formdata->DefaultPriceRange->uval);

                $_SESSION['RT']['COUNTRIES']      = $formdata->Searchcountry;
                $_SESSION['RT']['TYPES']          = $formdata->Type;
                $_SESSION['RT']['CATEGORIES']     = $formdata->Category;
                $_SESSION['RT']['COUNTRYKEY']     = $formdata->Countrykey;
                $_SESSION['RT']['COUNTRY_MAP']    = $formdata->Country;
                
            }
            
            $this->params   = $_SESSION['RT'];

        }catch(Exception $ex){

            if($ex->faultcode){
                echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
            }
        }  
                                     
    }   
    
    public function decimalFloorRounding($number) {
        if(is_numeric($number)) {
            $returnNumber   =   $number;
            $numberLength   =   strlen(trim($number));
            if($numberLength>1) {
                if($numberLength <= '3') {
                    $count          =   1;
                    $returnNumber   =   substr($number, 0, -1);
                } else if($numberLength=='4' || $numberLength=='5') {
                    $count          =   2;
                    $returnNumber   =   substr($number, 0, -2);
                } else if($numberLength > '5') {
                    $count          =   3;
                    $returnNumber   =   substr($number, 0, -3);
                }
                for($iteration=1;$iteration<=$count;$iteration++) {
                    $returnNumber  .=   '0';
                }
            }
            return $returnNumber;
        }
        return false;
    }
    
    // TO DISPLAY FORM 
    public function displaySearchForm() {
        
        global  $rt_config; 
        
        echo '<div class="rt_search rt_widget_content">';
        ?>      
        <style>
        .requiredError{display: none;}
        </style>
        <script>
           jQuery.noConflict();
            var apikey    = '<?php echo $this->apikey; ?>';
            var wsdl      = '<?php echo $this->wsdl; ?>';
            var ajax_url  = '<?php echo plugins_url('ajaxcall.php' , __FILE__).'/?lang='.$this->lang; ?>';
            
            var price_min   = '<?php echo $this->params['START']['PRICE']['MIN'] ?>';
            var price_max   = '<?php echo $this->params['START']['PRICE']['MAX'] ?>';
            var price_step  = '<?php echo $this->params['START']['PRICE']['STEP'] ?>';
            var stepVal     = '<?php echo $this->params['START']['PRICE']['MULTIPLE'] ?>';
                        
            var bed_min     = '<?php echo $this->params['START']['BED']['MIN'] ?>';
            var bed_max     = '<?php echo $this->params['START']['BED']['MAX'] ?>';
            var bed_step    = '<?php echo $this->params['START']['BED']['STEP'] ?>';
            var language    = '<?php echo $this->lang; ?>';
            
            var isLoading = false;
            
            jQuery(document).ready(function() {
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
                    jQuery("select#price_min").html(apminOptions);
                    jQuery("select#price_max").html(apmaxOptions);
                    jQuery("#priceMIN").val(dMinPrice);
                    jQuery("#priceMAX").val(dMaxPrice);
                    jQuery("#price_step").val(data.price.step);
                    jQuery("#price_uval").val(data.price.uval);
                    jQuery("#price_ushow").val(data.price.ushow);
                    jQuery("#hmaxprice").val("0");
                }
                function ajaxReqcountry(){
                    isLoading = true;
                    jQuery('.rt_search_wrapper .btn-outer').addClass("rt_opacity");
                    jQuery.ajax({ 
                        url: ajax_url,
                        type: "POST",
                        dataType:"json",
                        data: {action: 'dynamicvalues', id: jQuery('#location').val() ,countryId: jQuery('#searchcountry').val(),  typeid:'', category:'', language:language, ajax: 'true', level:6, apikey: apikey, wsdl: wsdl, ignoreAll:1, isPortal:1},

                        success: function(data) {
                            
                            //TYPE
                            var options = '';
                            jQuery.each(data.type, function(key, val) {
                                options += '<option value="' + key + '">' + val + '</option>';
                            });
                            jQuery("select#type").html(options);

                            //CATEGORY
                            var options = '';
                            jQuery.each(data.category, function(key, val) {
                                options += '<option value="' + key + '">' + val + '</option>';
                            });
                            jQuery("select#category").html(options);
                            
                            /* PRICE DROP DOWN STARTS */
                            processPriceDropDown(data);
                            /* PRICE DROP DOWN ENDS */
                            
                            jQuery('#type,#category,#price_min,#price_max').removeAttr('disabled');
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
                        data: {action: 'dynamicvalues',  id: jQuery('#location').val(), countryId: jQuery('#searchcountry').val(),  typeid: jQuery('#type').val(), category:'', language:language, ajax: 'true', level:6, apikey: apikey, wsdl: wsdl, ignoreAll:1, isPortal:1},

                        success: function(data) {

                            //CATEGORY
                            var options = '';
                            jQuery.each(data.category, function(key, val) {
                                options += '<option value="' + key + '">' + val + '</option>';
                            });
                            jQuery("select#category").html(options);
                            
                            /* PRICE DROP DOWN STARTS */
                            processPriceDropDown(data);
                            /* PRICE DROP DOWN ENDS */
                            
                            jQuery('#category,#price_min,#price_max').removeAttr('disabled');
                            jQuery('.rt_search_wrapper .btn-outer').removeClass("rt_opacity");
                            isLoading = false;
                            
                        }
                    });
                }                
         
               //VALIDATION START
              
                jQuery("#searchForm<?php echo $this->widget; ?>").validate({
                    showErrors: function(errorMap, errorList) {
                        this.defaultShowErrors();                        
                    }
                });
                
                jQuery.extend(jQuery.validator.messages, {
                    required: "<?php _e('GENERIC_REQUIRED','realtransac'); ?>",                    
                    email   : "<?php _e('GENERIC_VALID_EMAIL','realtransac'); ?>",
                    number  : "<?php _e('GENERIC_VALID_NUMBER','realtransac'); ?>"
                });
                
                jQuery('#searchButton<?php echo $this->widget; ?>').click(function(event){
                    if(!isLoading){
                        jQuery('#searchForm<?php echo $this->widget; ?>').submit();
                    }
                });
                
                //VALIDATION END  
                
                jQuery("select#searchcountry").change(function(){
                    jQuery('#type,#category,#price_min,#price_max').attr('disabled', 'disabled');
                    jQuery('#searchcountry option:selected').text(); 
                    jQuery("#countryname").val(jQuery("#searchcountry").find("option:selected").text()); // set country name
                    ajaxReqcountry();
                });

                jQuery('select#type').change(function(){ 
                    jQuery('#category,#price_min,#price_max').attr('disabled', 'disabled');
                    ajaxReqType();
                }); 
                
                

                jQuery("#bed_min").change(function() {
                    var bed = jQuery(this).val();   
                    var lastIndex = jQuery('option:last', '#bed_min').val();
                    if(bed == lastIndex){
                        bed = 0;
                        jQuery("#bed_hmax").val(bed);
                    }                
                    
                    if(jQuery('#bed_max').val() < jQuery(this).val()){
                       jQuery('#bed_max').val(jQuery(this).val());
                    }                
                });

                jQuery("#bed_max").change(function() {
                    var bed = jQuery(this).val();   
                    var lastIndex = jQuery('option:last', '#bed_max').val();
                    if(bed == lastIndex){
                        bed = 0;
                    }                
                    jQuery("#bed_hmax").val(bed);
                    
                    if(jQuery('#bed_min').val() > jQuery(this).val()){
                        jQuery('#bed_min').val(jQuery(this).val());
                    }
                });

                jQuery("#price_min").change(function(){
                    
                    var price     = jQuery(this).val();  
                    var lastIndex = jQuery('option:last', '#price_min').val();
                    if(price == lastIndex){
                       price = 0;
                       jQuery("#hmaxprice").val(price);
                    }

                    if(parseInt(jQuery('#price_max').val()) < parseInt(jQuery(this).val())){
                        jQuery('#price_max').val(jQuery(this).val());
                    }
                });
                
                jQuery("#price_max").change(function(){
                
                    var price     = jQuery(this).val();  
                    var lastIndex = jQuery('option:last', '#price_max').val();
                    if(price == lastIndex){
                    price = 0;

                    }
                    jQuery("#hmaxprice").val(price);

                    if(parseInt(jQuery('#price_min').val()) > parseInt(jQuery(this).val())){
                        jQuery('#price_min').val(jQuery(this).val());
                    }
                });
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
        <form id="searchForm<?php echo $this->widget; ?>" name="searchForm" class="searchForm <?php echo $display_class;?>"  method="post" action="<?php echo $this->permalink; ?>">
        <div  class="rt_search_wrapper">
            
            <?php if($this->mls_show == '2'){ ?>
                <div  class="rt_search_row rt_search_down">	                      
                    <div class="label">
                        <?php _e('GENERIC_COUNTRY',"realtransac"); ?>
                    </div>                      
                    <div class="drop_down_list">
                        <select id="searchcountry" name="searchcountry"> 
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
            <div  class="rt_search_row">	
                <div class="label">
                    <?php _e('GENERIC_LOCATION',"realtransac"); ?>
                </div>
                <div class="element">
                    <?php
                        if(empty($this->params['LOCALBOX'])) { 
                    ?>
                        <input id="localbox" name="localbox" class="required" type="text" value="" />
                    <?php } else { ?>
                        <input id="localbox" name="localbox" class="required" type="text" value="<?php echo $this->params['LOCALBOX'];?>" />
                    <?php }  ?>
                </div>
            </div> 
            
            <div  class="rt_search_row rt_search_down">	                      
                <div class="label">
                    <?php _e('GENERIC_TYPE',"realtransac"); ?>
                </div>                      
                <div class="drop_down_list">
                    <select id="type" name="type"> 
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
            
            <div  class="rt_search_row rt_search_down">
                <div class="label">
                     <?php _e('GENERIC_CATEGORY',"realtransac"); ?>
                </div>                    
                <div class="drop_down_list">
                    <select id="category" name="category">		
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
            
            <div  class="rt_search_slider">
                <div class="label">
                    <?php _e('GENERIC_PRICE',"realtransac"); ?>
                </div>
                <div class="element">                    
                    <div id="nor_price">
                        <div class="price_range_min rangemin">
                            <div id="mm">
                                <?php _e('GENERIC_MIN',"realtransac"); ?>
                            </div>
                            <div class="drop-down-outer">
                            <?php
                                $price_hmax         =   '';
                                $dprice_min         =   $this->params['START']['PRICE']['MIN'];
                                $dprice_max         =   $this->params['START']['PRICE']['MAX'];
                                $dprice_step        =   $this->params['START']['PRICE']['STEP'];
                            
                                $defaultPriceMin    =   $this->params['DEFAULT_PRICE']['MIN'];
                                $defaultPriceMax    =   $this->params['DEFAULT_PRICE']['MAX'];
                                $defaultPriceStep   =   $this->params['DEFAULT_PRICE']['STEP'];

                                $price_min          =   $this->params['PRICE']['MIN'];
                                $price_max          =   $this->params['PRICE']['MAX'];
                                $price_uval         =   $this->params['START']['PRICE']['VAL'];
                                $price_ushow        =   $this->params['PRICE']['USHOW'];
                                if(isset($this->params['PRICE']['HMAX'])) {
                                    $price_hmax     =   $this->params['PRICE']['HMAX'];
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
                            <select id="price_min" name="price_min">
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
                        
                        <div class="price_range_max rangemax">
                             <div id="mmxx">
                                 <?php _e('GENERIC_MAX',"realtransac"); ?>
                             </div>
                            <div class="drop-down-outer">
                            <select id="price_max" name="price_max">
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
                            <input type="hidden" id="priceMIN" name="priceMIN" value="<?php echo $price_min; ?>"/>
                            <input type="hidden" id="priceMAX" name="priceMAX" value="<?php echo $PRICEMAX; ?>"/>
                            <?php if($price_hmax != '' || $price_hmax == '0'){ ?>
                                    <input type="hidden" id="hmaxprice" name="hmaxprice" value="<?php echo $price_hmax; ?>"/>
                            <?php }else { ?>
                                    <input type="hidden" id="hmaxprice" name="hmaxprice" value="<?php echo $PRICEMAX; ?>"/>
                            <?php } ?>
                            <input type="hidden" id="price_uval" name="price_uval" value="<?php echo $price_uval; ?>"/>
                            <input type="hidden" id="price_ushow" name="price_ushow" value="<?php echo $price_ushow; ?>"/>
                            <input type="hidden" id="price_step" name="price_step" value="<?php echo $dprice_step; ?>"/>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
            
            <div  class="rt_search_slider">
                <div class="label">
                     <?php  _e('GENERIC_BEDROOMS',"realtransac"); ?>
                </div>
                <div class="element">                    
                    <div id="nor_bedroom_range">
                        <?php
                        $bed_min    =  $this->params['START']['BED']['MIN'];
                        $bed_max    =  $this->params['START']['BED']['MAX'];
                        $bed_step   =  $this->params['START']['BED']['STEP'];
                        $bed_hmax   =  $this->params['BED']['HMAX'];

                        $bedVal = $bed_min;
                        $minVal = array(0);
                        while($bedVal != $bed_max-$bed_step){
                        $bedVal += $bed_step;
                        $minVal[] = $bedVal;
                        }
                        ?>
                        <div class="bedroom_range_min rangemin">
                            <div id="mm">
                                <?php _e('GENERIC_MIN',"realtransac"); ?>
                            </div>
                        <div class="drop-down-outer">
                            <select id="bed_min" name="bed_min">
                            <?php
                            foreach ($minVal as $key=>$val) {
                                $pmore    = '';
                                $selected = '';
                                $bmin = $val;
                                if($val == '0'){
                                $bmin = 'studio';
                                }
                                if($val == $bed_max){
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
                        
                        <div class="bedroom_range_max rangemax">
                            <div id="mmxx">
                               <?php _e('GENERIC_MAX',"realtransac"); ?>
                            </div>
                        <div class="drop-down-outer">
                            <select id="bed_max" name="bed_max">
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
                            <input type="hidden" id="bed_step" name="bed_step" value="<?php echo $bed_hmax;?>"/>
                            <input id="bed_hmax" type="hidden" value="0" name="bed_hmax" value=""/>
                       </div>
                    </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="search_type" value="1" />  
            <input type="hidden" name="language" value="<?php echo $this->lang; ?>" />  
            <input type="hidden" name="location" id="location" value="<?php echo $this->params['LOCATION']; ?>" />  
            
            <input type="hidden" name="countryname" id="countryname" value="<?php echo $this->params['COUNTRY_MAP']; ?>" />   
            <input type="hidden" name="saveForm" value="1" />
            <div class="btn-outer">
                <a class="viewbutton" id="searchButton<?php echo $this->widget;?>">
                    <span class="btnleft"></span>
                    <span class="btncenter"> 
                    <?php _e('GENERIC_SEARCH',"realtransac"); ?>
                    </span>
                    <span class="btnright"></span>
                </a>
            </div>
            <div class="clear"></div>
        </div>
        </form>       
      
        <?php                    
	} // INVALID SUBSCRIPTION END
        echo '</div>';
       
    }

    public function displaySearchResults(){
         
        global  $rt_config;
        
        $html = '';      
        $html.= '<div class = "'.$rt_config['plugin_design'].' rt_searchlist rt_widget">'; 
       
	if($this->error){
             $html.= "<div  class='rt_search_wrapper'>";
                    $html.= '<div class="InvalidSub" style="color: #F4F4F7; font-size: 15px; font-weight: bold; text-align: center;">'.$this->error.'</div>';
             $html.= "</div>";
        }else{  
             $param = array(
                'apikey'         => $this->apikey,
                'version'        => $this->plugver,
                'DISTANCE'       => $this->params['DISTANCE']['VAL'],
                'TYPE'           => $this->params['TYPE'],
                'CATEGORY'       => $this->params['CATEGORY'],
                'PICTURE'        => $this->params['ISPICTURE'],
                'PRICE'          => $this->params['PRICE'],
                'BEDROOM'        => $this->params['BED'],
                'BATHROOM'       => $this->params['BATH'],
                'AREA'           => $this->params['SURFACE'],
                'localbox'       => $this->params['LOCALBOX'],
                'localisation'   => $this->params['LOCATION'],
                'country_id'     => $this->params['COUNTRYKEY'],
                'sorted'         => $this->params['ASORTED'],
                'language'       => $this->lang,
                'mls_show'       => $this->mls_show,
                'ip'             => $this->ip,
                'mcenter'        => $this->params['MCENTER']
            );
             
            /**
            * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE SEARCH RESULTS BASED ON IT */
            if(isset($rt_config['rt_currency']['globalCurrency'])) {
                $param['rtglobal_currency'] =   $rt_config['rt_currency']['globalCurrency'];
            }
            $parameters = array('data' => $param, 'ismap' => false);
            
            $result = $this->client->call('getSearchResults', $parameters, '', '', false, true);
                        
            if ($this->client->fault) {
                     echo '<h6>'.__('GENERIC_SERVER_CONNECTION_FAULT',"realtransac").'</h6>';
            } else {
                    $err = $this->client->getError();
                    if ($err) {
                            echo '<h2>'.__('GENERIC_ERROR',"realtransac").'</h2><pre>' . $err . '</pre>';
                            echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
                    }
            }

            $data           =   json_decode($result);
            $latLngArray    =   array();
            $rt_config['searchresults'] = $data->mapresult;


           //SOME TRANSLATION TEXT
            $sortdata = array(
                __('SORT_PRICE_UP','realtransac') =>'PRICE#DESC',
                __('SORT_PRICE_DOWN','realtransac') =>'PRICE#ASC',
                __('SORT_DATE_UP','realtransac')  =>'CREATION_DATE#DESC',
                __('SORT_DATE_DOWN','realtransac')  =>'CREATION_DATE#ASC'
            );
            
             $from = $page*$data->perpage;
             $from = $from + 1;
             $to   = $from+$data->perpage;
             $to   = $to - 1 ;
             if($to > $data->totalcount){
                 $to = $data->totalcount;
             }

            if($to >= 1){
                $title =   __('GENERIC_TOTAL_RESULT','realtransac').' '.$from.' - '.$to.' '.__('GENERIC_RESULT_OF','realtransac').' '.$data->totalcount;
            }
            $html.= '<div class="search_header_container"> <div class="search_head">'.$title.'</div>  <div class="rt_result_pagination"></div> </div>';
            $html.= '<div id="Searchresult">';

            if ($data->searchresult){ ?>

                     <script type="text/javascript">

                        var PER_PAGE  = '<?php echo $data->perpage; ?>';
                        var MAX_COUNT = '<?php echo $data->totalcount; ?>';
                        var searchurl = '<?php echo plugins_url('ajaxcall.php' , __FILE__).'/?lang='.$this->lang; ?>';
                        var APIKEY    = '<?php echo $this->apikey; ?>';
                        var WSDL      = '<?php echo $this->wsdl; ?>';
                        var VERSION   = '<?php echo $this->plugver; ?>';
                        var LANGUAGE  = '<?php echo $this->lang; ?>';
                        var IP        = '<?php echo $this->ip; ?>';
                        var WSDL      = '<?php echo $this->wsdl; ?>';
                        var PERMALINK = '<?php echo $this->permalink; ?>';
                        var DETAILLINK = '<?php echo $this->detailLink; ?>';
                        var WIDGETID  = '<?php echo $this->widget; ?>';
                        var PAGETYPE  = '<?php echo $this->pageType; ?>';
                        var PLUGIN_URL = '<?php echo plugins_url( '' , __FILE__ ); ?>';


                        var DISTANCE  = '<?php echo $this->params['DISTANCE']['VAL']; ?>';
                        var TYPE      = '<?php echo $this->params['TYPE']; ?>';
                        var CATEGORY  = '<?php echo $this->params['CATEGORY']; ?>';
                        var PICTURE   = '<?php echo $this->params['ISPICTURE']; ?>';
                        var PRICE     = '<?php echo json_encode($this->params['PRICE']); ?>';
                        var BEDROOM   = '<?php echo json_encode($this->params['BED']); ?>';
                        var BATHROOM  = '<?php echo json_encode($this->params['BATH']); ?>';
                        var AREA      = '<?php echo json_encode($this->params['SURFACE']); ?>';
                        var LOCALBOX  = '<?php echo $this->params['LOCALBOX']; ?>';
                        var LOCALISATION  = '<?php echo $this->params['LOCATION']; ?>';
                        var COUNTRY_ID    = '<?php echo $this->params['COUNTRYKEY']; ?>';
                        var MLS_SHOW      = '<?php echo $this->mls_show; ?>';
                        // Create pagination element with options from form
                        var options = {
                            items_per_page: PER_PAGE,
                            num_display_entries : '<?php echo NO_OF_PAGINATION_LINK_SHOW;?>',
                            num_edge_entries : '<?php echo NO_OF_PAGE_LEFT_RIGHT;?>',
                            prev_text : '<?php _e('PRV_TEXT', 'realtransac'); ?>',
                            next_text : '<?php _e('NEXT_TEXT', 'realtransac'); ?>',
                            callback: pageselectCallback
                        };
                        
                        jQuery.noConflict();
                        jQuery(document).ready(function(){
                            jQuery(".rt_result_pagination").pagination(MAX_COUNT, options);
                        });
                        
                        function sortResults()
                        {
                            jQuery("#asorted").val(jQuery("#asortedby").val());
                            jQuery(".rt_result_pagination").pagination(MAX_COUNT, options);
                            loadResults(0);
                            return false;
                        }


                        function loadResults(page)
                        {                            
                            if(jQuery('#Searchresult').height() < 50){
                               jQuery('#Searchresult').css("min-height", '50px');
                            }
                            jQuery('#Searchresult').append('<div class="loader"><?php _e("GENERIC_LOADING","realtransac"); ?></div>');
                            jQuery('#Searchresult').find('table').animate({opacity: "0.5"});

                            var loader  =jQuery('#Searchresult').find('.loader');
                            var pos     =jQuery('#Searchresult').position();
                            var top     = Math.max(0, pos.top + (jQuery('#Searchresult').height()/ 2)) - (loader.height()/2) + "px";
                            var left    = Math.max(0, pos.left + (jQuery('#Searchresult').width() / 2)) - (loader.width()/2) + "px";

                            loader.css("top", top);
                            loader.css("left", left);

                            jQuery.ajax({
                              type: "POST",
                              url: searchurl,
                              dataType: "json",
                              data: {
                                  action: 'searchresults',
                                  pluginurl: PLUGIN_URL,
                                  permalink: PERMALINK,
                                  detaillink: DETAILLINK,
                                  apikey: APIKEY,
                                  wsdl: WSDL,
                                  version: VERSION,
                                  distance:DISTANCE,
                                  type:TYPE,
                                  category:CATEGORY,
                                  picture: PICTURE,
                                  price: PRICE,
                                  bed: BEDROOM,
                                  bath: BATHROOM,
                                  area: AREA,
                                  localbox: LOCALBOX,
                                  localisation: LOCALISATION,
                                  country_id:COUNTRY_ID,
                                  sorted: jQuery('#asorted').val(),
                                  language: LANGUAGE,
                                  ip: IP,
                                  mls_show:MLS_SHOW,
                                  page: page
                            },
                            success: function( response ){
                                loadData(response.jsoncontent, false, PAGETYPE, true);
                                jQuery('.search_head').html(response.Title);
                                jQuery('#Searchresult').html(response.htmlcontent);
                                jQuery('#Searchresult').animate({opacity: "1"});
                                jQuery.isFunction(function(){setInterval(function(){new ElementMaxHeight();},500)});
                                //setInterval(function(){new ElementMaxHeight();},500);
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

                     $html.= '<div class="search_sort">';
                     $html.= '<select name="asortedby" id="asortedby" onchange="sortResults();">';

                     foreach($sortdata as $key => $val){
                            if(isset($this->params['ASORTED'])&&($this->params['ASORTED'] == $val)){

                                  $html.= '<option selected value="'.$val.'">';
                                  $html.= $key;
                                  $html.= '</option>';
                            }else {

                        $html.= '<option value="'.$val.'">';
                        $html.= $key;
                        $html.= '</option>';

                         }
                      }
                     $html.= '</select>';
                     $html.= '</div>';

                     $markCount= 0;
                     $html.= '<table id="results" celpadding="0" cellspacing="0" width="100%">';
                     $html.= '<th></th>';

                    foreach ($data->searchresult as $product){
                        $url = $this->append_params_page_url($this->detailLink, array('id' => $product->idPRODUCT));
                        $html.= '<tr>';
                        $html.= '<th>';
                        $html.= '<fieldset>';
                            $html.= '<div class="rt_listing_rows">';
                                $html.= '<div class="rt_listing_wrapper">';
                                    if($product->lat != '' && $product->lng != ''){
                                        $isExists   =   false;
                                        if(is_array($latLngArray) && count($latLngArray)>0) {
                                            foreach($latLngArray as $latLngValue) {
                                                if($product->lat==$latLngValue['Latitude'] && $product->lng==$latLngValue['Longitude']) {
                                                    $imageName  =   $latLngValue['ImageName'];
                                                    $isExists   =   true;
                                                }
                                            }
                                        }
                                        if(!$isExists) {
                                            $latLngArray[$markCount]['Latitude']    =   trim($product->lat);
                                            $latLngArray[$markCount]['Longitude']   =   trim($product->lng);
                                            $latLngArray[$markCount]['ImageName']   =   chr(65 + $markCount);
                                            $imageName  =   chr(65 + $markCount);
                                            $markCount++;
                                        }
                                        $html.= '<div class="rt_listing_marker">';
                                            $html.= '<img src="'.plugins_url( 'images/markerimage/' , __FILE__ ). $imageName.'_Hover.png'.'"/>';
                                        $html.= '</div>';
                                    }
                                    $html.= ' <div class="rt_listing_details">';
                                        $html.= '<div class="rt_listing_title">';
                                            $html.= '<span>'.$product->TITLE.'</span>';
                                        $html.= '</div>';
                                    $html.= '</div>';
                                    $html.= '<div style="clear:both;">';
                                        //PRODUCT DETAIL LEFT CONTENT
                                        $html.= '<div class="rt_listing_content_left">';
                                            $html.= ' <div class="rt_listing_iconset">';
                                                if($product->AREA != NULL){
                                                    //$html.= $product->AREA.'<img src="'.plugins_url( 'images/car.png' , __FILE__ ).'" />';
                                                }
                                                if($product->BEDROOM != NULL){
                                                    $html.= '<span class="count bedroom">'.$product->BEDROOM.'</span>';
                                                }
                                                if($product->BATHROOM != NULL){
                                                    $html.= '<span class="count bathroom">' .$product->BATHROOM.'</span><br/>';
                                                }
                                            $html.= '</div>';
                                            $html.= '<div class="rt_listing_price">';
                                                $html.= $product->PRICE->PRICE_UNIT.$product->PRICE.'<br/>';
                                            $html.= '</div>' ;
                                            $html.= '<div class="rt_listing_description">';
                                                $html.= $product->DESC.'<br/>';
                                            $html.= '</div>';
                                            $html.= '<div class="rt_listing_viewdetails">';
                                                $html.= '<a class="viewbutton" href="'.$url.'"><span class="btnleft"></span><span class="btncenter">'.__("GENERIC_VIEW_DETAILS","realtransac").'</span><span class="btnright"></span></a>';
                                                $html.= '<div class="clear"></div>';
                                            $html.= '</div>';
                                        $html.= '</div>';

                                        //PRODUCT IMAGES
                                        $html.= '<div class="rt_listing_content_right">';
                                            $html.= ' <div class="rt_listing_imgwrapper">';
                                                $html.= ' <a href="'.$url.'"><img src="'.$product->PICTURE.'" /></a>';
                                            $html.= '</div>';
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

         }else{
              $html.= '<div class="property_notfound">'.$data->error.'</div>';
        }
        $html.=  '</div>';
        $html.=  '<div class="search_bottom_container"> <div class="search_head">'.$title.'</div> <div class="rt_result_pagination"></div> </div>';
      }
      $html.=  '</div>';

      return $html;
    }
    public function displayCurrencyDrop() { 
        global  $rt_config;        
        ?>
        <div class="curr-bg">
            <script type="text/javascript">
                jQuery.noConflict();
                var globalCurr  =   '';
                var ajax_url    =   '<?php echo plugins_url( 'ajaxcall.php' , __FILE__ ); ?>';
                <?php if(isset($rt_config['rt_currency']['globalCurrency'])) { ?>
                        globalCurr  =   '<?php echo $rt_config['rt_currency']['globalCurrency'];?>';
                <?php } ?>
                function setCurrency(code) {
                    if(globalCurr!='' && code==globalCurr) {
                        return false;
                    }
                    jQuery.ajax({ 
                        url: ajax_url,
                        type: "POST",
                        dataType:"json",
                        data: {action: 'setcurrency', currency : code},
                        success: function(data) {                                                      
                            if(data.result=="success") {                                
                                window.location.href = window.location.href.replace(/#.*$/, '');
                            }
                        }
                    });
                }
                jQuery(function(){
                    jQuery(".currency_switch_dropdown dt a").click(function() {
                        jQuery(".currency_switch_dropdown dd ul").toggle();
                    });

                    jQuery(".currency_switch_dropdown dd ul li a").click(function() {
                        var code = jQuery(this).html();
                        text     = code+"<span></span>";
                        jQuery(".currency_switch_dropdown dd ul").hide();
                        jQuery(".currency_switch_dropdown dt a").html(text);
                        setCurrency(code);
                    });

                    jQuery(document).bind('click', function(e) {
                        var clicked = jQuery(e.target);
                        if (! clicked.parents().hasClass("currency_switch_dropdown"))
                            jQuery(".currency_switch_dropdown dd ul").hide();
                    });
                });
            </script>
            <?php
                $activeCurrency =   '';
                $firstCurrency  =   '';
                $currencyKey    =   '0';
                $currencyCodes  =   $rt_config['rt_currency']['currency_list'];
                /**
                * SETTING AGENCY CURRENCY AS DEFAULT ONE */
                foreach($currencyCodes as $currency) {
                    if($currency["IsDefault"]){
                        $activeCurrency  =  $currency["currencyCode"];
                    }
                }

                $html_drop      = '<dd><ul class="currency_chooser" id="currency-chooser">';
                foreach($currencyCodes as $currency) {
                    if($currencyKey=='0') {
                        $firstCurrency  =   $currency["currencyCode"];
                    }
                    if(isset($rt_config['rt_currency']['globalCurrency']) && $rt_config['rt_currency']['globalCurrency'] == $currency["currencyCode"]){
                        $activeCurrency =   $currency["currencyCode"];
                    }
                    $html_drop .= '<li><a href="#">'.$currency["currencyCode"].'</a></li>';
                    $currencyKey++;
                }
                $html_drop .= "</ul></dd>";

                if($activeCurrency=='') {
                    $activeCurrency  =  $firstCurrency;
                }
                echo '<div class="currency_switch">';
                echo '<dl class="currency_switch_dropdown"><dt><a href="#">'.$activeCurrency.'<span></span></a></dt>'.$html_drop.'</dl>';
                echo '</div>';
            ?>
        </div>
    <?php
    }
}
?>