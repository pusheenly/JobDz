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
    <title>FAQ | JobDZ</title>
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
            padding: 0 20px 60px;
        }

        .search-wrapper {
            margin-bottom: 50px;
            position: relative;
            z-index: 10;
        }

        .search-box {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            padding: 14px 18px 14px 50px;
            border: 1px solid #e8eef6;
            border-radius: 16px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            background: white;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
            outline: none;
            transition: all .2s;
        }

        .search-input::placeholder {
            color: #cbd5e1;
        }

        .search-input:focus {
            border-color: #6366f1;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.12);
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            pointer-events: none;
        }

        .category-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 40px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .category-tab {
            padding: 10px 20px;
            border: 1px solid #e8eef6;
            background: white;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
        }

        .category-tab:hover,
        .category-tab.active {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }

        .faq-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 16px;
            margin-bottom: 50px;
        }

        .faq-item {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
            transition: all .2s;
        }

        .faq-item:hover {
            border-color: #c7d2fe;
            box-shadow: 0 10px 28px rgba(99, 102, 241, 0.1);
        }

        .faq-header {
            padding: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: background .2s;
        }

        .faq-item.active .faq-header {
            background: #f5f3ff;
        }

        .faq-icon {
            width: 40px;
            height: 40px;
            background: #f5f3ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6366f1;
            font-size: 18px;
            flex-shrink: 0;
        }

        .faq-item.active .faq-icon {
            background: #ddd6fe;
        }

        .faq-question {
            flex: 1;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }

        .faq-toggle {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 18px;
            flex-shrink: 0;
            transition: all .2s;
        }

        .faq-item.active .faq-toggle {
            background: #6366f1;
            color: white;
            transform: rotate(180deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height .3s cubic-bezier(0.4, 0, 0.2, 1), padding .3s;
        }

        .faq-item.active .faq-answer {
            max-height: 500px;
            padding: 0 20px 20px 20px;
        }

        .faq-answer-text {
            font-size: 13px;
            color: #64748b;
            line-height: 1.6;
        }

        .cta-section {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            color: white;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
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
        }

        .cta-content {
            position: relative;
            z-index: 1;
        }

        .cta-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .cta-text {
            font-size: 15px;
            margin-bottom: 24px;
            opacity: 0.9;
        }

        .cta-button {
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

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 48px;
            color: #c7d2fe;
            margin-bottom: 16px;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .empty-text {
            font-size: 14px;
            color: #64748b;
        }

        @media (max-width: 768px) {
            .hero {
                padding: 40px 20px 30px;
            }

            .hero-title {
                font-size: 36px;
            }

            .hero-subtitle {
                font-size: 14px;
            }

            .faq-container {
                grid-template-columns: 1fr;
            }

            .category-tabs {
                gap: 8px;
            }

            .category-tab {
                padding: 8px 16px;
                font-size: 12px;
            }

            .cta-section {
                padding: 40px 30px;
            }

            .cta-title {
                font-size: 22px;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 28px;
            }

            .breadcrumb {
                font-size: 12px;
            }

            .search-input {
                padding: 12px 14px 12px 44px;
                font-size: 13px;
            }

            .faq-header {
                padding: 16px;
            }

            .faq-question {
                font-size: 13px;
            }

            .faq-answer-text {
                font-size: 12px;
            }

            .cta-section {
                padding: 30px 20px;
                border-radius: 16px;
            }

            .cta-title {
                font-size: 20px;
            }

            .cta-text {
                font-size: 13px;
            }

            .cta-button {
                padding: 10px 24px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-overline">Questions & Answers</div>
            <h1 class="hero-title">Frequently Asked Questions</h1>
            <p class="hero-subtitle">Find answers to common questions about JobDZ. Can't find what you're looking for?</p>
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <span>FAQ</span>
            </div>
        </div>
    </section>

    <!-- MAIN CONTENT -->
    <div class="container">

        <!-- SEARCH BAR -->
        <div class="search-wrapper">
            <div class="search-box">
                <i class="ti ti-search search-icon"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="Search questions...">
            </div>
        </div>

        <!-- CATEGORY TABS -->
        <div class="category-tabs">
            <button class="category-tab active" data-category="all">All</button>
            <button class="category-tab" data-category="general">General</button>
            <button class="category-tab" data-category="jobs">For Job Seekers</button>
            <button class="category-tab" data-category="companies">For Companies</button>
            <button class="category-tab" data-category="billing">Account & Billing</button>
        </div>

        <!-- FAQ ITEMS -->
        <div class="faq-container" id="faqContainer">

            <!-- GENERAL CATEGORY -->
            <div class="faq-item" data-category="general">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-info-circle"></i></div>
                    <div class="faq-question">What is JobDZ?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        JobDZ is a free job platform connecting job seekers and employers across Algeria. We help candidates find their dream jobs and companies find talented people. Everything is completely free - no hidden fees, no subscriptions.
                    </div>
                </div>
            </div>

            <div class="faq-item" data-category="general">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-help"></i></div>
                    <div class="faq-question">Is JobDZ really free?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        Yes! JobDZ is completely free forever. Both job seekers and employers can use all features at no cost. Post unlimited jobs, apply to positions, and connect with opportunities without paying anything.
                    </div>
                </div>
            </div>

            <!-- JOB SEEKERS CATEGORY -->
            <div class="faq-item" data-category="jobs">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-briefcase"></i></div>
                    <div class="faq-question">How do I create a job seeker account?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        Click the "Sign Up" button, fill in your details (name, email, password), and verify your email. Then complete your profile with your work experience, skills, and education. Your profile is now ready to apply for jobs!
                    </div>
                </div>
            </div>

            <div class="faq-item" data-category="jobs">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-file-text"></i></div>
                    <div class="faq-question">How do I apply for a job?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        Browse job listings on our platform, click on a job you're interested in, review the details, and click the "Apply" button. Your profile information will be submitted to the employer. You'll be notified when they respond.
                    </div>
                </div>
            </div>

            <div class="faq-item" data-category="jobs">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-user"></i></div>
                    <div class="faq-question">Can I edit my profile after creating it?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        Absolutely! You can update your profile anytime. Go to your profile settings, edit your information, add new experience, update your skills, and upload a new profile picture. Changes take effect immediately.
                    </div>
                </div>
            </div>

            <div class="faq-item" data-category="jobs">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-search"></i></div>
                    <div class="faq-question">How do I search for specific jobs?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        Use the search bar on the jobs page to filter by job title, company, or location. You can also use advanced filters to narrow results by job type, experience level, salary range, and more.
                    </div>
                </div>
            </div>

            <!-- COMPANIES CATEGORY -->
            <div class="faq-item" data-category="companies">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-building"></i></div>
                    <div class="faq-question">How do I create a company account?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        Click "Post a Job" or "For Companies", fill in your company details, and verify your email. Complete your company profile with logo, description, industry, and location. Once verified, you can start posting jobs immediately.
                    </div>
                </div>
            </div>

            <div class="faq-item" data-category="companies">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-circle-plus"></i></div>
                    <div class="faq-question">How do I post a job listing?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        Go to your company dashboard and click "Post New Job". Fill in the job title, description, requirements, location, and salary range. Review and publish. Your job will be visible to candidates immediately. You can post unlimited jobs!
                    </div>
                </div>
            </div>

            <div class="faq-item" data-category="companies">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-users"></i></div>
                    <div class="faq-question">How do I manage applications?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        Your company dashboard shows all applications. Review applicant profiles, check their qualifications, and contact top candidates. You can accept, reject, or keep applications pending. Track all communication in one place.
                    </div>
                </div>
            </div>

            <div class="faq-item" data-category="companies">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-edit"></i></div>
                    <div class="faq-question">Can I edit job listings after posting?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        Yes! You can edit any active job listing anytime. Go to your job management section, select the job, update the details, and save. You can also close or reopen positions as needed.
                    </div>
                </div>
            </div>

            <!-- BILLING CATEGORY -->
            <div class="faq-item" data-category="billing">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-credit-card"></i></div>
                    <div class="faq-question">Do I need to add payment information?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        No payment information is required. JobDZ is completely free for all users. No credit cards, no subscriptions, no hidden charges. You get full access to all features at no cost.
                    </div>
                </div>
            </div>

            <div class="faq-item" data-category="billing">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-lock"></i></div>
                    <div class="faq-question">Is my personal information secure?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        Yes! We use industry-standard encryption and security measures to protect your data. Your personal information is never shared without your consent. We comply with data protection regulations to keep you safe.
                    </div>
                </div>
            </div>

            <div class="faq-item" data-category="billing">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-trash"></i></div>
                    <div class="faq-question">How do I delete my account?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        Go to your account settings and find the "Delete Account" option. We'll ask you to confirm your request. Your profile and data will be permanently removed. Contact support if you need assistance.
                    </div>
                </div>
            </div>

            <div class="faq-item" data-category="general">
                <div class="faq-header">
                    <div class="faq-icon"><i class="ti ti-headphones"></i></div>
                    <div class="faq-question">How do I contact support?</div>
                    <div class="faq-toggle"><i class="ti ti-chevron-down"></i></div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-text">
                        Have a question not answered here? <a href="contact.php" style="color: #6366f1; font-weight: 600; text-decoration: none;">Visit our contact page</a> to reach our support team. We typically respond within 24-48 hours.
                    </div>
                </div>
            </div>

        </div>

        <!-- CTA SECTION -->
        <div class="cta-section">
            <div class="cta-content">
                <h2 class="cta-title">Still have questions?</h2>
                <p class="cta-text">Can't find the answer you're looking for? Our support team is here to help!</p>
                <a href="contact.php" class="cta-button">
                    <span>Get in Touch</span>
                    <i class="ti ti-arrow-right"></i>
                </a>
            </div>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // FAQ Accordion Functionality
        const faqItems = document.querySelectorAll('.faq-item');
        const categoryTabs = document.querySelectorAll('.category-tab');
        const searchInput = document.getElementById('searchInput');

        // Toggle FAQ items
        faqItems.forEach(item => {
            item.querySelector('.faq-header').addEventListener('click', () => {
                item.classList.toggle('active');
            });
        });

        // Category filtering
        categoryTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                categoryTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const category = tab.dataset.category;
                filterFAQ(category, '');
            });
        });

        // Search functionality
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const activeCategory = document.querySelector('.category-tab.active').dataset.category;
            filterFAQ(activeCategory, searchTerm);
        });

        function filterFAQ(category, searchTerm) {
            faqItems.forEach(item => {
                let showItem = true;

                // Category filter
                if (category !== 'all' && item.dataset.category !== category) {
                    showItem = false;
                }

                // Search filter
                if (searchTerm) {
                    const question = item.querySelector('.faq-question').textContent.toLowerCase();
                    const answer = item.querySelector('.faq-answer-text').textContent.toLowerCase();
                    if (!question.includes(searchTerm) && !answer.includes(searchTerm)) {
                        showItem = false;
                    }
                }

                item.style.display = showItem ? '' : 'none';
            });

            // Show empty state if no results
            const visibleItems = Array.from(faqItems).filter(item => item.style.display !== 'none');
            if (visibleItems.length === 0) {
                if (!document.querySelector('.empty-state')) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = `
                        <div class="empty-icon"><i class="ti ti-search"></i></div>
                        <div class="empty-title">No results found</div>
                        <div class="empty-text">Try adjusting your search or browse another category</div>
                    `;
                    document.getElementById('faqContainer').appendChild(emptyState);
                }
            } else {
                const emptyState = document.querySelector('.empty-state');
                if (emptyState) emptyState.remove();
            }
        }

        // Close accordion when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.faq-item')) {

            }
        });
    </script>

</body>

</html>