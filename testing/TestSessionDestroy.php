<?php

/*
 * Test that the session_destroy works as expected.
 */

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/settings.php');

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if ($db->connect_error)
{
    throw new Exception("Failed to connect to database");
}

$handler = new \Programster\SessionHandler\SessionHandler($db, 'my_sessions_table');
session_set_save_handler($handler, true);
session_start();

// set a session variable.
$_SESSION['my_session_variable'] = 'some data here';

// check that row is set in the database.
$sessionId = session_id();

$destructionStatus = session_destroy();

if ($destructionStatus === false)
{
    die("Failed to destroy the session successfully");
}

$query = "SELECT * FROM `my_sessions_table` WHERE `id` = '{$sessionId}'";
$db2 = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
$result = $db2->query($query);

if ($result === false)
{
    throw new Exception("Failed to select data from sessions table." . $db2->error);
}

/* @var $result mysqli_result */
if ($result->num_rows > 0)
{
    die("Session destroy didn't work as expected. Data still in the database.");
}

print "Test PASSED." . PHP_EOL;

