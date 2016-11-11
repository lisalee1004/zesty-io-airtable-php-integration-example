<?php
require('../vendor/autoload.php');

error_reporting(-1);
ini_set('display_errors', 0);

// Load environment variables from .env if present
if (file_exists('../.env')) {
    $dotenv = new Dotenv\Dotenv('../');
    $dotenv->load();
}

// variables
$air_table_id = getenv('AIRTABLE_TABLE_ID'); 
$air_table_key 	= getenv('AIRTABLE_API_KEY');
$air_table_api_version = getenv('AIRTABLE_API_VERSION');
$air_table_name 	= getenv('AIRTABLE_NAME');

$https = $_SERVER['HTTPS'] ? 'http://' : 'https://';
$download_domain 	= $https.$_SERVER['HTTP_HOST'];

if (empty($_FILES)) {
    die('Service online');
}

// Get the file size limit (or default to 15 MB)
// Note, if you go over a certain size, you may need to add a custom ini setting for Heroku
$maxFileSize = getenv('MAX_FILE_SIZE');
$maxFileSize = (!empty($maxFileSize)) ? $maxFileSize : 15;

// Convert to bytes
$maxFileSizeInBytes = $maxFileSize * 1024 * 1024;

if ($_FILES['userfile']['size'] > $maxFileSizeInBytes) {
    die('The file you are trying to upload is too big. It must not be more than ' . $maxFileSize . ' megabytes (MB).');
}

$originalFilename = basename($_FILES['userfile']['name']);
$originalExtension = (count(explode('.', $originalFilename)) > 1) ? '.' . array_reverse(explode('.', $originalFilename))[0] : '';

$newFilename = uniqid() .  $originalExtension;
$uploadTarget = 'uploads/' . $newFilename;

if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadTarget)) {
    
    $url_request 	= "https://api.airtable.com/".$air_table_api_version."/".$air_table_id."/".$air_table_name."?api_key=".$air_table_key;

	// Auth Request
	// first request to retreive the api auth key
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $url_request); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$output = curl_exec($ch);
	curl_close($ch);
	$output = json_decode($output);
	
	// this is the key we will use to send data
	$api_auth_key = $output->records[0]->id;
	
	
	// The fields to send to the AirTable form
	// This example shows using a text field, and a url field
	$data_for_entry = array (
		'fields' => array (
			'Name' => $_POST['name'],
			'Resume' => $download_domain.'/'.$uploadTarget
		)
	);
	
	// Data Request
	// setup new curl request for sending data
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url_request);
	curl_setopt($ch, CURLOPT_POST, 1);
	// encode data here
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_for_entry) );  //Post Fields
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// header are setup using the auth key retrieved in the previous request
	$headers = [
	    'Authorization: Bearer ' . $api_auth_key,
	    'Content-type: application/json'
	];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	$server_output = curl_exec ($ch);
	
	curl_close ($ch);
	
	// catpure return message
	print  $server_output ;
    
    

    $redirectTarget = (!empty($_POST['redirect_target'])) ? $_POST['redirect_target'] : $_SERVER['HTTP_REFERER'];
    header('Location: ' . $redirectTarget);
    die();
}

// AIR TABLE REQUESTS







