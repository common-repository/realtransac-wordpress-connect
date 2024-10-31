function LoadMsg(url) {
    window.location = url;
}


function loadDataGrid(container, url, callback, data){    
    
    jQuery(container).html('<div class="loader">Loading...</div>');
    setTimeout(function(){
        jQuery(container).load(url, data, function() {            
            if(typeof callback == 'function'){                
                callback();
            }            
     
            jQuery('.loader').hide();
        });        
    }, 1000);
}
function fnConfirm(txt){
    var result = confirm (txt)
    if (result){
        return true;
    }else{
        return false;
    }
        
}
function urlConfirm(url,txt){
    var result = confirm (txt)
    if (result){
        LoadMsg(url);
        return true;
    }else{
        return false;
    }

}
function ShowOrHide(div) {
        var ele = document.getElementById(div);
        if(ele.style.display == "none"){
            ele.style.display = "block" ;
        }else{
            ele.style.display = "none" ;
        }
    }
function Check(ele)
{
    var chk = document.forms[0].elements["ids[]"];
   
    if(chk.length){
        for (i = 0; i < chk.length; i++){             
            if(ele.checked == true){
                chk[i].parentNode.setAttribute('class','checked');
                chk[i].checked = true ;
            }else{
                chk[i].parentNode.removeAttribute('class');
                chk[i].checked = false ;
            }            
        }
    }else{
        if(ele.checked ==true){
            chk.checked = true ;
        }else{
            chk.checked = false ;
        }
    }
}
function findCheck(){
    var chk = document.forms[0].elements["ids[]"];
    if(chk.length){
        for (i = 0; i < chk.length; i++){
            if(chk[i].checked == true){
              return true;
            }
        }
    }else{
        if(chk.checked == true){
            return true;
        }
    }
    alert("No Conversations Selected");
    return false;
}
/*
function growSelect(element){
    var elem = document.getElementById(element);
    elem.style.width = "150px";
}
function shrinkSelect(element){
    document.getElementById(element).style.width = "100px";
}
*/