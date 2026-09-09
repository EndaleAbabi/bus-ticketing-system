<?php
session_start(); require_once '../config/database.php'; require_once '../includes/functions.php'; requireLogin();
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: booking.php');exit;}
$trip_id=(int)($_POST['trip_id']??0);
$selected_seats=array_values(array_unique(array_filter(array_map('intval',explode(',',$_POST['selected_seats']??'')),fn($v)=>$v>0)));
$full_name=trim($_POST['full_name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??''); $gender=trim($_POST['gender']??''); $age=(int)($_POST['age']??0); $payment_method=$_POST['payment_method']??'cash';
$allowed_methods=['cash','card','mobile_money','bank_transfer'];
if(!$trip_id||!$selected_seats||$full_name===''||$phone===''||!in_array($payment_method,$allowed_methods,true)){die('Invalid booking data. Please return and try again.');}
if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL)){die('Please enter a valid email address.');}
$trip=getTripDetails($conn,$trip_id); if(!$trip||$trip['status']==='cancelled'||$trip['status']==='completed'){die('This trip is no longer available.');}
$conn->begin_transaction();
try{
 $ids=implode(',',array_map('intval',$selected_seats));
 $check=$conn->query("SELECT seat_id,status FROM trip_seats WHERE trip_id=".(int)$trip_id." AND seat_id IN ($ids) FOR UPDATE");
 if(!$check||$check->num_rows!==count($selected_seats)){throw new Exception('One or more selected seats are not available.');}
 while($seat=$check->fetch_assoc()){if($seat['status']!=='available'){throw new Exception('One or more selected seats have just been booked. Please choose again.');}}
 $booking_reference=generateBookingReference(); $user_id=(int)$_SESSION['user_id']; $total_price=(float)$trip['fare']*count($selected_seats);
 $stmt=$conn->prepare('INSERT INTO bookings (booking_reference,trip_id,customer_id,customer_name,customer_gender,customer_age,customer_phone,customer_email,fare,booking_type,payment_status,payment_method,booked_by_user_id,created_at) VALUES (?,?,?,?,?,?,?,?,?,"online","paid",?,?,NOW())');
 if(!$stmt)throw new Exception('Could not prepare booking.');
 $stmt->bind_param('siississdsi',$booking_reference,$trip_id,$user_id,$full_name,$gender,$age,$phone,$email,$total_price,$payment_method,$user_id);
 if(!$stmt->execute())throw new Exception('Booking creation failed.'); $booking_id=$conn->insert_id;
 $update=$conn->prepare("UPDATE trip_seats SET status='paid',booking_reference=?,booked_at=NOW() WHERE trip_id=? AND seat_id IN ($ids) AND status='available'"); $update->bind_param('si',$booking_reference,$trip_id); if(!$update->execute()||$update->affected_rows!==count($selected_seats))throw new Exception('Seat reservation failed.');
 $ticket_number=generateTicketNumber(); $qr=generateQRCodeData($ticket_number); $ticket=$conn->prepare('INSERT INTO tickets(ticket_number,booking_id,trip_id,qr_code,status,created_at) VALUES(?,?,?,? ,"active",NOW())'); $ticket->bind_param('siis',$ticket_number,$booking_id,$trip_id,$qr); if(!$ticket->execute())throw new Exception('Ticket generation failed.');
 $payment=$conn->prepare('INSERT INTO payments(booking_id,amount,payment_method,payment_status,processed_by,created_at) VALUES(?,?,?,"completed",?,NOW())'); $payment->bind_param('idsi',$booking_id,$total_price,$payment_method,$user_id); if(!$payment->execute())throw new Exception('Payment record failed.');
 logAudit($conn,$user_id,'BOOKING_CREATED','booking',$booking_id,null,'Booking Reference: '.$booking_reference); $conn->commit(); header('Location: ticket.php?booking_id='.$booking_id); exit;
}catch(Throwable $e){$conn->rollback(); http_response_code(400); die('Booking failed: '.htmlspecialchars($e->getMessage()));}
?>