<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$role = $_SESSION['user']['role'] ?? 'guest';

$isAdmin = ($role === 'admin');
$isHome  = $currentPage === 'index';

$isTransparentNavbar = false;
$isLoggedInUser = isLoggedIn();
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    .nb-wrap {
        width: 100%;
        background: transparent;
        padding: 8px 0;
        position: sticky;
        top: 0;
        z-index: 50;
        height: 76px;
        display: flex;
        align-items: center;
    }

    .nb {
        max-width: 1280px;
        width: calc(100% - 32px);
        margin: 0 auto;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 0 20px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 16px rgba(15, 23, 42, .06);
        font-family: 'Poppins', sans-serif;
    }

    .nb-logo {
        display: flex;
        align-items: center;
        gap: 9px;
        text-decoration: none;
    }

    .nb-logo-name {
        font-size: 18px;
        font-weight: 700;
        color: #6366f1;
        letter-spacing: -.3px;
        display: block;
        line-height: 1.1;
    }

    .nb-logo-sub {
        font-size: 8.5px;
        font-weight: 600;
        color: #a5b4fc;
        letter-spacing: .2em;
        text-transform: uppercase;
        display: block;
    }

    .nb-nav {
        display: flex;
        align-items: center;
        gap: 1px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 13px;
        padding: 3px;
    }

    .nb-nl {
        padding: 7px 15px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        transition: .2s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .nb-nl.nb-inactive { color: #64748b; background: transparent; }
    .nb-nl.nb-inactive:hover { background: white; color: #0f172a; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
    .nb-nl.nb-active { background: white; color: #6366f1; font-weight: 600; box-shadow: 0 1px 6px rgba(0,0,0,.08); }
    .nb-nl i { font-size: 11px; opacity: .8; }

    .nb-right { display: flex; align-items: center; gap: 8px; }

    .nb-pill {
        display: flex;
        align-items: center;
        gap: 6px;
        background: transparent;
        border: none;
        padding: 4px 8px;
        cursor: pointer;
        transition: .2s;
        font-family: 'Poppins', sans-serif;
        position: relative;
        border-radius: 11px;
    }

    .nb-pill:hover { background: #f5f3ff; }

    .nb-avatar {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: #6366f1;
        color: white;
        font-size: 13px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }

    .nb-avatar img { width: 100%; height: 100%; object-fit: cover; }

    .nb-pill-info { display: flex; flex-direction: column; line-height: 1.2; text-align: left; }
    .nb-pill-role { font-size: 9px; font-weight: 600; color: #a78bfa; text-transform: uppercase; letter-spacing: .12em; }
    .nb-pill-name { font-size: 12.5px; font-weight: 700; color: #0f172a; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nb-chev { color: #94a3b8; font-size: 9px; margin-left: 2px; }

    .nb-dropdown {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: 240px;
        background: white;
        border: 1px solid #ede9fe;
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(99,102,241,.10);
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        transform: translateY(8px);
        transition: all .22s ease;
        z-index: 100;
        font-family: 'Poppins', sans-serif;
    }

    .nb-dropdown.nb-open { opacity: 1; visibility: visible; transform: translateY(0); }

    .nb-drop-body { padding: 6px; }

    .nb-drop-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        border-radius: 14px;
        font-size: 12.5px;
        font-weight: 500;
        color: #6b7280;
        text-decoration: none;
        transition: .16s;
        margin-bottom: 1px;
    }

    .nb-drop-item:hover { background: #f5f3ff; color: #6366f1; }
    .nb-drop-item:hover .nb-drop-icon { background: #ede9fe; color: #6366f1; }

    .nb-drop-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #f8fafc;
        color: #94a3b8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        flex-shrink: 0;
        transition: .16s;
    }

    .nb-drop-sep { height: 1px; background: #f3f4f6; margin: 4px 8px; }

    .nb-drop-item.nb-danger { color: #ef4444; }
    .nb-drop-item.nb-danger .nb-drop-icon { background: #fef2f2; color: #ef4444; }
    .nb-drop-item.nb-danger:hover { background: #fef2f2; color: #ef4444; }

    .nb-auth-switch { display: flex; align-items: center; gap: 14px; }

    .nb-btn-login {
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        color: #111827;
        background: none;
        border: none;
        padding: 0;
        margin: 0;
        line-height: 1;
        transition: .2s ease;
        font-family: 'Poppins', sans-serif;
        cursor: pointer;
    }

    .nb-btn-login:hover { opacity: .75; }

    .nb-btn-register {
        height: 38px;
        min-width: 110px;
        padding: 0 18px;
        border-radius: 999px;
        background: white;
        color: #111827;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
        transition: .2s ease;
        font-family: 'Poppins', sans-serif;
    }

    .nb-btn-register:hover { transform: translateY(-1px); background: #f9fafb; }
    .nb-btn-login i, .nb-btn-register i { display: none; }

    /* Transparent */
    .nb-wrap.nb-transparent .nb { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.15); backdrop-filter: blur(12px); }
    .nb-wrap.nb-transparent .nb-logo-name { color: white; }
    .nb-wrap.nb-transparent .nb-logo-sub { color: rgba(255,255,255,.6); }
    .nb-wrap.nb-transparent .nb-nav { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.15); }
    .nb-wrap.nb-transparent .nb-nl.nb-inactive { color: rgba(255,255,255,.8); }
    .nb-wrap.nb-transparent .nb-nl.nb-active { background: rgba(255,255,255,.2); color: white; }

    /* ══════════════════════════════
       BOTTOM NAV (mobile only)
    ══════════════════════════════ */
    #nb-bottom-nav {
        display: none;
    }

    /* ══════════════════════════════
       MOBILE RESPONSIVE
    ══════════════════════════════ */
    @media (max-width: 768px) {

        /* Navbar */
        .nb-wrap {
            padding: 6px 12px;
            height: auto;
        }

        .nb {
            width: 100%;
            padding: 0 14px;
            height: 52px;
            border-radius: 14px;
        }

        /* إخفاء nav الوسطى */
        .nb-nav {
            display: none !important;
        }

        /* إخفاء اسم المستخدم في الـ pill */
        .nb-pill-info,
        .nb-chev {
            display: none !important;
        }

        .nb-pill { padding: 4px; }

        .nb-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            font-size: 14px;
        }

        /* الـ dropdown يفتح لليسار على الهاتف */
        .nb-dropdown {
            right: -10px;
            width: 210px;
        }

        /* أزرار Guest أصغر */
        .nb-btn-login  { font-size: 12px; }
        .nb-btn-register { height: 34px; min-width: 90px; padding: 0 14px; font-size: 12px; }

        /* Logo */
        .nb-logo-name { font-size: 16px; }
        .nb-logo-sub  { display: none; }

        /* Bottom nav ظاهر */
        #nb-bottom-nav {
            display: flex;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 8px 0;
            padding-bottom: max(8px, env(safe-area-inset-bottom));
            z-index: 999;
            justify-content: space-around;
            box-shadow: 0 -4px 20px rgba(15,23,42,.06);
        }

        /* مسافة سفلية للمحتوى */
        body { padding-bottom: 68px !important; }
    }

    /* Bottom nav links */
    .bn-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        text-decoration: none;
        color: #94a3b8;
        font-size: 10px;
        font-weight: 600;
        font-family: 'Poppins', sans-serif;
        flex: 1;
        transition: .15s;
        padding: 2px 0;
    }

    .bn-link i { font-size: 19px; }

    .bn-link.bn-active { color: #6366f1; }
    .bn-link.bn-active i { color: #6366f1; }
    .bn-link:not(.bn-active):hover { color: #475569; }
</style>

<div class="nb-wrap <?php echo $isTransparentNavbar ? 'nb-transparent' : ''; ?>" style="width:100%;box-sizing:border-box;">
    <div class="nb">

        <!-- LEFT: logo -->
        <a href="<?php echo $isAdmin ? 'admin.php' : 'index.php'; ?>" class="nb-logo">
            <div>
                <span class="nb-logo-name"><?php echo $isAdmin ? 'JobDZ Admin' : 'JobDZ'; ?></span>
                <span class="nb-logo-sub"><?php echo $isAdmin ? 'Management Panel' : 'Recruitment Reimagined'; ?></span>
            </div>
        </a>

        <!-- CENTER: nav pill -->
        <?php if (!$isAdmin): ?>
            <?php
            $navItems = [
                ['url' => 'index.php',     'label' => 'Home',      'icon' => 'fa-home'],
                ['url' => 'job.php',       'label' => 'Jobs',      'icon' => 'fa-briefcase'],
                ['url' => 'companies.php', 'label' => 'Companies', 'icon' => 'fa-building'],
                ['url' => 'about.php',     'label' => 'About',     'icon' => 'fa-info-circle'],
            ];
            ?>
            <nav class="nb-nav">
                <?php foreach ($navItems as $item):
                    $isActive = ($currentPage === basename($item['url'], '.php'));
                ?>
                    <a href="<?php echo $item['url']; ?>"
                       class="nb-nl <?php echo $isActive ? 'nb-active' : 'nb-inactive'; ?>">
                        <i class="fas <?php echo $item['icon']; ?>"></i>
                        <?php echo $item['label']; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php else: ?>
            <div></div>
        <?php endif; ?>

        <!-- RIGHT -->
        <div class="nb-right">

            <?php if ($isLoggedInUser): ?>
                <?php
                if ($role === 'admin') {
                    $accountType = 'Admin';
                    $displayName = 'Administrator';
                    $avatarUrl   = '';
                    $dotColor    = '#ef4444';
                } elseif ($role === 'company') {
                    $accountType = 'Company';
                    $profileData = getCompanyProfile($_SESSION['user']['id']);
                    $displayName = $profileData['company_name'] ?? $_SESSION['user']['email'];
                    $avatarUrl   = $profileData['logo_url'] ?? '';
                    $dotColor    = '#8b5cf6';
                } else {
                    $accountType = 'Candidate';
                    $profileData = getCandidateProfile($_SESSION['user']['id']);
                    $displayName = $profileData['full_name'] ?? $_SESSION['user']['email'];
                    $avatarUrl   = $profileData['image_path'] ?? '';
                    $dotColor    = '#10b981';
                }
                $initial = strtoupper(substr($displayName, 0, 1));
                ?>

                <div class="nb-pill" id="nb-profile-btn" style="position:relative;">
                    <div class="nb-avatar">
                        <?php if ($avatarUrl): ?>
                            <img src="<?php echo e($avatarUrl); ?>" alt="Avatar">
                        <?php else: ?>
                            <?php echo e($initial); ?>
                        <?php endif; ?>
                    </div>
                    <div class="nb-pill-info">
                        <span class="nb-pill-role"><?php echo e($accountType); ?></span>
                        <span class="nb-pill-name"><?php echo e($displayName); ?></span>
                    </div>
                    <i class="fas fa-chevron-down nb-chev"></i>

                    <!-- Dropdown -->
                    <div class="nb-dropdown" id="nb-dropdown">
                        <div style="background:linear-gradient(135deg,#ede9fe 0%,#f5f3ff 100%);padding:14px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #ddd6fe;">
                            <div style="width:44px;height:44px;border-radius:50%;background:white;color:#6366f1;font-size:16px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 8px rgba(99,102,241,.18);overflow:hidden;">
                                <?php if ($avatarUrl): ?>
                                    <img src="<?php echo e($avatarUrl); ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?>
                                    <?php echo e($initial); ?>
                                <?php endif; ?>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-size:9px;font-weight:600;color:#6366f1;text-transform:uppercase;letter-spacing:.22em;margin-bottom:3px;"><?php echo e($accountType); ?></div>
                                <div style="font-size:13px;font-weight:600;color:#1e1b4b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($displayName); ?></div>
                            </div>
                        </div>

                        <div class="nb-drop-body">
                            <?php if ($isAdmin): ?>
                                <a href="admin.php?page=dashboard" class="nb-drop-item">
                                    <div class="nb-drop-icon"><i class="fas fa-chart-line"></i></div>Dashboard
                                </a>
                                <a href="admin.php?page=users" class="nb-drop-item">
                                    <div class="nb-drop-icon"><i class="fas fa-users"></i></div>Users
                                </a>
                                <a href="admin.php?page=jobs" class="nb-drop-item">
                                    <div class="nb-drop-icon"><i class="fas fa-briefcase"></i></div>Jobs
                                </a>
                            <?php else: ?>
                                <a href="<?php echo $role === 'company' ? 'company_profile.php' : 'profile.php?id=' . (int)($_SESSION['user']['id'] ?? 0); ?>" class="nb-drop-item">
                                    <div class="nb-drop-icon"><i class="fas fa-user"></i></div>My Profile
                                </a>
                                <?php if ($role === 'candidate'): ?>
                                    <a href="saved_jobs.php" class="nb-drop-item">
                                        <div class="nb-drop-icon"><i class="fas fa-bookmark"></i></div>Saved Jobs
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo $role === 'company' ? 'recruiter_applications.php' : 'applications.php'; ?>" class="nb-drop-item">
                                    <div class="nb-drop-icon"><i class="fas fa-briefcase"></i></div>Applications
                                </a>
                                <?php if ($role === 'company'): ?>
                                    <a href="job_positions.php" class="nb-drop-item">
                                        <div class="nb-drop-icon"><i class="fas fa-briefcase"></i></div>My Jobs
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo $role === 'company' ? 'company_notifications.php' : 'notifications.php'; ?>" class="nb-drop-item">
                                    <div class="nb-drop-icon"><i class="fas fa-bell"></i></div>Job Alerts
                                </a>
                                <div class="nb-drop-sep"></div>
                                <a href="settings.php" class="nb-drop-item">
                                    <div class="nb-drop-icon"><i class="fas fa-cog"></i></div>Settings
                                </a>
                            <?php endif; ?>
                            <div class="nb-drop-sep"></div>
                            <a href="logout.php" class="nb-drop-item nb-danger">
                                <div class="nb-drop-icon"><i class="fas fa-sign-out-alt"></i></div>Logout
                            </a>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="nb-auth-switch">
                    <a href="login.php" class="nb-btn-login">Login</a>
                    <a href="register.php" class="nb-btn-register">Register</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ══ BOTTOM NAV (mobile only, hidden on desktop via CSS) ══ -->
<?php if (!$isAdmin): ?>
    <?php
    $bottomNavItems = [
        ['url' => 'index.php',     'label' => 'Home',      'icon' => 'fa-home'],
        ['url' => 'job.php',       'label' => 'Jobs',      'icon' => 'fa-briefcase'],
        ['url' => 'companies.php', 'label' => 'Companies', 'icon' => 'fa-building'],
        ['url' => 'about.php',     'label' => 'About',     'icon' => 'fa-info-circle'],
    ];
    ?>
    <nav id="nb-bottom-nav">
        <?php foreach ($bottomNavItems as $item):
            $isActive = ($currentPage === basename($item['url'], '.php'));
        ?>
            <a href="<?php echo $item['url']; ?>"
               class="bn-link <?php echo $isActive ? 'bn-active' : ''; ?>">
                <i class="fas <?php echo $item['icon']; ?>"></i>
                <span><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const btn  = document.getElementById('nb-profile-btn');
        const drop = document.getElementById('nb-dropdown');

        if (btn && drop) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                drop.classList.toggle('nb-open');
            });
            document.addEventListener('click', function (e) {
                if (!btn.contains(e.target)) drop.classList.remove('nb-open');
            });
        }

    });
</script>
