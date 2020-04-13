<?php
date_default_timezone_set("Asia/Kolkata");
$curl = curl_init();

curl_setopt_array($curl, array(
	CURLOPT_URL => "https://covid19india.p.rapidapi.com/getIndiaStateData",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => array(
		"x-rapidapi-host: covid19india.p.rapidapi.com",
		"x-rapidapi-key: YOUR-API-KEY-FROM-RAPIDAPI"
	),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);
ob_start();
echo "Last updated on ".explode(" ->", file_get_contents('count.txt'))[0];
if ($err) {
	echo "cURL Error #:" . $err;
} else {
	?>
<table border="1" cellspacing="1" cellpadding="1" style="font-weight:bold; border-collapse: collapse; width: 100%;">
<thead>
	<tr>
	<th style="padding: 12px;"><strong>S. No.</strong></th>
	<th style="padding: 12px;"><strong>Name of State / UT</strong></th>
	<th style="padding: 12px;"><strong>Total Confirmed Cases</strong></th>                              
	<th style="padding: 12px;"><strong>Recovered</strong></th>
	<th style="padding: 12px;"><strong>Death</strong></th>
	</tr>
</thead>
<tbody>
<?php
	$x = json_decode($response, TRUE)['response'];
	$new_x = array();
	for($i=0;$i<32;$i++)
	    array_push($new_x, $x[$i]["confirmed"]);
	array_multisort($new_x, SORT_DESC, $x, SORT_DESC);
	$t_c = 0;
	$t_d = 0;
	$t_r = 0;
	for($i=0;$i<32;$i++) {
		$t_c += intval($x[$i]["confirmed"]);
		$t_d += intval($x[$i]["deaths"]);
		$t_r += intval($x[$i]["recovered"]);
	    if($x[$i]["name"] == "Telengana") {
			$inner = explode(" -> ", file_get_contents("count.txt"));
			$sentMail = $inner[2];
			if($sentMail == "1") {
				$sentMail = true;
			} else {
				$sentMail = false;
			}
	        $count = explode("|", $inner[1]);
	        $cnf = $count[0];
	        $deaths = $count[1];
	        $c = $x[$i]["confirmed"];
			$d = $x[$i]["deaths"];
			$r = $x[$i]["recovered"];
	    }
	    ?>
	    <tr>
    	<td style="text-align: center; padding: 12px;"><?php echo $i+1; ?></td>
    	<td style="text-align: center; padding: 12px;"><?php echo $x[$i]["name"]; ?></td>
    	<td style="text-align: center; padding: 12px;"><?php echo $x[$i]["confirmed"]; ?></td>
    	<td style="text-align: center; padding: 12px;"><?php echo $x[$i]["recovered"]; ?></td>
    	<td style="text-align: center; padding: 12px;"><?php echo $x[$i]["deaths"]; ?></td>
    	</tr>
	  <?php
	}
	?>
	<tr>
    	<td style="text-align: center; padding: 12px;">Total</td>
    	<td style="text-align: center; padding: 12px;">India</td>
    	<td style="text-align: center; padding: 12px;"><?php echo $t_c; ?></td>
    	<td style="text-align: center; padding: 12px;"><?php echo $t_r; ?></td>
    	<td style="text-align: center; padding: 12px;"><?php echo $t_d; ?></td>
    </tr>
</tbody>
</table>
<?php
		if((!$sentMail) && ((intval($c) - intval($count[0]) > 100) || (intval($d) - intval($count[1]) > 10))) {
			// Send mail
			$html = ob_get_contents();
			include 'mail_tut.php';

			$cURLConnection = curl_init();
$msg = "Cases in Telangana:
Total Cases: $c
Total Deaths: $d
Total Recovered: $r";
			$msg = urlencode($msg);
			// Provide your chat id in URL below
			curl_setopt($cURLConnection, CURLOPT_URL, 'https://api.telegram.org/bot1180556166:AAHc4n3Q9Pnxpj0e8D0y2e0_RQ-MQtm-6aE/sendMessage?chat_id=YOUR-CHAT-ID&text='.$msg);
			curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

			$phoneList = curl_exec($cURLConnection);
			curl_close($cURLConnection);

			ob_end_clean();

			// Change sentMail
			$ss = file_get_contents('count.txt');
			$ss[strlen($ss)-1] = "1";
			file_put_contents('count.txt', $ss);
		}
		echo $html;
		$r = $x[$i]["recovered"];
		
		// Check if it is a new day
		// If yes, update count.txt
		if(time() - strtotime($inner[0]) > 86400) {
			$f = file_get_contents('count.txt');
			$f .= "\n";
			$f .= date("jS F Y h:i:s A")." -> $c|$d|$r -> 0";
			file_put_contents('count.txt', $f);
		}
}

?>