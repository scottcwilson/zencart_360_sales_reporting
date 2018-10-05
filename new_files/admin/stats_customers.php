<?php
/**
 * @package admin
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: DrByte  Sat Oct 17 21:23:07 2015 -0400 Modified in v1.5.5 $
 */

/**
 * Do a comparison which will ensure that the items are priced from highest to lowest.
 * @param $a - first customer 
 * @param $b - second customer 
 * @return int - 0 = same; 1 = a is lower; -1 = b  is lower.
 */
function total_sort($a, $b) {
   if ($a['ordersum'] == $b['ordersum'])
      return 0;
   if ($a['ordersum'] < $b['ordersum'])
      return 1;
   return -1;
}

  require('includes/application_top.php');

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
  // -->
</script>
</head>
<body onload="init()">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<?php
  $customers_query_raw = "SELECT c.customers_id, c.customers_email_address, c.customers_default_address_id, c.customers_firstname, c.customers_lastname, sum(o.order_total) as ordersum
                          FROM " . TABLE_CUSTOMERS . " c, " . TABLE_ORDERS . " o
                          WHERE c.customers_id = o.customers_id
                          GROUP BY c.customers_id"; 
  $customers = $db->Execute($customers_query_raw);
  $customer_list = array(); 
  while (!$customers->EOF) {
    $email = $customers->fields['customers_email_address']; 
    $customer_list[$email] = array(
       'customers_id' => $customers->fields['customers_id'], 
       'customers_name' => $customers->fields['customers_firstname'] . " " . $customers->fields[    'customers_lastname'],
       'customers_default_address_id' => $customers->fields['customers_default_address_id'],
       'customers_email_address' => $customers->fields['customers_email_address'],
       'ordersum' => $customers->fields['ordersum']); 
    $customers->MoveNext();
  }


  // Now handle direct deposits 
  $direct_query_raw = "SELECT email_address, name, sum(amount) as ordersum 
                          FROM " . TABLE_DIRECT_DEPOSIT . " 
                          GROUP BY email_address";
  $direct_query = $db->Execute($direct_query_raw); 
  while(!$direct_query->EOF) {
     $email = $direct_query->fields['email_address']; 
     if (isset($customer_list[$email])) {
        $customer_list[$email]['ordersum'] += $direct_query->fields['ordersum']; 
     } else {
        $customer_list[$email] = array(
            'ordersum' => $direct_query->fields['amount'],
            'customers_name' => $direct_query->fields['name'],
            'customers_email_address' => $direct_query->fields['email_address'],
            'ordersum' => $direct_query->fields['ordersum']); 
     }
     $direct_query->MoveNext(); 
  }

  // Now handle pre-store payments 
  $direct_query_raw = "SELECT email_address, name, sum(amount) as ordersum 
                          FROM " . TABLE_PRE_STORE. " 
                          GROUP BY email_address";
  $direct_query = $db->Execute($direct_query_raw); 
  while(!$direct_query->EOF) {
     $email = $direct_query->fields['email_address']; 
     if (isset($customer_list[$email])) {
        $customer_list[$email]['ordersum'] += $direct_query->fields['ordersum']; 
     } else {
        $customer_list[$email] = array(
            'ordersum' => $direct_query->fields['amount'],
            'customers_name' => $direct_query->fields['name'],
            'customers_email_address' => $direct_query->fields['email_address'],
            'ordersum' => $direct_query->fields['ordersum']); 
     }
     $direct_query->MoveNext(); 
  }

  // for emails from pre_store
  if (isset($emails_to_map)) { 
     foreach ($emails_to_map as $map) {
        $old = $map[0]; 
        $new = $map[1]; 
        $ordersum = $customer_list[$old]['ordersum']; 
        unset($customer_list[$old]); 
        $customer_list[$new]['ordersum'] += $ordersum; 
     }
  }

  // Last Step: 
  // handle masters 
  $subs_query_raw = "SELECT customers_id, customers_email_address, master_account  
                          FROM " . TABLE_CUSTOMERS . "  
                          WHERE master_account != 0"; 
  $subs = $db->Execute($subs_query_raw);
  while (!$subs->EOF) { 
     $email = $subs->fields['customers_email_address']; 
     $master = $subs->fields['master_account']; 
     $ordersum = $customer_list[$email]['ordersum']; 
     if (isset($customer_list[$email])) { 
        unset($customer_list[$email]); 
        $real_email_query = $db->Execute("SELECT customers_email_address FROM " . TABLE_CUSTOMERS . " WHERE customers_id = " . (int)$master); 
        $new_email = $real_email_query->fields['customers_email_address']; 
        $customer_list[$new_email]['ordersum'] += $ordersum; 
     }
     $subs->MoveNext(); 
  } 

  // Remove me 
  unset($customer_list['scottcwilson@gmail.com']); 
  $total_sales = 0; 
  foreach ($customer_list as $customer) { 
     $total_sales += $customer['ordersum']; 
  }
?>
<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td align="right"><span class="pageHeading"><?php echo TOTAL_SALES;?></span><span><?php echo $currencies->format($total_sales);?></span></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_RANK; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_NUMBER; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMERS; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_EMAIL; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_PURCHASED; ?>&nbsp;</td>
              </tr>
<?php
  // Now sort the data
  usort($customer_list, "total_sort"); 
  $rank = 1; 
  foreach ($customer_list as $customer) { 
      if (isset($customer['customers_id'])) { 
?>
              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?php echo zen_href_link(FILENAME_CUSTOMERS, 'cID=' . $customer['customers_id'], 'NONSSL'); ?>'">
                <td class="dataTableContent" align="left"><?php echo $rank; ?>&nbsp;&nbsp;</td>
                <td class="dataTableContent" align="left"><?php echo $customer['customers_id']; ?>&nbsp;&nbsp;</td>
                <td class="dataTableContent"><?php echo '<a href="' . zen_href_link(FILENAME_CUSTOMERS, 'cID=' . $customer['customers_id'], 'NONSSL') . '">' . $customer['customers_name'] . '</a>';?>
<?php
      } else {
?>
              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
                <td class="dataTableContent" align="left"><?php echo $rank; ?>&nbsp;&nbsp;</td>
                <td class="dataTableContent" align="left"><?php echo "N/A"; ?>&nbsp;&nbsp;</td>
                <td class="dataTableContent"><?php echo $customer['customers_name'];?>
<?php
      }
                 $company = get_customers_company($customer['customers_default_address_id']); 
                 if (!empty($company)) { 
                   echo '&nbsp;&nbsp;' . '(' . $company .  ')';
                }
                echo '</td>'; ?>
                <td class="dataTableContent" align="left"><?php echo $customer['customers_email_address']; ?>&nbsp;&nbsp;</td>
                <td class="dataTableContent" align="right"><?php echo $currencies->format($customer['ordersum']); ?>&nbsp;</td>
              </tr>
<?php
    $customers->MoveNext();
    $rank++; 
  }
?>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
<?php
function get_customers_company($default_address) { 
   global $db; 
   if (ACCOUNT_COMPANY != 'true') return ''; 
   $company_query = "SELECT entry_company FROM " . TABLE_ADDRESS_BOOK . " WHERE address_book_id = :default_address:"; 
   $company_query = $db->bindVars($company_query, ":default_address:", $default_address, 'integer'); 
   $company = $db->Execute($company_query); 
   return $company->fields['entry_company']; 
}
