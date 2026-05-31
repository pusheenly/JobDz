<?php
require_once 'config.php';

function currentUser()
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn()
{
    return !empty($_SESSION['user']);
}

function isAdmin()
{
    return isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function getCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token)
{
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function rateLimitCheck($identifier, $action, $maxAttempts = 5, $timeWindow = 900)
{
    static $attempts = [];
    $key = md5(strtolower(trim($identifier)) . '|' . $action);
    $now = time();

    if (!isset($attempts[$key])) {
        $attempts[$key] = [];
    }

    $attempts[$key] = array_filter($attempts[$key], function ($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });

    if (count($attempts[$key]) >= $maxAttempts) {
        return false;
    }

    $attempts[$key][] = $now;
    return true;
}

function sanitizeText($text)
{
    return trim(filter_var((string)$text, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

function timeAgo($datetime)
{
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;

    if ($difference < 60) {
        return 'Just now';
    }

    if ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' min ago';
    }

    if ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    }

    if ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }

    if ($difference < 2592000) {
        $weeks = floor($difference / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    }

    if ($difference < 31536000) {
        $months = floor($difference / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    }

    $years = floor($difference / 31536000);
    return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
}
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function isCandidateProfileComplete($userId)
{
    global $pdo;

    $profile = getCandidateProfile($userId);

    if (
        empty($profile['full_name']) ||
        empty($profile['phone']) ||
        empty($profile['city']) ||
        empty($profile['category']) ||
        empty($profile['job_title'])
    ) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidate_educations WHERE user_id = ?");
    $stmt->execute([$userId]);
    $educationCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidate_experiences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $experienceCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidate_skills WHERE user_id = ?");
    $stmt->execute([$userId]);
    $skillsCount = $stmt->fetchColumn();

    return $educationCount > 0 && $experienceCount > 0 && $skillsCount > 0;
}

function isCompanyProfileComplete($userId)
{
    $profile = getCompanyProfile($userId);
    return !empty($profile['company_name']) && !empty($profile['industry']) && !empty($profile['city']);
}

function requireProfileComplete()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    $userId = $_SESSION['user']['id'];
    $role = $_SESSION['user']['role'];

    if ($role === 'candidate' && !isCandidateProfileComplete($userId)) {
        header('Location: edit_profile.php');
        exit;
    } elseif ($role === 'company' && !isCompanyProfileComplete($userId)) {
        header('Location: edit_company_profile.php');
        exit;
    }
}

function registerUser($email, $password, $role)
{
    global $pdo;

    // Validate input
    $email = sanitizeInput($email, 'email');
    if (!validateEmail($email)) {
        return 'Invalid email address.';
    }

    if (!validatePasswordStrength($password)) {
        return 'Password must be at least 8 characters long and contain at least one letter and one number.';
    }

    if (!in_array($role, ['candidate', 'company'])) {
        return 'Invalid user role.';
    }

    // Check rate limiting
    if (!rateLimitCheck($email, 'register', 3, 3600)) {
        return 'Too many registration attempts. Please try again later.';
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return 'Email is already in use.';
    }

    $hash = hashPassword($password);
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, created_at) VALUES (?, ?, ?, NOW())');
    $result = $stmt->execute([$email, $hash, $role]);

    if (!$result) {
        return 'Database insert failed.';
    }

    $_SESSION['user_id'] = (int) $pdo->lastInsertId();

    return true;
}

function loginUser($email, $password)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    if (empty($user['password_hash'])) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
    return true;
}

