USE barber_db;

-- Simple ALTER TABLE to add the appointment_time column
ALTER TABLE appointments ADD COLUMN appointment_time TIME AFTER appointment_date; 