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
    <title>About Us | JobDZ</title>
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

        /* ═══════════════════════════════ */
        /* HERO SECTION                    */
        /* ═══════════════════════════════ */

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
            max-width: 700px;
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

        /* ═══════════════════════════════ */
        /* MAIN CONTAINER                  */
        /* ═══════════════════════════════ */

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 80px;
        }

        /* ─── STATS SECTION ─── */

        .stats-section {
            margin-bottom: 60px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 18px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
            transition: all .2s;
        }

        .stat-card:hover {
            border-color: #c7d2fe;
            transform: translateY(-4px);
            box-shadow: 0 10px 28px rgba(99, 102, 241, 0.1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: #f5f3ff;
            color: #6366f1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 20px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #6366f1;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .stat-description {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
        }

        /* ─── SECTION TITLE ─── */

        .section-title {
            text-align: center;
            margin-bottom: 48px;
        }

        .section-overline {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f5f3ff;
            color: #6366f1;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        .section-heading {
            font-size: 36px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 14px;
        }

        .section-description {
            font-size: 15px;
            color: #64748b;
            line-height: 1.6;
            max-width: 650px;
            margin: 0 auto;
        }

        /* ─── VALUES GRID ─── */

        .values-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 60px;
        }

        .value-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
            transition: all .2s;
        }

        .value-card:hover {
            border-color: #c7d2fe;
            transform: translateY(-4px);
            box-shadow: 0 10px 28px rgba(99, 102, 241, 0.1);
        }

        .value-icon {
            width: 50px;
            height: 50px;
            background: #f5f3ff;
            color: #6366f1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 16px;
        }

        .value-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .value-description {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
        }

        /* ─── STEPS GRID ─── */

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 60px;
        }

        .step-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
            transition: all .2s;
            position: relative;
        }

        .step-card:hover {
            border-color: #c7d2fe;
            transform: translateY(-4px);
            box-shadow: 0 10px 28px rgba(99, 102, 241, 0.1);
        }

        .step-number {
            position: absolute;
            top: 20px;
            right: 24px;
            font-size: 48px;
            font-weight: 800;
            color: #f5f3ff;
            line-height: 1;
        }

        .step-icon {
            width: 52px;
            height: 52px;
            background: #f5f3ff;
            color: #6366f1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 18px;
        }

        .step-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .step-description {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
        }

        /* ─── CTA SECTION ─── */

        .cta-section {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border-radius: 24px;
            padding: 60px 50px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .cta-content {
            position: relative;
            z-index: 1;
            max-width: 700px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 14px;
        }

        .cta-description {
            font-size: 15px;
            margin-bottom: 28px;
            opacity: 0.95;
            line-height: 1.6;
        }

        .cta-buttons {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: #6366f1;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            transition: all .2s;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 12px 28px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            transition: all .2s;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* ═══════════════════════════════ */
        /* RESPONSIVE                      */
        /* ═══════════════════════════════ */

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .steps-grid {
                grid-template-columns: repeat(2, 1fr);
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

            .stats-grid,
            .values-grid,
            .steps-grid {
                grid-template-columns: 1fr;
            }

            .section-heading {
                font-size: 28px;
            }

            .cta-section {
                padding: 45px 30px;
            }

            .cta-title {
                font-size: 26px;
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

            .section-heading {
                font-size: 24px;
            }

            .stat-number {
                font-size: 26px;
            }

            .value-title,
            .step-title {
                font-size: 16px;
            }

            .cta-section {
                padding: 35px 20px;
                border-radius: 18px;
            }

            .cta-title {
                font-size: 22px;
            }

            .cta-buttons {
                gap: 10px;
            }

            .btn-primary,
            .btn-ghost {
                padding: 10px 20px;
                font-size: 12px;
            }

            .step-number {
                font-size: 36px;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-overline">About JobDZ</div>
            <h1 class="hero-title">Connecting Talent with Opportunity Across Algeria</h1>
            <p class="hero-subtitle">JobDZ helps candidates discover better career opportunities and enables employers to hire faster and smarter across Algeria.</p>
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <span>About</span>
            </div>
        </div>
    </section>

    <!-- MAIN CONTENT -->
    <div class="container">

        <!-- STATS SECTION -->
        <section class="stats-section">
            <div class="stats-grid">

                <div class="stat-card">
                    <div class="stat-icon"><i class="ti ti-building"></i></div>
                    <div class="stat-number">1,200+</div>
                    <div class="stat-label">Employers</div>
                    <div class="stat-description">Trusted companies hiring every day</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="ti ti-briefcase"></i></div>
                    <div class="stat-number">8,400+</div>
                    <div class="stat-label">Jobs</div>
                    <div class="stat-description">Opportunities across all wilayas</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="ti ti-users"></i></div>
                    <div class="stat-number">25K+</div>
                    <div class="stat-label">Candidates</div>
                    <div class="stat-description">Professionals building better careers</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="ti ti-headphones"></i></div>
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Support</div>
                    <div class="stat-description">Fast help for candidates and recruiters</div>
                </div>

            </div>
        </section>

        <!-- VALUES SECTION -->
        <section>

            <div class="section-title">
                <div class="section-overline"><i class="ti ti-heart"></i> Our Values</div>
                <h2 class="section-heading">What Drives Us Forward</h2>
                <p class="section-description">We believe every Algerian deserves access to the right opportunity and every employer deserves the right talent.</p>
            </div>

            <div class="values-grid">

                <div class="value-card">
                    <div class="value-icon"><i class="ti ti-target"></i></div>
                    <h3 class="value-title">Our Mission</h3>
                    <p class="value-description">Build a recruitment platform that is simple, transparent, and accessible for everyone in Algeria.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon"><i class="ti ti-eye"></i></div>
                    <h3 class="value-title">Our Vision</h3>
                    <p class="value-description">Make hiring easier and help candidates discover meaningful opportunities anywhere in Algeria.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon"><i class="ti ti-shield-check"></i></div>
                    <h3 class="value-title">Trust & Transparency</h3>
                    <p class="value-description">Verified employers, real opportunities, and a recruitment process users can trust.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon"><i class="ti ti-rocket"></i></div>
                    <h3 class="value-title">Built for Algeria</h3>
                    <p class="value-description">Designed specifically for the Algerian market with local categories and wilaya-based search.</p>
                </div>

            </div>

        </section>

        <!-- HOW IT WORKS SECTION -->
        <section>

            <div class="section-title">
                <div class="section-overline"><i class="ti ti-map"></i> How It Works</div>
                <h2 class="section-heading">Get Hired in 3 Simple Steps</h2>
                <p class="section-description">A simple experience for both candidates and employers.</p>
            </div>

            <div class="steps-grid">

                <div class="step-card">
                    <div class="step-number">01</div>
                    <div class="step-icon"><i class="ti ti-user-plus"></i></div>
                    <h3 class="step-title">Create Your Profile</h3>
                    <p class="step-description">Create your account and complete your profile in minutes.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">02</div>
                    <div class="step-icon"><i class="ti ti-search"></i></div>
                    <h3 class="step-title">Discover Jobs</h3>
                    <p class="step-description">Browse jobs and discover opportunities that match your skills.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">03</div>
                    <div class="step-icon"><i class="ti ti-send"></i></div>
                    <h3 class="step-title">Apply Easily</h3>
                    <p class="step-description">Apply instantly and track your applications directly.</p>
                </div>

            </div>

        </section>

        <!-- CTA SECTION -->
        <section class="cta-section">
            <div class="cta-content">
                <h2 class="cta-title">Ready to Find Your Next Opportunity?</h2>
                <p class="cta-description">Join thousands of candidates and employers already using JobDZ across Algeria.</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn-primary">
                        <i class="ti ti-user-plus"></i>
                        Get Started
                    </a>
                    <a href="job.php" class="btn-ghost">
                        <i class="ti ti-briefcase"></i>
                        Browse Jobs
                    </a>
                </div>
            </div>
        </section>

    </div>

    <?php include 'includes/footer.php'; ?>

</body>

</html>