function getCandidateProfile($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT * 
        FROM candidates_profiles
        WHERE user_id = ?
    ");

    $stmt->execute([$userId]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function ensureCandidateProfileColumns()
{
    global $pdo;

    $required = [
        'job_title'        => 'VARCHAR(255) NULL',
        'summary'          => 'TEXT NULL',
        'availability'     => 'VARCHAR(50) NULL',
        'experience_level' => 'VARCHAR(50) NULL',
        'cv_file'          => 'VARCHAR(255) NULL',
        'linkedin_url'     => 'VARCHAR(255) NULL',
        'github_url'       => 'VARCHAR(255) NULL',
        'portfolio_url'    => 'VARCHAR(255) NULL'
    ];

    foreach ($required as $column => $definition) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
            AND table_name = 'candidates_profiles'
            AND column_name = ?
        ");

        $stmt->execute([$column]);

        if (!$stmt->fetchColumn()) {
            $pdo->exec("
                ALTER TABLE candidates_profiles
                ADD COLUMN $column $definition
            ");
        }
    }
}

function getCandidateExperiences($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT * 
        FROM candidate_experiences
        WHERE user_id = ?
        ORDER BY start_date DESC, id DESC
    ");

    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCandidateEducations($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT * 
        FROM candidate_educations
        WHERE user_id = ?
        ORDER BY start_date DESC, id DESC
    ");

    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCandidateSkills($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT * 
        FROM candidate_skills
        WHERE user_id = ?
        ORDER BY id DESC
    ");

    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCandidateProjects($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT * 
        FROM candidate_projects
        WHERE user_id = ?
        ORDER BY id DESC
    ");

    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCandidateLanguages($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT * 
        FROM candidate_languages
        WHERE user_id = ?
        ORDER BY id DESC
    ");

    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCandidateInterests($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT * 
        FROM candidate_interests
        WHERE user_id = ?
        ORDER BY id DESC
    ");

    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCandidateSocialLinks($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT * 
        FROM candidate_social_links
        WHERE user_id = ?
        ORDER BY id DESC
    ");

    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ensureCompanyProfileColumns()
{
    global $pdo;
    $required = [
        'logo_url' => 'VARCHAR(255) NULL',
        'linkedin' => 'VARCHAR(255) NULL',
        'facebook' => 'VARCHAR(255) NULL',
        'twitter' => 'VARCHAR(255) NULL',
    ];
    foreach ($required as $column => $definition) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'companies_profiles' AND column_name = ?");
        $stmt->execute([$column]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE companies_profiles ADD COLUMN $column $definition");
        }
    }
}

function ensureJobPositionsColumns()
{
    global $pdo;
    $required = [
        'status' => 'VARCHAR(50) DEFAULT "open"',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'work_mode' => 'VARCHAR(50) NULL',
    ];
    foreach ($required as $column => $definition) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'jobs' AND column_name = ?");
        $stmt->execute([$column]);
        if (!$stmt->fetchColumn()) {
            try {
                $pdo->exec("ALTER TABLE jobs ADD COLUMN $column $definition");
            } catch (Exception $e) {
                // Column might already exist or other error, continue
            }
        }
    }
}

function getCompanyProfile($userId)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM companies_profiles WHERE user_id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: [];
}

function updateCandidateProfile($userId, $data)
{
    global $pdo;

    ensureCandidateProfileColumns();

    $profile = getCandidateProfile($userId);

    $payload = [
        $data['full_name'] ?? '',
        $data['job_title'] ?? '',
        $data['phone'] ?? '',
        $data['city'] ?? '',
        $data['summary'] ?? '',
        $data['category'] ?? '',
        $data['specialty'] ?? '',
        $data['availability'] ?? '',
        $data['experience_level'] ?? '',
        $data['image_path'] ?? '',
        $data['cv_file'] ?? '',
    ];

    if ($profile) {
        $stmt = $pdo->prepare("
            UPDATE candidates_profiles SET
                full_name = ?,
                job_title = ?,
                phone = ?,
                city = ?,
                summary = ?,
                category = ?,
                specialty = ?,
                availability = ?,
                experience_level = ?,
                image_path = ?,
                cv_file = ?,
                updated_at = NOW()
            WHERE user_id = ?
        ");

        $payload[] = $userId;
        $stmt->execute($payload);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO candidates_profiles (
                user_id,
                full_name,
                job_title,
                phone,
                city,
                summary,
                category,
                specialty,
                availability,
                experience_level,
                image_path,
                cv_file,
                updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");

        array_unshift($payload, $userId);
        $stmt->execute($payload);
    }
}

function pdfEscapeText($text)
{
    $text = trim((string) $text);
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function pdfLines($text, $maxChars = 90)
{
    $text = str_replace(["\r\n", "\r"], "\n", trim((string) $text));
    if ($text === '') {
        return ['-'];
    }
    $wrapped = wordwrap($text, $maxChars, "\n", true);
    return explode("\n", $wrapped);
}

function generateCandidateResumePdf($profile)
{
    $fullName = pdfEscapeText($profile['full_name'] ?? 'Candidate Name');
    $jobTitle = pdfEscapeText($profile['job_title'] ?? 'Professional Title');
    $phone = pdfEscapeText($profile['phone'] ?? '');
    $city = pdfEscapeText($profile['city'] ?? '');
    $email = pdfEscapeText($_SESSION['user']['email'] ?? '');
    $summary = pdfEscapeText($profile['summary'] ?? '');
    $education = pdfEscapeText($profile['education'] ?? '');
    $experience = pdfEscapeText($profile['experience'] ?? '');
    $skills = pdfEscapeText($profile['skills'] ?? '');
    $projects = pdfEscapeText($profile['projects'] ?? '');
    $languages = pdfEscapeText($profile['languages'] ?? '');
    $interests = pdfEscapeText($profile['interests'] ?? '');

    $content = '';
    $content .= "BT /F1 22 Tf 40 780 Td (" . $fullName . ") Tj ET\n";
    $content .= "BT /F1 14 Tf 40 760 Td (" . $jobTitle . ") Tj ET\n";
    $contactParts = array_filter([$phone, $city, $email]);
    if ($contactParts) {
        $content .= "BT /F1 10 Tf 40 740 Td (" . pdfEscapeText(implode(' • ', $contactParts)) . ") Tj ET\n";
    }

    $y = 710;
    $sections = [
        'Professional Summary' => $summary,
        'Education' => $education,
        'Experience' => $experience,
        'Skills' => $skills,
        'Projects & Achievements' => $projects,
        'Languages' => $languages,
        'Interests' => $interests,
    ];

    foreach ($sections as $heading => $text) {
        if ($text === '') {
            continue;
        }
        $content .= "BT /F1 14 Tf 40 {$y} Td (" . pdfEscapeText($heading) . ") Tj ET\n";
        $y -= 18;
        $lines = pdfLines($text, 90);
        foreach ($lines as $line) {
            $content .= "BT /F1 10 Tf 40 {$y} Td (" . pdfEscapeText($line) . ") Tj ET\n";
            $y -= 14;
        }
        $y -= 10;
    }

    $pageObject = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $pageTree = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $page = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
    $stream = "4 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n";
    $font = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

    $objects = [$pageObject, $pageTree, $page, $stream, $font];
    $offsets = [0];
    $pdf = "%PDF-1.4\n";
    $pos = strlen($pdf);
    foreach ($objects as $obj) {
        $offsets[] = $pos;
        $pdf .= $obj;
        $pos += strlen($obj);
    }

    $xref = "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    foreach (array_slice($offsets, 1) as $offset) {
        $xref .= sprintf('%010d 00000 n \n', $offset);
    }

    $startxref = strlen($pdf);
    $pdf .= $xref;
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $startxref . "\n%%EOF";

    return $pdf;
}

function updateCompanyProfile($userId, $data)
{
    global $pdo;
    ensureCompanyProfileColumns();
    $profile = getCompanyProfile($userId);
    if ($profile) {
        $stmt = $pdo->prepare('UPDATE companies_profiles SET company_name = ?, industry = ?, size = ?, phone = ?, city = ?, website = ?, description = ?, logo_url = ?, address = ?, mission = ?, vision = ?, specialties = ?, benefits = ?, working_hours = ?, founded_year = ?, employees_count = ?, linkedin = ?, facebook = ?, twitter = ?, instagram = ?, github = ?, updated_at = NOW() WHERE user_id = ?');
        $stmt->execute([$data['company_name'], $data['industry'], $data['size'], $data['phone'], $data['city'], $data['website'], $data['description'], $data['logo_url'], $data['address'], $data['mission'], $data['vision'], $data['specialties'], $data['benefits'], $data['working_hours'], $data['founded_year'], $data['employees_count'], $data['linkedin'], $data['facebook'], $data['twitter'], $data['instagram'], $data['github'], $userId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO companies_profiles (user_id, company_name, industry, size, phone, city, website, description, logo_url, address, mission, vision, specialties, benefits, working_hours, founded_year, employees_count, linkedin, facebook, twitter, instagram, github, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$userId, $data['company_name'], $data['industry'], $data['size'], $data['phone'], $data['city'], $data['website'], $data['description'], $data['logo_url'], $data['address'], $data['mission'], $data['vision'], $data['specialties'], $data['benefits'], $data['working_hours'], $data['founded_year'], $data['employees_count'], $data['linkedin'], $data['facebook'], $data['twitter'], $data['instagram'], $data['github']]);
    }
}

function addJob($userId, $data)
{
    global $pdo;

    $status = 'open';

    $expiresAt = date(
        'Y-m-d H:i:s',
        strtotime('+' . $data['duration_days'] . ' days')
    );

    $stmt = $pdo->prepare('
        INSERT INTO jobs (
            user_id,
            title,
            description,
            requirements,
            responsibilities,
            benefits,
            specialty,
            category,
            city,
            contract_type,
            salary,
            skills,
            expires_at,
            work_mode,
            experience,
            status,
            experience_level,
            education_level,
            language_required,
            duration_days,
            created_at
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )
    ');

    $stmt->execute([
        $userId,
        $data['title'],
        $data['description'],
        $data['requirements'],
        $data['responsibilities'],
        $data['benefits'],
        $data['specialty'],
        $data['category'],
        $data['city'],
        $data['contract_type'],
        $data['salary'],
        $data['skills'],
        $expiresAt,
        $data['work_mode'],
        $data['experience'],
        $status,
        $data['experience_level'],
        $data['education_level'],
        $data['language_required'],
        $data['duration_days']
    ]);

    return $pdo->lastInsertId();
}


function updateJob($jobId, $data)
{
    global $pdo;

    $expiresAt = date(
        'Y-m-d H:i:s',
        strtotime('+' . $data['duration_days'] . ' days')
    );

    $stmt = $pdo->prepare('
        UPDATE jobs
        SET
            title = ?,
            description = ?,
            requirements = ?,
            responsibilities = ?,
            benefits = ?,
            specialty = ?,
            category = ?,
            city = ?,
            contract_type = ?,
            work_mode = ?,
            experience_level = ?,
            experience = ?,
            education_level = ?,
            language_required = ?,
            salary = ?,
            skills = ?,
            duration_days = ?,
            expires_at = ?
        WHERE id = ?
    ');

    $stmt->execute([
        $data['title'],
        $data['description'],
        $data['requirements'],
        $data['responsibilities'],
        $data['benefits'],
        $data['specialty'],
        $data['category'],
        $data['city'],
        $data['contract_type'],
        $data['work_mode'],
        $data['experience_level'],
        $data['experience'],
        $data['education_level'],
        $data['language_required'],
        $data['salary'],
        $data['skills'],
        $data['duration_days'],
        $expiresAt,
        $jobId
    ]);
}

function deleteJob($jobId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM jobs WHERE id = ? AND user_id = ?');
    $stmt->execute([$jobId, $userId]);
}

function getJobById($jobId)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT jobs.*, companies_profiles.company_name FROM jobs LEFT JOIN companies_profiles ON jobs.user_id = companies_profiles.user_id WHERE jobs.id = ?');
    $stmt->execute([$jobId]);
    return $stmt->fetch();
}

function searchJobs(
    $search = '',
    $city = '',
    $category = '',
    $specialty = '',
    $contract = '',
    $experience = '',
    $worktime = ''
) {

    global $pdo;
    ensureJobPositionsColumns();

    $sql = "SELECT jobs.*, companies_profiles.company_name
            FROM jobs
            LEFT JOIN companies_profiles
            ON jobs.user_id = companies_profiles.user_id
            WHERE jobs.status = 'open'";

    $params = [];

    // Search
    if ($search !== '') {

        $sql .= " AND (
            jobs.title LIKE ?
            OR jobs.description LIKE ?
            OR jobs.skills LIKE ?
            OR jobs.specialty LIKE ?
            OR companies_profiles.company_name LIKE ?
        )";

        $like = "%$search%";

        $params = array_merge($params, [
            $like,
            $like,
            $like,
            $like,
            $like
        ]);
    }

    // City
    if ($city !== '') {

        $sql .= " AND jobs.city LIKE ?";
        $params[] = "%$city%";
    }

    // Category
    if ($category !== '') {

        $sql .= " AND jobs.category = ?";
        $params[] = $category;
    }

    // Specialty
    if ($specialty !== '') {

        $sql .= " AND jobs.specialty = ?";
        $params[] = $specialty;
    }

    // Contract
    if ($contract !== '') {

        $sql .= " AND jobs.contract_type = ?";
        $params[] = $contract;
    }

    // Experience
    if ($experience !== '') {

        $sql .= " AND jobs.experience = ?";
        $params[] = $experience;
    }

    // Work Time
    if ($worktime !== '') {

        $sql .= " AND jobs.work_mode = ?";
        $params[] = $worktime;
    }

    $sql .= " ORDER BY jobs.created_at DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}


function getRecommendedJobs($candidateProfile, $search = '', $city = '', $category = '', $specialty = '', $contract = '', $experience = '')
{
    global $pdo;
    ensureJobPositionsColumns();

    if (!$candidateProfile) {
        return searchJobs($search, $city, $category, $specialty, $contract, $experience);
    }

    $skills = trim($candidateProfile['skills'] ?? '');
    $candidateCity = trim($candidateProfile['city'] ?? '');
    $candidateSpecialty = trim($candidateProfile['specialty'] ?? '');

    $sql = "SELECT jobs.*, companies_profiles.company_name,
        (
            (jobs.city = ?) * 3 +
            (jobs.specialty LIKE ?) * 3 +
            (jobs.skills LIKE ?) * 2 +
            (jobs.description LIKE ?) * 1
        ) AS score
        FROM jobs
        LEFT JOIN companies_profiles
        ON jobs.user_id = companies_profiles.user_id
        WHERE jobs.status = 'open'";

    $params = [
        $candidateCity,
        "%$candidateSpecialty%",
        "%$skills%",
        "%$skills%"
    ];

    // Search
    if ($search !== '') {
        $sql .= " AND (
            jobs.title LIKE ?
            OR jobs.description LIKE ?
            OR jobs.skills LIKE ?
            OR jobs.specialty LIKE ?
            OR companies_profiles.company_name LIKE ?
        )";

        $like = "%$search%";
        $params = array_merge($params, [$like, $like, $like, $like, $like]);
    }

    // City filter
    if ($city !== '') {
        $sql .= " AND jobs.city LIKE ?";
        $params[] = "%$city%";
    }

    // Category filter
    if ($category !== '') {
        $sql .= " AND jobs.category = ?";
        $params[] = $category;
    }

    // Specialty filter
    if ($specialty !== '') {
        $sql .= " AND jobs.specialty = ?";
        $params[] = $specialty;
    }

    // Contract filter
    if ($contract !== '') {
        $sql .= " AND jobs.contract_type = ?";
        $params[] = $contract;
    }

    // Experience filter
    if ($experience !== '') {
        $sql .= " AND jobs.experience = ?";
        $params[] = $experience;
    }

    $sql .= " ORDER BY score DESC, jobs.created_at DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}
function getExperienceOptions()
{
    return ['Entry Level', 'Mid Level', 'Senior Level'];
}

function getJobSuggestions($query)
{
    global $pdo;
    $like = "%$query%";
    $stmt = $pdo->prepare('SELECT DISTINCT title FROM jobs WHERE title LIKE ? OR skills LIKE ? LIMIT 6');
    $stmt->execute([$like, $like]);
    return array_column($stmt->fetchAll(), 'title');
}

function hasAppliedJob($jobId, $candidateId)
{
    global $pdo;
    ensureApplicationStatusColumn();
    $stmt = $pdo->prepare('SELECT id, status, created_at FROM applications WHERE job_id = ? AND user_id = ?');
    $stmt->execute([$jobId, $candidateId]);
    return $stmt->fetch();
}

function applyJob($jobId, $candidateId)
{
    global $pdo;
    $existing = hasAppliedJob($jobId, $candidateId);
    if ($existing) {
        return false;
    }
    ensureApplicationStatusColumn();
    $stmt = $pdo->prepare('INSERT INTO applications (job_id, user_id, status, created_at) VALUES (?, ?, "pending", NOW())');
    $result = $stmt->execute([$jobId, $candidateId]);

    if ($result) {
        // Get the application ID
        $applicationId = $pdo->lastInsertId();

        // Get job details to find the company (job owner)
        $stmt = $pdo->prepare('SELECT jobs.*, candidates_profiles.full_name as candidate_name FROM jobs LEFT JOIN candidates_profiles ON jobs.user_id = candidates_profiles.user_id WHERE jobs.id = ?');
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();

        if ($job) {
            $companyUserId = $job['user_id'];
            $jobTitle = $job['title'];

            // Get candidate name
            $stmt = $pdo->prepare('SELECT full_name FROM candidates_profiles WHERE user_id = ?');
            $stmt->execute([$candidateId]);
            $candidateProfile = $stmt->fetch();
            $candidateName = $candidateProfile['full_name'] ?? 'A candidate';

            // Create notification for the company
            $message = "$candidateName applied for $jobTitle";
            createNotification($companyUserId, $message, $companyUserId, $candidateId, 'application', $applicationId);
        }
    }

    return $result;
}

/**
 * Check if a job can accept applications
 * @param int $jobId Job ID
 * @return bool True if job is open and accepting applications
 */
function canApplyForJob($jobId)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT status FROM jobs WHERE id = ?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    return $job && $job['status'] === 'open';
}

/**
 * Get job position summary (status and basic info)
 * @param int $jobId Job ID
 * @return array|null Job status info or null if not found
 */
function getJobPositionsSummary($jobId)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, title, status, contract_type FROM jobs WHERE id = ?');
    $stmt->execute([$jobId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getSavedJobsColumnNames()
{
    global $pdo;
    static $columns;
    if ($columns !== null) {
        return $columns;
    }

    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM saved_jobs');
        $columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    } catch (Exception $e) {
        $columns = [];
    }

    return $columns;
}

function getSavedJobsKeyField()
{
    $columns = getSavedJobsColumnNames();
    return in_array('candidate_id', $columns, true) ? 'candidate_id' : 'user_id';
}

function getSavedJobsTimestampField()
{
    $columns = getSavedJobsColumnNames();
    return in_array('created_at', $columns, true) ? 'saved_jobs.created_at' : 'jobs.created_at';
}

function hasSavedJob($jobId, $candidateId)
{
    global $pdo;
    $field = getSavedJobsKeyField();
    $stmt = $pdo->prepare("SELECT id FROM saved_jobs WHERE job_id = ? AND $field = ?");
    $stmt->execute([$jobId, $candidateId]);
    return (bool) $stmt->fetch();
}

function saveJob($jobId, $candidateId)
{
    global $pdo;
    $field = getSavedJobsKeyField();
    $stmt = $pdo->prepare("SELECT id FROM saved_jobs WHERE job_id = ? AND $field = ?");
    $stmt->execute([$jobId, $candidateId]);
    if ($stmt->fetch()) {
        return false;
    }
    $stmt = $pdo->prepare("INSERT INTO saved_jobs (job_id, $field) VALUES (?, ?)");
    return $stmt->execute([$jobId, $candidateId]);
}

function getSavedJobs($candidateId)
{
    global $pdo;
    $field = getSavedJobsKeyField();
    $timestampField = getSavedJobsTimestampField();
    $stmt = $pdo->prepare("SELECT jobs.*, companies_profiles.company_name, $timestampField AS saved_at FROM saved_jobs JOIN jobs ON saved_jobs.job_id = jobs.id LEFT JOIN companies_profiles ON jobs.user_id = companies_profiles.user_id WHERE saved_jobs.$field = ? ORDER BY $timestampField DESC");
    $stmt->execute([$candidateId]);
    return $stmt->fetchAll();
}

function removeSavedJob($jobId, $candidateId)
{
    global $pdo;
    $field = getSavedJobsKeyField();
    $stmt = $pdo->prepare("DELETE FROM saved_jobs WHERE job_id = ? AND $field = ?");
    return $stmt->execute([$jobId, $candidateId]);
}

function removeApplication($jobId, $candidateId)
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM applications WHERE job_id = ? AND user_id = ?');
    return $stmt->execute([$jobId, $candidateId]);
}

function ensureApplicationStatusColumn()
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'applications' AND column_name = 'status'");
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE applications ADD COLUMN status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending'");
        }
    } catch (PDOException $e) {
        // Ignore
    }
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'applications' AND column_name = 'interview_date'");
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE applications ADD COLUMN interview_date DATE NULL");
        }
    } catch (PDOException $e) {
        // Ignore
    }
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'applications' AND column_name = 'interview_time'");
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE applications ADD COLUMN interview_time TIME NULL");
        }
    } catch (PDOException $e) {
        // Ignore
    }
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'applications' AND column_name = 'company_message'");
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE applications ADD COLUMN company_message TEXT NULL");
        }
    } catch (PDOException $e) {
        // Ignore
    }
}


