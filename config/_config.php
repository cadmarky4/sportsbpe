<?php //app configuration 
//used as configuration page and as installer

declare(strict_types=1);

$PATH_2_ROOT = '../';


//#############################################
//here we need to get the CONFIG and DB_CONFIG

//we also use this page to install:
//1. the .env file
//2. the database init script
//3. the Admin User

//some basic data ################
//4. the first Location [optional]
//5. the first Group [optional]
//6. the extra Admin Users (LocationAdmin, GroupAdmin, GroupAdmin2) [optional]
//7. Sample Dropdowns [optional]
//8. Sports Data [optional]
//#############################################


$CONFIG = array();
$DB_CONFIG = array();
$ENV_File = __DIR__ . '/' . $PATH_2_ROOT . '.env';
$ENV_File_Sample = __DIR__ . '/' . $PATH_2_ROOT . '.env.sample';

function get_DB_CONFIG__From_ENV_File(string $ENV_File):mixed {
	$DB_CONFIG_arr = array();
	
	// Try DATABASE_URL first (Railway format)
	$database_url = getenv("DATABASE_URL");
	if ($database_url) {
		$url = parse_url($database_url);
		$DB_CONFIG_arr["DB_Host"] = $url['host'];
		$DB_CONFIG_arr["DB_Name"] = ltrim($url['path'], '/');
		$DB_CONFIG_arr["DB_User"] = $url['user'];
		$DB_CONFIG_arr["DB_Pass"] = $url['pass'];
		$DB_CONFIG_arr["DB_Port"] = $url['port'] ?? '3306';
		$DB_CONFIG_arr["DB_Type"] = 'mysqli';
		return $DB_CONFIG_arr;
	}
	
	//load Environment Variables - try multiple formats
	// Try standard format first (with underscore)
	$DB_CONFIG_arr["DB_Host"] = getenv("MYSQL_HOST");
	$DB_CONFIG_arr["DB_Name"] = getenv("MYSQL_DATABASE");
	$DB_CONFIG_arr["DB_User"] = getenv("MYSQL_USER");
	$password = getenv("MYSQL_PASSWORD");
	$port = getenv("MYSQL_PORT");
	
	// If standard format not found, try Railway format (no underscore)
	if (!$DB_CONFIG_arr["DB_Host"]) {
		$DB_CONFIG_arr["DB_Host"] = getenv("MYSQLHOST");
	}
	if (!$DB_CONFIG_arr["DB_Name"]) {
		$DB_CONFIG_arr["DB_Name"] = getenv("MYSQLDATABASE");
	}
	if (!$DB_CONFIG_arr["DB_User"]) {
		$DB_CONFIG_arr["DB_User"] = getenv("MYSQLUSER") ?: "root";
	}
	if (!$password) {
		$password = getenv("MYSQLPASSWORD");
	}
	if (!$port) {
		$port = getenv("MYSQLPORT");
	}
	
	// Set default port if not specified
	if (!$port) {
		$port = "3306";
	}
	$DB_CONFIG_arr["DB_Port"] = $port;
	
	// Detect database type based on hostname
	$host = $DB_CONFIG_arr["DB_Host"];
	if ($host && strpos($host, 'dpg-') === 0) {
		// This is a Render PostgreSQL hostname
		$DB_CONFIG_arr["DB_Type"] = 'pgsql';
	} elseif ($host === 'db') {
		// This is local Docker MySQL
		$DB_CONFIG_arr["DB_Type"] = 'mysqli';
	} elseif ($host && (strpos($host, 'railway.app') !== false || strpos($host, 'rlwy.net') !== false || strpos($host, 'db4free.net') !== false)) {
		// This is external MySQL service (Railway or others)
		$DB_CONFIG_arr["DB_Type"] = 'mysqli';
	} else {
		// Default to MySQL for external services
		$DB_CONFIG_arr["DB_Type"] = 'mysqli';
	}
	
	// Handle password from file or direct
	$file_path = getenv("MYSQL_PASSWORD_FILE");
	$file_contents = "";
	if ($file_path) {
		$file_contents = file_get_contents($file_path);
	} 
	if ($file_contents) {
		$DB_CONFIG_arr["DB_Pass"] = trim($file_contents);
	} else {
		// Use the password we found above
		$DB_CONFIG_arr["DB_Pass"] = $password;
	}
	
	return $DB_CONFIG_arr;
}

