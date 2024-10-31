<?php
/**
 * Realtransac AJAX Process Execution.
 *
 * @package Realtransac wordpress connect 
 * 
 */
if(!session_id()){
    session_start();
}
define( 'DOING_AJAX', true );
define( 'WP_ADMIN', false );

/** Load WordPress Bootstrap */
require_once( dirname( dirname( __FILE__ ) ) . '/../../wp-load.php' );

/** Allow for cross-domain requests (from the frontend). */
send_origin_headers();

// Require an action parameter
if ( empty( $_REQUEST['action'] ) )
	die();

@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
@header( 'X-Robots-Tag: noindex' );

send_nosniff_header();
nocache_headers();

$core_actions = array(
	'formatarea', 'dynamicvalues', 'searchresults','productsearchresults','agentresult','research', 'setcurrency','martgage'
);

// Register core Ajax calls.
if ( ! empty( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $core_actions ) )
	add_action( 'rt_ajax_' . $_REQUEST['action'], 'rt_ajax_' . str_replace( '-', '_', $_REQUEST['action'] ), 1 );

do_action( 'rt_ajax_' . $_REQUEST['action'] );

function rt_ajax_formatarea() {
    
    require_once('lib/soap/nusoap.php');
    include_once 'common.class.php';
    if(isset($_POST)){
        extract($_POST);
    }

    $data = '';

    try{
        
        $client        =   new nusoap_client($wsdl, 'wsdl'); // true is for WSDL
        $common_class  =   new Realtransac_API_Common();

	$parameters = array('data' => array( 'area'    => $area, 'isrange' =>$isrange,  'unit' =>$unit  ));
        $data = $client->call('getFormatArea', $parameters, '', '', true, false);

        // Check for a fault
        if($client->fault){
            $data .= '<h6>'.__('GENERIC_SERVER_CONNECTION_FAULT',"realtransac").'</h6>';
        }else{
            $err = $client->getError();
            if ($err){
                    $data .= '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
            }
        }

    }catch(Exception $ex){
        //echo "<h4>".'Message: ' .$ex->getMessage()."</h4>";
        if($ex->faultcode){
           $data .= '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
       }
    }
      
    wp_die( $data );
}

function rt_ajax_dynamicvalues() {	

        require_once('lib/soap/nusoap.php');
        include_once 'common.class.php';
        if(isset($_POST)){
            extract($_POST);
        }

        $data = '';

        try{
            $client        =   new nusoap_client($wsdl, 'wsdl'); // true is for WSDL
            $common_class  =   new Realtransac_API_Common();
            $param         =   array(
                                     'apikey' => $apikey,
                                     'wsdl' => $wsdl,
                                     'id' => $id,
                                     'countryId'=> $countryId,
                                     'typeid' => $typeid,
                                     'language' => $language,
                                     'category' => $category,
                                     'ajax' => $ajax,
                                     'level' => $level,
                                     'ignoreAll' => $ignoreAll,
                                     'isPortal' => $isPortal
                                    );
            /**
            * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE FORM BASED ON IT */
            if(isset($_SESSION['RT_CURRENCY']['CURRENCY'])) {
                $param['rtglobal_currency'] =   $_SESSION['RT_CURRENCY']['CURRENCY'];
            }
            $parameters = array('data' => $param);

            $data = $client->call('getDynamicvalues', $parameters, '', '', true, false);
            
            /**
            * IF INVALID CURRENCY HAS BEEN GIVEN IN ADMIN, THEN WE WILL SWITCH TO DEFAULT CURRENCY */
            if($data->SwitchToDefault) {
                $_SESSION['RT_CURRENCY']['CURRENCY']    =   $_SESSION['RT_CURRENCY']['DEFAULT_CURRENCY'];
            }
            
            // Check for a fault
            if($client->fault){
                $data .= '<h6>'.__('GENERIC_SERVER_CONNECTION_FAULT',"realtransac").'</h6>';
            }else{
                $err = $client->getError();
                if ($err){
                        $data .= '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
                }
            }
        }catch(Exception $ex){
            if($ex->faultcode){
               $data .= '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
           }
        }
        
	wp_die( $data );
}

