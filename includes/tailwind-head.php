<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle ?? 'JobDZ'; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<!-- Font Awesome (site-wide) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: {
          sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        },
        colors: {
          brand: {
            50: '#eef2ff',
            100: '#e0e7ff',
            200: '#c7d2fe',
            300: '#a5b4fc',
            400: '#818cf8',
            500: '#6366f1',
            600: '#4f46e5',
            700: '#4338ca',
            800: '#3730a3',
            900: '#312e81',
          },
        },
        boxShadow: {
          soft: '0 24px 60px rgba(15, 23, 42, 0.08)',
        },
      },
    },
  };
</script>
<style>
  :root {
    color-scheme: light;
  }

  html {
    scroll-behavior: smooth;
  }

  body {
    font-family: 'Inter', system-ui, sans-serif;
    background-color: #f8fafc;
    color: #0f172a;
    min-height: 100vh;
  }

  .tw-card {
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
  }

  .navbar-shadow {
    box-shadow: 0 10px 35px rgba(15, 23, 42, 0.08);
  }

  .link-fade:hover {
    color: #1f2937;
  }

  .container {
    width: min(1240px, 100% - 2rem);
    margin: 0 auto;
    padding: 0 1rem;
  }

  /* =============================================
     .card مستثنى عمداً — كل صفحة تعرّف .card
     خاصة بيها باش ما يتكسرش التصميم
  ============================================= */
  .panel,
  .notifications-card,
  .review-card,
  .company-card,
  .job-card,
  .auth-card,
  .profile-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 2rem;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    padding: 2rem;
  }

  .panel:hover,
  .review-card:hover,
  .company-card:hover,
  .job-card:hover,
  .auth-card:hover,
  .profile-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 32px 80px rgba(15, 23, 42, 0.12);
    border-color: rgba(37, 99, 235, 0.16);
  }

  .alert {
    border-radius: 1.75rem;
    padding: 1rem 1.25rem;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
    margin-bottom: 1.5rem;
  }

  .alert.success {
    background: #ecfdf5;
    border: 1px solid #d1fae5;
    color: #166534;
  }

  .alert.error {
    background: #fef2f2;
    border: 1px solid #fecdd3;
    color: #991b1b;
  }

  .footer {
    background: #0f172a;
    color: #cbd5e1;
  }

  .footer a {
    color: inherit;
    text-decoration: none;
  }

  .footer a:hover {
    color: #ffffff;
  }

  .footer-content {
    display: grid;
    gap: 2rem;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    padding: 3rem 0 1.5rem;
  }

  .footer-section h4 {
    margin-bottom: 1rem;
    color: #ffffff;
    font-weight: 700;
  }

  .footer-section ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0.75rem;
  }

  .footer-bottom {
    border-top: 1px solid rgba(148, 163, 184, 0.28);
    padding: 1rem 0 0;
    font-size: 0.95rem;
    color: #94a3b8;
  }

  .empty-state {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 1.75rem;
    padding: 3rem 2rem;
    text-align: center;
    color: #475569;
  }

  .feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
  }

  .notification-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1.75rem;
    padding: 1.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .notification-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
  }

  .review-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 1.75rem;
    padding: 1.75rem;
  }

  .progress-bar {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .progress-step {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 1rem;
    border-radius: 1.5rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
  }

  .progress-step.active {
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.08);
  }

  .progress-step.completed {
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.1);
  }

  .step-circle {
    width: 2.5rem;
    height: 2.5rem;
    display: grid;
    place-items: center;
    border-radius: 9999px;
    background: #e0e7ff;
    color: #4338ca;
    font-weight: 700;
  }

  .upload-preview {
    width: 140px;
    height: 140px;
    min-width: 140px;
    border-radius: 1.5rem;
    background: #f8fafc;
    display: grid;
    place-items: center;
    color: #64748b;
    border: 1px dashed #e2e8f0;
    overflow: hidden;
  }

  .upload-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .section-title {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .section-title h3,
  .section-title h4,
  .section-title h2 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: #0f172a;
  }

  .section-title p,
  .section-title span,
  .small-note {
    color: #64748b;
    line-height: 1.65;
  }

  .modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 50;
    background: rgba(15, 23, 42, 0.5);
    align-items: center;
    justify-content: center;
    padding: 2rem;
  }

  .modal.show {
    display: flex;
  }

  .modal-content {
    width: min(560px, 100%);
    background: #ffffff;
    border-radius: 1.75rem;
    padding: 2rem;
    box-shadow: 0 32px 80px rgba(15, 23, 42, 0.12);
  }

  .auth-container {
    display: grid;
    place-items: center;
    min-height: calc(100vh - 8rem);
    padding: 2rem 0;
  }

  .auth-card {
    width: min(520px, 100%);
    background: #ffffff;
    border-radius: 2rem;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    padding: 2rem;
  }

  .auth-toggle {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.75rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 1.5rem;
    overflow: hidden;
  }

  .auth-toggle-btn {
    border: none;
    background: transparent;
    padding: 1rem 1.25rem;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    transition: background-color 0.2s ease, color 0.2s ease;
  }

  .auth-toggle-btn.active {
    background: #4f46e5;
    color: #ffffff;
  }

  .auth-social {
    display: grid;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
  }

  .social-btn {
    display: inline-flex;
    width: 100%;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 0.95rem 1rem;
    border-radius: 1.25rem;
    border: 1px solid #e2e8f0;
    color: #334155;
    background: #ffffff;
    cursor: pointer;
  }

  .auth-divider {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1.5rem 0;
  }

  .auth-divider span {
    color: #94a3b8;
  }

  .auth-divider::before,
  .auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e2e8f0;
  }

  .auth-form {
    display: none;
  }

  .auth-form.active {
    display: grid;
    gap: 1rem;
  }

  .auth-error {
    background: #fee2e2;
    border: 1px solid #fecaca;
    border-radius: 1.5rem;
    color: #991b1b;
    padding: 1rem 1.25rem;
    margin-bottom: 1rem;
  }

  .password-field {
    position: relative;
  }

  .password-toggle {
    position: absolute;
    right: 0.8rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
  }

  @media (max-width: 900px) {
    .field-group {
      grid-template-columns: 1fr;
    }

    .section-title {
      flex-direction: column;
      align-items: stretch;
    }

    .footer-content {
      grid-template-columns: 1fr;
    }
  }
</style>