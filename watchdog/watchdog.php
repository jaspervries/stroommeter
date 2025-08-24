<?php
/*
*   stroommeter - toolchain for reading energy meters
*   Copyright (C) 2025  Jasper Vries
*
*   This program is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.

*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
* The watchdog program is used to detect if your meter is still running and
* sends you an e-mail if not. It is intended to be run on the remote server
* from a cronjob.
*/
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

include('../dbconnect.inc.php');
include('../config.inc.php');

$sendmail = FALSE;
$mailcontent = '';

//expects 287 for each counter
$counter_results = array();
$qry = "SELECT `counter`, count(*) FROM `" . $db['prefix'] . "temp` 
WHERE `datetime` >= DATE_SUB(NOW(), INTERVAL 1 DAY) 
GROUP BY `counter`";
$res = mysqli_query($db['link'], $qry);
while ($row = mysqli_fetch_row($res)) {
    $counter_results[$row[0]] = $row[1];
}
//check each configured counter
foreach ($counters as $i => $counter) {
    if ($counter_results[$i] < 287) {
        $sendmail = TRUE;
    }
    $mailcontent .= $i . ': ' . $counter['name'] . ' expected 287 found ' . $counter_results[$i] . '<br>';
}

if ( $sendmail == TRUE) {
    //setup mail
    require '../bundled/PHPMailer/src/PHPMailer.php';
    require '../bundled/PHPMailer/src/SMTP.php';
    require '../bundled/PHPMailer/src/Exception.php';

    //send mail
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();                                 //Send using SMTP
        $mail->Host       = $cfg['mail']['server'];      //Set the SMTP server to send through
        $mail->SMTPAuth   = $cfg['mail']['smtpauth'];    //Enable SMTP authentication
        $mail->Username   = $cfg['mail']['smtpuser'];    //SMTP username
        $mail->Password   = $cfg['mail']['smtppass'];    //SMTP password
        switch ($cfg['mail']['smtpsecure']) {            //Enable implicit TLS encryption
            case 'ENCRYPTION_SMTPS':
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                break;
            case 'ENCRYPTION_STARTTLS':
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                break;
            default:
                $mail->SMTPSecure = '';
        }
        $mail->Port       = $cfg['mail']['smtpport'];    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
    
        //Recipients
        $mail->setFrom($cfg['mail']['from'], $cfg['mail']['from_name']);
        $mail->addAddress($cfg['mail']['to']);           //Add a recipient
        
        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = $cfg['mail']['subject'];
        $mail->Body    = $mailcontent;
        $mail->AltBody = $mail->html2text($mailcontent);;
    
        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

echo '<p>' . $mailcontent . '</p>';
?>