function ensureCompanyReviewsTable()
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'company_reviews'");
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            $pdo->exec("CREATE TABLE company_reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_user_id INT NOT NULL,
                candidate_user_id INT NOT NULL,
                rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                review TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_review (company_user_id, candidate_user_id),
                FOREIGN KEY (company_user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (candidate_user_id) REFERENCES users(id) ON DELETE CASCADE
            )");
        }
    } catch (PDOException $e) {
        // Ignore if table already exists
    }
}

function ensureReviewsTable()
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'reviews'");
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                user_type ENUM('candidate', 'company') NOT NULL,
                review_text TEXT NOT NULL,
                rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                approved TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    } catch (PDOException $e) {
        // Ignore
    }
}

function ensureReviewCommentsTable()
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'review_comments'");
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS review_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                review_id INT NOT NULL,
                user_id INT NOT NULL,
                parent_id INT DEFAULT NULL,
                comment_text TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (parent_id) REFERENCES review_comments(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    } catch (PDOException $e) {
        // Ignore
    }
}

function ensureMessagesTable()
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'messages'");
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sender_id INT NOT NULL,
                receiver_id INT NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message_text TEXT NOT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    } catch (PDOException $e) {
        // Ignore
    }
}

function ensureNotificationsTable()
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'notifications'");
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                receiver_id INT DEFAULT NULL,
                sender_id INT DEFAULT NULL,
                type VARCHAR(50) DEFAULT NULL,
                related_id INT DEFAULT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_receiver_id (receiver_id),
                INDEX idx_sender_id (sender_id),
                INDEX idx_is_read (is_read),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    } catch (Exception $e) {
        // Table might already exist or other error, continue
    }
}

