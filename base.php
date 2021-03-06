<html>
	<head>
		<title>Open Civilization Online</title>
		<link href="styles.css" rel="stylesheet" />
	</head>
	<body>
		<pre>
<?php
	$user = $_REQUEST['user'];
	$base = $_REQUEST['base'];

	require 'credis/Client.php';
	$redis = new Credis_Client('localhost');

	// read in map and associated data
	$raw_map = $redis->get('world:map');
	$raw_bases = json_decode ($redis->get('world:bases'), yes);
	$raw_resources = json_decode ($redis->get('world:resources'));
	$raw_buildings_available = $redis->keys('buildings:*');
	$json_base_data = json_decode ($redis->get("world:bases:$user"));
	$base_data = json_decode ($redis->get("world:bases:$user"), yes);

	// render map to array (maybe should be json)
	$raw_map_exploded = explode ("\n", $raw_map);
	$max_x = $raw_map_exploded[0];
	$max_y = $raw_map_exploded[1];

	$map = array ();

	$offset = 2;

	for ($y = $offset; $y < $max_y; $y++) {
		$cur_y = $y - $offset;
		$map[$cur_y] = array ();
		for ($x = 0; $x < $max_x; $x++) {
			$map[$cur_y][$x] = $raw_map_exploded[$y]{$x};
		}
	}

	// find base
	$base_x = -1;
	$base_y = -1;
	for ($x = 0; $x < $max_x - 2; $x++) {
		for ($y = 0; $y < $max_y; $y++) {
			if ($raw_bases[$x][$y] == $base) {
				$base_x = $x;
				$base_y = $y;
			}
		}
	}

	echo "<a href='./?user=$user'>&lt; back</a><br />";
	echo "User: $user<br />";
	echo "Base: $base<br />";
	echo "location: x: $base_x; y: $base_y<br />"; 

	if ($base == $user) {
		$number_of_buildings = sizeof ($json_base_data->buildings);
?>
		<h2>Your Base</h2>
		<h3>Buildings</h3>
<?php
		if ($number_of_buildings == 0) {
			echo "Build something!";
		} else {
?>
		<ul>
<?php
			for ($i = 0; $i < $number_of_buildings; $i++) {
				$building_name = $json_base_data->buildings[$i];
				echo "<li><a href='building.php?user=$user&amp;building=$building_name'>$building_name</a></li>";
			}
?>
		</ul>
<?php
	}

	if ($base_data["build-queue"] != "") {
?>
		<h3>Building queue</h3>
		<ul>
			<?php
				$exploded_build_queue = explode ("|", $base_data["build-queue"]);
				$building = $exploded_build_queue[0];
				$start_building_time = $exploded_build_queue[1];

				$build_time = json_decode ($redis->get("buildings:$building"), yes)["time"];
			?>
			<li><?php echo $building; ?>(<span class="timer" data-duration-in-seconds="<?php echo $build_time; ?>" data-start-time="<?php echo $start_building_time; ?>"></span>)</li>
		</ul>
<?php
	}

	// are there buildings left to build?
	$canBuild = false;
	for ($i = 0; $i < sizeof ($raw_buildings_available); $i++) {
		$building_name = explode (':', $raw_buildings_available[$i])[1];
		if (!in_array ($building_name, $json_base_data->buildings) && $building_name != $building) {
			$canBuild = true;
			break;
		}
	}

		if ($canBuild) {
?>
		<h3>Build</h3>
		<ul>
<?php
		for ($i = 0; $i < sizeof ($raw_buildings_available); $i++) {
			$building_name = explode (':', $raw_buildings_available[$i])[1];
			if (!in_array ($building_name, $json_base_data->buildings) && $building_name != $building) {
				echo "<li><a href='build.php?user=$user&amp;base=$base&amp;resource=$building_name&amp;x=$x&amp;y=$y'>$building_name</a></li>";
			}
		}
?>
		</ul><?php
		}
	} ?>
		</pre>
		<script src="/js/timer.js"></script>
	</body>
</html>