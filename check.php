<?php
    session_start();
    $captcha     = '';
    $captcha_img = '';
    
    if(isset($_POST['contact_letters_code']) && $_POST['contact_letters_code'] != ''){
        
        $captcha        =   $_POST['contact_letters_code'];
        $captcha_img    =   $_SESSION['contact_letters_code'];
        
    }else if(isset($_POST['worth_letters_code']) && $_POST['worth_letters_code'] != ''){
        
        $captcha        =   $_POST['worth_letters_code'];
        $captcha_img    =   $_SESSION['worth_letters_code'];
        
    }else if(isset($_POST['view_letters_code']) && $_POST['view_letters_code'] != ''){
        
        $captcha        =   $_POST['view_letters_code'];
        $captcha_img    =   $_SESSION['view_letters_code'];
        
    }


    if($captcha == $captcha_img){
        echo "true";
    } else {
        echo "false";
    }

?>