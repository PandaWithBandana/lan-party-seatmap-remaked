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
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Seatmap Login - by PandaWithBandana and Rogst</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Varela+Round">
  <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  
      <link rel="stylesheet" href="css/style2.css">
	  <link rel="stylesheet" href="scss/style.scss">
	  <link rel="stylesheet" type="text/css" href="theme.css"/>
    <script src="jquery-2.0.3.min.js"></script>
    <script src="index.js"></script>
    <style>
<?php echo($inlineCSS); ?>
    </style>
	
</head>

<body>

<h1>Please use your login details you've got to login to our seatmap</h1>
<h3>If you haven't got a ticket yet you won't be able to view or book a seat at the seatmap</h3>
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
<a class="btn" href="/buyticket.php"><i class="fa fa-ticket" aria-hidden="true"></i> Buy ticket</a>
<p>&nbsp;</p>
<p>&nbsp;</p>
</div>

  <body class="align">

  <div class="grid">

    <div id="login">

      <h2><span class="fontawesome-lock"></span>Login</h2>

      <form id="login_form" action="index2.php" method="POST">

        <fieldset>

          <p><label for="text">Username</label></p>
          <p><input type="text" name="code"></p>

          <p><label for="password">Password</label></p>
          <p><input type="password" name="password"></p>

          <p><input type="submit" name="submitlogin" value="Login"></p>

        </fieldset>

      </form>

    </div> <!-- end login -->
  </div>

</body>
  
  
</body>
</html>
