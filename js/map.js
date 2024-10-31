
 var mlatlang = new google.maps.LatLng(48.48, 2.20);
 var mCount;
 var nCount=0;


 // To Hold all the markers
 var markers = new Array();

// To Hold all the groups
 var groups = new Array();

 var map,circleMarker,poly;
 var polypath;
 var selected;
 var notShowInfoWindow = false;
 var geocoder;
 var ptype;

 function checkMap(){
    if(self.map){return true;}else{return false;}
 }

 function select(buttonId) {
     document.getElementById('shape_poly').className="unselected";
     document.getElementById('shape_circ').className="unselected";
     document.getElementById(buttonId).className="selected";
     document.getElementById('shape').value = buttonId;
     self.selected = buttonId;
 }

 function closeInfowindow() {
     if (self.infowindow != null) {
         self.infowindow.close();
     }
 }

 function getMarkerDetails(id){
     var result;
     jQuery.each(markersArray, function(i, m){
         if(m.id == id){
             result = m;
         }
     });
       return result;     
 }

 function showInfoWindow(items){
     
         var tabs = '';
         var pages = '';
         jQuery.each(items, function(j, item){
             var mInfo = getMarkerDetails(item);
     
             tabs    +=  '<li><a href="#mapTabs-'+(j+1)+'">'+(mInfo.label) +'</a></li>';
             pages   +=  '<div id="mapTabs-'+(j+1)+'" class="ui-mtabs-panel">';
             pages   +=  '<div class="IWAdHeader"><div class="prevDiv"></div><div class="nextDiv"></div></div>';
             pages   +=  '<div class="IWAdMain">';
             pages   +=  '<div class="IWAdLeft">';
             if (mInfo.picture){
                 pages += '<img class="thumb" alt="'+ mInfo.title +'" title="'+ mInfo.title +'" src="'+mInfo.picture+'"/>';
             }else{
                 pages += '<img class="thumb" src="'+includePath+'/images/noImg.jpg"/>';
             }
             pages   +=  '</div>';
             pages   +=  '<div class="IWAdRight">';
             pages   +=  '<div class="IWAdText"><p class="IWAdHead">'+ mInfo.title +'</p><p>'+ mInfo.content +'</p></div>';
             var center = self.circleMarker.getPosition();
             // Divided by 10 to represent the radius accurately
             // 1 unit = 10 units
             var d = distanceBetweenPoints(center, new google.maps.LatLng(mInfo.lat, mInfo.lng)) / 10;
             //pages   +=  '<div class="IWAdText"><p class="IWAdHead">Area: '+ mInfo.area['VALUE'] +' '+ mInfo.area['UNITS']+'</p><p class="IWAdHead">Price: '+ mInfo.showprice +'</p><p class="IWAdHead">BedRooms: '+ mInfo.bedroom['VALUE']+'</p><p class="IWAdHead">Bathrooms: '+ mInfo.bathroom['VALUE'] +'</p><p class="IWAdHead">Distance: '+(d.toFixed(2))+'</p></div>';
             pages   +=  '</div>';
             pages   +=  '</div>';
             pages   +=  '</div>';

         });        

         pagesHtml =  '<div class="mapTabs"><ul>'+ tabs + '</ul>'+ pages +'</div>';

     return pagesHtml;
 }

 function mapCenter(latlang){

	 if(!latlang){
             latlang = self.map.getCenter();
         }
         self.map.setCenter(latlang);
         self.circleMarker.setPosition(latlang);
         if (self.circlePolygon != null) {
            self.circlePolygon.setMap(null);
         }
         setMapCenter(latlang);
         updateCircle();
 }
 
 function setMapCenter(mcenter) {
     var eleCenter = document.getElementById('map_center');

     if(eleCenter){
         eleCenter.value = mcenter.lat()+'#'+ mcenter.lng();
     }     
 }
 
 function getMapCenter() {
     var eleCenter = document.getElementById('map_center');
     if(eleCenter.value != 0 && eleCenter.value != '#'){
         latlng = eleCenter.value.split('#')
         mlatlang = new google.maps.LatLng(parseFloat(latlng[0]), parseFloat(latlng[1]));
     }
     return eleCenter.value;
 }

 function setZoom(level) {
     var eleZoom = document.getElementById('zoom_level');
     if(eleZoom){
         eleZoom.value = level;
     }
 }
 
 function getZoom() {
     var CurZoom = 8;
     var eleZoom = document.getElementById('zoom_level');
     if(eleZoom.value != 0){
         CurZoom = eleZoom.value;
     }
     return CurZoom;
 }
 
 function loadData(json, notShowInfo, pageType, mexcat){
    //alert(json.toSource());

    json = eval(json);
    ptype = pageType;
    self.params = new Object();
    if(json){
        markersArray = json;
        mCount = markersArray.length;
    }else{
        mCount = 0;
    }
    var z = parseInt(getZoom());
    mapcenter = getMapCenter();
    var mapOptions = {
        zoom        : 9,
        minZoom     : 8,
        maxZoom     : 10,
        scrollwheel : false,
        center      : mlatlang,
        mapTypeId   : google.maps.MapTypeId.ROADMAP
    };
    if(mexcat){
        mapOptions = {
            zoom        : z,
            scrollwheel : false,
            center      : mlatlang,
            mapTypeId   : google.maps.MapTypeId.ROADMAP
        };
    }

    if(notShowInfo){
        self.notShowInfoWindow = true;
    }

    self.map = new google.maps.Map(document.getElementById("map_realestate"), mapOptions);   

    google.maps.event.addListener(self.map, 'click', addPoint);

    self.geocoder = new google.maps.Geocoder();

    var cirImage = new google.maps.MarkerImage(includePath+'/images/circle_marker.png',
        new google.maps.Size(40, 40),
        new google.maps.Point(0, 0),
        new google.maps.Point(17,17)
    );

    self.circleMarker = new google.maps.Marker({
        icon      : cirImage,
        draggable : false,
        raiseOnDrag:false,
        position  : self.map.getCenter(),
        map       : self.map
    });
    
    if(ptype){
        changeAddress(); // lat and lng empty
    }

    google.maps.event.addListener(self.circleMarker, "dragend", function() {
        updateCircle();
    });

    google.maps.event.addListener(self.map, "zoom_changed", function() {
        setZoom(this.getZoom());
    });

    google.maps.event.addListener(self.map, "dragend", function() {
        setMapCenter(this.getCenter());
    });
    
    startFilter();

 }


 function createGroups() {
        
     clearGroups();

     self.markers = new Array();
     self.groups = new Array();

     for (var i = 0; i < mCount; i++) {
         if (filter(i)) {
             var data =  {
                            "lat" : markersArray[i].lat ,
                            "lng" : markersArray[i].lng ,
                            "id" : [ markersArray[i].id ],
                            "label" : markersArray[i].label
                         };
                         
             if (!self.groups.length) {
                 self.groups.push(data);

             } else{

                 var isAdded = false;
                 for (var j= 0; j < self.groups.length; j++) {

                     var lat = markersArray[i].lat;
                     var lng  = markersArray[i].lng;

                     if (self.groups[j].lat == lat && self.groups[j].lng == lng){

                         isAdded = true;
                         self.groups[j].id.push(markersArray[i].id);
                         break;
                     }
                 }

                 if (!isAdded) {
                     self.groups.push(data);
                 }
             }
         }

     }

     for (var i = 0; i < self.groups.length; i++) {
        
        self.markers[i] = addMarker(self.groups[i]);
         
     }
 }

 function addMarker(info) {
   //alert(info.toSource());
    var gLabel = info.label;
    
    var Mlatlng = new google.maps.LatLng(info.lat, info.lng);
       
     var gCount = info.id.length;
     var mInfo = getMarkerDetails(info.id[0])
     
     var marker = new google.maps.Marker({
         position    :   Mlatlng,
         map         :   self.map,
         icon        :   includePath + "/images/markerimage/"+ gLabel +"_Hover.png",
         title       :   gLabel
     });
    
      
     google.maps.event.addListener(marker, 'click', function() {
     
         closeInfowindow();
         document.getElementById("map-rollover-container").style.display = "none";
         if(self.notShowInfoWindow){
             
             self.infowindow = new google.maps.InfoWindow({
                 //info.id will return an array of id of the markers represented by the group                 
                 content : showInfoWindow(info.id)
             });
                        
             google.maps.event.addListener(self.infowindow, 'domready', function() {                 
                    
                    var mapTabs = jQuery(".mapTabs").tabs();
                    
                    jQuery(".ui-mtabs-panel").each(function(i){
                         var totalSize = jQuery(".ui-mtabs-panel").size() - 1;
                         
                         if (i != totalSize) {
                             next = i + 2;
                             jQuery(".nextDiv",this).html("<a href=# class=next-tabs mover rel=" + next + "></a>");
                         }if (i != 0) {
                             prev = i;
                             jQuery(".prevDiv",this).html("<a href=# class=prev-tabs mover rel=" + prev + "></a>");
                         }
                    });
                    jQuery(".next-tabs, .prev-tabs").click(function() {
                         mapTabs.tabs("select", jQuery(this).attr("rel"));
                         return false;
                    });
            });
              
           self.infowindow.open(self.map, marker);
        }
      

     });
   
     google.maps.event.addListener(marker, "mouseout", function() {
         marker.setIcon(includePath+"/images/markerimage/" + gLabel + "_Hover.png");
         document.getElementById("map-rollover-container").style.display = "none";

     });
   
     google.maps.event.addListener(marker, "mouseover", function() {

         closeInfowindow();
         marker.setIcon(includePath+"/images/markerimage/" + gLabel + "_Norm.png");
         document.getElementById("map-rollover-container").style.display = "block";


         var scale = Math.pow(2, self.map.getZoom());
         var nw = new google.maps.LatLng(self.map.getBounds().getNorthEast().lat(), self.map.getBounds().getSouthWest().lng());

         var worldCoordinateNW = map.getProjection().fromLatLngToPoint(nw);
         var worldCoordinate = map.getProjection().fromLatLngToPoint(marker.getPosition());
         var p = new google.maps.Point(Math.floor((worldCoordinate.x - worldCoordinateNW.x) * scale),
         Math.floor((worldCoordinate.y - worldCoordinateNW.y) * scale));

         var cx, cy;         

         /*if(ptype == 1){
            cx = p.x - 270;
            cy = p.y - 195;
          }else if(ptype == 2){
            cx = p.x - 10;
            cy = p.y - 175;
         }else{
            cx = p.x - 110;
            cy = p.y - 85;
         }*/
         
         //alert(p.x+':'+p.y);
         cx = p.x - 110;
         cy = p.y - 190;
         //alert(mInfo.toSource());
         
         document.getElementById("map-rollover-container").style.top   = cy + "px";
         document.getElementById("map-rollover-container").style.left  = cx + "px";

         var html = '';

         if (mInfo.picture){
             html += '<img class="thumb" src="'+ mInfo.picture +'"/><br/>';
         }else{
             html += '<img class="thumb" src="'+includePath+'/images/noImg.jpg"/><br/>';
         }
         
         html += mInfo.title;            
         
         document.getElementById("map-rollover").innerHTML = html;

         if (gCount == 1){
             document.getElementById("map-rollover").style.backgroundImage = "url("+includePath+"/images/rollover1.png)";
         }else if (gCount == 2){
             document.getElementById("map-rollover").style.backgroundImage = "url("+includePath+"/images/rollover2.png)";
         }else{
             document.getElementById("map-rollover").style.backgroundImage = "url("+includePath+"/images/rollover3.png)";
         }

     });
    
     return marker;
    }  
 

