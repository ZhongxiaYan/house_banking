<?php

require_once 'init.php';

$cells_sql = $mysqli->query('SELECT * FROM ' . $config['db']['tables']['cellinfo'] . ' ORDER BY row_index');
$cell_map = array();
if ($cells_sql && $cell = $cells_sql->fetch_assoc()) { // if any data at all
	$curr_row = $cell['row_index'];
	$curr_row_array = array($cell['column_index'] => $cell['val']);
	while ($cell = $cells_sql->fetch_assoc()) {
		if ($cell['row_index'] !== $curr_row) { // encounter new row, start a new array
			$cell_map[$curr_row] = $curr_row_array;
			$curr_row_array = array();
			$curr_row = $cell['row_index'];
		}
		$curr_row_array[$cell['column_index']] = $cell['val'];
	}
	$cell_map[$curr_row] = $curr_row_array;
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
		$check = array_key_exists('before', $_POST) ? $_POST['before'] : array();
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
			$return['success'] = '1';
			$mysqli->query('DELETE FROM ' . $config['db']['tables']['cellinfo'] . ';');
			if (array_key_exists('after', $_POST)) {
				$cell_map = $_POST['after'];
				$query = 'INSERT INTO ' . $config['db']['tables']['cellinfo'] . ' (row_index, column_index, val) VALUES ';
				$first = 1;

				$parameter_str = '';
				$temp_arr = array('', '', '');
				$args = array(&$parameter_str);
				$count = 0;
				foreach ($cell_map as $row_index => $row) {
					foreach ($row as $column_index => $val) {
						if ($first === 0) {
							$query .= ', ';
						}
						$first = 0;
						$query .= '(?, ?, ?)';
						$temp_arr[$count] = $row_index;
						$temp_arr[$count + 1] = $column_index;
						$temp_arr[$count + 2] = $val;
						$args[] = &$temp_arr[$count];
						$args[] = &$temp_arr[$count + 1];
						$args[] = &$temp_arr[$count + 2];
						$parameter_str .= 'sss';
						$count += 3;
					}
				}
				$query .= ';';
				$stmt = $mysqli->prepare($query);
				if (!call_user_func_array(array($stmt, 'bind_param'), $args)) {
					echo 'Binding parameter failed: (' . $stmt->errno . ') ' . $stmt->error;
				}
				if (!$stmt->execute()) {
					echo 'Execution failed: (' . $stmt->errno . ') ' . $stmt->error;
				}
			}
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

function is_ajax() {
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}



?>
