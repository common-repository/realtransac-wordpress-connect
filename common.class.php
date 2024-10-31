<?php

class Realtransac_API_Common{
  
    
    public function __construct(){
            
    } 
        
    public function append_params_page_url($url, $params = array()) {
        
        $return       = '';
        $temp_params  = array();    
        $temp_url     = '';
        $postion      = strpos($url, '?');
        
        if($postion){
          // ? Exists

          $url_split  = explode('?', $url);  

          if(isset($url_split[0]) && $url_split[0] != ''){
             // Url Split
             $temp_url = $url_split[0]; // Url
             
             if(isset($url_split[1]) && $url_split[1] != ''){  
                $temp_url_params = explode('&', $url_split[1]); // Url Params
                
                $temp_parameters = array();
                foreach ($temp_url_params as $value)
                {
                    $temp = explode('=', $value);
                    if(isset($temp[0]) && isset($temp[1])){ 
                        $temp_parameters[$temp[0]] = $temp[1];
                    }
                }
                $temp_params   = array_merge($temp_parameters, $params);    
               
              }
          }

        }else{      
          // ? Not Exists
          $temp_params   = $params;     
          $temp_url     = $url;
        }

        
        $parameters = array();
      
        foreach ($temp_params as $key => $value)
        {
            $parameters[] = $key.'='.$value;
        }
  
        
        return $temp_url.'?'.implode('&', $parameters);
    }
    
    
} 
?>