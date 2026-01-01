	</div> <!-- .container -->
	<?php
	// Include main module at the end of the body so scripts run after DOM is ready.
	$modulePath = __DIR__ . '/../../../public/assets/js/controllers/adController.js';
	$ver = file_exists($modulePath) ? filemtime($modulePath) : time();
	?>
	<script type="module" src="/e-bazar/public/assets/js/controllers/adController.js?v=<?php echo $ver; ?>"></script>
</body>
</html>



