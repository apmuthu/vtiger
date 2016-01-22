<?php
/*
// Program       : Pop-Up Display of vTigerCRM Contact page triggered by Asterisk on Call Pickup
// Author        : Ap.Muthu <apmuthu@usa.net>
// Version       : 1.2
// Release Date  : 2016-01-19
// Last Updated  : 2016-01-22
// Example Usage : http://DOMAIN.TLD/PATH.TO.VTIGERCRM/popup_clid.php?extn=1000
// URL GET var   : extn
*/

define("REFRESH_SECS", 10);
define("SHOW_LINKS", true);
define("RCV_TAG", 'Local'); // Receiving IP Phone prefix => SIP, Local
define("INC_TAG", 'DAHDI'); // Incoming line PSTN, Voice Gateway prefix => DAHDI

$content = '<html>
    <head>
	<meta http-equiv="refresh" content="' . REFRESH_SECS . '">
    </head>
    <body>
';
$endpage = '</body></html>';

//Capturing the extension safely
$extn = isset($_REQUEST['extn']) ? $_REQUEST['extn']+0 : 0;
if ($extn == 0) die($content . $endpage);

$content .= "<p>Extension No: <b>$extn</b></p>";

$callerid = get_callerid($extn);
$callerid = trim($callerid);
if (strlen($callerid) == 0) die($content . $endpage);

$content .= "<p>Caller ID: <b>$callerid</b></p>";

require_once('vtigerversion.php');
require_once('config.inc.php');
require_once('include/utils/utils.php');

// true if vTigerCRM version is 6.x and false if 5.x - tested on versions 6.3 and 5.2.1
$vtiger6 = (substr($vtiger_current_version, 0, 1) == '6');

$contactid = getContactId($callerid);
$count = count($contactid);
$contactid1 =  ($count == 1) ? $contactid[0]['contactid'] : false;

if (SHOW_LINKS) {
    if ($count == 0) {
      if (!$vtiger6)
        $content .= '<p><a href="index.php?module=Contacts&action=EditView&return_action=DetailView" target="blank">New Caller</a></p>';
      else
        $content .= '<p><a href="index.php?module=Contacts&view=Edit" target="blank">New Caller</a></p>';
    } elseif ($count == 1) {
        $content .= '<a href="' . popup_link($contactid1) . '" target="blank">Contact ID is ' . $contactid1 . '</p>';
    } else {
        $content .= '<p>' . list_contacts($contactid) . '</p>';
    }
}

echo $content . $endpage;



// Functions
// =========

function get_callerid($extn, $delim="!") {
	$cmd = 'asterisk -rx"core show channels concise" | grep ^' . INC_TAG . ' | grep "' . RCV_TAG . '/' . $extn . '"';
	$a = shell_exec($cmd);
	$b = explode($delim, $a);
	return $b[7];
}

function popup_link($contactid) {
    global $vtiger6;
	if ($contactid > 0) {
	  if (!$vtiger6)
		return 'index.php?module=Contacts&action=DetailView&record=' . $contactid;
      else
        return 'index.php?module=Contacts&view=Detail&record=' . $contactid . '&mode=showDetailViewByMode&requestMode=full';
    }
}

function getContactId($callerid) {
	global $log;

	$log->debug("Entering getContactId(".$callerid.") method ...");
	$log->info("in getContactId ".$callerid);

	global $adb;
	$contactid = Array();
	if($callerid != ''){
		$recordid_sql = "SELECT * FROM vtiger_contactdetails WHERE phone = ? OR mobile = ?";
// 		$result = $adb->pquery($recordid_sql, array($callerid));
// 		$contactid = $adb->query_result($result,0,"contactid");
		$result = $adb->pquery($recordid_sql, array($callerid, $callerid));
		if($result) 
			while($nameArray = $adb->fetch_array($result))
				if(!empty($nameArray)) $contactid[] = $nameArray;
	}
	$log->debug("Exiting getContactId method ...");
	return $contactid;
}

function list_contacts($contactid) {
	$output = '';
	if (count($contactid) > 1) {
		$output  = '<table border=1>';
		$output .= '<tr><th>ContactID</th><th>Name</th></tr>';

		foreach ($contactid as $contact) {
			$output .= '<tr>';
			$output .= '<td>'.$contact['contactid'].'</td>';
			$ContactName = (($contact['salutation'] <> '--None--') ? $contact['salutation'] : '') .' '.$contact['firstname'].' '.$contact['lastname'];
			$output .= '<td><a href="'.popup_link($contact['contactid']).'" target="blank">' . $ContactName . '</a></td>';
			$output .= '</tr>';
		}

		$output .= '</table>';
	}
	return $output;
}

?>
