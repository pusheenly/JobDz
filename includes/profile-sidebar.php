<?php
if (!isLoggedIn()) {
    return;
}
$role = $_SESSION['user']['role'] ?? 'candidate';
$accountType = $role === 'company' ? 'Company Account' : 'Candidate Account';
if ($role === 'company') {
    $profileData = getCompanyProfile($_SESSION['user']['id']);
    $displayName = $profileData['company_name'] ?? $_SESSION['user']['email'];
    $avatarUrl = $profileData['logo_url'] ?? '';
} else {
    $profileData = getCandidateProfile($_SESSION['user']['id']);
    $displayName = $profileData['full_name'] ?? $_SESSION['user']['email'];
    $avatarUrl = $profileData['image_path'] ?? '';
}
$avatarInitial = strtoupper(substr($displayName, 0, 1));
$activeTab = $activeProfileTab ?? '';
$userIdForProfile = (int) ($_SESSION['user']['id'] ?? 0);

// Navigation items with proper order
$navItems = [
    ['label' => 'My Profile', 'href' => $role === 'company' ? 'company_profile.php' : 'profile.php?id=' . $userIdForProfile, 'icon' => 'user', 'key' => 'profile', 'show' => true, 'section' => 'account'],
    ['label' => 'Saved Jobs', 'href' => 'saved_jobs.php', 'icon' => 'bookmark', 'key' => 'saved_jobs', 'show' => $role === 'candidate', 'section' => 'account'],
    ['label' => 'Applications', 'href' => $role === 'company' ? 'recruiter_applications.php' : 'applications.php', 'icon' => 'briefcase', 'key' => 'applications', 'show' => true, 'section' => 'account'],
    ['label' => 'My Jobs', 'href' => 'job_positions.php', 'icon' => 'briefcase', 'key' => 'jobs', 'show' => $role === 'company', 'section' => 'account'],
    ['label' => 'Job Alerts', 'href' => $role === 'company' ? 'company_notifications.php' : 'notifications.php', 'icon' => 'bell', 'key' => 'alerts', 'show' => true, 'section' => 'account'],
    ['label' => 'Settings', 'href' => 'settings.php', 'icon' => 'cog', 'key' => 'settings', 'show' => true, 'section' => 'security'],
    ['label' => 'Logout', 'href' => 'logout.php', 'icon' => 'sign-out-alt', 'key' => 'logout', 'show' => true, 'section' => 'security'],
];
?>

<!-- Desktop Sidebar -->
<aside class="hidden lg:block">
    <div class="sticky top-24 space-y-4">
        
        <!-- Profile Card -->
        <div class="rounded-[28px] bg-gradient-to-br from-[var(--bg-purple-light)] to-white p-6 shadow-soft border border-purple-100">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-white text-[var(--primary)] shadow-md flex-shrink-0">
                    <?php if ($avatarUrl): ?>
                        <img src="<?php echo e($avatarUrl); ?>" alt="Avatar" class="h-full w-full object-cover">
                    <?php else: ?>
                        <span class="text-2xl font-bold"><?php echo e($avatarInitial); ?></span>
                    <?php endif; ?>
                </div>
                <div class="min-w-0">
                    <p class="text-xs uppercase tracking-[0.28em] text-[var(--primary)] font-semibold"><?php echo e($accountType); ?></p>
                    <p class="mt-2 text-lg font-semibold text-[var(--text-dark)] truncate"><?php echo e($displayName); ?></p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="rounded-[28px] bg-white p-3 shadow-soft border border-[var(--border)]">
            <nav class="space-y-1">
                <?php foreach ($navItems as $item): ?>
                    <?php if (!$item['show']) continue; ?>
                    <a href="<?php echo $item['href']; ?>" 
                       
                       class="flex items-center gap-3 rounded-[20px] px-4 py-3 text-sm font-medium transition duration-200
                       <?php echo $activeTab === $item['key'] 
                           ? 'bg-[var(--primary)] text-white shadow-lg shadow-purple-200' 
                           : 'text-[var(--text-gray)] hover:text-[var(--text-dark)] hover:bg-[var(--bg-light)]'; ?>">
                        <i class="fas fa-<?php echo $item['icon']; ?> w-5"></i>
                        <span><?php echo e($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

    </div>
</aside>

<!-- Mobile Sidebar -->
<div class="lg:hidden mb-6 rounded-[28px] bg-gradient-to-br from-[var(--bg-purple-light)] to-white p-4 shadow-soft border border-purple-100">
    <div class="flex items-center gap-4">
        <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full bg-white text-[var(--primary)] shadow-md flex-shrink-0">
            <?php if ($avatarUrl): ?>
                <img src="<?php echo e($avatarUrl); ?>" alt="Avatar" class="h-full w-full object-cover">
            <?php else: ?>
                <span class="text-xl font-bold"><?php echo e($avatarInitial); ?></span>
            <?php endif; ?>
        </div>
        <div class="min-w-0">
            <p class="text-xs uppercase tracking-[0.28em] text-[var(--primary)] font-semibold"><?php echo e($accountType); ?></p>
            <p class="mt-1 text-base font-semibold text-[var(--text-dark)] truncate"><?php echo e($displayName); ?></p>
        </div>
    </div>

    <div class="mt-5 space-y-2">
        <?php foreach ($navItems as $item): ?>
            <?php if (!$item['show']) continue; ?>
            <a href="<?php echo $item['href']; ?>" 
              
               class="block rounded-[20px] px-4 py-3 text-sm font-medium transition duration-200
               <?php echo $activeTab === $item['key'] 
                   ? 'bg-[var(--primary)] text-white shadow-lg shadow-purple-200' 
                   : 'text-[var(--text-gray)] hover:text-[var(--text-dark)] hover:bg-[var(--bg-light)]'; ?>">
                <i class="fas fa-<?php echo $item['icon']; ?> w-4 me-2"></i><?php echo e($item['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div id="logoutModal"
     class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 px-4">

    <div class="w-full max-w-md rounded-[32px] bg-white p-8 shadow-2xl animate-in">

        <!-- Title -->
        <div class="flex items-center justify-between">
            <h3 class="text-2xl font-bold text-[var(--text-dark)]">
                Log out?
            </h3>

            <button id="closeLogoutModal"
                    type="button"
                    class="text-[var(--text-gray)] hover:text-[var(--text-dark)] transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Text -->
        <p class="mt-4 text-sm leading-6 text-[var(--text-gray)]">
            Are you sure you want to log out from your JobDZ account?
        </p>

        <!-- Buttons -->
        <div class="mt-8 flex gap-3">

            <button id="cancelLogout"
                    type="button"
                    class="flex-1 rounded-full border-2 border-[var(--border)] px-5 py-3 text-sm font-semibold text-[var(--text-dark)] transition hover:bg-[var(--bg-light)]">
                Cancel
            </button>

            <a href="logout.php"
               class="flex-1 rounded-full bg-rose-500 px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-rose-600 shadow-lg shadow-rose-200">
                Logout
            </a>

        </div>

    </div>
</div>

