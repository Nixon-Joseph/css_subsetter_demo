<?php
/**
 * @author nieminen <nieminen432@gmail.com>
 */

//$start = microtime(true);
require './vendor/autoload.php';
//$app = new devpirates\MVC\TemplateMVCApp("./app/cache");  // enable caching
$app = new devpirates\MVC\TemplateMVCApp();

$app->Autoload("./app/controllers", array("./app/models", "./app/helpers", "./app/repos"));

// private creds file, sets $dbServer, $dbName, $dbUser, and $dbPass variables
require "../private/mvc_db_creds.php";
// configure the templated mvc app db access
$app->Config($dbServer, $dbName, $dbUser, $dbPass);
unset ($dbServer, $dbName, $dbUser, $dbPass); // unset variables so they can't be accessed again
// configure the templated mvc app without db access
//$app->ConfigSession();


require "./app/classes/Constants.php";
$siteData = array(); // set site data replacer values
$siteData["SiteTitle"] = Constants::SITE_NAME;
$siteData["SiteName"] = Constants::SITE_NAME;
$siteData["Scripts"] = "";
$siteData["SiteSubtitle"] = Constants::SITE_SUBTITLE;
$siteData["CopyYear"] = date("Y");
$siteData["SiteAddress"] = Constants::SITE_ADDRESS;
$siteData["SiteDescription"] = Constants::SITE_DESCRIPTION;
$siteData["PageTitle"] = Constants::SITE_NAME;

// starts the mvc process. Passing in view directory, name of 404 controller, and default site data replacer values
$app->Start("./app/views", "FileNotFoundController", $siteData);
//echo 'Render took ' . number_format(microtime(true) - $start, 3) . ' seconds.';
?>