function searchAddress(){
    var search = document.getElementById("search_input").value;
    if(search != 'enter city, suburb or zip' && search != '' && search != 0){
        self.geocoder.geocode({'address': search, 'partialmatch': true}, geocodeResult);
        mapCenter(mlatlang);
    }
}

 function  updateCircle() {
     if(!checkMap()){return;}
     //select("shape_circ");
    
    var radius = jQuery("#distance").val();
    
     if(radius == null){
         radius = 0;
     }

     clearMarkers();

     var center  = self.circleMarker.getPosition();
     var latConv = distanceBetweenPoints(center, new google.maps.LatLng(center.lat()+0.1, center.lng()));
     var lngConv = distanceBetweenPoints(center, new google.maps.LatLng(center.lat(), center.lng()+0.1));
     
  

     var nodes  = 50;
     var points = [];
     var bounds = new google.maps.LatLngBounds();


     var step   = parseInt(360/nodes)||10;
     for(var i=0; i<=360; i+=step) {
         var pint = new google.maps.LatLng(center.lat() + (radius/latConv * Math.cos(i * Math.PI/180)), center.lng() + (radius/lngConv * Math.sin(i * Math.PI/180)));
         
         points.push(pint);
         bounds.extend(pint);
     }

     points.push(points[0]);

     if (self.circlePolygon != null) {
         self.circlePolygon.setMap(null);
     }

     self.circlePolygon = new google.maps.Polygon({
         paths         : points,
         strokeColor   : "#FF0000",
         strokeOpacity : 0.2,
         strokeWeight  : 1,
         fillColor     : "#FF0000",
         fillOpacity   : 0.2,
         map           : self.map
     });


     // Add markers while condition is satisfied
     if (radius == 0) {
         showAllMarkers();
     } else {
         for(var i=0; i< self.markers.length; i++) {

             var marker = self.markers[i];

             var latLang = marker.getPosition();

             if (bounds.contains(latLang)) {
                 if (isBounds(latLang)) {
                     marker.setMap(self.map);
                 }
             }

         }
     }
     if(self.circleMarker){
        self.map.setCenter(self.circleMarker.getPosition());
        setMapCenter(self.circleMarker.getPosition());
     }
 }


 function filter(Mindex){
         
     var Category = markersArray[Mindex].category;
     var Picture = markersArray[Mindex].picture;
     var Area = parseInt(markersArray[Mindex].area.VALUE);
     if (isNaN(Area)){
         Area = 0;
     }
     var Price = parseFloat(markersArray[Mindex].price.VALUE);
     if (isNaN(Price)){
         Price = 0;
     }
     var Bathroom = parseInt(markersArray[Mindex].bathroom.VALUE);
     if (isNaN(Bathroom)){
         Bathroom = 0;
     }
     var Bedroom = parseInt(markersArray[Mindex].bedroom.VALUE);
     if (isNaN(Bedroom)){
         Bedroom = 0;
     }
     var Typeid = markersArray[Mindex].type_id;
     var lat = markersArray[Mindex].lat;
     var lng = markersArray[Mindex].lng;
     var includeItem = true;
     if(lat && lng){
        /*  
        // OLD Type Filter
        var ec = 0;

        for (e in self.params['TYPE']) {ec++;}

        if (ec && ec < 4){
         if(Typeid){
             for (i = 1; i <= ec; i++){
                 if(Typeid == self.params['TYPE'][i]){
                     includeItem = false;
                 }
             }
         }else{
             includeItem = false;
         }
        }
      
        // Type Filter

        if (self.params['TYPE']){
             if(Typeid){
                 if (Typeid != self.params['TYPE']){
                    includeItem = false;
                 }
             }else{
                includeItem = false;
            }
        }

        //Category Filter
        if (self.params['CATEGORY']){
             if(Category){
                 if (Category != self.params['CATEGORY']){
                    includeItem = false;
                }
            }else{
                 includeItem = false;
             }
        }
       */  
        
         //Picture Filter
         
        if (self.params['PICTURE']==true){
          if (Picture == ''){
                     includeItem = false;
             }
         }
         
         
         //Bathroom Filter
         if (!isNaN(Bathroom)){
             if (Bathroom < self.params['BATH']['MIN'] || (Bathroom > self.params['BATH']['MAX'] && self.params['BATH']['HMAX'])){
                includeItem = false;
             }
         }else {
            includeItem = false;
         }
          
         //Bedroom Filter
         if (!isNaN(Bedroom)){
             if (Bedroom < self.params['BED']['MIN'] || (Bedroom > self.params['BED']['MAX'] && self.params['BED']['HMAX'])){
                includeItem = false;
             }
         }else {
            includeItem = false;
         }

         //Price Filter
         if(!isNaN(Price)){
           if (Price < self.params['PRICE']['MIN'] || (Price > self.params['PRICE']['MAX'] && self.params['PRICE']['HMAX'])) {
                    includeItem = false;
             }
         }else {
            includeItem = false;
         }
          
          
          //Area Filter
         if (!isNaN(Area)){
              if (Area < self.params['SURFACE']['MIN'] || (Area > self.params['SURFACE']['MAX'] && self.params['SURFACE']['HMAX'])) {
                includeItem = false;
             }
         }else {
            includeItem = false;
         }         
        
     }else {
         includeItem = false;
     }
    
     return includeItem;
 }

 function isBounds(latLang) {

     var radius = jQuery("#distance").val();

     var center = self.circleMarker.getPosition();
     // divided by 10 to represent the radius accurately
     // 1 unit = 10 units
     var d = distanceBetweenPoints(center, latLang)/10;

     if (d <= radius) {
         return true;
     } else {
         return false;
     }
 }

 function startFilter(){
    
     if(!checkMap()){return;}

     self.params = new Object();
//     self.params['TYPE'] = new Object();
//
//     var t = 1;
//     for (i = 1; i <= 4; i++){
//         if(jQuery('#' + i + 'type').attr('checked') == false){
//             self.params['TYPE'][t] = jQuery('#' + i + 'type').val();
//             t++;
//         }
//     }

     //self.params['TYPE'] = jQuery('#rType').val(); //Type

     //self.params['CATEGORY'] = jQuery('#rCategory').val();//category
     
     self.params['ISPICTURE'] = jQuery('#aispicture').attr('checked');//Picture
         
     self.params['BED'] = new Object();

     self.params['BED']['MIN']  = parseInt(jQuery('#abed_min').val());
     self.params['BED']['MAX']  = parseInt(jQuery('#abed_max').val());
     self.params['BED']['HMAX'] = parseInt(jQuery('#abed_hmax').val());

     self.params['BATH'] = new Object();

     self.params['BATH']['MIN']   = parseInt(jQuery('#abath_min').val());
     self.params['BATH']['MAX']   = parseInt(jQuery('#abath_max').val());
     self.params['BATH']['HMAX']  = parseInt(jQuery('#abath_hmax').val());


     self.params['SURFACE'] = new Object();

     self.params['SURFACE']['MIN'] = parseInt(jQuery('#asurface_min').val());
     self.params['SURFACE']['MAX'] = parseInt(jQuery('#asurface_max').val());
     self.params['SURFACE']['HMAX'] = parseInt(jQuery('#asurface_hmax').val());

     self.params['PRICE'] = new Object();

     self.params['PRICE']['MIN'] = parseInt(jQuery('#aprice_min').val());
     self.params['PRICE']['MAX'] = parseInt(jQuery('#aprice_max').val());
     self.params['PRICE']['HMAX'] = parseInt(jQuery('#aprice_hmax').val());



     createGroups();
      
     if(self.selected == "shape_poly"){
         updatePolygon();
     }else {
         updateCircle();
     }
 }


 function showAllMarkers() {
    try{        
         var m;
         if (self.markers) {
             for (m in self.markers) {
                 self.markers[m].setMap(self.map);
             }
         }
     }catch(e){}

 }

 function distanceBetweenPoints(p1, p2) {
     if (!p1 || !p2) {
         return 0;
     }

     var R = 6371; // Radius of the Earth in km
     var dLat = (p2.lat() - p1.lat()) * Math.PI / 180;
     var dLon = (p2.lng() - p1.lng()) * Math.PI / 180;
     var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
         Math.cos(p1.lat() * Math.PI / 180) * Math.cos(p2.lat() * Math.PI / 180) *
         Math.sin(dLon / 2) * Math.sin(dLon / 2);
     var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
     var d = R * c;

     return d;
 }

 function clearMarkers() {
     closeInfowindow();
     try{
         if (self.markers) {
             for (i in self.markers) {
                 self.markers[i].setMap(null);
             }
         }
     }catch(e){}
 }


 function clearGroups() {
     clearMarkers();

     self.markers = null;
     self.groups = null;
 }


 function clearRadius(){
     jQuery("#distance-range").slider('value',0);
     jQuery(".volumeimage_hover").css({'width':0});
     jQuery("#distance").val(0);
 }

 function clearShapes(){
     try{
         if (self.circlePolygon != null) {
             self.circlePolygon.setMap(null);
         }

         if (self.circleMarker != null) {
             self.circleMarker.setMap(null);
         }

         if (self.poly != null) {
             self.poly.setMap(null);

             if (self.pmarkers.length) {
                 for (i in self.pmarkers) {
                    self.pmarkers[i].setMap(null);
                 }
             }
         }
     }catch(e){}

     clearRadius();
 }

 function startCircle() {
     //select("shape_circ");
     clearRadius();
     clearShapes();
     showSlider();
     showAllMarkers();
     if(self.circleMarker){
         self.circleMarker.setMap(self.map);
         updateCircle();
     }
 }


 function startShape() {
     //select("shape_poly");
     clearShapes();
     hideSlider();
     showAllMarkers();

     if (self.poly != null) {
         self.poly = null;
     }

     self.polypath = new google.maps.MVCArray();

     self.pmarkers = new Array();

     self.poly = new google.maps.Polygon({
         strokeColor   : "#FF0000",
         strokeOpacity : 0.8,
         strokeWeight  : 1,
         fillColor     : "#FF0000",
         fillOpacity   : 0.2,
         map           : self.map
     });
     self.poly.setPaths(new google.maps.MVCArray([self.polypath]));

 }




 function  updatePolygon() {
     if(!checkMap()){return;}
     //select("shape_poly");
     clearMarkers();

     var length = self.polypath.getLength();

     if (length < 3) {
         showAllMarkers();
     } else {
         var bounds = new google.maps.LatLngBounds();
         for (var i = 0; i< length; i++) {
             bounds.extend(self.polypath.getAt(i));
         }

         var southWest = bounds.getSouthWest();
         var northEast = bounds.getNorthEast();

         var x1 = southWest.lat();
         var y1 = southWest.lng();

         var x2 = northEast.lat();
         var y2 = northEast.lng();

         // adding markers that satisy the condition
         for(var i=0; i< self.markers.length; i++) {

             var p = self.markers[i].getPosition();

             if (bounds.contains(p)) {
                 if (isBoundsPolygon(p)) {
                     self.markers[i].setMap(self.map);
                 }
             }
         }
     }
 }

 function isBoundsPolygon(p) {
     var counter = 0;
     var N = self.polypath.getLength();

     if (N >= 3) {
         p1 = self.polypath.getAt(0);
         for (i=1; i<= N; i++) {

             p2 = self.polypath.getAt(i % N);

             if (p.lng() > Math.min(p1.lng(),p2.lng())) {
                 if (p.lng() <= Math.max(p1.lng(),p2.lng())) {
                     if (p.lat() <= Math.max(p1.lat(),p2.lat())) {
                         if (p1.lng() != p2.lng()) {
                             xinters = (p.lng()-p1.lng())*(p2.lat()-p1.lat())/(p2.lng()-p1.lng())+p1.lat();
                             if (p1.lat() == p2.lat() || p.lat() <= xinters)
                                 counter++;
                         }
                     }
                 }
             }
             p1 = p2;
         }

     }

     if (counter % 2 == 0){
         return false;
     }else{
         return true;
     }
 }


 function hideSlider(){
     jQuery("#distance-range").slider("disable");
 }
 
 function showSlider(){
     jQuery("#distance-range").slider("enable");
 }

 function addPoint(event) {

     if (document.getElementById("shape_poly").className =="selected"){

         polypath.insertAt(polypath.length, event.latLng);

         var Mpoint = new google.maps.Marker({
             position: event.latLng,
             map: self.map,
             draggable: true
         });

         Mpoint.setTitle("#" + polypath.length);

         self.pmarkers.push(Mpoint);

         google.maps.event.addListener(Mpoint, 'click', function() {

             Mpoint.setMap(null);
             for (var i = 0, I = self.pmarkers.length; i < I && self.pmarkers[i] != Mpoint; ++i);
             self.pmarkers.splice(i, 1);
             polypath.removeAt(i);
             updatePolygon();
         });

         google.maps.event.addListener(Mpoint, 'dragend', function() {

             for (var i = 0, I = self.pmarkers.length; i < I && self.pmarkers[i] != Mpoint; ++i);
             polypath.setAt(i, Mpoint.getPosition());
             updatePolygon();
         });

         updatePolygon();
     }
 }

