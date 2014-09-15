<?php

define("SHIPWORKS_PLATFORM", "Shipworks Server Test");
define("SHIPWORKS_DEVELOPER", "Harry Qin (zergling9999@hotmail.com)");
define("SHIPWORKS_MODULE_VERSION", "3.9.3.0");
define("SHIPWORKS_SCHEMA_VERSION", "1.0.0");

shipworks();

function shipworks() {
  // using output buffering to get around headers that magento is setting after we've started output
  ob_start();

  header("Content-Type: text/xml;charset=utf-8");
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

  // HTTP/1.1
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);

  // HTTP/1.0
  header("Pragma: no-cache");

  // Open the XML output and root
  writeXmlDeclaration();
  writeStartTag("ShipWorks", array("moduleVersion" => SHIPWORKS_MODULE_VERSION, "schemaVersion" => SHIPWORKS_SCHEMA_VERSION));

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
      case 'updatestatus': Action_UpdateStatus(); break;
      case 'updateshipment': Action_UpdateShipment(); break;
      default:
        outputError(20, "'$action' is not supported.");
    }
  }

  // Close the output
  writeCloseTag("ShipWorks");

  // end output
  ob_end_flush();
}

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

// Check to see if admin functions exist.  And if so, determine if the user
// has access.
function checkAdminLogin()
{
  // TODO: Detail authorize check, now only simply return true.
  return true;
}

// NOTE: Drupal stored time commly by unix timestamp, so below codes maybe need some change.
// Converts an xml datetime string to sql date time
function toLocalSqlDate($sqlUtc)
{   
  $pattern = "/^(\d{4})-(\d{2})-(\d{2})\T(\d{2}):(\d{2}):(\d{2})$/i";

  if (preg_match($pattern, $sqlUtc, $dt)) 
  {
    $unixUtc = gmmktime($dt[4], $dt[5], $dt[6], $dt[2], $dt[3], $dt[1]);  

    return date("Y-m-d H:i:s", $unixUtc);
  }

  return $sqlUtc;
}

// Write module data
function Action_GetModule()
{
  writeStartTag("Module");
  
    writeElement("Platform", SHIPWORKS_PLATFORM);
    writeElement("Developer", SHIPWORKS_DEVELOPER);

    writeStartTag("Capabilities");
      writeElement("DownloadStrategy", "ByModifiedTime");
      writeFullElement("OnlineCustomerID", "", array("supported" => "true", "dataType" => "numeric"));
      writeFullElement("OnlineStatus", "", array("supported" => "true", "dataType" => "text", "downloadOnly" => "false" ));
      writeFullElement("OnlineShipmentUpdate", "", array("supported" => "true"));
    writeCloseTag("Capabilities");
  
  writeCloseTag("Module");
}

// Write store data
function Action_GetStore()
{
  $name = "My owesome store";
  $owner = "Harry Qin";
  $email = "zergling9999@hotmail.com";
  $country = "China";
  $website = "http://baidu.com";

  writeStartTag("Store");
    writeElement("Name", $name);
    writeElement("CompanyOrOwner", $owner);
    writeElement("Email", $email);
    writeElement("Country", $country);
    writeElement("Website", $website);
  writeCloseTag("Store");
}

// Returns the status codes for the store
function Action_GetStatusCodes()
{
  writeStartTag("StatusCodes");

  $statuses = array();
  $statuses[] = array('code' => '001', 'name' => 'Checkout');
  $statuses[] = array('code' => '002', 'name' => 'Pending');
  $statuses[] = array('code' => '003', 'name' => 'Paid');
  $statuses[] = array('code' => '004', 'name' => 'Shipped');
  $statuses[] = array('code' => '005', 'name' => 'Complete');

  foreach ($statuses as $status)
  {
    writeStartTag("StatusCode");
      writeElement("Code", $status['code']);
      writeElement("Name", $status['name']);
    writeCloseTag("StatusCode");
  }

  writeCloseTag("StatusCodes");
}

// Get the count of orders greater than the start ID
function Action_GetCount()
{         
  $start = 0;

  if (isset($_REQUEST['start']))
  {
    $start = $_REQUEST['start'];
  }

  // NOTE: Drupal stored time commly by unix timestamp, so below codes maybe need some change.

  // only get orders through 2 seconds ago
  $end = date("Y-m-d H:i:s", time() - 2);
  // Convert to local SQL time
  $start = toLocalSqlDate($start);

  // Write the params for easier diagnostics
  writeStartTag("Parameters");
  writeElement("Start", $start);
  writeCloseTag("Parameters");

  writeElement("OrderCount", 3);
}

