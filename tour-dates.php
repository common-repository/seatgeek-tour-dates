<?php
/*
Plugin Name: SeatGeek Tour Dates
 
Description: Plugin for bands and artists that will enable them to show their upcoming events.
Author: Arnav Joy
Author URI: http://www.ajonweb.com
Version: 1.1
 
*/
 

define('TD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TD_PLUGIN_FILE', basename(__FILE__));
define('TD_PLUGIN_FULL_PATH', __FILE__);
define('TD_PREFIX','td_');



add_action('wp_footer', 'td_show_custom_css', 100);
function td_show_custom_css() {
    $tour_dates_data = get_option( 'tour_dates_data' );
     if ( !empty($tour_dates_data['td_custom_css']) ) {
	   echo '<style type="text/css">' . $tour_dates_data['td_custom_css'] . '</style>';
     }
}


function td_enqueue_scripts() {
global $wp_styles;
	  wp_enqueue_style( 'td-fonts', 'http://fonts.googleapis.com/css?family=Open+Sans:400,600,700' );	
    wp_enqueue_style( 'td-style', TD_PLUGIN_URL . 'td-style.css' );	
	
	// Loads the Internet Explorer specific stylesheet.
	wp_register_style( 'td-ie', TD_PLUGIN_URL . 'css/ie-td.css' );
	$wp_styles->add_data( 'td-ie', 'conditional', 'lt IE 9' );
	
	wp_enqueue_style( 'td-ie');
	
	
	
}

add_action( 'wp_enqueue_scripts', 'td_enqueue_scripts' );


function td_correct_name( $artist_name ){
		
		$artist_name = str_replace(" ", '-', $artist_name);
        $artist_name = str_replace("'s", ' ', $artist_name);	
        $artist_name = strtolower(trim($artist_name));
		
		return $artist_name;
}
function td_event_data( $artist_name , $per_page = 10 ) {
   
        $artist_name = td_correct_name( $artist_name );
		
        $useragent   = 'cURL';
        $headers     = false;
        $follow_redirects = false;
        $debug       = false;   
   
	$api_end_point = 'http://api.seatgeek.com/2/events?per_page='.$per_page.'&performers.slug=';
   
        $url = $api_end_point.$artist_name;
	# initialise the CURL library
	$ch = curl_init();
	 
	# specify the URL to be retrieved
	curl_setopt($ch, CURLOPT_URL,$url);
 
	# we want to get the contents of the URL and store it in a variable
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	 
	# specify the useragent: this is a required courtesy to site owners
	curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
	 
	# ignore SSL errors
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	 
	# return headers as requested
	if ($headers==true){
		curl_setopt($ch, CURLOPT_HEADER,1);
	}
	 
	# only return headers
	if ($headers=='headers only') {
		curl_setopt($ch, CURLOPT_NOBODY ,1);
	}
 
	# follow redirects - note this is disabled by default in most PHP installs from 4.4.4 up
	if ($follow_redirects==true) {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	}
	 
	# if debugging, return an array with CURL's debug info and the URL contents
	if ($debug==true) {
		$result['contents']=curl_exec($ch);
		$result['info']=curl_getinfo($ch);
	}
 
	# otherwise just return the contents as a variable
	else $result=curl_exec($ch);
	 
	# free resources
	curl_close($ch);
	 
	# send back the data	
	$result = json_decode($result, true);	
	
 
		 
	if( empty( $result['events'] ))	{
		return -1;
	}
	else
	return $result['events'];
}

