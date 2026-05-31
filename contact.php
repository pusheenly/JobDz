<?php
require_once 'config.php';
require_once 'functions.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/tailwind-head.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | JobDZ</title>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }


        .hero {
            padding: 60px 20px 50px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -15%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-overline {
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #6366f1;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .hero-title {
            font-size: 48px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 15px;
            color: #64748b;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #64748b;
        }

        .breadcrumb a {
            color: #0f172a;
            text-decoration: none;
            font-weight: 600;
            transition: color .2s;
        }

        .breadcrumb a:hover {
            color: #6366f1;
        }

        .breadcrumb span {
            color: #6366f1;
            font-weight: 600;
        }


        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 80px;
        }


        .contact-wrapper {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 28px;
        }


        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .info-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
            transition: all .2s;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .info-card:hover {
            border-color: #c7d2fe;
            transform: translateY(-2px);
        }

        .info-card-icon-wrapper {
            width: 44px;
            height: 44px;
            background: #f5f3ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .info-card-icon {
            font-size: 20px;
            color: #6366f1;
        }

        .info-card-text {
            flex: 1;
            min-width: 0;
        }

        .info-card-title {
            font-size: 11px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 3px;
            font-weight: 700;
        }

        .info-card-content {
            font-size: 13px;
            color: #0f172a;
            font-weight: 600;
            line-height: 1.3;
        }

        .socials-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
        }

        .socials-title {
            font-size: 11px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 14px;
            font-weight: 700;
        }

        .socials {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .social-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #f5f3ff;
            color: #6366f1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all .2s;
            border: 1px solid #ddd6fe;
            font-size: 16px;
        }

        .social-icon:hover {
            background: #6366f1;
            color: white;
            transform: translateY(-3px);
            border-color: #6366f1;
        }

        /* ─── FORM SECTION ─── */

        .form-section {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
            height: fit-content;
        }

        .form-title {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e8eef6;
            border-radius: 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: #0f172a;
            background: #fafafe;
            outline: none;
            transition: all .2s;
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #cbd5e1;
        }

        .form-input:focus,
        .form-textarea:focus {
            border-color: #6366f1;
            background: white;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.08);
        }

        .form-textarea {
            resize: vertical;
            min-height: 140px;
        }

        .submit-button {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.2);
            margin-top: 8px;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.3);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        .submit-button i {
            font-size: 15px;
        }


        .success-message {
            display: none;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 20px;
            text-align: center;
            color: #166534;
            font-weight: 500;
            font-size: 13px;
        }

        .success-message.show {
            display: block;
            animation: slideDown .3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 1024px) {
            .contact-wrapper {
                grid-template-columns: 280px 1fr;
                gap: 24px;
            }

            .hero-title {
                font-size: 40px;
            }
        }

        @media (max-width: 768px) {
            .hero {
                padding: 40px 20px 30px;
            }

            .hero-title {
                font-size: 32px;
            }

            .hero-subtitle {
                font-size: 14px;
            }

            .contact-wrapper {
                grid-template-columns: 1fr;
            }

            .form-section {
                padding: 30px;
            }

            .sidebar {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                order: -1;
            }

            .socials-card {
                grid-column: 1 / -1;
            }

            .form-title {
                font-size: 18px;
                margin-bottom: 20px;
            }

            .container {
                padding: 0 20px 60px;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 28px;
            }

            .hero-subtitle {
                font-size: 13px;
            }

            .form-section {
                padding: 20px;
                border-radius: 16px;
            }

            .form-title {
                font-size: 16px;
                margin-bottom: 16px;
            }

            .form-input,
            .form-textarea {
                padding: 11px 14px;
                font-size: 13px;
            }

            .submit-button {
                height: 44px;
                font-size: 13px;
            }

            .breadcrumb {
                font-size: 12px;
            }

            .sidebar {
                grid-template-columns: 1fr;
            }

            .socials {
                justify-content: center;
            }

            .social-icon {
                width: 40px;
                height: 40px;
                font-size: 14px;
            }

            .info-card {
                padding: 16px 18px;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-overline">Get In Touch</div>
            <h1 class="hero-title">Contact Us</h1>
            <p class="hero-subtitle">Have questions? We're here to help and would love to hear from you.</p>
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <span>Contact</span>
            </div>
        </div>
    </section>

    <!-- MAIN CONTENT -->
    <div class="container">

        <!-- CONTACT WRAPPER (SIDEBAR + FORM) -->
        <div class="contact-wrapper">

            <!-- SIDEBAR -->
            <aside class="sidebar">

                <!-- Location Card -->
                <div class="info-card">
                    <div class="info-card-icon-wrapper">
                        <i class="ti ti-map-pin info-card-icon"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-title">Location</div>
                        <div class="info-card-content">Annaba, Algeria</div>
                    </div>
                </div>

                <!-- Phone Card -->
                <div class="info-card">
                    <div class="info-card-icon-wrapper">
                        <i class="ti ti-phone info-card-icon"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-title">Phone</div>
                        <div class="info-card-content">+213 555 55 55 55</div>
                    </div>
                </div>

                <!-- Email Card -->
                <div class="info-card">
                    <div class="info-card-icon-wrapper">
                        <i class="ti ti-mail info-card-icon"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-title">Email</div>
                        <div class="info-card-content">hello@jobdz.com</div>
                    </div>
                </div>

                <!-- Hours Card -->
                <div class="info-card">
                    <div class="info-card-icon-wrapper">
                        <i class="ti ti-clock info-card-icon"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-title">Hours</div>
                        <div class="info-card-content">Mon–Fri 9:00–18:00</div>
                    </div>
                </div>

                <!-- Socials Card -->
                <div class="socials-card">
                    <div class="socials-title">Follow</div>
                    <div class="socials">
                        <a href="#" class="social-icon" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-icon" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>

            </aside>

            <!-- FORM SECTION -->
            <section class="form-section">
                <h2 class="form-title">Send Message</h2>

                <div class="success-message" id="successMessage">
                    ✓ Thank you! Your message has been sent successfully.
                </div>

                <form action="contact_process.php" method="POST" id="contactForm">

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input
                            type="text"
                            name="full_name"
                            class="form-input"
                            placeholder="Your full name"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input
                            type="email"
                            name="email"
                            class="form-input"
                            placeholder="your@email.com"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input
                            type="text"
                            name="subject"
                            class="form-input"
                            placeholder="What is this about?"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea
                            name="message"
                            class="form-textarea"
                            placeholder="Tell us how we can help you..."
                            required></textarea>
                    </div>

                    <button type="submit" class="submit-button">
                        <span>Send Message</span>
                        <i class="ti ti-send"></i>
                    </button>

                </form>
            </section>

        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Form handling
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input[required], textarea[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = '#ef4444';
                    input.style.background = '#fef2f2';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });

        // Clear error styling
        document.querySelectorAll('.form-input, .form-textarea').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = '';
                this.style.background = '';
            });

            input.addEventListener('input', function() {
                this.style.borderColor = '';
                this.style.background = '';
            });
        });
    </script>

</body>

</html>