<?php

require_once '../lib/config.php';
require_once 'init.php';

if (file_exists($config['paths']['editable_table'])) {
	$cell_map_ser = file_get_contents($config['paths']['editable_table']);
	$cell_map = unserialize($cell_map_ser);
} else {
	$cell_map = array();
}

$rows = 0;
$columns = 0;
if (is_array($cell_map)) {
	foreach ($cell_map as $key => $value) {
		$rows = max($rows, $key + 1);
		$columns = max($columns, max(array_keys($value)) + 1);
	}
}
if (is_ajax() && $_POST['session_token'] === $user_session_token) { // respond to ajax
	if ($_POST['action'] === 'send') {
		$check = $_POST['before'];
		$same = true;
		if (count($cell_map) === count($check)) {
			foreach ($cell_map as $row_index => $row) { // check if the data has be changed by others
				if (count($row) !== count($check[$row_index])) {
					$same = false;
					break;
				}
				foreach ($row as $column_index => $data) {
					if ($check[$row_index][$column_index] !== $data) {
						$same = false;
						break 2;
					}
				}
			}
		} else {
			$same = false;
		}
		if ($same) {
			$cell_map = $_POST['after'];
			$return['success'] = '1';
		} else {
			$return['reference'] = $cell_map;
			$return['success'] = '0';
		}
	} else if ($_POST['action'] === 'check') {
		$return['reference'] = $cell_map;
	}		
	echo json_encode($return, JSON_FORCE_OBJECT);
} else {
	for ($y = 0; $y < $rows; $y++) {
		echo '<tr>';
		if (isset($cell_map[$y])) {
			$row = $cell_map[$y];
		} else {
			$row = array();
		}
		for ($x = 0; $x < $columns; $x++) {
			$str = isset($row[$x]) ? $row[$x] : '';
			echo '<td contenteditable>' . $str . '</td>';
		}
		echo '</tr>';
	}
}

$cell_map_ser = serialize($cell_map);
file_put_contents($config['paths']['editable_table'], $cell_map_ser);

function is_ajax() {
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

?>