function is_CONFIG_Option_Missing():bool {
	global $CONFIG;
	if (
		!isset($CONFIG['DOMAIN']) OR 
		!isset($CONFIG['REGmon_Folder']) OR 
		!isset($CONFIG['EMAIL']['Host']) OR 
		!isset($CONFIG['EMAIL']['Port']) OR 
		!isset($CONFIG['EMAIL']['Username']) OR 
		!isset($CONFIG['EMAIL']['Password']) OR 
		!isset($CONFIG['EMAIL']['From_Name']) OR 
		!isset($CONFIG['EMAIL']['From_Email']) OR 
		//-!isset($CONFIG['EMAIL']['Replay_Name']) OR 
		//-!isset($CONFIG['EMAIL']['Replay_Email']) OR 
		!isset($CONFIG['EMAIL']['Support']) OR 
		!isset($CONFIG['Production_Mode']) OR 
		!isset($CONFIG['HTTP']) OR 
		!isset($CONFIG['Force_Redirect_To_HTTPS']) OR 
		!isset($CONFIG['SEC_Page_Secret']) OR 
		!isset($CONFIG['SEC_Hash_Secret']) OR 
		!isset($CONFIG['SEC_Encrypt_Secret']) OR 
		!isset($CONFIG['SEC_Hash_IP']) OR 
		!isset($CONFIG['LogLimiter']['Max_Attempts']) OR 
		!isset($CONFIG['LogLimiter']['Block_Minutes']) OR 
		!isset($CONFIG['Use_VisualCaptcha']) OR 
		!isset($CONFIG['Use_Multi_Language_Selector']) OR 
		!isset($CONFIG['Default_Language']) OR 
		!isset($CONFIG['DB_Debug_File']) OR 
		!isset($CONFIG['DB_Debug'])
	) {
		return true;
	}
	return false;
}


//get $DB_CONFIG from .env file ##################
if (is_file($ENV_File)) { //.env file exists
	if (is_readable($ENV_File)) { //.env file is readable
		$DB_CONFIG = get_DB_CONFIG__From_ENV_File($ENV_File);
	} else {
		die("Permission Denied for reading the file " . $ENV_File);
	}
}
else {
	//die("Environment File '" . $ENV_File . "' is Missing.");
	$SEC_check_config = 'ENV_File_Missing';
	require(__DIR__ . '/' . $PATH_2_ROOT . 'config.php');
	exit;
}
//get $DB_CONFIG #################################


//set report off - only for MySQL
if (isset($DB_CONFIG['DB_Type']) && $DB_CONFIG['DB_Type'] === 'mysqli') {
	mysqli_report(MYSQLI_REPORT_OFF);
}
//from php 8.1.0 the default is MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT


//Init DB ###############################################
require_once(__DIR__.'/'.$PATH_2_ROOT.'php/class.db.php');	
// Check if db::open supports port parameter
if (isset($DB_CONFIG['DB_Port']) && $DB_CONFIG['DB_Port'] != '3306') {
	// Try to pass port as host:port format for Railway
	$host_with_port = $DB_CONFIG['DB_Host'] . ':' . $DB_CONFIG['DB_Port'];
	$db = db::open($DB_CONFIG['DB_Type'], $DB_CONFIG['DB_Name'].'', $DB_CONFIG['DB_User'].'', $DB_CONFIG['DB_Pass'].'', $host_with_port);
} else {
	$db = db::open($DB_CONFIG['DB_Type'], $DB_CONFIG['DB_Name'].'', $DB_CONFIG['DB_User'].'', $DB_CONFIG['DB_Pass'].'', $DB_CONFIG['DB_Host'].'');
}
//Init DB ###############################################


//*check DB Connection ###########################
//we check DB Connection only if .env file missing
//we check for missing data if we have a $db->myError in get CONFIG


//get CONFIG ####################################
$row = $db->fetchRow("SELECT val FROM config WHERE name = 'config'", array());
//we have entries
if ($db->numberRows() > 0)  {
	if ($row['val'] != '') {
		$CONFIG = json_decode($row['val'], true);
	}
	else {
		die('CONFIG Object Empty');
	}
}

