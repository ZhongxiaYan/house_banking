<!DOCTYPE html>
<html>
	<head>
		<?php
			require_once $PAGES['util'];
			require_once "$WWW/views/head_header.php";
		?>
	</head>
	<body>
		<p><?= htmlspecialchars($message) ?></p>
	</body>
</html>