function rt_ajax_searchresults() {

        require_once('lib/soap/nusoap.php');
        include_once 'common.class.php';
        if(isset($_POST)){
            extract($_POST);
        }

        $data    = '';
        $isValid = false;
        try{
            
            $client        =   new nusoap_client($wsdl, 'wsdl'); // true is for WSDL
            $common_class  =   new Realtransac_API_Common();

            //SOME TRANSLATION TEXT
            $sortdata = array(
                __('SORT_PRICE_UP','realtransac') =>'PRICE#DESC',
                __('SORT_PRICE_DOWN','realtransac') =>'PRICE#ASC',
                __('SORT_DATE_UP','realtransac')  =>'CREATION_DATE#DESC',
                __('SORT_DATE_DOWN','realtransac')  =>'CREATION_DATE#ASC'
            );

           $param = array(

                'apikey'         => $apikey,
                'version'        => $version,
                'DISTANCE'       => $distance,
                'TYPE'           => $type,
                'CATEGORY'       => $category,
                'PICTURE'        => $picture,
                'PRICE'          => json_decode(stripslashes($price)),
                'BEDROOM'        => json_decode(stripslashes($bed)),
                'BATHROOM'       => json_decode(stripslashes($bath)),
                'AREA'           => json_decode(stripslashes($area)),
                'localbox'       => stripslashes($localbox),
                'localisation'   => $localisation,
                'country_id'     => $country_id,
                'sorted'         => $sorted,
                'language'       => $language,
                'ip'             => $ip,
                'mls_show'       => $mls_show,
                'page'           => $page
            );
            /**
            * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE FORM BASED ON IT */
            if(isset($_SESSION['RT_CURRENCY']['CURRENCY'])) {
                $param['rtglobal_currency'] =   $_SESSION['RT_CURRENCY']['CURRENCY'];
            }
            $parameters = array('data' => $param, 'ismap' => false );
            
            $result = $client->call('getSearchResults', $parameters, '', '', false, true);
            
            // Check for a fault
            if ($client->fault) {
                     $data .= "<h6>".'Message: ' ."Server Connection Fault"."</h6>";
            } else {
                    // Check for errors
                    $err = $client->getError();
                    if ($err) {
                            // Display the error
                            $data .= '<h2>Error</h2><pre>' . $err . '</pre>';
                            $data .= "<h6>".'Message: ' ."Server Connection Error"."</h6>";
                    }
            }

            $searchdata     =   json_decode($result);
            $latLngArray    =   array();
            if ($searchdata->searchresult){
                        
                     $from = $page*$searchdata->perpage;
                     $from = $from + 1;
                     $to = $from+$searchdata->perpage;
                     $to = $to - 1 ;
                     if($to > $searchdata->totalcount){
                         $to = $searchdata->totalcount;
                     }
                     $title= '<div class="search_head">'.__('GENERIC_TOTAL_RESULT','realtransac').' '.$from.' - '.$to.' '.__('GENERIC_RESULT_OF','realtransac').' '.$searchdata->totalcount.'</div>';
                     
                     $data.= '<div class="search_sort">';
                     $data.= '<select name="asortedby" id="asortedby" onchange="sortResults();">';


                     foreach($sortdata as $key => $val){
                            if(isset($sorted)&&($sorted == $val)){

                                  $data.= '<option selected value="'.$val.'">';
                                  $data.= $key;
                                  $data.= '</option>';
                            }else {

                        $data.= '<option value="'.$val.'">';
                        $data.= $key;
                        $data.= '</option>';

                         }
                      }
                     $data.= '</select>';
                     $data.= '</div>';

                     $markCount= 0;
                     $data.= '<table id="results" celpadding="0" cellspacing="0" width="100%">';
                     $data.= '<th></th>';
                    foreach ($searchdata->searchresult as $product){
                         $url = $common_class->append_params_page_url($detaillink, array('id' => $product->idPRODUCT));

                        $data.= '<tr>';
                        $data.= '<th>';
                        $data.= '<fieldset>';
                            $data.= '<div class="rt_listing_rows">';
                                $data.= '<div class="rt_listing_wrapper">';
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
                                            $data.= ' <div class="rt_listing_marker">';
                                                $data.= '<img src="'.plugins_url( 'images/markerimage/' , __FILE__ ). $imageName.'_Hover.png'.'"/>';
                                            $data.= '</div>';
                                        }
                                    $data.= ' <div class="rt_listing_details">';
                                        $data.= '<div class="rt_listing_title">';
                                            $data.= '<span>'.$product->TITLE.'</span>';
                                        $data.= '</div>';
                                    $data.= '</div>';
                                    $data.= '<div style="clear:both;">';
                                        //PRODUCT DETAIL LEFT CONTENT
                                        $data.= '<div class="rt_listing_content_left">';
                                            $data.= ' <div class="rt_listing_iconset">';
                                                if($product->AREA != NULL){
                                                    // $data.= $product->AREA.'<img src="'.plugins_url( 'images/car.png' , __FILE__ ).'" />';
                                                }
                                                if($product->BEDROOM != NULL){
                                                    $data.= '<span class="count bedroom">'.$product->BEDROOM.'</span>';
                                                }
                                                if($product->BATHROOM != NULL){
                                                    $data.= '<span class="count bathroom">' .$product->BATHROOM.'</span><br/>';
                                                }
                                            $data.= '</div>';
                                            $data.= ' <div class="rt_listing_price">';
                                                $data.= $product->PRICE->PRICE_UNIT.$product->PRICE.'<br/>';
                                            $data.= '</div>' ;
                                            $data.= '<div class="rt_listing_description">';
                                                $data.= $product->DESC.'<br/>';
                                            $data.= '</div>';
                                            $data.= '<div class="rt_listing_viewdetails">';
                                                $data.= '<a class="viewbutton" href="'.$url.'"><span class="btnleft"></span><span class="btncenter">'.__("GENERIC_VIEW_DETAILS","realtransac").'</span><span class="btnright"></span></a>';
                                                $data.= '<div class="clear"></div>';
                                            $data.= '</div>';
                                        $data.= '</div>';

                                        //PRODUCT IMAGES
                                        $data.= '<div class="rt_listing_content_right">';
                                            $data.= ' <div class="rt_listing_imgwrapper">';
                                                $data.= ' <a href="'.$url.'"><img src="'.$product->PICTURE.'" /></a>';
                                            $data.= '</div>';
                                        $data.= '</div>';
                                    $data.= '</div>';
                                    $data.= '<div style="clear:both;"></div>';
                                $data.= '</div>';
                            $data.= '</div>';
                     $data.= '</fieldset>';
                     $data.= '</th>';
                     $data.= '</tr>';

                 }
               $data.= '</table>';

         }else{

              $data.= '<div class="property_notfound">'.$searchdata->error.'</div>';

        }
        
    }catch(Exception $ex){
        //echo "<h4>".'Message: ' .$ex->getMessage()."</h4>";
        if($ex->faultcode){
           echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
       }
    }

    $return = array(
        'Title'       => $title,
        'htmlcontent' => $data,
        'jsoncontent' => $searchdata->mapresult,
    );
  
    wp_die(json_encode($return));
}
function rt_ajax_productsearchresults(){
    
        require_once('lib/soap/nusoap.php');
        include_once 'common.class.php';
        if(isset($_POST)){
            extract($_POST);
        }

        $html  = '';
        $title = '';

        try{
            
            $client        =   new nusoap_client($wsdl, 'wsdl'); // true is for WSDL
            $common_class  =   new Realtransac_API_Common();
           
          $param = array(
            'apikey'   => $apikey, 
            'version'  => $version,
            'type'     => $type,
            'show'     => $show,
            'built'    => $built,
            'limit'    => $limit,
            'language' => $language,
            'page'     => $page
        );
        
        /**
        * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE FORM BASED ON IT */
        if(isset($_SESSION['RT_CURRENCY']['CURRENCY'])) {
            $param['rtglobal_currency'] =   $_SESSION['RT_CURRENCY']['CURRENCY'];
        }
            $parameters = array('data' => $param, 'ismap' => false);
            $result = $client->call('getPropertyList', $parameters, '', '', false, true);

            // Check for a fault
            if ($client->fault) {
                     $html .= "<h6>".'Message: ' ."Server Connection Fault"."</h6>";
            } else {
                    // Check for errors
                    $err = $client->getError();
                    if ($err) {
                            // Display the error
                            $html .= '<h2>Error</h2><pre>' . $err . '</pre>';
                            $html .= "<h6>".'Message: ' ."Server Connection Error"."</h6>";
                    }
            }

            $productsearchdata   =   json_decode($result);
           
            if ($productsearchdata->products){

                 $from = $page*$productsearchdata->perpage;
                 $from = $from + 1;
                 $to   = $from+$productsearchdata->perpage;
                 $to   = $to - 1 ;
                 if($to > $productsearchdata->totalcount){
                     $to = $productsearchdata->totalcount;
                 }
                $title= '<div class="search_head">'.__('GENERIC_TOTAL_RESULT','realtransac').' '.$from.' - '.$to.' '.__('GENERIC_RESULT_OF','realtransac').' '.$productsearchdata->totalcount.'</div>';               
                
                $html.= '<table id="results" celpadding="0" cellspacing="0" width="100%">';
                $html.= '<th></th>';
                 
                foreach ($productsearchdata->products as $product){
                
                $url = $common_class->append_params_page_url($detailLink, array('id' => $product->id));
                
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

         }else{
             
               $html.= '<div class="property_notfound">'.$productsearchdata->error.'</div>';
               
        }
        
    }catch(Exception $ex){
        //echo "<h4>".'Message: ' .$ex->getMessage()."</h4>";
        if($ex->faultcode){
           echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
       }
    }
     $return = array(
        'Title'       => $title,
        'htmlcontent' => $html,
     );
    
     wp_die(json_encode($return));
}

function rt_ajax_agentresult(){
    
        require_once('lib/soap/nusoap.php');
        include_once 'common.class.php';
        if(isset($_POST)){
            extract($_POST);
        }

        $html  = '';
        $title = '';

        try{
            
            $client        =   new nusoap_client($wsdl, 'wsdl'); // true is for WSDL
            $common_class  =   new Realtransac_API_Common();

            $param = array(
                'apikey'   => $apikey, 
                'version'  => $version,
                'language' => $language,
                'filter'   => $filter,
                'limit'    => $limit,
                'partners' => $partners,
                'page'     => $page
                );

            $parameters = array('data' => $param, 'ismap' => false);

            $result = $client->call('getListingBroker', $parameters, '', '', false, true);

            // Check for a fault
            if ($client->fault) {
                     $html .= "<h6>".'Message: ' ."Server Connection Fault"."</h6>";
            } else {
                    // Check for errors
                    $err = $client->getError();
                    if ($err) {
                            // Display the error
                            $html .= '<h2>Error</h2><pre>' . $err . '</pre>';
                            $html .= "<h6>".'Message: ' ."Server Connection Error"."</h6>";
                    }
            }

            $productsearchdata   =   json_decode($result);
            
            $title = $productsearchdata->totalcount;
            
            if($productsearchdata->agentlist){
                $html = '<div id="rt_agent_container" class="">
                    <table cellpadding="3" border="0" cellspacing="0" width="100%">  
                        <tbody>';                  
                        foreach($productsearchdata->agentlist as $key => $agency){
                            
                            //$url = $common_class->append_params_page_url($permalink, array('id' => $agency->idUSERS));
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
                                        <td class="rt_agency-link-desc">'.$agency->city.'</td>
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
              $html .= '</tbody>
                </table>  
             </div>';
            }else{
                $html = '<div id="rt_agent_container" class="">
                            <div class="no_result">'.__('GENERIC_NO_RESULT',"realtransac").'</div>
                         </div>';
          }

            
        }catch(Exception $ex){
                //echo "<h4>".'Message: ' .$ex->getMessage()."</h4>";
            if($ex->faultcode){
               echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
           }
        }
        
        $return = array(
           'Title'       => $title, 
           'htmlcontent' => $html,
        );
    
     wp_die(json_encode($return));
}

function rt_ajax_research(){
    
        require_once('lib/soap/nusoap.php');
        include_once 'common.class.php';
        if(isset($_POST)){
            extract($_POST);
        }

        $html  = '';
        $title = '';

        try{
            
            $client        =   new nusoap_client($wsdl, 'wsdl'); // true is for WSDL
            $common_class  =   new Realtransac_API_Common();

            $param = array(
                'apikey'   => $apikey, 
                'version'  => $version,
                'language' => $language,
                'filter'   => '1',
                'CATEGORY' => $category,
                'TYPE'     => $typeid,
                'country_id'=>$country_id,
                'page'     => $page
                );
            /**
            * IF CURRENCY SESSION HAS BEEN TRIGGERED, THEN WE NEED TO MAKE THE FORM BASED ON IT */
            if(isset($_SESSION['RT_CURRENCY']['CURRENCY'])) {
                $param['rtglobal_currency'] =   $_SESSION['RT_CURRENCY']['CURRENCY'];
            }
            $_SESSION['RESEARCH']['country']  = $country_id;
            $_SESSION['RESEARCH']['type']     = $typeid;
            $_SESSION['RESEARCH']['category'] = $category;
            
            $parameters = array('data' => $param, 'ismap' => false);

            $result = $client->call('getResearchList', $parameters, '', '', false, true);

            
            // Check for a fault
            if ($client->fault) {
                     $html .= "<h6>".'Message: ' ."Server Connection Fault"."</h6>";
            } else {
                // Check for errors
                $err = $client->getError();
                if ($err) {
                        // Display the error
                        $html .= '<h2>Error</h2><pre>' . $err . '</pre>';
                        $html .= "<h6>".'Message: ' ."Server Connection Error"."</h6>";
                }
            }

            $research   =   json_decode($result);
            
            
            $total   = $research->totalcount;
            $perpage = $research->perpage;
            
            if($research->research){
                $html .= '<div id="rt_research_container" class="">';
                    $html .= '<table cellpadding="1" border="0" cellspacing="0" width="100%">';
                        $html .= '<tbody>';
                            $html .= '<tr class=rt_research_header>
                                        <th width = "13%" style="padding-left: 10px;">'. __('GENERIC_CATEGORY','realtransac').'</th>
                                        <th width = "10%">'.__('GENERIC_TYPE','realtransac').'</th>
                                        <th width = "45%">'.__('GENERIC_DESCRIPTION','realtransac').'</th>
                                        <th width = "22%">'.__('GENERIC_BUDGET','realtransac').'</th>
                                        <th width = "10%"> </th>
                                    </tr>';
                                    if ($research->research){    
                                        foreach($research->research as $key => $agency){

                                            if($agency->deal_type == '1'){
                                                $type = __('GENERIC_SALE','realtransac');
                                            }else {
                                                $type = __('GENERIC_RENT','realtransac');
                                            }

                                            $html .= '<tr class="rt_research_rows">  
                                                        <td class="rt_research_val first rt_research_cat_'.$agency->property_type.'">  </td>
                                                        <td class="rt_research_val">'.$type.'</td>
                                                        <td class="rt_research_val">'.$agency->RE_DESCRIPTION.'</td>
                                                        <td class="rt_research_val">'.$agency->RE_BUDGET.'</td>
                                                        <td class="rt_search-button"> 
                                                            <a class="rt_research_mail" href="mailto:'.$agency->ContactEmail.'">
                                                           </a>
                                                       </td>
                                                    </tr>
                                                    <tr><td></td></tr>';
                                        }
                                    }
                        $html .= '</tbody>';
                    $html .= '</table>';  
                $html .= '</div>';
            }else{
                $html = '<div id="rt_research_container" class="">
                            <div class="no_result">'.__('GENERIC_NO_RESULT',"realtransac").'</div>
                         </div>';
          }

            
        }catch(Exception $ex){
                //echo "<h4>".'Message: ' .$ex->getMessage()."</h4>";
            if($ex->faultcode){
               echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
           }
        }
        
        $return = array(
                'total'       => $total, 
                'perpage'     => $perpage,
                'htmlcontent' => $html
            );
    
     wp_die(json_encode($return));
}

function rt_ajax_setcurrency() {
    include_once 'common.class.php';
    if(isset($_POST)){
        extract($_POST);
    }
    try {
        $_SESSION['RT_CURRENCY']['OLD_CURRENCY']    =   $_SESSION['RT_CURRENCY']['CURRENCY'];
        $_SESSION['RT_CURRENCY']['CURRENCY']        =   $currency;
    } catch(Exception $ex) {
        if($ex->faultcode){
            echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
        }
    }
    wp_die(json_encode(array("result" => "success")));
}
function rt_ajax_martgage() {
    include_once 'common.class.php';
    if(isset($_POST)){
        extract($_POST);
    }

    $html ='';
    try {
        
        /* Calculate total amount per month*/
        if($term == '1'){
        $payments = $termsofyears; 
        }else{
        $payments = $termsofyears * 12;
        }
        $percentage  = $interestrate / 100 / 12; //Monthly interest rate
        $totalAmount =  $amount * ( $percentage * pow(1 + $percentage, $payments) ) / ( pow(1 + $percentage, $payments) - 1);
        
        /* Display Results */
        
        $html .= '<div class="mortgage_result_container">
                    <table border="0" cellspacing="0" cellpadding="0" class="" align="center">
                         <tr>
                            <td>
                                <fieldset>
                                    <legend><b>'. __('GENERIC_RESULT',"realtransac") .'</b></legend>
                                    <hr></hr>
                                        <table border="0" cellspacing="10" cellpadding="0" >
                                            <tr>
                                                <td><div class="RESearchLabelStatus">'. __('MORTGAGE_MORTGAGE_AMOUNT',"realtransac").'</div></td>
                                                <td><div class="RESearchColon">: </div></td>
                                                <td><div class="RESearchElementStatus">'. $amount .'</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><div class="RESearchLabelStatus">'. __('MORTGAGE_INTEREST_RATE',"realtransac").'</div></td>
                                                <td><div class="RESearchColon">: </div></td>
                                                <td><div class="RESearchElementStatus">'.$interestrate.'</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><div class="RESearchLabelStatus">'. __('MORTGAGE_INTEREST_TERM',"realtransac").'</div></td>
                                                <td><div class="RESearchColon">: </div></td>
                                                <td><div class="RESearchElementStatus">'. $termsofyears.'</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><div class="RESearchLabelStatus">'. __('MORTGAGE_MONTHLY_PAYMENTS',"realtransac").'</div></td>
                                                <td><div class="RESearchColon">: </div></td>
                                                <td><div class="RESearchElementStatus">'. round($totalAmount,2).'</div>
                                                </td>
                                            </tr> 
                                        </table>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </div>';
    } catch(Exception $ex) {
        if($ex->faultcode){
            echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
        }
    }
    wp_die(json_encode(array("MortgageResult" => $html)));
}
// Default status
die();

?>