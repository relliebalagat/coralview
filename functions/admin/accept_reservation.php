<?php

session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require('../assets/connection.php');
require('../../composer/vendor/autoload.php');

$db = connect_to_db();

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    if(isset($_POST["down_payment_reference_no"])) {
        $dp_reference_no = mysqli_real_escape_string($db, trim($_POST['down_payment_reference_no']));
    }

    if(empty($_POST["down_payment_amount"]) && empty($_POST["down_payment_description"])) {
        $_SESSION['msg'] = "DESCRIPTION AND AMOUNT IN DOWNPAYMENT DETAILS IS EMPTY";
        $_SESSION['alert'] = "alert alert-danger";
        header('location: ../../admin/accept.php?reference_no='. $dp_reference_no . '');
    } else {

        if(!empty($_POST["down_payment_description"])) {
            $dp_description = mysqli_real_escape_string($db, trim($_POST['down_payment_description']));
        } else {
            $_SESSION['msg'] = "DESCRIPTION IN DOWNPAYMENT DETAILS IS EMPTY";
            $_SESSION['alert'] = "alert alert-danger";
            header('location: ../../admin/accept.php?reference_no='. $dp_reference_no . '');
        }

        if(!empty($_POST["down_payment_amount"])) {
            $dp_amount = mysqli_real_escape_string($db, trim($_POST['down_payment_amount']));
        } else {
            $_SESSION['msg'] = "AMOUNT IN DOWNPAYMENT DETAILS IS EMPTY";
            $_SESSION['alert'] = "alert alert-danger";
            header('location: ../../admin/accept.php?reference_no='. $dp_reference_no . '');
        }

    }

    $insert_to_downpayment = "
        INSERT INTO downpayment (reference_no, amount, description, time_stamp)
        VALUES ('$dp_reference_no' , '$dp_amount', '$dp_description' , NOW())
    ";

    $insert_to_downpayment_result = mysqli_query($db, $insert_to_downpayment);


    // $insert_query = "
    //     INSERT INTO billing (reference_no, amount_paid, total_amount, description, time_stamp)
    //     VALUES ('$dp_reference_no', '$dp_amount', NULL, '$dp_description', NOW())
    // ";

    // $insert_result = mysqli_query($db, $insert_query); 

    if($insert_to_downpayment_result) {

        // $insert_to_downpayment = "
        //     INSERT INTO downpayment (reference_no, amount, time_stamp)
        //     VALUES ('$dp_reference_no', '$dp_amount', NOW())
        // ";

        // $insert_to_downpayment_result = mysqli_query($db, $insert_to_downpayment);

        // Update status of reservation to Check-in
        $room_status_query = "UPDATE reservation SET status='FOR CHECK IN' WHERE reference_no='$dp_reference_no'";
        $room_status_result = mysqli_query($db, $room_status_query);

        if($room_status_result) {

            // GET EMAIL OF THE CLIENT
            $find_guestid_query = "SELECT guest_id FROM reservation WHERE reference_no='$dp_reference_no'";
            $find_guestid_result = mysqli_query($db, $find_guestid_query);

            if(mysqli_num_rows($find_guestid_result) == 1) {

                while($guest = mysqli_fetch_assoc($find_guestid_result)) {

                    $guest_id = $guest["guest_id"];

                    $find_email_query = "SELECT email FROM guest WHERE id=$guest_id";
                    $find_email_result = mysqli_query($db, $find_email_query);

                    if(mysqli_num_rows($find_email_result) == 1) {

                        while($email = mysqli_fetch_assoc($find_email_result)) {
                            
                            $guest_email = $email["email"];

                            // SEND EMAIL TO CLIENT
                            $reservationMessage = '
                                <style>

                                    h1, p {
                                        font-family: \'Segoe UI\', sans-serif;
                                    }

                                    p {
                                        font-size: 16px;
                                    }

                                </style>

                                <div style="width: 100%;">
                                    <h1>CORALVIEW  RESORT</h1>
                                    <p>This is to inform you that your reservation has been accepted. Please be reminded of the date of your check in.</p>
                                </div>
                            ';

                            $mail = new PHPMailer(true);
                            try {
                                $message = $reservationMessage;
                                $mail->SMTPDebug = 1;
                                $mail->isSMTP();
                                $mail->Host = 'smtp.gmail.com';
                                $mail->SMTPAuth = true;
                                $mail->Username = 'coralviewthesis@gmail.com';  // Fill this up
                                $mail->Password = 'Qwerty1234@1234';  // Fill this up
                                $mail->SMTPSecure = 'tls';
                                $mail->Port = 587;
                                $mail->setFrom('coralviewthesis@gmail.com');
                                $mail->isHTML(true);
                                $mail->addAddress($guest_email);
                                $mail->Subject = 'Coralview Reservation Accepted';
                                $mail->Body = $message;
                                $mail->send();
                                $_SESSION['msg'] = "RESERVATION IS SUCCESSFULLY TAG AS ACCEPTED";
                                $_SESSION['alert'] = "alert alert-success";
                                header('location: ../../admin/accept.php?reference_no='. $dp_reference_no . '');
                            } catch (Exception $e) {
                                $_SESSION['msg'] = "There\'s an error processing your request";
                                $_SESSION['alert'] = "alert alert-danger";
                                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                            }


                        }

                    }

                }

            }

        } 


    } else {

        $_SESSION['msg'] = "Cannot process reservation";
        $_SESSION['alert'] = "alert alert-danger";
        header('location: ../../admin/accept.php?reference_no='. $dp_reference_no . '');

    }
    

}