function td_tour_dates_shortocde( $atts ) {
     extract( shortcode_atts( array(
              'title' => 'Tour Dates',
	      	  'artist' => '',
	          'display_limit' => '',
			  'affiliate_id' => ''
     ), $atts ) );
     
	  if( !empty( $affiliate_id ) ) {
		$affiliate_url = '?aid='.$affiliate_id;
		$track_url     = '&fb_track=1';
	 }	
	 else{
	 	$track_url     = '?fb_track=1';
	 }
		
        $td_event_data = td_event_data( trim($artist) , $display_limit );
	   
	    $artist_name = td_correct_name( $artist );	  
		 
		
     $str = '<div id="seatgeek"><div class="td-wrapper td-wrapper-main">
                <div class="td-header">';
                    if( empty( $artist ) ){
                        $str .= '<h2>'. $artist . " " . $title . '</h2>';  
                    }
                    else{
						$title_url = 'http://seatgeek.com/'.$artist_name.'-tickets/'.$affiliate_url;					
                        $str .= '<h2><a href="'. $title_url .'" target="_blank">'. $artist . " " . $title .'</a></h2>'; 
                    }
               $str .= '</div>'; 
               $str .='<div class="td-content">';
            if( empty( $artist ) ){
                $str .= "<div>Please Enter Artist Name.</div>";	  
            }
            else if( $td_event_data == -1 && !empty( $artist ) ){
				$str .= '<p class="td-no-tour">No tour dates for <a href="'.$title_url.'" target="_blank" >'.$artist.'</a>. <br /><span>Get new show alerts from <a href="http://seatgeek.com/'.$affiliate_url.'" target="_blank">SeatGeek</a>.</span></p>';
	        }
            else{
                
                $str .=  '<ul>';
				if( !empty( $td_event_data ) ) {
					foreach( $td_event_data as $ted ) {
		 			 $date = date( 'm/d' , strtotime($ted['datetime_local'] ));
                                         
                      $address = $ted['venue']['extended_address'];
		  
						$zip_code = substr($address, -5);
						if (is_numeric($zip_code))
						  $address = substr($address, 0, strlen($address) - 5);
	 		
        	 		 $str .= '<li>                         
                            <div class="td-date">
                                <span>'.$date.'</span>
                            </div>
                            <div class="td-title-detail">                
                                <h3>'.$ted['title'].'</h3>
                                <span>'.$ted['venue']['name'] . ', ' .  $address .'</span>
                            </div>
                            <div class="td-button">                	
                                <a class="td-green-button" href="'.$ted['url'].$affiliate_url.'" target="_blank" >Tickets</a>
                                <a class="td-gray-button" href="'.$ted['url'].$affiliate_url.$track_url.'" target="_blank" >Track</a>
                            </div>            
                         </li>';
	           }
	        } 	
            $str .=  '</ul>';                
      }
            
     $str .= '</div>';  
     
    /* $str .= '<div class="td-footer">
    	    <p><a href="http://seatgeek.com/'.$affiliate_url.'" target="_blank"><span class="powered_by">Powered by </span><img src="'.TD_PLUGIN_URL.'images/seat-geek-logo.png" alt="SeatGeek" /></a></p>
         </div>';*/
		 
		  $str .= '<div class="td-footer">
    	    <p><a href="'. $title_url .'" target="_blank"><span class="powered_by">See more events </span><img src="'.TD_PLUGIN_URL.'images/seat-geek-logo.png" alt="SeatGeek" /></a></p>
         </div>'; 
     
     $str .= '</div></div>';
     
     
     return $str;
}
add_shortcode( 'seatgeek_events', 'td_tour_dates_shortocde' ); 


add_action( 'admin_menu', 'td_admin_tour_dates_menu' );

function td_admin_tour_dates_menu(){
    add_menu_page( 'Tour Dates', 'Tour Dates', 'manage_options', 'tour-dates', 'td_admin_tour_dates', '', '90' );
}

function td_admin_tour_dates(){
    
    include TD_PLUGIN_DIR.'tour-dates-admin.php' ;
}



class TourDates extends WP_Widget
{
  function TourDates()
  {
    $widget_ops = array('classname' => 'TourDates', 'description' => 'Displays Upcoming Events' );
    $this->WP_Widget('TourDates', 'Tour Dates', $widget_ops);
	$this->td_prefix = TD_PREFIX;
  }
 
  function form($instance)
  {
        $instance   = wp_parse_args( (array) $instance, array( $this->td_prefix.'title' => '' , $this->td_prefix.'artist_name' => '' , $this->td_prefix.'display_limit' => '' ) );
        $title     	    = $instance[$this->td_prefix.'title'];
		$artist_name    = $instance[$this->td_prefix.'artist_name'];
		$display_limit  = $instance[$this->td_prefix.'display_limit'];
		$affiliate_id   = $instance[$this->td_prefix.'affiliate_id'];

?>
    <p>
		<label for="<?php echo $this->get_field_id($this->td_prefix.'title'); ?>">Title (optional):
		 <input class="widefat" id="<?php echo $this->get_field_id($this->td_prefix.'title'); ?>" name="<?php echo $this->get_field_name($this->td_prefix.'title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" />
		</label>
    </p>
	
	<p>
		<label for="<?php echo $this->get_field_id($this->td_prefix.'artist_name'); ?>">Artist Name:
		 <input class="widefat" id="<?php echo $this->get_field_id($this->td_prefix.'artist_name'); ?>" name="<?php echo $this->get_field_name($this->td_prefix.'artist_name'); ?>" type="text" value="<?php echo attribute_escape($artist_name); ?>" />
		</label>
    </p>
	
	<p>
		<label for="<?php echo $this->get_field_id($this->td_prefix.'display_limit'); ?>">Display Limit:
		 <input class="widefat" id="<?php echo $this->get_field_id($this->td_prefix.'display_limit'); ?>" name="<?php echo $this->get_field_name($this->td_prefix.'display_limit'); ?>" type="text" value="<?php echo attribute_escape($display_limit); ?>" size="20" /><br />
		 <span>Leave blank to show all events.</span>
		</label>
    </p>
	<p>
		<label for="<?php echo $this->get_field_id($this->td_prefix.'affiliate_id'); ?>">Affiliate ID:
		 <input class="widefat" id="<?php echo $this->get_field_id($this->td_prefix.'affiliate_id'); ?>" name="<?php echo $this->get_field_name($this->td_prefix.'affiliate_id'); ?>" type="text" value="<?php echo attribute_escape($affiliate_id); ?>" size="20" /><br />		 
		</label>
    </p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance[$this->td_prefix.'title']         = $new_instance[$this->td_prefix.'title'];
	$instance[$this->td_prefix.'artist_name']   = $new_instance[$this->td_prefix.'artist_name'];
	$instance[$this->td_prefix.'display_limit'] = $new_instance[$this->td_prefix.'display_limit'];
	$instance[$this->td_prefix.'affiliate_id']  = $new_instance[$this->td_prefix.'affiliate_id'];
    return $instance;
  }
 