//we have DB error
elseif ($db->myError) {
	$SEC_check_config = 'DB_Error__On_Get_CONFIG : ' . $db->myError;

	//check if we have tables
	$num_of_tables = $db->fetchRow("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", array($DB_CONFIG['DB_Name']));
	if ($db->numberRows() > 0) {
		//TODO: 21 is the hardcoded count of app tables --this should come from app schema
		$app_tables_count = 21;

		//no tables found
		if ($num_of_tables['count'] === '0') {
			$SEC_check_config = 'APP_Database_Empty';	
			require(__DIR__ . '/' . $PATH_2_ROOT . 'config.php');
			exit;
		}
		elseif ($num_of_tables['count'] != $app_tables_count) {
			die('APP Database Tables Missing! <br>' . $num_of_tables['count'] . ' of ' . $app_tables_count . ' found!');

			// $SEC_check_config = 'APP_Database_Tables_Missing';

			//TODO: check for missing database tables or fields
			//TODO: not needed now. Maybe in the future for updates
			//1. build app schema
			//2. check app schema with current db and find differences
			//3. apply differences to current db
		}
	}


	//check if we have config table
	$rows = $db->fetchAllwithKey("SELECT * FROM config", array(), 'name');
	if ($db->numberRows() > 0) {
		//table exist but config value not
		$SEC_check_config .= '<br>CONFIG_Table_Exist';

		if (isset($rows['config'])) {
			$SEC_check_config .= '<br>CONFIG_Object_Exist';	

			if ($rows['config'] != '') {
				$SEC_check_config .= '<br>CONFIG_Object_OK';

				if (is_CONFIG_Option_Missing()) {
					die('CONFIG Option Missing!');
				}
			}
			else {
				$SEC_check_config .= '<br>CONFIG_Object_Empty';			
			}
		}
	}
	elseif ($db->myError != '') {
		//table not exist
		$SEC_check_config .= '<br>CONFIG_Table_Missing'.'<br>'.$db->myError;
	}
	else {
		//empty table
		$SEC_check_config .= '<br>CONFIG_Table_Empty';
	}

	//never come here in my tests
	die($SEC_check_config);
}

//we have table but not the config key
else {

	//*now its a good time to install some basic data

	//Admin user check
	$row = $db->fetchRow("SELECT id FROM users WHERE uname = 'admin' AND level = 99", array());
	if ($db->numberRows() > 0)  {} //exist
	else {
		//die('APP Admin User not exist');

		$SEC_check_config = 'APP_Admin_User_Missing';
		require(__DIR__ . '/' . $PATH_2_ROOT . 'config.php');
		exit;
	}



	//die('CONFIG Key Missing!');

	$SEC_check_config = 'CONFIG_Key_Missing';
	require(__DIR__ . '/' . $PATH_2_ROOT . 'config.php');
	exit;
}
//get CONFIG ####################################



//###############################
if (is_CONFIG_Option_Missing()) {
	//TODO: for new config values check
	die('CONFIG Option Missing!');
}




//secure ajax sub pages from direct call
$SEC_check = $CONFIG['SEC_Page_Secret'];


//Init DB Log ###########################################
//set db log from the URL with page.php?dblog --not work for ajax calls
if (!$CONFIG['DB_Debug'] and isset($_GET['dblog'])) {
	$CONFIG['DB_Debug'] = true;
}
if ($CONFIG['DB_Debug']) {
	//enable query logging
	$db->logToFile($CONFIG['DB_Debug_File']);
}
//Init DB Log ###########################################


//Load languages
require_once(__DIR__.'/'.$PATH_2_ROOT.'php/class.language.php');
$LANG = Language::getInstance($CONFIG['REGmon_Folder'].'', $CONFIG['Default_Language'].'', !!$CONFIG['Use_Multi_Language_Selector']);


//Load Date functions -> they based on language
require_once(__DIR__.'/'.$PATH_2_ROOT.'php/inc.date_functions.php');


//Load Global functions
require_once(__DIR__.'/'.$PATH_2_ROOT.'php/inc.global_functions.php');



//continue to the page that call this
?>