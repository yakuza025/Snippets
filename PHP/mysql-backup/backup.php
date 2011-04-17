<?

	// MySQL Backup Tool

	// all database configuration settings here
	$config = array(

		// host defines database host, plus username and password
		"host" => array(
			"name" => "localhost",
			"user" => "root",
			"password" => "root",
		),

		// db defines name of the database you'd like to work with, plus an optional filename for importing/exporting
		"db" => array(
			"name" => "testing",
			"backupFile" => "db-backup-" . date("Y-m-d-(H-i-s)") . ".sql",
		),
	);

	// backup an entire db, or just a table, to a flat file
	// (assumes open connection)
	// (more or less copied wholesale from http://davidwalsh.name/backup-mysql-database-php)
	function exportDB($connection, $name, $file, $tables = '*') {

		// initialize
	  $return = $row2 = $result = "";
		mysql_query('SET NAMES utf8');
		mysql_query('SET CHARACTER SET utf8');
	  mysql_select_db($name, $connection);
	  
	  // get all of the tables
	  if($tables == '*') {
	    $tables = array();
	    $result = mysql_query('SHOW TABLES');
	    while($row = mysql_fetch_row($result)) {
	      $tables[] = $row[0];
	    }
	  }
	  else {
	    $tables = is_array($tables) ? $tables : explode(',', $tables);
	  }
	  
	  // cycle through
	  foreach($tables as $table) {
	    $result = mysql_query('SELECT * FROM ' . $table);
	    $num_fields = mysql_num_fields($result);
	    
	    $return .= 'DROP TABLE ' . $table . ';';
	    $row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE ' . $table));
	    $return .= "\n\n" . $row2[1] . ";\n\n";
	    
	    for ($i = 0; $i < $num_fields; $i++) {
	      while($row = mysql_fetch_row($result)) {
	        $return .= 'INSERT INTO ' . $table . ' VALUES(';
	        for($j = 0; $j < $num_fields; $j++) {
	          $row[$j] = addslashes($row[$j]);
	          $row[$j] = ereg_replace("\n", "\\n", $row[$j]);
	          if (isset($row[$j])) {
	          	$return .= '"' . $row[$j].'"';
	          } else {
	          	$return .= '""';
	          }
	          if ($j < ($num_fields - 1)) {
	          	$return .= ',';
	          }
	        }
	        $return .= ");\n";
	      }
	    }
	    $return .= "\n\n\n";
	  }
	  
	  // save file
	  $handle = fopen($file , 'w+');
	  fwrite($handle, $return);
	  fclose($handle);

	}



	// list all databases on the host
	// (assumes open connection)
	function listDBs($connection) {

		$dbs = Array();
	
		$db_list = mysql_list_dbs($connection);
		while ($row = mysql_fetch_object($db_list)) {
			array_push($dbs, $row->Database);
		}
		if (count($dbs)) {
			return $dbs;
		}
	}



	// strict error reporting while debugging
	error_reporting(E_ALL);
	ini_set('display_errors', 'On');



	// connect to db server	
	$connection = @mysql_connect ($config["host"]["name"], $config["host"]["user"], $config["host"]["password"]) or die ('Couldn\'t connect: ' . mysql_error());


	$dbs = listDBs($connection);

	$exported = false;
	if (isset($_POST["export"])) {
		// dump this database to a file
		exportDB($connection, $_POST["db"], $_POST["filename"]);
		$exported = true;
	}


	// disconnect from db server	
	mysql_close($connection);



?>



<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>MySQL Backup</title>
	<meta name="robots" content="all">

	<link rel="stylesheet" href="../../common-ui/css/default.css" media="screen">
</head>
<body>


<div class="dialog">

	<header>
		<h1>MySQL Backup</h1>
	</header>

<?php
	if (!$exported) {
?>
	<p>This script will backup the MySQL database of your choice to a flat file.</p>

	<form method="post" action="./backup.php">
		<div>
			<input type="hidden" name="export" value="true">
			<label>Select a database:</label>
			<select name="db">
		<?php
			foreach ($dbs as $key => $name) {
				echo "<option value=\"$name\">$name</option>";
			}
		?>
			</select>
		</div>
		<div>
			<label>Filename:</label>
			<input name="filename" value="<?php echo $config["db"]["backupFile"]; ?>" type="text">
		</div>
		<div>
<!-- 			<button type="submit">Save to Disk</button> -->
			<button type="submit">Download</button>
		</div>
	</form>
<?php
	} else {
?>
	<p>Exported!</p>
<?php
	}
?>


</div>

</body>
</html>