  function widget($args, $instance)
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance[$this->td_prefix.'title']) ? 'Tour Dates' : apply_filters('widget_title', $instance[$this->td_prefix.'title']);
	$artist_name    = $instance[$this->td_prefix.'artist_name'];
	$display_limit  = $instance[$this->td_prefix.'display_limit'];
	$affiliate_id   = $instance[$this->td_prefix.'affiliate_id'];
	
	 if( !empty( $affiliate_id ) ) {
		$affiliate_url = '?aid='.$affiliate_id;
		$track_url     = '&fb_track=1';
	 }	
	 else{
	 	$track_url     = '?fb_track=1';
	 }
 	
	echo '<div id="seatgeek-widget"><div class="td-sidebar"><div class="td-wrapper">
		  <div class="td-header">';
	
    if (empty($artist_name)){      
	 	 
	 echo $before_title . $artist_name . " " . $title . $after_title;   
     	 echo "<div>Please Enter Artist Name.</div>";	  
		 
        }else{
	    
	        $artist = td_correct_name( $artist_name );
		
	    $td_event_data = td_event_data( $artist_name , $display_limit );
	    $title_url = 'http://seatgeek.com/'.$artist.'-tickets/'.$affiliate_url;	
		echo '<h2><a href="'. $title_url .'" target="_blank">'. $artist_name . " " . $title .'</a></h2>'; 
		
	    
    	 
    echo '</div> 	
    	  <div class="td-content">';
	 
	 	if( $td_event_data == -1 ){ 
			echo '<p class="td-no-tour">No tour dates for <a href="'.$title_url.'" target="_blank" >'.$artist_name.'. </a><br /><span>Get new show alerts from <a href="http://seatgeek.com/'.$affiliate_url.'" target="_blank">SeatGeek</a>.</span></p>';
		}
	else {
		 
            echo '<ul>';
		if( !empty( $td_event_data ) ) {
		foreach( $td_event_data as $ted ) {
		  $date = date( 'm/d' , strtotime( $ted['datetime_local'] ));
		  $address = $ted['venue']['extended_address'];
		  
		    $zip_code = substr($address, -5);
			if (is_numeric($zip_code))
			  $address = substr($address, 0, strlen($address) - 5);
	?>		
        	<li>
            
            	<div class="td-date">
                	<span><?php echo $date;?></span>
                </div><!-- /.td-date -->
                <div class="td-title-detail">
                
                	<h3><?php echo $ted['title'];?></h3>
                    <span><?php echo $ted['venue']['name'] . ', ' .  $address;?></span>
                </div><!-- /.td-title-detail -->
                <div class="td-button">                	
                	<a class="td-green-button" href="<?php echo $ted['url'].$affiliate_url;?>" target="_blank" >Tickets</a>
                    <a class="td-gray-button" href="<?php echo $ted['url'].$affiliate_url.$track_url;?>" target="_blank" >Track</a>
                </div><!-- /.td-button -->
            
            </li>
             
	<?php	
	   }
	  } 	
        echo '</ul>';
	 }  
    echo '</div> ';
   
 
		  
	}
	
	echo '<div class="td-footer">
    	    <p><a href="'.$title_url.'" target="_blank"><span class="powered_by">See more events </span><img src="'.TD_PLUGIN_URL.'images/seat-geek-logo.png" alt="SeatGeek" /></a></p>
         </div><!-- /.td-footer -->    
   </div></div>';
    $tour_dates_data = get_option( 'tour_dates_data' );
     if ( !empty($tour_dates_data['td_custom_css']) ) {
	   echo '<style type="text/css">' . $tour_dates_data['td_custom_css'] . '</style>';
     }
   echo '</div>';
    echo $after_widget;
  }
 }
add_action( 'widgets_init', create_function('', 'return register_widget("TourDates");') ); 
?>