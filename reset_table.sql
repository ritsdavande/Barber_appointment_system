USE barber_db;

-- Drop the existing appointments table
DROP TABLE IF EXISTS appointments;

-- Recreate the appointments table with improved structure
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    mobile_no VARCHAR(10) NOT NULL,
    category VARCHAR(50) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('confirmed', 'cancelled', 'completed') DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add a unique constraint to prevent double booking
ALTER TABLE appointments ADD CONSTRAINT unique_appointment_slot UNIQUE (appointment_date, appointment_time);

-- Add a unique constraint for booking reference
ALTER TABLE appointments ADD CONSTRAINT unique_booking_reference UNIQUE (booking_reference);

-- Verify the table structure
DESCRIBE appointments; 