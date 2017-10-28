<?php
session_start();

require_once('config.php');
require_once('lib/container.php');
require_once('lib/dal.php');
require_once('lib/helper_functions.php');

$context = null;
if (isset($_SESSION['context']) && $_SESSION['context'] != null) {
    $context = unserialize($_SESSION['context']);
} else {
    $context = new Container();
    $context->loggedIn = false;
    $context->hasBooked = false;
}

// Check if context has been loaded
if ($context == null) {
    $_SESSION['context'] = null;
    exit('Failed to load context, try and reload the page.');
}

$database = new DAL($config);

if (isset($_GET['action']) and $_GET['action'] == 'getseat') {
    $data = $database->Query('getSeatAndRow', array($_GET['x'],$_GET['y']));
    if ($config->displayRowAsLetter) {
        echo(json_encode(array($data[0]['seat'], chr($data[0]['row'] + 64))));
    } else {
        echo(json_encode(array($data[0]['seat'], $data[0]['row'])));
    }
    exit;
}

if (isset($_GET['action']) and $_GET['action'] == 'getholdername') {
    $data = $database->Query('getTicketHolderName', array($_GET['x'],$_GET['y']));
    echo(json_encode($data[0]['holder_name']));
    exit;
}

if (CheckArrayKeys($_GET, array('action','x','y')) and $_GET['action'] == 'bookseat') {
    $database->Query('bookSeat', array($_GET['x'], $_GET['y'], $context->ticket['id'], date('Y-m-d H:i:s')));
    $data = $database->Query('getSeat', array($_GET['x'], $_GET['y']));
    if ($data[0]['ticket'] == $context->ticket['id']) {
        echo(json_encode('success'));
    } else {
        echo(json_encode('failed')); 
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['seat_number']) && isset($_POST['seat_row'])) {
        $data = $database->Query('getSeat', array($_POST['seat_number'], $_POST['seat_row']));
        if ($data[0]['ticket'] == null) {
            $database->Query('bookSeat', array($_POST['seat_number'], $_POST['seat_row'], $context->ticket['id'], date('Y-m-d H:i:s')));
        } else {
            
        }
    } elseif (isset($_POST['submitlogin'])) {
        $data = $database->Query('getTicket', array($_POST['code'], $_POST['password']));
        if ($data != null) {
            $context->ticket = $data[0];
            $context->loggedIn = true;
        }
    } elseif (isset($_POST['submitlogout'])) {
        $context->loggedIn = false;
        $context->ticket = null;
        $context->hasBooked = false;
    } elseif (isset($_POST['submitunbook'])) {
        $database->Query('unbookSeat', array($context->ticket['id']));
        $context->hasBooked = false;
    }
}

$inlineCSS = '';
$style = array();
$data = $database->Query('getFloortypes');
foreach ($data as $row) {
    $style[$row['id']] = array($row['codename'], $row['displayname'], $row['color']);
    $border = '';
    if ($row['border'] == 1) {
        $border = 'border: 1px solid #000000;';
    }
    if ($row['color'] != null ) {
        $inlineCSS .= '.'.$row['codename'].' { background: '.$row['color'].'; '.$border.' }'."\n";
    }
    if ($context->loggedIn == true && $row['hovercolor'] != null) {
        $inlineCSS .= '.'.$row['codename'].':hover { background: '.$row['hovercolor'].'; '.$border.' }'."\n";
    }
}

$data = $database->Query('getFloorplan', array(), 'ENUM');
$floorplan = array();
$lastrow = 0;
$currentrow = array();
foreach ($data as $row) {
    if ($row[1] != $lastrow) {
        $lastrow = $row[1];
        $floorplan[] = $currentrow;
        $currentrow = array();
    }

    $type = $row[2];
    if ($context->loggedIn == true) {
        if ($context->ticket['id'] == $row[3]) {
            $type = 8;
            $context->hasBooked = true;
        }
    }
    $currentrow[] = $type;
}
$floorplan[] = $currentrow;

