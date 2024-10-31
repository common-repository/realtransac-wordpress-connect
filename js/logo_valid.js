
// Set maxlength value for your required fields
var maxlength = 255;
/*
* You can pass three parameters this function
* Example : ValidateRequiredField(phone,"Telephone must be filled out!", "number");
* For string format no need to pass any strFormat.
*/
function ValidateRequiredField_logo(field,alerttxt,strFormat) {
with (field) {
if (value == null|| value == "") {
field.style.background= "white";
alert(alerttxt);return false;
} else if (value.length > maxlength ) {
field.style.background= "grey";
alert('Maxlenth should be not more than 255 charactor');return false;
} else if (strFormat == 'number' && isNaN(value) ) {
field.style.background= "grey";
alert(field.name + ' is not a number, Please put in Numric format');return false;
} else {return true;}
}
}
 

 
function ValidateCompleteForm(thisform){
with (thisform) {
  

if (ValidateRequiredField_logo(apikey,"Please enter a valid APIKEY!")== false) {apikey.focus();return false;}
if (ValidateRequiredField_logo(apiwsdl,"Please enter a valid WSDL is required!")== false) {apiwsdl.focus();return false;}


 
}
}

function ValidateCompleteForm_logo(thisform){
with (thisform) {
  
if (ValidateRequiredField_logo(image,"Please enter a valid image!")== false) {image.focus();return false;}

 
}
}

