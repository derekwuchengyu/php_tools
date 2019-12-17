<?php
	public function printSqlTable(array $_sqlData){
		if (count($_sqlData)==0)
			exit("0 results");

		
		echo "<style>table, th, td { border: 1px solid black; }</style>";
		echo "<table>";
		echo "<tr>";
		$columns = array_keys ($_sqlData[0]);
		foreach ($columns as $key => $value) {
			echo "<th>".$value."</th>";
		}
		echo "</tr>";
		foreach ($_sqlData as $key => $value) {
			echo "<tr>";
			foreach ($value as $column => $val) {
				echo "<td>".$val."</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
		exit;
	}

?>