$inlineCSS .= '.seat { border: 1px solid #000000; }'."\n";
?>
/* SEATMAP SYSTEM
 * LAN PARTY SEATMAP SYSTEM MADE BY PANDAWITHBANDANA AND ROGST
<html>
<head>
    <meta charset="utf8">
    <title>Seatmap - by PandaWithBandana and Rogst</title> 
    <link rel="stylesheet" type="text/css" href="theme2.css"/>
    <script src="jquery-2.0.3.min.js"></script>
    <script src="index4.js"></script>
    <style>
<?php echo($inlineCSS); ?>
    </style>
</head>
<body>
    <table>
        <tr>
            <td width="75%">
                <table class="floorplan">
                <?php foreach($floorplan as $row): ?>
                    <tr>
                    <?php foreach($row as $col => $value): ?>
                        <?php if ($context->loggedIn == true && $context->hasBooked == false && $value == 6): ?>
                        <td class='seat <?php echo($style[$value][0]); ?>' onclick='select_seat(this);'></td>
                        <?php elseif ($context->loggedIn == true && $value == 7): ?>
                        <td class='seat <?php echo($style[$value][0]); ?>' onclick='view_seat(this);'></td>
                        <?php else: ?>
                        <td class='<?php echo($style[$value][0]); ?>'></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </table>
            </td>
            <td>
                <table class="sidebar">
                    <tr>
                        <td height="100%" valign="top">
                            <div id="login_view">
                                <?php if ($context->loggedIn == true): ?>
                                <img src="/img/logo.png" width="162" height="53" class="navbar-logo-img" alt="">
								<p><b>Welcome <?php echo($context->ticket['holder_name']); ?></b></p>
                                <form id="login_form" method="POST">
								<style>
								.button::-moz-focus-inner{
  border: 0;
  padding: 0;
}

.button{
  display: inline-block;
  *display: inline;
  zoom: 1;
  padding: 6px 20px;
  margin: 0;
  cursor: pointer;
  border: 1px solid #bbb;
  overflow: visible;
  font: bold 13px arial, helvetica, sans-serif;
  text-decoration: none;
  white-space: nowrap;
  color: #555;
  
  background-color: #ddd;
  background-image: -webkit-gradient(linear, left top, left bottom, from(rgba(255,255,255,1)), to(rgba(255,255,255,0)));
  background-image: -webkit-linear-gradient(top, rgba(255,255,255,1), rgba(255,255,255,0));
  background-image: -moz-linear-gradient(top, rgba(255,255,255,1), rgba(255,255,255,0));
  background-image: -ms-linear-gradient(top, rgba(255,255,255,1), rgba(255,255,255,0));
  background-image: -o-linear-gradient(top, rgba(255,255,255,1), rgba(255,255,255,0));
  background-image: linear-gradient(top, rgba(255,255,255,1), rgba(255,255,255,0));
  
  -webkit-transition: background-color .2s ease-out;
  -moz-transition: background-color .2s ease-out;
  -ms-transition: background-color .2s ease-out;
  -o-transition: background-color .2s ease-out;
  transition: background-color .2s ease-out;
  background-clip: padding-box; /* Fix bleeding */
  -moz-border-radius: 3px;
  -webkit-border-radius: 3px;
  border-radius: 3px;
  -moz-box-shadow: 0 1px 0 rgba(0, 0, 0, .3), 0 2px 2px -1px rgba(0, 0, 0, .5), 0 1px 0 rgba(255, 255, 255, .3) inset;
  -webkit-box-shadow: 0 1px 0 rgba(0, 0, 0, .3), 0 2px 2px -1px rgba(0, 0, 0, .5), 0 1px 0 rgba(255, 255, 255, .3) inset;
  box-shadow: 0 1px 0 rgba(0, 0, 0, .3), 0 2px 2px -1px rgba(0, 0, 0, .5), 0 1px 0 rgba(255, 255, 255, .3) inset;
  text-shadow: 0 1px 0 rgba(255,255,255, .9);
  
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -khtml-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

.button:hover{
  background-color: #eee;
  color: #555;
}

.button:active{
  background: #e9e9e9;
  position: relative;
  top: 1px;
  text-shadow: none;
  -moz-box-shadow: 0 1px 1px rgba(0, 0, 0, .3) inset;
  -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .3) inset;
  box-shadow: 0 1px 1px rgba(0, 0, 0, .3) inset;
}