// Get all orders greater than the given start id, limited by max count
function Action_GetOrders()
{
  $start = 0;
  $maxcount = 50;

  if (isset($_REQUEST['start']))
  {
      $start = $_REQUEST['start'];
  }

  if (isset($_REQUEST['maxcount']))
  {
      $maxcount = $_REQUEST['maxcount'];
  }

  // NOTE: Drupal stored time commly by unix timestamp, so below codes maybe need some change.

  // Only get orders through 2 seconds ago.
  $end = date("Y-m-d H:i:s", time() - 2);
  // Convert to local SQL time
  $start = toLocalSqlDate($start);

  // Write the params for easier diagnostics
  writeStartTag("Parameters");
  writeElement("Start", $start);
  writeElement("End", $end);
  writeElement("MaxCount", $maxcount);
  writeCloseTag("Parameters");                                    

  writeStartTag("Orders");

  $orders = array();

  $items = array();
  $items[] = array(
    'item_id' => 'item_001',
    'product_id' => 'product_001',
    'code' => '001',
    'sku' => '001',
    'name' => 'Product name 001',
    'quantity' => 30,
    'unit_price' => 30.55,
    'weight' => 12.5,
  );
  $items[] = array(
    'item_id' => 'item_002',
    'product_id' => 'product_002',
    'code' => '002',
    'sku' => '002',
    'name' => 'Product name 002',
    'quantity' => 40,
    'unit_price' => 12.55,
    'weight' => 0.5,
  );
  $items[] = array(
    'item_id' => 'item_003',
    'product_id' => 'product_003',
    'code' => '003',
    'sku' => '003',
    'name' => 'Product name 003',
    'quantity' => 15,
    'unit_price' => 12.55,
    'weight' => 15,
  );

  $orders[] = array(
    'order_number_prefix' => '',
    'order_number' => '00001',
    'order_number_postfix' => '',
    'order_date' => gmdate("Y-m-d\TH:i:s", strtotime('2014-08-15')),
    'last_modified' => gmdate("Y-m-d\TH:i:s", time()-3000),
    'shipping_method' => 'Horse',
    'status_code' => '001',
    'customer_id' => '125',
    'shipping_address' => array(
      'full_name' => 'Steve Jobs',
      'company' => 'Banana',
      'street1' => 'Street 1 content',
      'street2' => 'Street 2 content',
      'street3' => 'Street 3 content',
      'city' => 'Shanghai',
      'postal_code' => '200000',
      'country' => 'cn',
      'phone' => '0086-021-88888888',
    ),
    'billing_address' => array(
      'full_name' => 'Bill Gates',
      'company' => 'MicroHard',
      'street1' => 'Street 1 content',
      'street2' => 'Street 2 content',
      'street3' => 'Street 3 content',
      'city' => 'Shanghai',
      'postal_code' => '200000',
      'country' => 'cn',
      'phone' => '0086-021-66666666',
    ),
    'items' => $items,
  );

  $orders[] = array(
    'order_number_prefix' => '',
    'order_number' => '00002',
    'order_number_postfix' => '',
    'order_date' => gmdate("Y-m-d\TH:i:s", strtotime('2014-08-26')),
    'last_modified' => gmdate("Y-m-d\TH:i:s", time()-1200),
    'shipping_method' => 'Donkey',
    'status_code' => '002',
    'customer_id' => '130',
    'shipping_address' => array(
      'full_name' => 'Bill Gates',
      'company' => 'MicroHard',
      'street1' => 'Street 1 content',
      'street2' => 'Street 2 content',
      'street3' => 'Street 3 content',
      'city' => 'Shanghai',
      'postal_code' => '200000',
      'country' => 'cn',
      'phone' => '0086-021-88888888',
    ),
    'billing_address' => array(
      'full_name' => 'Bill Gates',
      'company' => 'MicroHard',
      'street1' => 'Street 1 content',
      'street2' => 'Street 2 content',
      'street3' => 'Street 3 content',
      'city' => 'Shanghai',
      'postal_code' => '200000',
      'country' => 'cn',
      'phone' => '0086-021-66666666',
    ),
    'items' => $items,
  );

  $orders[] = array(
    'order_number_prefix' => '',
    'order_number' => '00003',
    'order_number_postfix' => '',
    'order_date' => gmdate("Y-m-d\TH:i:s", strtotime('2014-09-05')),
    'last_modified' => gmdate("Y-m-d\TH:i:s", time()-200),
    'shipping_method' => 'Camel',
    'status_code' => '003',
    'customer_id' => '133',
    'shipping_address' => array(
      'full_name' => 'Bill Gates',
      'company' => 'MicroHard',
      'street1' => 'Street 1 content',
      'street2' => 'Street 2 content',
      'street3' => 'Street 3 content',
      'city' => 'Shanghai',
      'postal_code' => '200000',
      'country' => 'cn',
      'phone' => '0086-021-88888888',
    ),
    'billing_address' => array(
      'full_name' => 'Bill Gates',
      'company' => 'MicroHard',
      'street1' => 'Street 1 content',
      'street2' => 'Street 2 content',
      'street3' => 'Street 3 content',
      'city' => 'Shanghai',
      'postal_code' => '200000',
      'country' => 'cn',
      'phone' => '0086-021-66666666',
    ),
    'items' => $items,
  );

  // ShipWorks will make repeated calls to GetOders to download order data, and will cease when the response contains no orders.
  // So in this test server, we need uncomment below code (empty orders) when start downloading to avoid unlimited circle.
  $orders = array();

  foreach ($orders as $order)
  {
    writeStartTag("Order");
      writeElement("OrderNumberPrefix", $order['order_number_prefix']);
      writeElement("OrderNumber", $order['order_number']);
      writeElement("OrderNumberPostfix", $order['order_number_postfix']);
      writeElement("OrderDate", $order['order_date']);
      writeElement("LastModified", $order['last_modified']);
      writeElement("ShippingMethod", $order['shipping_method']);
      writeElement("StatusCode", $order['status_code']);
      writeElement("CustomerID", $order['customer_id']);
      writeStartTag("ShippingAddress");
        writeElement("FullName", $order['shipping_address']['full_name']);
        writeElement("Company", $order['shipping_address']['company']);
        writeElement("Street1", $order['shipping_address']['street1']);
        writeElement("Street2", $order['shipping_address']['street2']);
        writeElement("Street3", $order['shipping_address']['street3']);
        writeElement("City", $order['shipping_address']['city']);
        writeElement("PostalCode", $order['shipping_address']['postal_code']);
        writeElement("Country", $order['shipping_address']['country']);
        writeElement("Phone", $order['shipping_address']['phone']);
      writeCloseTag("ShippingAddress");
      writeStartTag("BillingAddress");
        writeElement("FullName", $order['billing_address']['full_name']);
        writeElement("Company", $order['billing_address']['company']);
        writeElement("Street1", $order['billing_address']['street1']);
        writeElement("Street2", $order['billing_address']['street2']);
        writeElement("Street3", $order['billing_address']['street3']);
        writeElement("City", $order['billing_address']['city']);
        writeElement("PostalCode", $order['billing_address']['postal_code']);
        writeElement("Country", $order['billing_address']['country']);
        writeElement("Phone", $order['billing_address']['phone']);
      writeCloseTag("BillingAddress");
      writeStartTag("Items");
      $items = $order['items'];
      foreach ($items as $item) {
        writeStartTag("Item");
          writeElement("ItemID", $item['item_id']);
          writeElement("ProductID", $item['product_id']);
          writeElement("Code", $item['code']);
          writeElement("SKU", $item['sku']);
          writeElement("Name", $item['name']);
          writeElement("Quantity", (int)$item['quantity']);
          writeElement("UnitPrice", $item['unit_price']);
          writeElement("Weight", $item['weight']);
        writeCloseTag("Item");
      }
      writeCloseTag("Items");
      writeStartTag("Totals");
      writeCloseTag("Totals");
    writeCloseTag("Order");
  }

  writeCloseTag("Orders");
}

// update order status
function Action_UpdateStatus() {
  // get parameters
  $order = isset($_REQUEST['order']) ? $_REQUEST['order'] : '';
  $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
  $comments = isset($_REQUEST['comments']) ? $_REQUEST['comments'] : '';

  // return success
  // writeStartTag('UpdateSuccess');
  // writeCloseTag('UpdateSuccess');

  // return error
  $error_msg = 'This is all your fault! order: ' . $order . ' status: ' . $status . ' comments: ' . $comments;
  outputError('FOO100', $error_msg);
}

// update order shipment
function Action_UpdateShipment() {
  // TODO: I dont know how to trigger this action in shipworks, always say has errors occurred while getting rates.
  // return success
  writeStartTag('UpdateSuccess');
  writeCloseTag('UpdateSuccess');
}