function createNotification($userId, $message, $receiverId = null, $senderId = null, $type = null, $relatedId = null)
{
    global $pdo;
    ensureNotificationsTable();
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, receiver_id, sender_id, type, related_id, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())');
    return $stmt->execute([$userId, $receiverId, $senderId, $type, $relatedId, $message]);
}

function getUnreadNotificationCount($userId)
{
    global $pdo;
    ensureNotificationsTable();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function getNotifications($userId, $limit = null)
{
    global $pdo;
    ensureNotificationsTable();

    try {

        $sql = "
            SELECT *
            FROM notifications
            WHERE user_id = ?
               OR receiver_id = ?
            ORDER BY created_at DESC
        ";

        if ($limit !== null) {
            $limit = (int)$limit;
            $sql .= " LIMIT $limit";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $userId]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($result) ? $result : [];
    } catch (Exception $e) {
        return [];
    }
}

function markNotificationAsRead($notificationId, $userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = ?
        AND (user_id = ? OR receiver_id = ?)
    ");

    return $stmt->execute([$notificationId, $userId, $userId]);
}

function markAllNotificationsAsRead($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE (user_id = ? OR receiver_id = ?)
        AND is_read = 0
    ");

    return $stmt->execute([$userId, $userId]);
}

function getCompanyNotifications($userId, $limit = null)
{
    global $pdo;
    ensureNotificationsTable();

    $sql = "SELECT n.*, cp.full_name as candidate_name, cp.image_path as candidate_image
            FROM notifications n
            LEFT JOIN candidates_profiles cp ON n.sender_id = cp.user_id
            WHERE n.user_id = ? AND n.type = 'application'
            ORDER BY n.created_at DESC";

    if ($limit !== null) {
        $limit = (int)$limit;
        $sql .= " LIMIT $limit";
    }

    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$userId])) {
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    return [];
}
function updateApplicationStatus($applicationId, $status, $interviewDate = null, $interviewTime = null, $companyMessage = null)
{
    global $pdo;

    ensureApplicationStatusColumn();

    // Get application + job + company info
    $stmt = $pdo->prepare('
        SELECT 
            applications.*,
            jobs.title AS job_title,
            jobs.id AS job_id,
            jobs.user_id AS company_user_id,
            companies_profiles.company_name,
            companies_profiles.id AS company_profile_id
        FROM applications
        JOIN jobs 
            ON applications.job_id = jobs.id
        LEFT JOIN companies_profiles 
            ON jobs.user_id = companies_profiles.user_id
        WHERE applications.id = ?
    ');

    $stmt->execute([$applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        return false;
    }

    $candidateId      = $application['user_id'];
    $jobId            = $application['job_id'];
    $jobTitle         = $application['job_title'];
    $companyName      = $application['company_name'] ?? 'The company';
    $companyUserId    = $application['company_user_id'] ?? null;


    $sql = 'UPDATE applications SET status = ?';
    $params = [$status];

    if ($interviewDate !== null) {
        $sql .= ', interview_date = ?';
        $params[] = !empty($interviewDate) ? $interviewDate : null;
    }

    if ($interviewTime !== null) {
        $sql .= ', interview_time = ?';
        $params[] = !empty($interviewTime) ? $interviewTime : null;
    }

    if ($companyMessage !== null) {
        $sql .= ', company_message = ?';
        $params[] = !empty($companyMessage) ? $companyMessage : null;
    }

    $sql .= ' WHERE id = ?';
    $params[] = $applicationId;

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);


    if ($result && $status === 'rejected') {

        createNotification(
            $candidateId,
            "Your application for \"$jobTitle\" at $companyName has been rejected.",
            null,
            $companyUserId,
            'rejected',
            $jobId
        );
    }

    if ($result && $status === 'accepted') {

        $notificationMsg = "Congratulations! Your application for \"$jobTitle\" at $companyName has been accepted.";

        if (!empty($interviewDate)) {
            $notificationMsg .= " Interview date: " . date('F d, Y', strtotime($interviewDate));
        }

        if (!empty($interviewTime)) {
            $notificationMsg .= " at " . date('g:i A', strtotime($interviewTime));
        }

        if (!empty($companyMessage)) {
            $notificationMsg .= " Message: " . $companyMessage;
        }

        createNotification(
            $candidateId,
            $notificationMsg,
            null,
            $companyUserId,
            'accepted',
            $jobId
        );
    }

    return $result;
}

function getCompanyApplications($companyId)
{
    global $pdo;
    ensureApplicationStatusColumn();
    $stmt = $pdo->prepare('SELECT applications.*, jobs.title AS job_title, companies_profiles.company_name, users.email AS candidate_email, candidates_profiles.full_name AS candidate_name FROM applications JOIN jobs ON applications.job_id = jobs.id JOIN users ON applications.user_id = users.id LEFT JOIN candidates_profiles ON applications.user_id = candidates_profiles.user_id LEFT JOIN companies_profiles ON jobs.user_id = companies_profiles.user_id WHERE jobs.user_id = ? ORDER BY applications.created_at DESC');
    $stmt->execute([$companyId]);
    return $stmt->fetchAll();
}

function getCandidateApplications($candidateId)
{
    global $pdo;
    ensureApplicationStatusColumn();
    // Only show pending and rejected applications (not accepted - they're removed from active list)
    $stmt = $pdo->prepare('SELECT applications.*, jobs.title, companies_profiles.company_name, companies_profiles.city as company_city FROM applications JOIN jobs ON applications.job_id = jobs.id LEFT JOIN companies_profiles ON jobs.user_id = companies_profiles.user_id WHERE applications.user_id = ? AND applications.status != "accepted" ORDER BY applications.created_at DESC');
    $stmt->execute([$candidateId]);
    return $stmt->fetchAll();
}

function getApplicantsByJob($jobId)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT applications.*, users.email, candidates_profiles.full_name FROM applications JOIN users ON applications.user_id = users.id LEFT JOIN candidates_profiles ON applications.user_id = candidates_profiles.user_id WHERE applications.job_id = ? ORDER BY applications.created_at DESC');
    $stmt->execute([$jobId]);
    return $stmt->fetchAll();
}