.button[disabled], .button[disabled]:hover, .button[disabled]:active{
  border-color: #eaeaea;
  background: #fafafa;
  cursor: default;
  position: static;
  color: #999;
  /* Usually, !important should be avoided but here it's really needed :) */
  -moz-box-shadow: none !important;
  -webkit-box-shadow: none !important;
  box-shadow: none !important;
  text-shadow: none !important;
}

/* Smaller buttons styles */

.button.small{
  padding: 4px 12px;
}

/* Larger buttons styles */

.button.large{
  padding: 12px 30px;
  text-transform: uppercase;
}

.button.large:active{
  top: 2px;
}

/* Colored buttons styles */

.button.green, .button.red, .button.blue {
  color: #fff;
  text-shadow: 0 1px 0 rgba(0,0,0,.2);
  
  background-image: -webkit-gradient(linear, left top, left bottom, from(rgba(255,255,255,.3)), to(rgba(255,255,255,0)));
  background-image: -webkit-linear-gradient(top, rgba(255,255,255,.3), rgba(255,255,255,0));
  background-image: -moz-linear-gradient(top, rgba(255,255,255,.3), rgba(255,255,255,0));
  background-image: -ms-linear-gradient(top, rgba(255,255,255,.3), rgba(255,255,255,0));
  background-image: -o-linear-gradient(top, rgba(255,255,255,.3), rgba(255,255,255,0));
  background-image: linear-gradient(top, rgba(255,255,255,.3), rgba(255,255,255,0));
}

/* */

.button.green{
  background-color: #57a957;
  border-color: #57a957;
}

.button.green:hover{
  background-color: #62c462;
}

.button.green:active{
  background: #57a957;
}

/* */

.button.red{
  background-color: #ca3535;
  border-color: #c43c35;
}

.button.red:hover{
  background-color: #ee5f5b;
}

.button.red:active{
  background: #c43c35;
}

/* */

.button.blue{
  background-color: #269CE9;
  border-color: #269CE9;
}

.button.blue:hover{
  background-color: #70B9E8;
}

.button.blue:active{
  background: #269CE9;
}

/* */

.green[disabled], .green[disabled]:hover, .green[disabled]:active{
  border-color: #57A957;
  background: #57A957;
  color: #D2FFD2;
}

.red[disabled], .red[disabled]:hover, .red[disabled]:active{
  border-color: #C43C35;
  background: #C43C35;
  color: #FFD3D3;
}

.blue[disabled], .blue[disabled]:hover, .blue[disabled]:active{
  border-color: #269CE9;
  background: #269CE9;
  color: #93D5FF;
}

/* Group buttons */

.button-group,
.button-group li{
  display: inline-block;
  *display: inline;
  zoom: 1;
}

.button-group{
  font-size: 0; /* Inline block elements gap - fix */
  margin: 0;
  padding: 0;
  background: rgba(0, 0, 0, .1);
  border-bottom: 1px solid rgba(0, 0, 0, .1);
  padding: 7px;
  -moz-border-radius: 7px;
  -webkit-border-radius: 7px;
  border-radius: 7px;
}

.button-group li{
  margin-right: -1px; /* Overlap each right button border */
}

.button-group .button{
  font-size: 13px; /* Set the font size, different from inherited 0 */
  -moz-border-radius: 0;
  -webkit-border-radius: 0;
  border-radius: 0;
}

.button-group .button:active{
  -moz-box-shadow: 0 0 1px rgba(0, 0, 0, .2) inset, 5px 0 5px -3px rgba(0, 0, 0, .2) inset, -5px 0 5px -3px rgba(0, 0, 0, .2) inset;
  -webkit-box-shadow: 0 0 1px rgba(0, 0, 0, .2) inset, 5px 0 5px -3px rgba(0, 0, 0, .2) inset, -5px 0 5px -3px rgba(0, 0, 0, .2) inset;
  box-shadow: 0 0 1px rgba(0, 0, 0, .2) inset, 5px 0 5px -3px rgba(0, 0, 0, .2) inset, -5px 0 5px -3px rgba(0, 0, 0, .2) inset;
}

.button-group li:first-child .button{
  -moz-border-radius: 3px 0 0 3px;
  -webkit-border-radius: 3px 0 0 3px;
  border-radius: 3px 0 0 3px;
}

