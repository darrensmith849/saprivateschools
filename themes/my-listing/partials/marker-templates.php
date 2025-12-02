<script id="case27-basic-marker-template" type="text/template">
	<a aria-label="<?php echo esc_attr( _ex( 'Location block map marker', 'Map marker - SR', 'my-listing' ) ) ?>" href="#" class="marker-icon">
		<div class="marker-img" style="background-image: url({{marker-bg}});"></div>
	</a>
</script>
<script id="case27-traditional-marker-template" type="text/template">
	<div class="cts-marker-pin">
		<img alt="<?php echo esc_attr( _ex( 'Map marker pin', 'Location field -> map marker', 'my-listing' ) ) ?>" src="<?php echo esc_url( c27()->image( 'pin.png' ) ) ?>">
	</div>
</script>
<script id="case27-user-location-marker-template" type="text/template">
	<div class="cts-geoloc-marker"></div>
</script>
<script id="case27-marker-template" type="text/template">
	<a aria-label="<?php echo esc_attr( _ex( 'Explore page map marker', 'Explore page > Map marker - SR', 'my-listing' ) ) ?>" href="#" class="marker-icon {{listing-id}}">
		{{icon}}
		<div class="marker-img" style="background-image: url({{marker-bg}});"></div>
	</a>
</script>