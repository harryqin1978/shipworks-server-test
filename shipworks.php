<?php

define("SHIPWORKS_PLATFORM", "Shipworks Server Test");
define("SHIPWORKS_DEVELOPER", "Harry Qin (zergling9999@hotmail.com)");
$moduleVersion = "3.9.3.0";
$schemaVersion = "1.0.0";

// using output buffering to get around headers that magento is setting after we've started output
ob_start();

header("Content-Type: text/xml;charset=utf-8");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

// HTTP/1.1
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

// HTTP/1.0
header("Pragma: no-cache");

// write xml documenta declaration
function writeXmlDeclaration()
{
  echo "<?xml version=\"1.0\" standalone=\"yes\" ?>";
}

function writeStartTag($tag, $attributes = null)
{
  echo '<' . $tag;
  
  if ($attributes != null)
  {
    echo ' ';
    
    foreach ($attributes as $name => $attribValue)
    {
      echo $name. '="'. htmlspecialchars($attribValue). '" ';
    }
  }
  
  echo '>';
}

// write closing xml tag
function writeCloseTag($tag)
{
  echo '</' . $tag . '>';
}

// Output the given tag\value pair
function writeElement($tag, $value)
{
  writeStartTag($tag);
  echo htmlspecialchars($value);
  writeCloseTag($tag);
}

// Outputs the given name/value pair as an xml tag with attributes
function writeFullElement($tag, $value, $attributes)
{
  echo '<'. $tag. ' ';

  foreach ($attributes as $name => $attribValue)
  {
    echo $name. '="'. htmlspecialchars($attribValue). '" ';
  }
  echo '>';
  echo htmlspecialchars($value);
  writeCloseTag($tag);
}

// Function used to output an error and quit.
function outputError($code, $error)
{
  writeStartTag("Error");
  writeElement("Code", $code);
  writeElement("Description", $error);
  writeCloseTag("Error");
}

// Open the XML output and root
writeXmlDeclaration();
writeStartTag("ShipWorks", array("moduleVersion" => $moduleVersion, "schemaVersion" => $schemaVersion));

// If the admin module is installed, we make use of it
if (checkAdminLogin())
{
  $action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : '');
  switch (strtolower($action)) 
  {
    case 'getmodule': Action_GetModule(); break;
    case 'getstore': Action_GetStore(); break;
    case 'getcount': Action_GetCount(); break;
    case 'getorders': Action_GetOrders(); break;
    case 'getstatuscodes': Action_GetStatusCodes(); break;
    case 'updateorder': Action_UpdateOrder(); break;
    default:
      outputError(20, "'$action' is not supported.");
  }
}

// Check to see if admin functions exist.  And if so, determine if the user
// has access.
function checkAdminLogin()
{
  // TODO: Detail authorize check, now only simply return true.
  return true;
}

function Action_GetModule()
{
  writeStartTag("Module");
  
    writeElement("Platform", SHIPWORKS_PLATFORM);
    writeElement("Developer", SHIPWORKS_DEVELOPER);

    writeStartTag("Capabilities");
      writeElement("DownloadStrategy", "ByModifiedTime");
      writeFullElement("OnlineCustomerID", "", array("supported" => "true", "dataType" => "numeric"));
      writeFullElement("OnlineStatus", "", array("supported" => "true", "dataType" => "text", "downloadOnly" => "true" ));
      writeFullElement("OnlineShipmentUpdate", "", array("supported" => "false"));
    writeCloseTag("Capabilities");
  
  writeCloseTag("Module");
}

// Close the output
writeCloseTag("ShipWorks");

// end output
ob_end_flush();