.button-group li:first-child .button:active{
  -moz-box-shadow: 0 0 1px rgba(0, 0, 0, .2) inset, -5px 0 5px -3px rgba(0, 0, 0, .2) inset;
  -webkit-box-shadow: 0 0 1px rgba(0, 0, 0, .2) inset, -5px 0 5px -3px rgba(0, 0, 0, .2) inset;
  box-shadow: 0 0 1px rgba(0, 0, 0, .2) inset, -5px 0 5px -3px rgba(0, 0, 0, .2) inset;
}

.button-group li:last-child .button{
  -moz-border-radius: 0 3px 3px 0;
  -webkit-border-radius: 0 3px 3px 0;
  border-radius: 0 3px 3px 0;
}

.button-group li:last-child .button:active{
  -moz-box-shadow: 0 0 1px rgba(0, 0, 0, .2) inset, 5px 0 5px -3px rgba(0, 0, 0, .2) inset;
  -webkit-box-shadow: 0 0 1px rgba(0, 0, 0, .2) inset, 5px 0 5px -3px rgba(0, 0, 0, .2) inset;
  box-shadow: 0 0 1px rgba(0, 0, 0, .2) inset, 5px 0 5px -3px rgba(0, 0, 0, .2) inset;
}
								</style>
								<div class="centered">
                                <input class="small button" type="submit" name="submitlogout" value="Log out">
                                    <?php if ($context->hasBooked == true): ?>
									    <p>Click on remove my seat to remove your seat and select a new one</p>
                                          <link href='https://fonts.googleapis.com/css?family=Droid+Sans+Mono'
        rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <style>
    .btn {
  display: block;
  width: 180px;
  height: 50px;
  font-family: 'Droid Sans Mono', sans-serif;
  font-size: 16px;
  color: #fff;
  background-color: #69A551;
  border-radius: 5px;
  box-shadow: 0px 3px 0px #3D8C72;
  text-align: center;
  line-height: 50px;
  text-decoration: none;
  margin: 50px auto 0;
  font-weight: bold;
  -webkit-animation: btnWiggle 5s infinite;
  -moz-animation: btnWiggle 5s infinite;
  -o-animation: btnWiggle 5s infinite;
  animation: btnWiggle 5s infinite;
}

.subtext {
  font-family: 'Droid Sans Mono', sans-serif;
  font-size: 14px;
  letter-spacing: .05em;
  text-align: center;
}

/* animation */
@-webkit-keyframes btnWiggle {
	0% {-webkit-transform: rotate(0deg);}
	2% {-webkit-transform: rotate(-1deg);}
	3.5% {-webkit-transform: rotate(1deg);}
	5% {-webkit-transform: rotate(0deg);}
	100% {-webkit-transform: rotate(0deg);}
}
@-o-keyframes btnWiggle {
	0% {-webkit-transform: rotate(0deg);}
	2% {-webkit-transform: rotate(-1deg);}
	3.5% {-webkit-transform: rotate(1deg);}
	5% {-webkit-transform: rotate(0deg);}
	100% {-webkit-transform: rotate(0deg);}
}
@keyframes btnWiggle {
	0% {-webkit-transform: rotate(0deg);}
	2% {-webkit-transform: rotate(-1deg);}
	3.5% {-webkit-transform: rotate(1deg);}
	5% {-webkit-transform: rotate(0deg);}
	100% {-webkit-transform: rotate(0deg);}
}
  </style>
<input class="btn" type="submit" name="submitunbook" value="Remove my seat">
                                    <?php endif; ?>
                                </form>
								<?php else: ?>
								  <img src="/img/logo.png" width="162" height="53" class="navbar-logo-img" alt="">
								  <p>&nbsp;</p>
								  <p>You're seeing this page because you've recently signed out, you typed the wrong username or password or you haven't logged in yet.</p>
								 								 <link href='https://fonts.googleapis.com/css?family=Droid+Sans+Mono'
        rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <style>
    .btn {
  display: block;
  width: 180px;
  height: 50px;
  font-family: 'Droid Sans Mono', sans-serif;
  font-size: 16px;
  color: #fff;
  background-color: #69A551;
  border-radius: 5px;
  box-shadow: 0px 3px 0px #3D8C72;
  text-align: center;
  line-height: 50px;
  text-decoration: none;
  margin: 50px auto 0;
  font-weight: bold;
  -webkit-animation: btnWiggle 5s infinite;
  -moz-animation: btnWiggle 5s infinite;
  -o-animation: btnWiggle 5s infinite;
  animation: btnWiggle 5s infinite;
}

