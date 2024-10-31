<?php
/*include_once 'common.class.php';

class Realtransac_API_AgentInformation extends Realtransac_API_Common {

     public function __construct($instance, $widget_id){
       
        global  $rt_config;
        $this->plugver  = plugin_get_version();
        $this->widget   = $widget_id;                
        $this->wsdl     = $rt_config['wsdl'];
        $this->apikey   = $rt_config['apikey'];
        $this->ip       = $rt_config['ip'];
        $this->client   = $rt_config['client'];
        $this->pageType = $rt_config['pageType'];
        $this->agentId  = $instance['id'];
        $this->qtranslate   = false;
        $this->permalink    = apply_filters('the_permalink', get_permalink());        
                      
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
            'language' => $this->lang,
            'agentId'       => $this->agentId
        );
        
        $parameters = array('data' => $param);
        $result     = $this->client->call('getAgentInformation', $parameters, '', '', false, true);
        
        if ($this->client->fault) {
                 echo '<h6>'.__('GENERIC_SERVER_CONNECTION_FAULT',"realtransac").'</h6>';
        } else {
                $err = $this->client->getError();
                if ($err) {
                        echo '<h6>'.__('GENERIC_SERVER_CONNECTION_ERROR',"realtransac").'</h6>';
                }
        }
        
        $this->results   =   json_decode($result);

        //echo "<pre>";print_r($this->results);echo "</pre>";


    }
     
     public function agentInformation(){
        
         $html = '';
         $html = '<div class="rt_agent rt_widget_content">
                    <div  class="rt_agent_row rt_agent_down">';
                   
                   if($this->results->agent){
                           $html .= '<br><br> AGENT INFORMATION <br><br>'; 
                           foreach ($this->results->agent as $key => $val) {
                                    $html .= $key .' => '.$val;
                                    $html .= '<br>';
                           }
                           $html .= '<br><br> AGENCY OR BROKER INFORMATION (apikey) <br><br>'; 
                           foreach ($this->results->agency as $key => $val) {
                                    $html .= $key .' => '.$val;
                                    $html .= '<br>';
                           }
                   }
                       
         $html .= '</div> </div>';
       
       return   $html;        
     }
     
} */
?>

 