<?php

header('Content-Type: application/json');

$HTTPS_required = false;
$authentication_required = true;

$api_response_code = array(
	0 => array('HTTP Response' => 400, 'Message' => 'Unknown Error'),
	1 => array('HTTP Response' => 200, 'Message' => 'Success'),
	2 => array('HTTP Response' => 403, 'Message' => 'HTTPS Required'),
	3 => array('HTTP Response' => 401, 'Message' => 'Authentication Required'),
	4 => array('HTTP Response' => 401, 'Message' => 'Authentication Failed'),
	5 => array('HTTP Response' => 404, 'Message' => 'Invalid Request'),
	6 => array('HTTP Response' => 400, 'Message' => 'Invalid Response Format')
);

if($HTTPS_required && $_SERVER['HTTPS'] != 'on')
{
	$response['code'] = 2;
	$response['status'] = $api_response_code[$response['code']]['HTTP Response'];
	$response['message'] = $api_response_code[$response['code']]['Message'];
	$response['success'] = false;
	sendresponse($response);
}

if($authentication_required == true)
{
	// $_POST
	// $_GET
	
	if (empty($_POST['username']) || empty($_POST['password']))
	{
		$response['code'] = 3;
		$response['status'] = $api_response_code[$response['code']]['HTTP Response'];
		$response['message'] = $api_response_code[$response['code']]['Message'];
		$response['success'] = false;		
		sendresponse($response);	
	}
	
	$username = $_POST['username'];
	$pass = $_POST['password'];
	$credential = validatecredentials($username, $pass);
	if($credential == false)
	{
		$response['code'] = 4;
		$response['status'] = $api_response_code[$response['code']]['HTTP Response'];
		$response['message'] = $api_response_code[$response['code']]['Message'];	
		$response['success'] = false;
		
		sendresponse($response);			
	}
}
/* we passed - send the data back */
$response['code'] = 1;
$response['status'] = $api_response_code[$response['code']]['HTTP Response'];
$response['message'] = $api_response_code[$response['code']]['Message'];		
$response['success'] = true;

sendresponse($response);

function sendresponse($response)
{
	header('HTTP/1.1 '.$response['status']. ' '.$response['message']);
	header('Content-Type: application/json; charset=utf-8');
	
	echo json_encode($response);
	
	exit;
}

function validatecredentials($username, $password)
{
	//return false;
	
	$passhash = hash("sha256", "$password");
	$config = parse_ini_file("settings/dataleader.txt");
	$dbUser = $config['user'];
	$dbPass = $config['pass'];
	$dbDatabase = $config['database'];
	$dbServer = $config['server'];
	$connect= new mysqli($dbServer, $dbUser, $dbPass, $dbDatabase);
	if ($connect->connect_error) 
	{
		die('Connect Error (' . $connect->connect_errno . ') '. $connect->connect_error);
	}
	$parm = "%$username%";
	$sql_select = "SELECT * FROM admin_users WHERE username like '$parm'";

	$results = mysqli_query($connect, $sql_select);
	if(!$results)
	{
		print("$dbpass, $dbUser, $dbDatabase, $dbServer, --- parm is $parm\n");
		die('Could not retrieve data from admin_users with '.$username. ' and '.$password.'!\n');
	}
	
	if(mysqli_num_rows($results) == 0)
	{
		echo "no results!";
		return false;
	} 
	else
	{
		//echo "we have results let's get shit";
		$row = mysqli_fetch_assoc($results);
		$r_pass = $row['password']; 
		$r_user = $row['username'];
		//die("passwords $r_pass and $passhash! usernames received $r_user and submitted $username\n");
		if(strcmp(trim($r_pass),trim($passhash)) == 0)
			return true;
		else
			return false;
	}
}