.subtext {
  font-family: 'Droid Sans Mono', sans-serif;
  font-size: 14px;
  letter-spacing: .05em;
  text-align: center;
}

/* animation */
@-webkit-keyframes btnWiggle {
	0% {-webkit-transform: rotate(0deg);}
	2% {-webkit-transform: rotate(-1deg);}
	3.5% {-webkit-transform: rotate(1deg);}
	5% {-webkit-transform: rotate(0deg);}
	100% {-webkit-transform: rotate(0deg);}
}
@-o-keyframes btnWiggle {
	0% {-webkit-transform: rotate(0deg);}
	2% {-webkit-transform: rotate(-1deg);}
	3.5% {-webkit-transform: rotate(1deg);}
	5% {-webkit-transform: rotate(0deg);}
	100% {-webkit-transform: rotate(0deg);}
}
@keyframes btnWiggle {
	0% {-webkit-transform: rotate(0deg);}
	2% {-webkit-transform: rotate(-1deg);}
	3.5% {-webkit-transform: rotate(1deg);}
	5% {-webkit-transform: rotate(0deg);}
	100% {-webkit-transform: rotate(0deg);}
}
  </style>
<a class="btn" href="/index.php"><i class="fa fa-sign-in" aria-hidden="true"></i> Login</a>
								 <link href='https://fonts.googleapis.com/css?family=Droid+Sans+Mono'
        rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <style>
    .btn {
  display: block;
  width: 180px;
  height: 50px;
  font-family: 'Droid Sans Mono', sans-serif;
  font-size: 16px;
  color: #fff;
  background-color: #69A551;
  border-radius: 5px;
  box-shadow: 0px 3px 0px #3D8C72;
  text-align: center;
  line-height: 50px;
  text-decoration: none;
  margin: 50px auto 0;
  font-weight: bold;
  -webkit-animation: btnWiggle 5s infinite;
  -moz-animation: btnWiggle 5s infinite;
  -o-animation: btnWiggle 5s infinite;
  animation: btnWiggle 5s infinite;
}

.subtext {
  font-family: 'Droid Sans Mono', sans-serif;
  font-size: 14px;
  letter-spacing: .05em;
  text-align: center;
}

/* animation */
@-webkit-keyframes btnWiggle {
	0% {-webkit-transform: rotate(0deg);}
	2% {-webkit-transform: rotate(-1deg);}
	3.5% {-webkit-transform: rotate(1deg);}
	5% {-webkit-transform: rotate(0deg);}
	100% {-webkit-transform: rotate(0deg);}
}
@-o-keyframes btnWiggle {
	0% {-webkit-transform: rotate(0deg);}
	2% {-webkit-transform: rotate(-1deg);}
	3.5% {-webkit-transform: rotate(1deg);}
	5% {-webkit-transform: rotate(0deg);}
	100% {-webkit-transform: rotate(0deg);}
}
@keyframes btnWiggle {
	0% {-webkit-transform: rotate(0deg);}
	2% {-webkit-transform: rotate(-1deg);}
	3.5% {-webkit-transform: rotate(1deg);}
	5% {-webkit-transform: rotate(0deg);}
	100% {-webkit-transform: rotate(0deg);}
}
  </style>
<a class="btn" href="http://linktoyourhomepagehere.com"><i class="fa fa-arrow-left" aria-hidden="true"></i> Homepage</a>
                                <?php endif; ?>
                            </div>
                            <div id="book_view">
                                <span id="selected_seat_info"></span>
                                <form id="selected_seat_form" method="POST">
                                <input id="seat_number" type="hidden" name="seat_number" value="">
                                    <input id="seat_row" type="hidden" name="seat_row" value="">
                                    <input id="book_seat_btn" type="button" value="Reserver plass" onclick="book_selected_seat();">
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table>
                            <?php foreach( $style as $legend): ?>
                            <tr>
                                <?php if ($legend[2] != null): ?>
                                <td style='background: <?php echo($legend[2]); ?>;'>&nbsp;</td><td><?php echo($legend[1]); ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

<?php
$_SESSION['context'] = serialize($context);
?>
