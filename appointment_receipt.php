<?php
/**
 * Template for generating appointment receipts
 * 
 * This file is used to create a structured HTML receipt that can be converted to PDF
 * and attached to confirmation emails.
 */

/**
 * Generate appointment receipt HTML
 * 
 * @param array $appointment Appointment details array containing:
 *                          - name: Customer name
 *                          - email: Customer email
 *                          - mobile_no: Customer phone number
 *                          - category: Service category
 *                          - appointment_date: Date of appointment
 *                          - appointment_time: Time of appointment
 *                          - formatted_date: Formatted date string
 *                          - formatted_time: Formatted time string
 *                          - booking_id: Unique booking ID/reference number
 * @return string HTML content for the appointment receipt
 */
function generate_appointment_receipt($appointment) {
    // Generate a booking reference if not provided
    if (!isset($appointment['booking_id'])) {
        $appointment['booking_id'] = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }
    
    // Get the current date and time
    $issued_date = date('F j, Y');
    $issued_time = date('g:i A');
    
    // Generate the HTML receipt
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Appointment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
        }
        .receipt {
            border: 1px solid #ddd;
            padding: 20px;
            margin-top: 20px;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #f7c044;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .receipt-header h1 {
            color: #333;
            margin: 0;
        }
        .receipt-details {
            margin-bottom: 20px;
        }
        .receipt-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .receipt-details th, .receipt-details td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .receipt-details th {
            width: 40%;
            color: #666;
        }
        .receipt-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .booking-id {
            background-color: #f7c044;
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
        .barcode {
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <h1>Barber Hair Salon</h1>
            <p>Teen Batti Tambe Mala Road, Ramchandra Jadhav, Ichalkaranji, 416115</p>
            <p>Tel: 012 (345) 67 89</p>
            <div class="booking-id">BOOKING ID: {$appointment['booking_id']}</div>
        </div>
        
        <div class="receipt-details">
            <h2>Appointment Receipt</h2>
            <p>This serves as confirmation of your appointment with us.</p>
            
            <table>
                <tr>
                    <th>Name:</th>
                    <td>{$appointment['name']}</td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td>{$appointment['email']}</td>
                </tr>
                <tr>
                    <th>Phone:</th>
                    <td>{$appointment['mobile_no']}</td>
                </tr>
                <tr>
                    <th>Service:</th>
                    <td>{$appointment['category']}</td>
                </tr>
                <tr>
                    <th>Date:</th>
                    <td>{$appointment['formatted_date']}</td>
                </tr>
                <tr>
                    <th>Time:</th>
                    <td>{$appointment['formatted_time']}</td>
                </tr>
                <tr>
                    <th>Receipt Issued:</th>
                    <td>{$issued_date} at {$issued_time}</td>
                </tr>
            </table>
        </div>
        
        <div class="barcode">
            <!-- Placeholder for barcode or QR code -->
            <p>|||||||||||||||||||||||||||||||||||||||||||||||</p>
            <p>{$appointment['booking_id']}</p>
        </div>
        
        <div class="receipt-footer">
            <p>Thank you for choosing Barber Hair Salon!</p>
            <p>Please arrive 10 minutes before your scheduled appointment time.</p>
            <p>If you need to reschedule or cancel, please contact us at least 24 hours in advance.</p>
            <p><strong>Our working hours:</strong> 8:00 AM - 9:00 PM, Sunday to Friday (Closed on Saturdays)</p>
            <p><strong>Appointment Policy:</strong> Each appointment requires a 30-minute buffer before and after for quality service.</p>
        </div>
    </div>
</body>
</html>
HTML;

    return $html;
} 