function getApplicationById($applicationId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT *
        FROM applications
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$applicationId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getApplicationWithJobById($applicationId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT applications.*, jobs.title AS job_title
        FROM applications
        JOIN jobs ON applications.job_id = jobs.id
        WHERE applications.id = ?
        LIMIT 1
    ");

    $stmt->execute([$applicationId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function closeJob($jobId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE jobs
        SET status = 'closed'
        WHERE id = ?
    ");

    return $stmt->execute([$jobId]);
}


function closeExpiredJobs()
{
    global $pdo;

    $sql = "
        UPDATE jobs
        SET status = 'closed'
        WHERE expires_at <= NOW()
        AND status = 'open'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}


function incrementFilledPosition($jobId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE jobs
        SET filled_positions = filled_positions + 1
        WHERE id = ?
    ");

    return $stmt->execute([$jobId]);
}

function changePassword($userId, $oldPassword, $newPassword)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
        return 'The current password is incorrect.';
    }
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$hash, $userId]);
    return true;
}

function deleteAccount($userId)
{
    global $pdo;
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            $pdo->rollBack();
            return false;
        }
        $role = $user['role'];

        if ($role === 'company') {
            $stmt = $pdo->prepare('SELECT id FROM jobs WHERE user_id = ?');
            $stmt->execute([$userId]);
            $jobIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($jobIds)) {
                $inQuery = implode(',', array_fill(0, count($jobIds), '?'));
                $pdo->prepare("DELETE FROM applications WHERE job_id IN ($inQuery)")->execute($jobIds);
                $pdo->prepare("DELETE FROM saved_jobs WHERE job_id IN ($inQuery)")->execute($jobIds);
            }
            $pdo->prepare('DELETE FROM jobs WHERE user_id = ?')->execute([$userId]);
            $pdo->prepare('DELETE FROM companies_profiles WHERE user_id = ?')->execute([$userId]);
        } else {
            $pdo->prepare('DELETE FROM saved_jobs WHERE user_id = ?')->execute([$userId]);
            $pdo->prepare('DELETE FROM candidates_profiles WHERE user_id = ?')->execute([$userId]);
        }

        $pdo->prepare('DELETE FROM applications WHERE user_id = ?')->execute([$userId]);

        $pdo->prepare('DELETE FROM notifications WHERE user_id = ? OR sender_id = ? OR receiver_id = ?')->execute([$userId, $userId, $userId]);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function getCompanyJobs($userId, $excludeJobId = null)
{
    global $pdo;

    $sql = "SELECT * FROM jobs 
            WHERE user_id = ?";

    if ($excludeJobId !== null) {
        $sql .= " AND id != ?";
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);

    if ($excludeJobId !== null) {
        $stmt->execute([$userId, $excludeJobId]);
    } else {
        $stmt->execute([$userId]);
    }

    return $stmt->fetchAll();
}

