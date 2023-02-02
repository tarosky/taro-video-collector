<?php
/**
 * Display YouTube video.
 */

?>
<div class="video-list-item">
	<?php
	echo tsvc_iframe( [
		'width'   => 1920,
		'height'  => 1080,
		'loading' => 'lazy',
	] );
	?>
</div>
