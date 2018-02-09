<?php
// This map is included in an iframe to show multiple markers
session_start();

if(!$_GET['nomarker']){
	$markerURL = 'http://'.$_SERVER['HTTP_HOST'].'/images/marker.png';
}

// Unserialize the map data from the caller
$a_locs = @unserialize($_SESSION['map_data']);
#print_r($a_locs);
if(!count($a_locs)){exit();}

#$a_locs = array('name' => 'London', 'address' => 'London W1', 'lat'=>'', 'lng' => '');

#echo $_SESSION['map_data'];
#echo '<hr/>';
#print_r($a_locs);

?>
<html>
<head>
<title>Map</title>
<!-- Mobile viewport optimized -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script type='text/javascript' src='assets/jquery.js'></script>
<script type='text/javascript' src='assets/jquery-migrate.js'></script>
<?php /* === GOOGLE MAP JAVASCRIPT NEEDED (JQUERY) ==== */ ?>
<script src="http://maps.google.com/maps/api/js?sensor=true" type="text/javascript"></script>
<script type='text/javascript' src='assets/gmaps.js'></script>
<style>
body{ background-color:#fff; margin:0px; padding:0px}
</style>
</head>
<body>

				<?php /* === THIS IS WHERE WE WILL ADD OUR MAP USING JS ==== */ ?>
				<div class="google-map-wrap" itemscope itemprop="hasMap" itemtype="http://schema.org/Map">
					<div id="google-map" class="google-map">
					</div><!-- #google-map -->
				</div>

				<?php /* === MAP DATA === */ ?>
				<?php
				$locations = array();
				
				/* Marker #1 */
				for($i=0; $i<count($a_locs); $i++){
					if($a_locs[$i]['lng']){
						$locations[] = array(
							'google_map' => array(
								'lat' => $a_locs[$i]['lat'],
								'lng' => $a_locs[$i]['lng'],
							),
							'location_address' => addslashes($a_locs[$i]['address']),
							'location_name'    => addslashes($a_locs[$i]['address'].' '.$a_locs[$i]['name']),
						);
					}
				}
				
				?>


				<?php /* === PRINT THE JAVASCRIPT === */ ?>

				<?php
				/* Set Default Map Area Using First Location */
				$map_area_lat = isset( $locations[0]['google_map']['lat'] ) ? $locations[0]['google_map']['lat'] : '';
				$map_area_lng = isset( $locations[0]['google_map']['lng'] ) ? $locations[0]['google_map']['lng'] : '';
				?>

				<script>
				jQuery( document ).ready( function($) {

					/* Do not drag on mobile. */
					var is_touch_device = 'ontouchstart' in document.documentElement;

					var map = new GMaps({
						el: '#google-map',
						lat: '<?php echo $map_area_lat; ?>',
						lng: '<?php echo $map_area_lng; ?>',
						zoom: 17,
						scrollwheel: false,
						draggable: ! is_touch_device
					});
					
					
					
					/* Map Bound */
					var bounds = [];
					<?php /* For Each Location Create a Marker. */
					
					foreach( $locations as $location ){
						$name = $location['location_name'];
						$addr = $location['location_address'];
						$map_lat = $location['google_map']['lat'];
						$map_lng = $location['google_map']['lng'];
						?>
						/* Set Bound Marker */
						var latlng = new google.maps.LatLng(<?php echo $map_lat; ?>, <?php echo $map_lng; ?>);
						//alert(latlng);
						bounds.push(latlng);
						
						/* Add Marker */
						map.addMarker({
							lat: <?php echo $map_lat; ?>,
							lng: <?php echo $map_lng; ?>,
							title: '<?php echo $name; ?>',
							icon: '<?php echo $markerURL;?>',
							infoWindow: {
								content: '<p><?php echo $name; ?></p>'
							}
						});
					<?php } //end foreach locations ?>

					/* Fit All Marker to map */
					map.fitLatLngBounds(bounds);

					/* Make Map Responsive */
					var $window = $(window);
					function mapWidth() {
						var size = $('.google-map-wrap').width();
						$('.google-map').css({width: size + 'px', height: '100%'});
					}
					mapWidth();
					$(window).resize(mapWidth);

				});
				</script>


</body>
</html>
<?php $_SESSION['map_data'] = '';?>