function getUserAvatarUrl($userId, $role)
{
    if ($role === 'candidate') {
        $profile = getCandidateProfile($userId);
        return $profile['image_path'] ?? '';
    }
    if ($role === 'company') {
        $profile = getCompanyProfile($userId);
        return $profile['logo_url'] ?? '';
    }
    return '';
}

function getRecentlyAddedJobs()
{
    global $pdo;
    ensureJobPositionsColumns();
    $stmt = $pdo->query('SELECT jobs.*, companies_profiles.company_name FROM jobs LEFT JOIN companies_profiles ON jobs.user_id = companies_profiles.user_id WHERE jobs.status = "open" ORDER BY jobs.created_at DESC LIMIT 3');
    return $stmt->fetchAll();
}

function getCityOptions()
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT DISTINCT city 
        FROM jobs
        WHERE city IS NOT NULL
        AND city != ''
        ORDER BY city ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getCategoryOptions()
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT DISTINCT category
        FROM jobs
        WHERE category IS NOT NULL
        AND category != ''
        ORDER BY category ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getSpecialtyOptions()
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT DISTINCT specialty
        FROM jobs
        WHERE specialty IS NOT NULL
        AND specialty != ''
        ORDER BY specialty ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getJobCategories()
{
    global $pdo;

    $stmt = $pdo->query("SELECT DISTINCT specialty FROM jobs ORDER BY specialty ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function uploadImage($file)
{
    if (empty($file['name'])) {
        return '';
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed)) {
        return '';
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = uniqid('profile_') . '.' . $ext;
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $target = $uploadDir . $name;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return 'uploads/' . $name;
    }
    return '';
}

function getActiveCompanies($limit = 6)
{

    global $pdo;

    $stmt = $pdo->prepare("
        SELECT 
            cp.*,

            COUNT(DISTINCT j.id) AS job_count,

            COUNT(a.id) AS applications_count

        FROM companies_profiles cp

        JOIN jobs j 
            ON cp.user_id = j.user_id

        LEFT JOIN applications a 
            ON j.id = a.job_id

        GROUP BY cp.user_id

        ORDER BY applications_count DESC

        LIMIT " . (int)$limit);

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function submitReview($userId, $userType, $reviewText, $rating)
{
    global $pdo;
    ensureReviewsTable();
    $stmt = $pdo->prepare('INSERT INTO reviews (user_id, user_type, review_text, rating, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$userId, $userType, $reviewText, $rating]);
    return $pdo->lastInsertId();
}

function updateReview($reviewId, $userId, $reviewText, $rating)
{
    global $pdo;
    $stmt = $pdo->prepare('UPDATE reviews SET review_text = ?, rating = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
    return $stmt->execute([$reviewText, $rating, $reviewId, $userId]);
}

function getUserReview($userId)
{
    global $pdo;
    ensureReviewsTable();
    $stmt = $pdo->prepare('SELECT * FROM reviews WHERE user_id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function deleteReview($reviewId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM reviews WHERE id = ? AND user_id = ?');
    return $stmt->execute([$reviewId, $userId]);
}

function addReviewComment($reviewId, $userId, $commentText, $parentId = null)
{
    global $pdo;
    ensureReviewCommentsTable();
    $stmt = $pdo->prepare('INSERT INTO review_comments (review_id, user_id, parent_id, comment_text, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$reviewId, $userId, $parentId, $commentText]);
    return $pdo->lastInsertId();
}

function getReviewComments($reviewId)
{
    global $pdo;
    ensureReviewCommentsTable();
    $stmt = $pdo->prepare('
        SELECT rc.*, 
               CASE 
                   WHEN rc.user_id IN (SELECT user_id FROM candidates_profiles) THEN cp.full_name 
                   WHEN rc.user_id IN (SELECT user_id FROM companies_profiles) THEN comp.company_name 
                   ELSE "User" 
               END as author_name,
               CASE 
                   WHEN rc.user_id IN (SELECT user_id FROM candidates_profiles) THEN "candidate" 
                   WHEN rc.user_id IN (SELECT user_id FROM companies_profiles) THEN "company" 
                   ELSE "user" 
               END as author_type
        FROM review_comments rc 
        LEFT JOIN candidates_profiles cp ON rc.user_id = cp.user_id
        LEFT JOIN companies_profiles comp ON rc.user_id = comp.user_id
        WHERE rc.review_id = ? 
        ORDER BY rc.created_at ASC
    ');
    $stmt->execute([$reviewId]);
    return $stmt->fetchAll();
}

function deleteReviewComment($commentId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM review_comments WHERE id = ? AND user_id = ?');
    return $stmt->execute([$commentId, $userId]);
}

// Message functions
function sendMessage($senderId, $receiverId, $subject, $messageText)
{
    global $pdo;
    ensureMessagesTable();
    $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, subject, message_text, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$senderId, $receiverId, $subject, $messageText]);
    return $pdo->lastInsertId();
}

function getUserMessages($userId, $limit = 20)
{
    global $pdo;
    ensureMessagesTable();
    $stmt = $pdo->prepare('
        SELECT m.*, 
               CASE 
                   WHEN m.sender_id IN (SELECT user_id FROM candidates_profiles) THEN cp.full_name 
                   WHEN m.sender_id IN (SELECT user_id FROM companies_profiles) THEN comp.company_name 
                   ELSE u.email 
               END as sender_name,
               CASE 
                   WHEN m.receiver_id IN (SELECT user_id FROM candidates_profiles) THEN cp2.full_name 
                   WHEN m.receiver_id IN (SELECT user_id FROM companies_profiles) THEN comp2.company_name 
                   ELSE u2.email 
               END as receiver_name
        FROM messages m 
        LEFT JOIN users u ON m.sender_id = u.id
        LEFT JOIN users u2 ON m.receiver_id = u2.id
        LEFT JOIN candidates_profiles cp ON m.sender_id = cp.user_id
        LEFT JOIN candidates_profiles cp2 ON m.receiver_id = cp2.user_id
        LEFT JOIN companies_profiles comp ON m.sender_id = comp.user_id
        LEFT JOIN companies_profiles comp2 ON m.receiver_id = comp2.user_id
        WHERE m.sender_id = ? OR m.receiver_id = ?
        ORDER BY m.created_at DESC 
        LIMIT ?
    ');
    $stmt->execute([$userId, $userId, $limit]);
    return $stmt->fetchAll();
}

function getConversation($userId, $otherUserId, $limit = 50)
{
    global $pdo;
    ensureMessagesTable();
    $stmt = $pdo->prepare('
        SELECT m.*, 
               CASE 
                   WHEN m.sender_id IN (SELECT user_id FROM candidates_profiles) THEN cp.full_name 
                   WHEN m.sender_id IN (SELECT user_id FROM companies_profiles) THEN comp.company_name 
                   ELSE u.email 
               END as sender_name
        FROM messages m 
        LEFT JOIN users u ON m.sender_id = u.id
        LEFT JOIN candidates_profiles cp ON m.sender_id = cp.user_id
        LEFT JOIN companies_profiles comp ON m.sender_id = comp.user_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at DESC 
        LIMIT ?
    ');
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId, $limit]);
    return array_reverse($stmt->fetchAll());
}

function markMessageAsRead($messageId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?');
    return $stmt->execute([$messageId, $userId]);
}

function getUnreadMessageCount($userId)
{
    global $pdo;
    ensureMessagesTable();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function deleteMessage($messageId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM messages WHERE id = ? AND (sender_id = ? OR receiver_id = ?)');
    return $stmt->execute([$messageId, $userId, $userId]);
}

// Security functions
function hashPassword($password)
{
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

function sanitizeInput($input, $type = 'string')
{
    $input = trim($input);

    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        default:
            return filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }
}

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePasswordStrength($password)
{
    // At least 8 characters, 1 letter, and 1 number
    return strlen($password) >= 8 &&
        preg_match('/[A-Za-z]/', $password) &&
        preg_match('/[0-9]/', $password);
}

function generateSecureToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 5242880)
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload error occurred.'];
    }

    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File size exceeds limit.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'error' => 'Invalid file type.'];
    }

    return ['valid' => true];
}

function calculateProfileStrength($profile)
{
    $fields = [
        'full_name' => 15,
        'phone' => 10,
        'city' => 10,
        'education' => 15,
        'experience' => 15,
        'skills' => 15,
        'projects' => 10,
        'languages' => 5,
        'image_path' => 5
    ];

    $strength = 0;
    foreach ($fields as $field => $weight) {
        if (!empty($profile[$field])) {
            $strength += $weight;
        }
    }

    return min(100, $strength);
}

function getProfileStrengthLevel($strength)
{
    if ($strength >= 80) {
        return [
            'level' => 'Excellent',
            'color' => '#16a34a',
            'bgColor' => 'rgba(22, 163, 74, 0.14)'
        ];
    } elseif ($strength >= 60) {
        return [
            'level' => 'Good',
            'color' => '#2563eb',
            'bgColor' => 'rgba(37, 99, 235, 0.14)'
        ];
    } elseif ($strength >= 40) {
        return [
            'level' => 'Average',
            'color' => '#ea580c',
            'bgColor' => 'rgba(234, 88, 12, 0.14)'
        ];
    }

    return [
        'level' => 'Needs Improvement',
        'color' => '#dc2626',
        'bgColor' => 'rgba(220, 38, 38, 0.14)'
    ];
}

function ensureActivitiesTable()
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'activities'");
        $stmt->execute();
        if (!$stmt->fetchColumn()) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS activities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                details TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");
        }
    } catch (Exception $e) {
        // Table might already exist or other error, continue
    }
}

