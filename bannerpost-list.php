<style type="text/css">

.banner-post {
    width: <?php $width = get_option('rtbannerwidth'); if(!empty($width)) {echo $width;} else {echo "960";}?>px;     
    height: <?php $height = get_option('rtbannerheight'); if(!empty($height)) {echo $height;} else {echo "268";}?>px;    
    padding:10px; 
}


#rtbanner-slideshow {
    width: <?php $width = get_option('rtbannerwidth'); if(!empty($width)) {echo $width;} else {echo "960";}?>px;
    padding:0px !important;    
    height: <?php $height = get_option('rtbannerheight'); if(!empty($height)) {echo $height;} else {echo "268";}?>px;
    overflow:hidden;    
    position: relative;
}

#rtbanner-slideshow ul {
    background:transparent !important;
    margin: 0 !important;
    border: none !important;
    padding: 0 !important;
    list-style-type: none !important;
    position: relative;
}           

#rtbanner-slideshow .rtcontent-slideshow {
    float:right;
    overflow: hidden;    
    margin: 0px !important;
    padding: 0px !important;
    width: <?php $img_width = get_option('rtbannerimgwidth'); if(!empty($img_width)) {echo $img_width;} else {echo "600";}?>px;
    height: <?php $img_height = get_option('rtbannerimgheight'); if(!empty($img_height)) {echo $img_height;} else {echo "270";}?>px;
    position: relative;
}

#rtbanner-slideshow .rtcontent-slideshow ul li {
    width: <?php $img_width = get_option('rtbannerimgwidth'); if(!empty($img_width)) {echo $img_width;} else {echo "600";}?>px;
    height: <?php $img_height = get_option('rtbannerimgheight'); if(!empty($img_height)) {echo $img_height;} else {echo "270";}?>px;
    display:none;    
    display:block;
    overflow: hidden;
    position:relative;
    top: 0px !important;
    left: 0px !important;
    float: left;
    margin: 0px !important;
    padding: 0px !important;
    z-index:1;
}

#rtbanner-slideshow .rtcontent-slideshow ul li img {
    margin: 0px !important;
    padding: 0px !important;
    border: none !important;
    float: left;
    width: <?php $img_width = get_option('rtbannerimgwidth'); if(!empty($img_width)) {echo $img_width;} else {echo "600";}?>px;
    position: absolute;
    top: 0px;
    height: <?php $img_height = get_option('rtbannerimgheight'); if(!empty($img_height)) {echo $img_height;} else {echo "270";}?>px;
}
 
#rtbanner-slideshow ul.rtslideshow-nav {
    height:<?php $height = get_option('rtbannerheight'); if(!empty($height)) {echo $height;} else {echo "268";}?>px;
    width:270px;
    margin:0;
    padding: 0;
    float:left;
    overflow:hidden;
}

#rtbanner-slideshow .rtslideshow-nav li {
    display:block;
    margin:0;
    padding:0;
    list-style-type:none;
    display:block;
}

.slideme {
    font-size: 9px;
    float: right;
}

.slideme a {
    font-size: 8px;
    text-decoration: none;
    color: #CCC;
}

#rtbanner-slideshow .rtslideshow-nav li {
    width: 270px;
    display:block;
    margin:0px !important;
    float: left;
    padding: 0px !important;
}

#rtbanner-slideshow .rtslideshow-nav li a {
    width: 270px;
    display:block;
    margin:0;
    padding:9px;
    list-style-type:none;
    display:block;
    height:31px;
    color:#333;
    overflow:hidden;  
    font-size: 14px;
    font-weight: bold;
    border-bottom: 1px solid #CCC;
    line-height:1.35em;
}

#rtbanner-slideshow .rtslideshow-nav li p {
    float: left;
    font-size: 12px;
    font-weight: normal;
    padding-top: 1px;
}

#rtbanner-slideshow .rtslideshow-nav li.on a {   
    color:#000;
}

#rtbanner-slideshow .rtslideshow-nav li a:hover,
#rtbanner-slideshow .rtslideshow-nav li a:active {     
}


.banner-post .banner-img-right{
    float: right;       
 }
 
.banner-post .banner-img-right img{
    width: <?php $img_width = get_option('rtbannerimgwidth'); if(!empty($img_width)) {echo $img_width;} else {echo "600";}?>px;
    height: <?php $img_height = get_option('rtbannerimgheight'); if(!empty($img_height)) {echo $img_height;} else {echo "270";}?>px;
}

