<?php
/**
 * Email templates for the barber shop application
 */

/**
 * Generate appointment confirmation email
 * 
 * @param array $data Appointment data including:
 *                     - name: Customer name
 *                     - booking_id: Booking reference number
 *                     - category: Service category
 *                     - formatted_date: Formatted date
 *                     - formatted_time: Formatted time
 *                     - mobile_no: Customer phone number
 * @return string Plain text email content
 */
function get_appointment_email($data) {
    $name = isset($data['name']) ? $data['name'] : 'Customer';
    $booking_id = isset($data['booking_id']) ? $data['booking_id'] : '';
    $category = isset($data['category']) ? $data['category'] : '';
    $formatted_date = isset($data['formatted_date']) ? $data['formatted_date'] : '';
    $formatted_time = isset($data['formatted_time']) ? $data['formatted_time'] : '';
    $mobile_no = isset($data['mobile_no']) ? $data['mobile_no'] : '';
    
    $message = "APPOINTMENT CONFIRMATION - BARBER HAIR SALON\n";
    $message .= "=============================================\n\n";
    
    $message .= "Dear $name,\n\n";
    $message .= "Thank you for booking an appointment with Barber Hair Salon. We're excited to serve you!\n\n";
    
    $message .= "BOOKING REFERENCE: $booking_id\n";
    $message .= "Please keep this reference number for future inquiries.\n\n";
    
    $message .= "APPOINTMENT DETAILS:\n";
    $message .= "---------------------\n";
    $message .= "Service: $category\n";
    $message .= "Date: $formatted_date\n";
    $message .= "Time: $formatted_time\n";
    $message .= "Phone: $mobile_no\n\n";
    
    $message .= "IMPORTANT INFORMATION:\n";
    $message .= "----------------------\n";
    $message .= "- Please arrive 10 minutes before your scheduled appointment time.\n";
    $message .= "- If you need to reschedule or cancel, please contact us at least 24 hours in advance.\n";
    $message .= "- Bring your booking reference number with you.\n";
    $message .= "- Our working hours: 8:00 AM - 9:00 PM, Sunday to Friday (Closed on Saturdays).\n";
    $message .= "- Each appointment requires a 30-minute buffer time before and after for quality service.\n\n";
    
    $message .= "We look forward to seeing you!\n\n";
    
    $message .= "Regards,\n";
    $message .= "The Team at Barber Hair Salon\n";
    $message .= "---------------------------------------------\n";
    $message .= "Address: Teen Batti Tambe Mala Road, Ramchandra Jadhav, Ichalkaranji, 416115\n";
    $message .= "Tel: 012 (345) 67 89\n";
    $message .= "Working Hours: Sunday - Friday, 08 am - 09 pm\n";
    
    return $message;
} 