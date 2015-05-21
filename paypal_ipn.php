<?php
require('config.php');

// STEP 1: read POST data
 
// Reading POSTed data directly from $_POST causes serialization issues with array data in the POST.
// Instead, read raw POST data from the input stream. 
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = array();
foreach ($raw_post_array as $keyval) {
  $keyval = explode ('=', $keyval);
  if (count($keyval) == 2)
     $myPost[$keyval[0]] = urldecode($keyval[1]);
}
// read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
$req = 'cmd=_notify-validate';
if(function_exists('get_magic_quotes_gpc')) {
   $get_magic_quotes_exists = true;
} 
foreach ($myPost as $key => $value) {        
   if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) { 
        $value = urlencode(stripslashes($value)); 
   } else {
        $value = urlencode($value);
   }
   $req .= "&$key=$value";
}
 
 
// STEP 2: POST IPN data back to PayPal to validate
 
$ch = curl_init(PAYPAL_URL);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
 
// In wamp-like environments that do not come bundled with root authority certificates,
// please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set 
// the directory path of the certificate as shown below:
// curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
if( !($res = curl_exec($ch)) ) {
    // error_log("Got " . curl_error($ch) . " when processing IPN data");
    curl_close($ch);
    exit;
}
curl_close($ch);
 
 
// STEP 3: Inspect IPN validation result and act accordingly
 
if (strcmp ($res, "VERIFIED") == 0)
{
    // The IPN is verified, process it:
    // check whether the payment_status is Completed
    // check that txn_id has not been previously processed
    // check that receiver_email is your Primary PayPal email
    // check that payment_amount/payment_currency are correct
    // process the notification
 
    $payment_status = $_POST['payment_status'];
    
    if ($payment_status == 'Completed')
    {
        $txn_id = $_POST['txn_id'];
        $reg_id = (int)$_POST['custom'];
        
        $db = new PDO(MYSQL_DSN, MYSQL_USER, MYSQL_PASS);
        $query = $db->prepare("UPDATE registration SET txn_id = :txn_id WHERE id = :reg_id");
        $query->bindParam(':txn_id', $txn_id);
        $query->bindParam(':reg_id', $reg_id);
        $query->execute();
        
        $query = $db->prepare("SELECT * FROM registration WHERE id = :reg_id");
        $query->bindParam(':reg_id', $reg_id);
        $result = $query->execute();
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $address = $row['email'];
        $city = $row['email'];
        $province = $row['email'];
        $telephone = $row['email'];
        $tshirt = $row['email'];
        
        require_once('MailChimp.php');
        $mc = new \Drewm\MailChimp(MAILCHIMP_API_KEY);
        $result = $mc->call('lists/subscribe', array(
            'id' => MAILCHIMP_LIST_ID,
            'email' => array('email'=>$email),
            'merge_vars' => array(
                'FNAME'=>$first_name, 
                'LNAME'=>$last_name, 
                'CTYPE'=>'Written', 
                'CDATE'=>date('Y-m-d'), 
                'COMMENTS'=>'Registered up on the Metro Bowl Reunion website.'
            ),
            'double_optin' => false,
            'send_welcome' => false
        ));
        
        require_once('lib/sendgrid-php/sendgrid-php.php');
        $sendgrid = new SendGrid(SENDGRID_API_KEY);
        $message = new SendGrid\Email();
        $message
            ->addTo('cmorris@ccistudios.com')
            ->setFrom('noreply@metrobowlreunion.com')
            ->setSubject('Registration for Metro Bowl Reunion')
            ->setText('First Name: '.$first_name.'\nLast Name: '.$last_name.'\nEmail: '.$email)
            ->setHtml("First Name: $first_name<br>Last Name: $last_name<br>Email: $email<br>Address: $address<br>City: $city<br>Province: $province<br>Telephone: $telephone<br>T-Shirt Size: $tshirt<br>Paypal Transaction ID: $txn_id")
        ;
        $sendgrid->send($message);
    }
}
else if (strcmp ($res, "INVALID") == 0)
{
    // IPN invalid, log for manual investigation
    echo "The response from IPN was: <b>" .$res ."</b>";
}
?>