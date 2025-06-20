<?php
/**
 * Send a simple email notification
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message (plain text)
 * @return bool True if email sent, false otherwise
 */
function send_email_notification($to, $subject, $message) {
    // Basic email validation
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Set email headers - keep it simple
    $headers = "From: noreply@" . $_SERVER['HTTP_HOST'];
    
    // Try to send email
    $success = false;
    try {
        $success = @mail($to, $subject, $message, $headers);
    } catch (Exception $e) {
        // Silently fail but log
        error_log("Email error: " . $e->getMessage());
    }
    
    return $success;
}

// Include email helpers
require_once 'assets/lib/mailer.php';
require_once 'assets/lib/email_templates.php';
require_once 'assets/lib/send_appointment_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "barber_db";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $name = $_POST['name'];
    $email = $_POST['email_address'];
    $mobile_no = $_POST['phone'];
    $category = $_POST['category'];
    $appointment_date = $_POST['appointment_date'];
    
    if(isset($_POST['appointment_time'])) {
        $appointment_time = $_POST['appointment_time'];
        
        // Check if the selected day is Saturday (6)
        $day_of_week = date('w', strtotime($appointment_date));
        if($day_of_week == 6) {
            $error_message = "Sorry, the salon is closed on Saturdays. Please select another day.";
        }
        // Convert appointment time to hours for validation
        else {
            $time_hours = date('H', strtotime($appointment_time));
            $time_minutes = date('i', strtotime($appointment_time));
            
            // Check if time is within opening hours (8 AM - 9 PM)
            if($time_hours < 8 || ($time_hours >= 21 && $time_minutes > 0) || $time_hours > 21) {
                $error_message = "Sorry, the salon is only open from 8:00 AM to 9:00 PM. Please select a time within our operating hours.";
            } else {
                // Get the requested appointment time as a timestamp
                $requested_time = strtotime($appointment_date . ' ' . $appointment_time);
                
                // Calculate 30 minutes before and after the requested time
                $time_before = date('H:i:s', $requested_time - (30 * 60));
                $time_after = date('H:i:s', $requested_time + (30 * 60));
                
                // Check if there are any appointments in a 60-minute window centered on the requested time
                $check_stmt = $conn->prepare("
                    SELECT id FROM appointments 
                    WHERE appointment_date = ? 
                    AND status = 'confirmed'
                    AND (
                        (appointment_time BETWEEN ? AND ?) 
                        OR (? BETWEEN DATE_SUB(appointment_time, INTERVAL 30 MINUTE) AND DATE_ADD(appointment_time, INTERVAL 30 MINUTE))
                    )
                ");
                $check_stmt->bind_param("ssss", $appointment_date, $time_before, $time_after, $appointment_time);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if($result->num_rows > 0) {
                    // Time slot is already booked or too close to an existing appointment
                    $error_message = "Sorry, this time slot is not available. We require at least 30 minutes between appointments. Please select a different time.";
                } else {
                    // Time slot is available, proceed with booking
                    try {
                        $booking_id = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                        $stmt = $conn->prepare("INSERT INTO appointments (booking_reference, name, email, mobile_no, category, appointment_date, appointment_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssssss", $booking_id, $name, $email, $mobile_no, $category, $appointment_date, $appointment_time);
                        
                        if ($stmt->execute()) {
                            $success_message = "Appointment booked successfully for " . date('F j, Y', strtotime($appointment_date)) . " at " . date('g:i A', strtotime($appointment_time)) . "!";
                            
                            // Send confirmation email to user
                            $appointment_data = array(
                                'name' => $name,
                                'email' => $email,
                                'booking_id' => $booking_id,
                                'category' => $category,
                                'date' => $appointment_date,
                                'time' => $appointment_time,
                                'mobile_no' => $mobile_no
                            );
                            
                            // Send email with our new function
                            $mail_result = send_appointment_confirmation($appointment_data);
                            
                            if($mail_result['success']) {
                                $success_message .= " A confirmation email has been sent to your email address.";
                            } else {
                                // Log the error but don't show to user
                                error_log("Failed to send email: " . $mail_result['error']);
                                $success_message .= " Please note your booking reference: $booking_id";
                            }
                        } else {
                            if ($stmt->errno == 1062) { // Duplicate entry error code
                                $error_message = "Sorry, this time slot was just booked by someone else. Please select a different time.";
                            } else {
                                $error_message = "Error: " . $stmt->error;
                            }
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                }
                
                if(isset($check_stmt)) {
                    $check_stmt->close();
                }
            }
        }
    } else {
        $error_message = "Please select an appointment time.";
    }
    
    if(isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- 
    - primary meta tags
  -->
    <title>Barber - Barbers & Hair Cutting</title>
    <meta name="title" content="Barber - Barbers & Hair Cutting" />
    <meta
      name="description"
      content="This is a barber html template made by codewithsadee"
    />

    <!-- 
    - favicon
  -->
    <link rel="shortcut icon" href="./favicon.svg" type="image/svg+xml" />

    <!-- 
    - custom css link
  -->
    <link rel="stylesheet" href="./assets/css/style.css" />
    
    <!-- Custom notification styles -->
    <style>
      .notification {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        padding: 15px 25px;
        border-radius: var(--radius-5);
        color: var(--white);
        font-family: var(--ff-oswald);
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        animation: slideDown 0.5s ease forwards;
        max-width: 80%;
        text-align: center;
      }
      
      .notification-success {
        background-color: #28a745;
      }
      
      .notification-error {
        background-color: #dc3545;
        font-weight: bold;
      }
      
      .notification i {
        font-size: 18px;
      }
      
      @keyframes slideDown {
        from { transform: translate(-50%, -50px); opacity: 0; }
        to { transform: translate(-50%, 0); opacity: 1; }
      }
      
      /* Style for form elements */
      .form-label {
        color: var(--white);
        font-family: var(--ff-oswald);
        font-weight: var(--fw-500);
        text-align: left;
        margin-bottom: 10px;
        font-size: 1.6rem;
      }
      
      .time-info {
        color: var(--white);
        font-size: 1.4rem;
        margin-top: -10px;
        margin-bottom: 20px;
        opacity: 0.8;
        text-align: center;
        font-style: italic;
      }
      
      /* Custom dropdown styling */
      select.input-field {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23131313%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
        background-repeat: no-repeat;
        background-position: right 15px top 50%;
        background-size: 12px auto;
        padding-right: 35px;
        cursor: pointer;
        transition: border-color 0.3s ease;
      }
      
      select.input-field:hover, 
      select.input-field:focus {
        border-color: var(--black_30);
      }
      
      /* Make placeholder text darker */
      select.input-field option {
        color: var(--eerie-black-1);
        background-color: var(--white);
        padding: 10px;
      }
      
      /* Warning message for date/time selection */
      .time-warning {
        color: #dc3545;
        font-weight: bold;
        font-size: 1.4rem;
        background-color: rgba(220, 53, 69, 0.1);
        padding: 8px;
        border-radius: var(--radius-5);
        animation: fadeIn 0.3s ease-in-out;
      }
      
      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }
    </style>

    <!-- 
    - google font link
  -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Rubik:wght@300,400;700&display=swap"
      rel="stylesheet"
    />

    <!-- 
    - flaticon
  -->
    <link rel="stylesheet" href="assets/css/flaticon.min.css" />

    <!-- 
    - preload images
  -->
    <link rel="preload" as="image" href="./assets/images/hero-banner.jpg" />
  </head>

  <body id="top">
    <?php if(isset($success_message)): ?>
      <div class="notification notification-success">
        <i class="flaticon-check"></i><?php echo $success_message; ?>
      </div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
      <div class="notification notification-error">
        <i class="flaticon-cancel"></i><?php echo $error_message; ?>
      </div>
    <?php endif; ?>
    
    <!-- 
    - #HEADER
  -->

    <header class="header">
      <div class="header-top">
        <div class="container">
          <ul class="header-top-list">
            <li class="header-top-item">
              <ion-icon name="call-outline" aria-hidden="true"></ion-icon>

              <p class="item-title">Call Us :</p>

              <a href="tel:01234567895" class="item-link">012 (345) 67 895</a>
            </li>

            <li class="header-top-item">
              <ion-icon name="time-outline" aria-hidden="true"></ion-icon>

              <p class="item-title">Opening Hour :</p>

              <p class="item-text">Sunday - Friday, 08 am - 09 pm</p>
            </li>

            <li>
              <ul class="social-list">
                <li>
                  <a href="#" class="social-link">
                    <ion-icon name="logo-facebook"></ion-icon>
                  </a>
                </li>

                <li>
                  <a href="#" class="social-link">
                    <ion-icon name="logo-twitter"></ion-icon>
                  </a>
                </li>

                <li>
                  <a href="#" class="social-link">
                    <ion-icon name="logo-youtube"></ion-icon>
                  </a>
                </li>

                <li>
                  <a href="#" class="social-link">
                    <ion-icon name="chatbubble-ellipses-outline"></ion-icon>
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>

      <div class="header-bottom" data-header>
        <div class="container">
          <a href="#" class="logo">
            Barber
            <span class="span">Hair Salon</span>
          </a>

          <nav class="navbar container" data-navbar>
            <ul class="navbar-list">
              <li class="navbar-item">
                <a href="#home" class="navbar-link" data-nav-link>Home</a>
              </li>

              <li class="navbar-item">
                <a href="#services" class="navbar-link" data-nav-link
                  >Services</a
                >
              </li>

              <li class="navbar-item">
                <a href="#pricing" class="navbar-link" data-nav-link>Pricing</a>
              </li>

              <li class="navbar-item">
                <a href="#appointment" class="navbar-link" data-nav-link>Contact</a>
              </li>
            </ul>
          </nav>

          <button
            class="nav-toggle-btn"
            aria-label="toggle menu"
            data-nav-toggler
          >
            <ion-icon name="menu-outline" aria-hidden="true"></ion-icon>
          </button>

          <a href="#appointment" class="btn has-before">
            <span class="span">Appointment</span>

            <ion-icon name="arrow-forward" aria-hidden="true"></ion-icon>
          </a>
        </div>
      </div>
    </header>

    <main>
      <article>
        <!-- 
        - #HERO
      -->

        <section
          class="section hero has-before has-bg-image"
          id="home"
          aria-label="home"
          style="background-image: url('./assets/images/hero-banner.jpg')"
        >
          <div class="container">
            <h1 class="h1 hero-title">Barbers & Hair Cutting</h1>

            <p class="hero-text">
              It is about loving the elite of concentrated learning, but due to
              the passage of time, it is necessary to work and endure great
              hardships. Suspend the heavy lifts.
            </p>
          </div>
        </section>

        <!-- 
        - #SERVICE
      -->

        <section class="section service" id="services" aria-label="services">
          <div class="container">
            <h2 class="h2 section-title text-center">Service We Provide</h2>

            <p class="section-text text-center">
              It is about focusing on elite learning, but due to time, work,
              pain, and great effort, it must be suspended.
            </p>

            <ul class="grid-list">
              <li>
                <div class="service-card">
                  <div class="card-icon">
                    <i class="flaticon-salon"></i>
                  </div>

                  <h3 class="h3">
                    <a href="#" class="card-title">Hair Cutting Style</a>
                  </h3>

                  <p class="card-text">
                    Focus on acquiring excellence, but due to time, work, and
                    suffering, great effort is required.
                  </p>

                  <a href="#" class="card-btn" aria-label="more">
                    <ion-icon
                      name="arrow-forward"
                      aria-hidden="true"
                    ></ion-icon>
                  </a>
                </div>
              </li>

              <li>
                <div class="service-card">
                  <div class="card-icon">
                    <i class="flaticon-shampoo"></i>
                  </div>

                  <h3 class="h3">
                    <a href="#" class="card-title">Hair Washing</a>
                  </h3>

                  <p class="card-text">
                    Focus on acquiring excellence, but due to time, work, and
                    suffering, great effort is required.
                  </p>

                  <a href="#" class="card-btn" aria-label="more">
                    <ion-icon
                      name="arrow-forward"
                      aria-hidden="true"
                    ></ion-icon>
                  </a>
                </div>
              </li>

              <li>
                <div class="service-card">
                  <div class="card-icon">
                    <i class="flaticon-hot-stone"></i>
                  </div>

                  <h3 class="h3">
                    <a href="#" class="card-title">Body Treatments</a>
                  </h3>

                  <p class="card-text">
                    Focus on acquiring excellence, but due to time, work, and
                    suffering, great effort is required.
                  </p>

                  <a href="#" class="card-btn" aria-label="more">
                    <ion-icon
                      name="arrow-forward"
                      aria-hidden="true"
                    ></ion-icon>
                  </a>
                </div>
              </li>

              <li>
                <div class="service-card">
                  <div class="card-icon">
                    <i class="flaticon-treatment"></i>
                  </div>

                  <h3 class="h3">
                    <a href="#" class="card-title">Beauty & Spa</a>
                  </h3>

                  <p class="card-text">
                    Focus on acquiring excellence, but due to time, work, and
                    suffering, great effort is required.
                  </p>

                  <a href="#" class="card-btn" aria-label="more">
                    <ion-icon
                      name="arrow-forward"
                      aria-hidden="true"
                    ></ion-icon>
                  </a>
                </div>
              </li>

              <li>
                <div class="service-card">
                  <div class="card-icon">
                    <i class="flaticon-shaving-razor"></i>
                  </div>

                  <h3 class="h3">
                    <a href="#" class="card-title">Stylist Shaving</a>
                  </h3>

                  <p class="card-text">
                    Focus on acquiring excellence, but due to time, work, and
                    suffering, great effort is required.
                  </p>

                  <a href="#" class="card-btn" aria-label="more">
                    <ion-icon
                      name="arrow-forward"
                      aria-hidden="true"
                    ></ion-icon>
                  </a>
                </div>
              </li>

              <li>
                <div class="service-card">
                  <div class="card-icon">
                    <i class="flaticon-hair-dye"></i>
                  </div>

                  
                  <h3 class="h3">
                    <a href="#" class="card-title">Multi Hair Colors</a>
                  </h3>

                  <p class="card-text">
                    Focus on acquiring excellence, but due to time, work, and
                    suffering, great effort is required.
                  </p>

                  <a href="#" class="card-btn" aria-label="more">
                    <ion-icon
                      name="arrow-forward"
                      aria-hidden="true"
                    ></ion-icon>
                  </a>
                </div>
              </li>
            </ul>
          </div>
        </section>

        <!-- 
        - #PRICING
      -->

        <section
          class="section pricing has-bg-image has-before"
          id="pricing"
          aria-label="pricing"
          style="background-image: url('./assets/images/pricing-bg.jpg')"
        >
          <div class="container">
            <h2 class="h2 section-title text-center">Awesome Pricing Plan</h2>

            <p class="section-text text-center">
              Focus on acquiring excellence, but due to time, work, and
              suffering, great effort is required.
            </p>

            <div class="pricing-tab-container">
              <ul class="tab-filter">
                <li>
                  <button class="filter-btn active" data-filter-btn="all">
                    <div class="btn-icon">
                      <i class="flaticon-beauty-salon" aria-hidden="true"></i>
                    </div>

                    <p class="btn-text">All Pricing</p>
                  </button>
                </li>

                <li>
                  <button class="filter-btn" data-filter-btn="beauty-spa">
                    <div class="btn-icon">
                      <i class="flaticon-relax" aria-hidden="true"></i>
                    </div>

                    <p class="btn-text">Beauty & Spa</p>
                  </button>
                </li>

                <li>
                  <button class="filter-btn" data-filter-btn="body-treatments">
                    <div class="btn-icon">
                      <i class="flaticon-massage" aria-hidden="true"></i>
                    </div>

                    <p class="btn-text">Body Treatments</p>
                  </button>
                </li>

                <li>
                  <button class="filter-btn" data-filter-btn="face-washing">
                    <div class="btn-icon">
                      <i class="flaticon-spa" aria-hidden="true"></i>
                    </div>

                    <p class="btn-text">Face Washing</p>
                  </button>
                </li>

                <li>
                  <button class="filter-btn" data-filter-btn="meditations">
                    <div class="btn-icon">
                      <i class="flaticon-yoga" aria-hidden="true"></i>
                    </div>

                    <p class="btn-text">Meditations</p>
                  </button>
                </li>

                <li>
                  <button class="filter-btn" data-filter-btn="shaving">
                    <div class="btn-icon">
                      <i class="flaticon-razor-blade" aria-hidden="true"></i>
                    </div>

                    <p class="btn-text">Shaving</p>
                  </button>
                </li>
              </ul>

              <ul class="grid-list">
                <li data-filter="shaving">
                  <div class="pricing-card">
                    <figure
                      class="card-banner img-holder"
                      style="--width: 90; --height: 90"
                    >
                      <img
                        src="./assets/images/pricing-1.jpg"
                        width="90"
                        height="90"
                        alt="Hair Cutting & Fitting"
                        class="img-cover"
                      />
                    </figure>

                    <div class="wrapper">
                      <h3 class="h3 card-title">Hair Cutting & Fitting</h3>

                      <p class="card-text">Clean & simple 30-40 minutes</p>
                    </div>

                    <data class="card-price" value="200">₹200</data>
                  </div>
                </li>

                <li data-filter="shaving">
                  <div class="pricing-card">
                    <figure
                      class="card-banner img-holder"
                      style="--width: 90; --height: 90"
                    >
                      <img
                        src="./assets/images/pricing-2.jpg"
                        width="90"
                        height="90"
                        alt="Shaving & Facial"
                        class="img-cover"
                      />
                    </figure>

                    <div class="wrapper">
                      <h3 class="h3 card-title">Shaving & Facial</h3>

                      <p class="card-text">Clean & simple 30-40 minutes</p>
                    </div>

                    <data class="card-price" value="150">₹150</data>
                  </div>
                </li>

                <li data-filter="face-washing">
                  <div class="pricing-card">
                    <figure
                      class="card-banner img-holder"
                      style="--width: 90; --height: 90"
                    >
                      <img
                        src="./assets/images/pricing-3.jpg"
                        width="90"
                        height="90"
                        alt="Hair Color & Wash"
                        class="img-cover"
                      />
                    </figure>

                    <div class="wrapper">
                      <h3 class="h3 card-title">Hair Color & Wash</h3>

                      <p class="card-text">Clean & simple 30-40 minutes</p>
                    </div>

                    <data class="card-price" value="100">₹100</data>
                  </div>
                </li>

                <li data-filter="body-treatments">
                  <div class="pricing-card">
                    <figure
                      class="card-banner img-holder"
                      style="--width: 90; --height: 90"
                    >
                      <img
                        src="./assets/images/pricing-4.jpg"
                        width="90"
                        height="90"
                        alt="Body Massage"
                        class="img-cover"
                      />
                    </figure>

                    <div class="wrapper">
                      <h3 class="h3 card-title">Body Massage</h3>

                      <p class="card-text">Clean & simple 30-40 minutes</p>
                    </div>

                    <data class="card-price" value="450">₹450</data>
                  </div>
                </li>

                <li data-filter="beauty-spa">
                  <div class="pricing-card">
                    <figure
                      class="card-banner img-holder"
                      style="--width: 90; --height: 90"
                    >
                      <img
                        src="./assets/images/pricing-5.jpg"
                        width="90"
                        height="90"
                        alt="Beauty & Spa"
                        class="img-cover"
                      />
                    </figure>

                    <div class="wrapper">
                      <h3 class="h3 card-title">Beauty & Spa</h3>

                      <p class="card-text">Clean & simple 30-40 minutes</p>
                    </div>

                    <data class="card-price" value="450">₹450</data>
                  </div>
                </li>

                <li data-filter="face-washing">
                  <div class="pricing-card">
                    <figure
                      class="card-banner img-holder"
                      style="--width: 90; --height: 90"
                    >
                      <img
                        src="./assets/images/pricing-6.jpg"
                        width="90"
                        height="90"
                        alt="Facial & Face Wash"
                        class="img-cover"
                      />
                    </figure>

                    <div class="wrapper">
                      <h3 class="h3 card-title">Facial & Face Wash</h3>

                      <p class="card-text">Clean & simple 30-40 minutes</p>
                    </div>

                    <data class="card-price" value="200">₹200</data>
                  </div>
                </li>

                <li data-filter="body-treatments">
                  <div class="pricing-card">
                    <figure
                      class="card-banner img-holder"
                      style="--width: 90; --height: 90"
                    >
                      <img
                        src="./assets/images/pricing-7.jpg"
                        width="90"
                        height="90"
                        alt="Backbone Massage"
                        class="img-cover"
                      />
                    </figure>

                    <div class="wrapper">
                      <h3 class="h3 card-title">Backbone Massage</h3>

                      <p class="card-text">Clean & simple 30-40 minutes</p>
                    </div>

                    <data class="card-price" value="350">₹350</data>
                  </div>
                </li>

                <li data-filter="meditations">
                  <div class="pricing-card">
                    <figure
                      class="card-banner img-holder"
                      style="--width: 90; --height: 90"
                    >
                      <img
                        src="./assets/images/pricing-8.jpg"
                        width="90"
                        height="90"
                        alt="Meditation & Massage"
                        class="img-cover"
                      />
                    </figure>

                    <div class="wrapper">
                      <h3 class="h3 card-title">Meditation & Massage</h3>

                      <p class="card-text">Clean & simple 30-40 minutes</p>
                    </div>

                    <data class="card-price" value="500">₹500</data>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </section>

        <!-- 
        - #APPOINTMENT
      -->

        <section
          class="section appoin"
          id="appointment"
          aria-label="appointment"
        >
          <div class="container">
            <div class="appoin-card">
              <figure
                class="card-banner img-holder"
                style="--width: 250; --height: 774"
              >
                <img
                  src="./assets/images/appoin-banner-1.jpg"
                  width="250"
                  height="774"
                  loading="lazy"
                  alt=""
                  class="img-cover"
                />
              </figure>

              <div class="card-content">
                <h2 class="h2 section-title">Make Appointment</h2>

                <p class="section-text">
                  Focus on acquiring excellence, but due to time, work, and
                  suffering, great effort is required.
                </p>

                <form action="index.php" method="POST" class="appoin-form">
                  <div class="input-wrapper">
                    <input
                      type="text"
                      name="name"
                      placeholder="Your Full Name"
                      required
                      class="input-field"
                    />

                    <input
                      type="email"
                      name="email_address"
                      placeholder="Email Address"
                      required
                      class="input-field"
                    />
                  </div>

                  <div class="input-wrapper">
                    <p class="form-label">Contact Information & Service</p>
                  </div>
                  
                  <div class="input-wrapper">
                    <input
                      type="text"
                      name="phone"
                      placeholder="Phone Number"
                      required
                      class="input-field"
                    />

                    <select name="category" class="input-field" required>
                      <option value="" disabled selected>Select Service Category</option>
                      <option value="Hair Cutting & Fitting">Hair Cutting & Fitting</option>
                      <option value="Shaving & Facial">Shaving & Facial</option>
                      <option value="Hair Color & Wash">Hair Color & Wash</option>
                      <option value="Body Massage">Body Massage</option>
                      <option value="Beauty & Spa">Beauty & Spa</option>
                      <option value="Facial & Face Wash">Facial & Face Wash</option>
                      <option value="Backbone Massage">Backbone Massage</option>
                      <option value="Meditation & Massage">Meditation & Massage</option>
                    
                    </select>
                  </div>

                  <div class="input-wrapper">
                    <p class="form-label">Select Appointment Date & Time</p>
                  </div>
                  
                  <div class="input-wrapper">
                    <input
                      type="date"
                      name="appointment_date"
                      id="appointment_date"
                      required
                      class="input-field"
                      min="<?php echo date('Y-m-d'); ?>"
                    />

                    <input
                      type="time"
                      name="appointment_time"
                      id="appointment_time"
                      required
                      class="input-field"
                      min="08:00"
                      max="21:00"
                    />
                  </div>
                  
                  <p class="time-info">Our working hours: 8:00 AM - 9:00 PM, Sunday to Friday. Salon is closed on Saturdays. Each appointment requires 30 minutes of buffer time before and after, so time slots may be unavailable if another appointment is nearby.</p>
                  <p id="date-time-warning" class="time-warning" style="display: none; color: #dc3545; text-align: center; margin-top: -10px; margin-bottom: 15px;"></p>

                  <button type="submit" class="form-btn" id="appointment-btn">
                    <span class="span">Appointment Now</span>
                    <ion-icon name="arrow-forward" aria-hidden="true"></ion-icon>
                  </button>
                </form>

                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const dateInput = document.getElementById('appointment_date');
                    const timeInput = document.getElementById('appointment_time');
                    const warningElement = document.getElementById('date-time-warning');
                    const appointmentBtn = document.getElementById('appointment-btn');
                    
                    // Validate date and time selection
                    function validateDateTime() {
                      let isValid = true;
                      const selectedDate = new Date(dateInput.value);
                      const dayOfWeek = selectedDate.getDay();
                      
                      // Clear previous warning
                      warningElement.style.display = 'none';
                      warningElement.textContent = '';
                      
                      // Check if Saturday (6)
                      if (dayOfWeek === 6) {
                        warningElement.textContent = 'The salon is closed on Saturdays. Please select another day.';
                        warningElement.style.display = 'block';
                        isValid = false;
                      }
                      
                      // Check time if entered
                      if (timeInput.value) {
                        const timeparts = timeInput.value.split(':');
                        const hours = parseInt(timeparts[0]);
                        const minutes = parseInt(timeparts[1]);
                        
                        if (hours < 8 || (hours >= 21 && minutes > 0) || hours > 21) {
                          warningElement.textContent = 'The salon is only open from 8:00 AM to 9:00 PM. Please select a time within our operating hours.';
                          warningElement.style.display = 'block';
                          isValid = false;
                        }
                      }
                      
                      return isValid;
                    }
                    
                    // Attach event listeners to inputs
                    dateInput.addEventListener('change', validateDateTime);
                    timeInput.addEventListener('change', validateDateTime);
                    
                    // Form submission validation
                    document.querySelector('form').addEventListener('submit', function(event) {
                      if (!validateDateTime()) {
                        event.preventDefault();
                      }
                    });
                  });
                </script>

              </div>

              <figure
                class="card-banner img-holder"
                style="--width: 250; --height: 774"
              >
                <img
                  src="./assets/images/appoin-banner-2.jpg"
                  width="250"
                  height="774"
                  loading="lazy"
                  alt=""
                  class="img-cover"
                />
              </figure>
            </div>
          </div>
        </section>
      </article>
    </main>

    <!-- 
    - #FOOTER
  -->

    <footer
      class="footer has-bg-image"
      style="background-image: url('./assets/images/footer-bg.png')"
    >
      <div class="container">
        <div class="footer-top">
          <div class="footer-brand">
            <a href="#" class="logo">
              Barber
              <span class="span">Hair Salon</span>
            </a>

            <form action="" class="input-wrapper">
              <input
                type="email"
                name="email_address"
                placeholder="Enter Your Email"
                required
                class="email-field"
              />

              <button type="submit" class="btn has-before">
                <span class="span">Subscribe Now</span>

                <ion-icon name="arrow-forward" aria-hidden="true"></ion-icon>
              </button>
            </form>
          </div>

          <div class="footer-link-box">
            <ul class="footer-list">
              <li>
                <p class="footer-list-title">Quick Links</p>
              </li>

              <li>
                <a href="#" class="footer-link has-before">Our Services</a>
              </li>

              <li>
                <a href="#" class="footer-link has-before">Meet Our Team</a>
              </li>

              <li>
                <a href="#" class="footer-link has-before">Our Portfolio</a>
              </li>

              <li>
                <a href="#" class="footer-link has-before">Need A Career?</a>
              </li>

              <li>
                <a href="#" class="footer-link has-before">News & Blog</a>
              </li>
            </ul>

            <ul class="footer-list">
              <li>
                <p class="footer-list-title">Services</p>
              </li>

              <li>
                <a href="#" class="footer-link has-before">Hair Cutting</a>
              </li>

              <li>
                <a href="#" class="footer-link has-before">Shaving & Design</a>
              </li>

              <li>
                <a href="#" class="footer-link has-before">Hair Colors</a>
              </li>

              <li>
                <a href="#" class="footer-link has-before">Beauty & Spa</a>
              </li>

              <li>
                <a href="#" class="footer-link has-before">Body Massages</a>
              </li>
            </ul>

            <ul class="footer-list">
              <li>
                <p class="footer-list-title">Recent News</p>
              </li>

              <li>
                <div class="blog-card">
                  <figure
                    class="card-banner img-holder"
                    style="--width: 70; --height: 75"
                  >
                    <img
                      src="./assets/images/footer-blog-1.jpg"
                      width="70"
                      height="75"
                      loading="lazy"
                      alt="The beginners guide "
                      class="img-cover"
                    />
                  </figure>

                  <div class="card-content">
                    <h3>
                      <a href="#" class="card-title">The beginners guide </a>
                    </h3>

                    <div class="card-date">
                      <ion-icon
                        name="calendar-outline"
                        aria-hidden="true"
                      ></ion-icon>

                      <time datetime="2022-08-25">31 March 2025</time>
                    </div>
                  </div>
                </div>
              </li>

              <li>
                <div class="blog-card">
                  <figure
                    class="card-banner img-holder"
                    style="--width: 70; --height: 75"
                  >
                    <img
                      src="./assets/images/footer-blog-2.jpg"
                      width="70"
                      height="75"
                      loading="lazy"
                      alt="How do I get rid of unwanted hair on my face?"
                      class="img-cover"
                    />
                  </figure>

                  <div class="card-content">
                    <h3>
                      <a href="#" class="card-title"
                        >How do I get rid of unwanted hair on my face?</a
                      >
                    </h3>

                    <div class="card-date">
                      <ion-icon
                        name="calendar-outline"
                        aria-hidden="true"
                      ></ion-icon>

                      <time datetime="2022-08-25">31 March 2025</time>
                    </div>
                  </div>
                </div>
              </li>
            </ul>

            <ul class="footer-list">
              <li>
                <p class="footer-list-title">Contact Us</p>
              </li>

              <li class="footer-list-item">
                <ion-icon name="location-outline" aria-hidden="true"></ion-icon>

                <address class="contact-link">
                  Teen Batti Tambe Mala Road, Ramchandra Jadhav, Ichalkaranji, 416115
                </address>
              </li>

              <li class="footer-list-item">
                <ion-icon name="call-outline" aria-hidden="true"></ion-icon>

                <a href="tel:+0123456789" class="contact-link"
                  >+012 (345) 67 89</a
                >
              </li>

              <li class="footer-list-item">
                <ion-icon name="time-outline" aria-hidden="true"></ion-icon>

                <span class="contact-link">Wed - Monday, 08 am - 09 pm</span>
              </li>

              <li class="footer-list-item">
                <ion-icon name="mail-outline" aria-hidden="true"></ion-icon>

                <a href="mailto:support@gmail.com" class="contact-link"
                  >support@gmail.com</a
                >
              </li>
            </ul>
          </div>
        </div>

        <div class="footer-bottom">
          <p class="copyright">
            &copy; 2025 <a href="#" class="copyright-link">Siddharth Desai</a>.
            All Rights Reserved.
          </p>
        </div>
      </div>
    </footer>

    <!-- 
    - #BACK TO TOP
  -->

    <a
      href="#top"
      class="back-top-btn"
      aria-label="back to top"
      data-back-top-btn
    >
      <ion-icon name="chevron-up" aria-hidden="true"></ion-icon>
    </a>

    <!-- 
    - custom js link
  -->
    <script src="./assets/js/script.js" defer></script>

    <!-- 
    - ionicon link
  -->
    <script
      type="module"
      src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"
    ></script>
    <script
      nomodule
      src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"
    ></script>
    
    <!-- Smooth scroll script -->
    <script>
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
          e.preventDefault();
          
          const targetId = this.getAttribute('href');
          const targetElement = document.querySelector(targetId);
          
          if (targetElement) {
            window.scrollTo({
              top: targetElement.offsetTop,
              behavior: 'smooth'
            });
          }
        });
      });
    </script>
  </body>
</html>