function getRecentActivities($userId, $limit = 10)
{
    global $pdo;
    ensureActivitiesTable();

    $stmt = $pdo->prepare("
        SELECT * FROM activities 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function logActivity($userId, $action, $details = null)
{
    global $pdo;
    ensureActivitiesTable();

    $stmt = $pdo->prepare("
        INSERT INTO activities (user_id, action, details, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $action, $details]);
}


function isJobOpen($jobId)
{
    global $pdo;
    ensureJobPositionsColumns();

    $stmt = $pdo->prepare("SELECT status FROM jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    $result = $stmt->fetch();

    if (!$result) {
        return false;
    }

    return $result['status'] === 'open';
}


function toggleJobStatus($jobId, $companyUserId)
{
    global $pdo;
    ensureJobPositionsColumns();

    // Verify company owns the job
    $stmt = $pdo->prepare("SELECT status, title FROM jobs WHERE id = ? AND user_id = ?");
    $stmt->execute([$jobId, $companyUserId]);
    $job = $stmt->fetch();

    if (!$job) {
        return false;
    }

    $newStatus = ($job['status'] === 'open') ? 'closed' : 'open';

    $stmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?");
    $result = $stmt->execute([$newStatus, $jobId]);

    if ($result) {
        $statusText = ($newStatus === 'open') ? 'reopened' : 'closed';
        createNotification(
            $companyUserId,
            "Job \"" . $job['title'] . "\" has been $statusText.",
            null,
            null,
            'job_status_changed',
            $jobId
        );
    }

    return $result;
}

function countAcceptedApplicationsForJob($jobId)
{
    global $pdo;
    ensureApplicationStatusColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT user_id) as count 
        FROM applications 
        WHERE job_id = ? AND status = 'accepted'
    ");
    $stmt->execute([$jobId]);
    $result = $stmt->fetch();

    return $result ? intval($result['count']) : 0;
}


function getApprovedReviews($limit = 4)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT r.*, 
        CASE 
            WHEN r.user_type = "candidate" THEN cp.full_name 
            WHEN r.user_type = "company" THEN comp.company_name 
        END as name
        FROM reviews r 
        LEFT JOIN candidates_profiles cp ON r.user_id = cp.user_id AND r.user_type = "candidate"
        LEFT JOIN companies_profiles comp ON r.user_id = comp.user_id AND r.user_type = "company"
        WHERE r.approved = 1 
        ORDER BY r.created_at DESC 
        LIMIT ' . (int)$limit);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getAllReviews()
{
    global $pdo;
    ensureReviewsTable();
    $stmt = $pdo->query('SELECT r.*, 
        CASE 
            WHEN r.user_type = "candidate" THEN cp.full_name 
            WHEN r.user_type = "company" THEN comp.company_name 
        END as name
        FROM reviews r 
        LEFT JOIN candidates_profiles cp ON r.user_id = cp.user_id AND r.user_type = "candidate"
        LEFT JOIN companies_profiles comp ON r.user_id = comp.user_id AND r.user_type = "company"
        ORDER BY r.created_at DESC');
    return $stmt->fetchAll();
}

function getReviewById($reviewId)
{
    global $pdo;
    ensureReviewsTable();
    $stmt = $pdo->prepare('SELECT r.*, 
        CASE 
            WHEN r.user_type = "candidate" THEN cp.full_name 
            WHEN r.user_type = "company" THEN comp.company_name 
        END as name
        FROM reviews r 
        LEFT JOIN candidates_profiles cp ON r.user_id = cp.user_id AND r.user_type = "candidate"
        LEFT JOIN companies_profiles comp ON r.user_id = comp.user_id AND r.user_type = "company"
        WHERE r.id = ?');
    $stmt->execute([$reviewId]);
    return $stmt->fetch();
}

function approveReview($reviewId)
{
    global $pdo;
    ensureReviewsTable();
    $stmt = $pdo->prepare('UPDATE reviews SET approved = 1 WHERE id = ?');
    $stmt->execute([$reviewId]);
}

function incrementJobViews($jobId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE jobs
        SET views_count = views_count + 1
        WHERE id = ?
    ");

    $stmt->execute([$jobId]);
}
function getJobViewsCount($jobId)
{

    global $pdo;

    $stmt = $pdo->prepare("
        SELECT views_count
        FROM jobs
        WHERE id = ?
    ");

    $stmt->execute([$jobId]);

    return (int) $stmt->fetchColumn();
}
function getCompanyViewsCount($companyUserId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT SUM(views_count)
        FROM jobs
        WHERE user_id = ?
    ");

    $stmt->execute([$companyUserId]);

    return (int)$stmt->fetchColumn();
}

function incrementProfileViews($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE candidates_profiles
        SET profile_views = profile_views + 1
        WHERE user_id = ?
    ");

    $stmt->execute([$userId]);
}
function getProfileViews($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT profile_views
        FROM candidates_profiles
        WHERE user_id = ?
    ");

    $stmt->execute([$userId]);

    return (int)$stmt->fetchColumn();
}

function getCompanyProfileViews($userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT profile_views
        FROM companies_profiles
        WHERE user_id = ?
    ");

    $stmt->execute([$userId]);

    return (int)$stmt->fetchColumn();
}

function getRelatedJobs($category, $jobId, $city = null)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT jobs.*, cp.company_name
        FROM jobs
        LEFT JOIN companies_profiles cp 
        ON jobs.user_id = cp.user_id
        WHERE jobs.category = ?
        AND jobs.id != ?
        AND jobs.status = 'open'
        LIMIT 4
    ");

    $stmt->execute([$category, $jobId]);

    $jobs = $stmt->fetchAll();

    if (count($jobs) === 0 && $city) {

        $stmt = $pdo->prepare("
            SELECT jobs.*, cp.company_name
            FROM jobs
            LEFT JOIN companies_profiles cp 
            ON jobs.user_id = cp.user_id
            WHERE jobs.city = ?
            AND jobs.id != ?
            AND jobs.status = 'open'
            LIMIT 4
        ");

        $stmt->execute([$city, $jobId]);

        $jobs = $stmt->fetchAll();
    }

    if (count($jobs) === 0) {

        $stmt = $pdo->prepare("
            SELECT jobs.*, cp.company_name
            FROM jobs
            LEFT JOIN companies_profiles cp 
            ON jobs.user_id = cp.user_id
            WHERE jobs.id != ?
            AND jobs.status = 'open'
            ORDER BY jobs.created_at DESC
            LIMIT 4
        ");

        $stmt->execute([$jobId]);

        $jobs = $stmt->fetchAll();
    }

    return $jobs;
}

function calculateJobMatch($candidateProfile, $job)
{
    $score = 40;

    $candidateSpeciality = strtolower($candidateProfile['speciality'] ?? '');
    $candidateCity       = strtolower($candidateProfile['city'] ?? '');

    $jobTitle    = strtolower($job['title'] ?? '');
    $jobCategory = strtolower($job['category'] ?? '');
    $jobCity     = strtolower($job['location'] ?? '');

    if (
        $candidateSpeciality &&
        (
            str_contains($jobTitle, $candidateSpeciality) ||
            str_contains($jobCategory, $candidateSpeciality)
        )
    ) {
        $score += 35;
    } elseif (
        $jobCategory &&
        str_contains($jobCategory, $candidateSpeciality)
    ) {
        $score += 20;
    }


    if (
        $candidateCity &&
        $jobCity &&
        $candidateCity === $jobCity
    ) {
        $score += 12;
    }


    return min($score, 98);
}

function getTopCompaniesByApplications(int $limit = 3): array
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.id AS user_id, cp.company_name, cp.logo_url,
               COUNT(DISTINCT j.id)   AS job_count,
               COUNT(a.id)            AS application_count
        FROM users u
        LEFT JOIN companies_profiles cp ON cp.user_id = u.id
        LEFT JOIN jobs j        ON j.user_id = u.id
        LEFT JOIN applications a ON a.job_id = j.id
        WHERE u.role = 'company'
        GROUP BY u.id
        ORDER BY application_count DESC, job_count DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function verifyUserPassword($userId, $password)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user && password_verify($password, $user['password_hash']);
}