function changeAddress() {
        var address = document.getElementById("alocalbox").value;
        var country = document.getElementById("countryname").value;
        var mapcenter = document.getElementById("map_center").value;
                    
        if(address != 'enter city, suburb or zip'){
            //address = country +' '+address;
            address += ' '+country;
        }else if(mapcenter == ''){
            address = country;            
        }
        //alert(address) ;
        if(address != '' && address != 0){
            self.geocoder.geocode({'address': address, 'partialmatch': true}, geocodeResult);            
            mapCenter(mlatlang);
        }
        /*else{               
            if(expand == '0'){
                if(navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            mlatlang = new google.maps.LatLng(position.coords.latitude,position.coords.longitude);
                            mapCenter(mlatlang);
                        },
                        function() {
                            mapCenter(mlatlang);
                        }
                    );
                } else if (google.gears) {

                    // Try Google Gears Geolocation
                    var geo = google.gears.factory.create('beta.geolocation');
                    geo.getCurrentPosition(
                        function(position) {
                            mlatlang = new google.maps.LatLng(position.latitude,position.longitude);
                            mapCenter(mlatlang);
                        },
                        function() {
                            mapCenter(mlatlang);
                        }
                    );
                }
            }
        }*/        
 }


function geocodeResult(results, status) {
    if (status == 'OK' && results.length > 0) {
        self.map.fitBounds(results[0].geometry.viewport);
        mapCenter(results[0].geometry.viewport.getCenter());
    }else {
        //alert("Given address is In-valid: " + status);
    }
}
