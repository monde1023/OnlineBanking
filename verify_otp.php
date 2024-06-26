<?php
	session_start();
	if (isset($_SESSION['empLogin'])) {
		header('location:homePageEmp.php');
	}
	include 'connection.php';
	include 'Globe\Connect\Sms.php';
	use Globe\Connect\Sms;
?>
<!--#####-->
<?php 
	if (isset($_SESSION['emp'])) { //check if the user who logs in is a bank personnel
		if (isset($_REQUEST['submitOTP'])) {
			$otpTxt = mysqli_real_escape_string($link, $_REQUEST['otpTxt']);//otp entered by the user into the text box
			$otpGen = $_SESSION['otp'];//otp generated by mt_rand() function
			$timeSent = $_SESSION['timeSent'];
			##get current time
			$query4 = "SELECT ADDTIME('$timeSent', '00:10:00') > NOW()";
			$result4 = mysqli_query($link, $query4) or die(mysqli_error($link));
			$rows4 = mysqli_fetch_array($result4);
			if ($otpTxt == $otpGen && $rows4[0] == 1) {
				$_SESSION['empLogin'] = 1;
				$_SESSION['emp'] = 0;
				echo "<script>window.location = 'homePageEmp.php';</script>";
				exit();
			}
			elseif ($rows4[0] == 0) { //check if time now minus timeSent is less than or equal to 10 minutes
				echo "<script>alert('Your OTP has expired!');";
			 	echo "window.location = 'empLogin.php';</script>";
		 	}
			else{
		 		echo "You entered an invalid code. Please wait for the message sent to your registered mobile number.";
			}
		}
		#####
		if (!isset($_REQUEST['submitOTP'])) {
			$query = "SELECT SHORTCODE_GLOBE 
					  FROM APP_CREDENTIALS
					  WHERE ID = 1";
			$result = mysqli_query($link, $query) or die(mysqli_error($link));
			$rows = mysqli_fetch_array($result);
			$senderAddress = substr($rows[0], 4) ;
			#####
			$user = $_SESSION['empID'];
			$pass = $_SESSION['password'];
			$query2 = "SELECT FULLNAME
							 ,ACCESS_TOKEN
							 ,SUBSCRIBER_NUM
					   FROM SUBSCRIBER_INFO
					   WHERE EMP_ID = (SELECT ID FROM PERSONNEL
								   WHERE EMAIL = '$user' AND PASSWORD = '$pass')";
			$result2 = mysqli_query($link, $query2) or die(mysqli_error($link));
			$rows2 = mysqli_fetch_array($result2);
			$accessToken = trim($rows2[1]);
			$clientCorrelator = mt_rand(1000,9999);
			$mobile = $rows2[2];
			$_SESSION['otp'] = mt_rand(10000,99999);//this generates the otp
			$msg = "Hi ".$rows2[0]."! Your otp is ".$_SESSION['otp'].".\nThis code is valid for 10 minutes only.";
			#####
			$sms = new Sms("$senderAddress", "$accessToken");
			$sms->setReceiverAddress("$mobile");
			$sms->setMessage("$msg");
			$sms->setClientCorrelator("$clientCorrelator");
			$sms->sendMessage();
			#####
			$query3 = "SELECT CURRENT_TIMESTAMP";
			$result3 = mysqli_query($link, $query3) or die(mysqli_error($link));
			$rows3 = mysqli_fetch_array($result3);
			$_SESSION['timeSent'] = $rows3[0];
		}
	}
?>
<!--#####-->
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>OTP | Verification</title>
	<link rel="stylesheet" type="text/css" href="design.css">
</head>
<?php include 'topBar.php' ?>
<div class="data_newAcc">
	<div style="height: 300px; width: 300px;">
		<form action="" method="POST">
			<table align="center" style="border-radius: 5px; margin-left: 500px; margin-top: 200px; border: 1px solid #363636;">
				<tr>
					<td colspan="2">Please enter your One-Time Password.</td>
				</tr>
				<tr>
					<td>
						<input type="text" name="otpTxt" required="">
					</td>
					<td>
						<input type="submit" name="submitOTP" value="Verify" class="addAcc_button">
					</td>
				</tr>
			</table>
		</form>
		<br><br><br><br><br>
		<table align="center" style="border-radius: 5px; margin-left: 500px; width: 100%;">
			<tr>
				<td>Didn't get the code?
					<input type="button" name="resend" value="Resend" class="addAcc_button" style="font-size: 12px; font-weight: bold;" onclick="reload()">
				</td>
			</tr>
		</table>
	</div>
</div>
<?php include 'bot.php' ?>
</body>
</html>
<script type="text/javascript">
	function reload() {
	  	window.location.reload(true);
	}
</script>