</style>

   
<?php 
if(get_option('rtstyletype') == 1){ ?>    
<div class="banner-post rt_vertical"> 
    <div id="rtbanner-slideshow">

        <div class="rtcontent-slideshow">
                        
                    <?php
                    $html .= '<ul>';
                    $pcount = 1;

                    $sort  = get_option('rtsorttype'); if(empty($sort)){$sort = "post_date";}
                    $order = get_option('rtordertype'); if(empty($order)){$order = "DESC";}

                    global $wpdb;
                    global $post;

                    $args = array( 'numberposts' => -1, 'meta_key' => 'rtbannerpost', 'meta_value'=> '1', 'suppress_filters' => 0, 'post_type' => array('post', 'page'), 'orderby' => $sort, 'order' => $order);

                    $myposts = get_posts( $args );
             
                        foreach( $myposts as $post ){
                            
                            setup_postdata($post);
                            $custom = get_post_custom($post->ID);
                            $thumb = bannerthumb("rtbannerpost");
                            
                            
                            $html .= '<li id="post_image_'.$pcount.'" onclick="location.href=\''.apply_filters('the_permalink', get_permalink()).'\'" title="">';
                            $html .= '<img src="'.$thumb.'" />';
                            $html .= '</li>';


                            $pcount = $pcount + 1;

                        } //end for each
                        $html .= '</ul>';
                   
                 
                     //CHECK IF POSTS HAVE OR NOT
                    if($pcount > 1){
                        echo $html;
                    }else{
                        echo '<b>'.__('BANNERPOST_NO_POST_AVAILABLE',"realtransac").'</b>';
                    } 
            ?>

            
        </div>
       
            <ul class="rtslideshow-nav">

                <?php

                global $wpdb;

                $pcount = 1;

                global $post;

                $args = array( 'meta_key' => 'rtbannerpost', 'meta_value'=> '1', 'suppress_filters' => 0, 'post_type' => array('post', 'page'), 'orderby' => $sort, 'order' => $order);

                $myposts = get_posts( $args );

                foreach( $myposts as $post ) :	setup_postdata($post);

                        $custom = get_post_custom($post->ID);

                ?>

                <?php if ( $pcount == 1 ) { ?>
                        <li class="on clearfix" id="post-<?php echo $pcount; ?>">
                                <a href="#post_image_<?php echo $pcount; ?>" title="<?php the_title(); ?>">
                                        <?php echo treatTitle(get_the_title(), 35, ""); ?> 
                                </a>
                        </li>
                <?php } else { ?>
                        <li id="post-<?php echo $pcount; ?>" class="clearfix">
                                <a href="#post_image_<?php echo $pcount; ?>" title="<?php the_title(); ?>">
                                        <?php echo treatTitle(get_the_title(), 35, ""); ?>
                                </a>
                        </li>
                <?php } ?>

                <?php

                $pcount = $pcount + 1;

                endforeach; ?>

            </ul>
         

    </div>
</div>    
<?php }else{ ?>
<div class="banner-post rt_horizontal"> 
    <div id="rtbannerpostprevious" class="primg"></div>
        <div class="outer-banner-block">
                          
                    <?php
                    $html .= '<ul id ="rtbannerpostslider">';
                    
                    $pcount = 1;

                    $sort  = get_option('rtsorttype'); if(empty($sort)){$sort = "post_date";}
                    $order = get_option('rtordertype'); if(empty($order)){$order = "DESC";}

                    global $wpdb;

                    global $post;

                    $args = array( 'numberposts' => -1, 'meta_key' => 'rtbannerpost', 'meta_value'=> '1', 'suppress_filters' => 0, 'post_type' => array('post', 'page'), 'orderby' => $sort, 'order' => $order);

                    $myposts = get_posts( $args );
                  
                    foreach( $myposts as $post ) {
                        
                        setup_postdata($post);
                        $custom = get_post_custom($post->ID);
                        $thumb = bannerthumb("rtbannerpost");
                        
                        
                        $html .= '<li id="main-post-'.$pcount.'">';
                        $html .= '<div class="banner-content-left">';
                        $html .= '<div class="curr-info">';
                        $html .= '<h2>'.get_the_title().'</h2>';
                        $html .= '<div class="banner-content-inner" align="justify">';
                        $html .= get_the_excerpt();
                        $html .= '</div>';
                        $html .= '<a class="viewbutton" href="'.apply_filters('the_permalink', get_permalink()).'" title="">';
                        $html .= '<span class="btnleft"></span>';
                        $html .= '<span class="btncenter">'.__("GENERIC_READ_MORE","realtransac").'</span>';
                        $html .= '<span class="btnright"></span>';
                        $html .= '</a>';
                        $html .= '</div>';
                        $html .= '</div>';
                        $html .= '<div class="banner-img-right"><img src="'.$thumb.'" /></div>';
                        $html .= '</li>';
                       

                        $pcount = $pcount + 1;
                    }
                    $html .= '</ul>';
                    
                    if($pcount > 1){
                       
                            echo $html;
                    
                    }else{
                        echo '<center><b>'.__('BANNERPOST_NO_POST_AVAILABLE',"realtransac").'</b></center>';
                    } 

                ?> 
            </ul>  
        
    </div> 
    <div id="rtbannerpostnext" class="nximg"></div>
    
    <script type="text/javascript">
         jQuery.noConflict();
         jQuery(function(){

            jQuery('#rtbannerpostslider').bxSlider({    
                pager: false,
                nextText: '',
                nextSelector: jQuery('#rtbannerpostnext'),
                prevText: '',
                prevSelector: jQuery('#rtbannerpostprevious')               
            });
        });
    </script>
</div>    
<?php }
?>