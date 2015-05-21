<?php
//honeypot
if ($_POST['website']) exit;

require('config.php');

$db = new PDO(MYSQL_DSN, MYSQL_USER, MYSQL_PASS);

$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$address = $_POST['address'];
$city = $_POST['city'];
$province = $_POST['province'];
$postal_code = $_POST['postal_code'];
$telephone = $_POST['telephone'];
$email = $_POST['email'];
$tshirt = $_POST['tshirt'];
$date_added = date('Y-m-d H:i:s');

$query = $db->prepare('INSERT INTO registration (
        date_added, 
        first_name, 
        last_name, 
        address, 
        city, 
        province, 
        postal_code, 
        telephone, 
        email, 
        tshirt
    ) 
    VALUES (
        :date_added, 
        :first_name, 
        :last_name, 
        :address, 
        :city, 
        :province, 
        :postal_code, 
        :telephone, 
        :email,
        :tshirt
    )');

$query->bindParam(':date_added', $date_added);
$query->bindParam(':first_name', $first_name);
$query->bindParam(':last_name', $last_name);
$query->bindParam(':address', $address);
$query->bindParam(':city', $city);
$query->bindParam(':province', $province);
$query->bindParam(':postal_code', $postal_code);
$query->bindParam(':telephone', $telephone);
$query->bindParam(':email', $email);
$query->bindParam(':tshirt', $tshirt);

$query->execute();
$id = $db->lastInsertId();


$paypal_options = array();
$paypal_options['cmd'] = '_xclick';
$paypal_options['business'] = 'metrobowlreunion@ccistudios.com';
$paypal_options['currency_code'] = 'CAD';
$paypal_options['amount'] = 50;
$paypal_options['no_shipping'] = '1';
$paypal_options['no_note'] = '1';
$paypal_options['country'] = 'CA';
$paypal_options['item_name'] = 'Metro Bowl Reunion ticket';
$paypal_options['notify_url'] = 'http://'.$_SERVER['SERVER_NAME'].'/paypal_ipn.php';
$paypal_options['custom'] = $id;

header("Location: ".PAYPAL_URL."?".http_build_query($paypal_options, '', '&'));
?>