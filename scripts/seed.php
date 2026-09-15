<?php
/**
 * Demo data seeder. Not web-accessible (lives outside src/, which is the
 * only directory nginx serves) — run it with:
 *   make seed
 * or it runs once automatically the first time `make up` brings the
 * stack up. Idempotent: safe to re-run, it skips users that already exist.
 *
 * Passwords are hashed with PHP's password_hash() so they match exactly
 * what login.php verifies with password_verify() — hand-computed bcrypt
 * hashes from another tool are a common source of "seed data doesn't
 * actually log in" bugs, so we avoid that entirely.
 */

$host = getenv('DB_HOST') ?: 'mysql';
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_NAME') ?: 'jobhunt';
$user = getenv('DB_USER') ?: 'jobhunt';
$pass = getenv('DB_PASSWORD') ?: 'jobhunt_dev_pw';

$mysqli = @new mysqli($host, $user, $pass, $name, (int)$port);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connection failed: {$mysqli->connect_error}\n");
    exit(1);
}

function get_or_create_user(mysqli $db, string $email, string $plainPassword, string $role, string $fullName, string $phone, bool $verified = true): int
{
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) {
        return (int)$res['id'];
    }
    $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
    $verifiedInt = $verified ? 1 : 0;
    $stmt = $db->prepare('INSERT INTO users (email, password, role, full_name, phone, is_verified) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sssssi', $email, $hash, $role, $fullName, $phone, $verifiedInt);
    $stmt->execute();
    return (int)$db->insert_id;
}

echo "Seeding demo accounts...\n";

// --- Admin ---
$adminId = get_or_create_user($mysqli, 'admin@jobhunt.local', 'AdminP@ss1', 'admin', 'System Admin', '0900000000');

// --- Employers ---
$emp1 = get_or_create_user($mysqli, 'hr@techcorp.local', 'Employer123', 'employer', 'TechCorp HR', '0901111111');
$emp2 = get_or_create_user($mysqli, 'hr@finbank.local', 'Employer123', 'employer', 'FinBank HR', '0902222222');

foreach ([
    [$emp1, 'TechCorp Vietnam', 'Software outsourcing & product company.', 'https://techcorp.example', 'https://webhook.site/placeholder', 2015],
    [$emp2, 'FinBank Digital', 'Digital banking arm of FinBank.', 'https://finbank.example', '', 2009],
] as [$uid, $company, $desc, $site, $webhook, $founded]) {
    $stmt = $mysqli->prepare('INSERT IGNORE INTO employer_profiles (user_id, company_name, company_desc, website, webhook_url, founded_year) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssi', $uid, $company, $desc, $site, $webhook, $founded);
    $stmt->execute();
}

// --- Candidates ---
$cand1 = get_or_create_user($mysqli, 'alice@example.com', 'Candidate123', 'candidate', 'Alice Nguyen', '0903333333');
$cand2 = get_or_create_user($mysqli, 'bob@example.com', 'Candidate123', 'candidate', 'Bob Tran', '0904444444');
$cand3 = get_or_create_user($mysqli, 'carol@example.com', 'Candidate123', 'candidate', 'Carol Le', '0905555555');

foreach ([
    [$cand1, 'Backend Developer', 'PHP/MySQL developer with 3 years experience.', 'PHP, MySQL, Docker', 3],
    [$cand2, 'Frontend Developer', 'React enthusiast.', 'React, JavaScript, CSS', 2],
    [$cand3, 'QA Engineer', 'Manual + automation testing.', 'Selenium, TestNG', 4],
] as [$uid, $headline, $bio, $skills, $exp]) {
    $stmt = $mysqli->prepare('INSERT IGNORE INTO candidate_profiles (user_id, headline, bio, skills, experience_years) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('isssi', $uid, $headline, $bio, $skills, $exp);
    $stmt->execute();
}

// --- Jobs ---
$jobs = [
    [$emp1, 'PHP Backend Developer', 'Build and maintain internal PHP services. Familiarity with MySQL required.', 'Ho Chi Minh City', 15000000, 25000000, 2],
    [$emp1, 'DevOps Engineer', 'Manage Docker/Kubernetes infrastructure.', 'Ha Noi', 20000000, 35000000, 3],
    [$emp1, 'Frontend Developer (React)', 'Build our candidate-facing product in React.', 'Remote', 12000000, 22000000, 1],
    [$emp2, 'Java Backend Engineer', 'Core banking services in Java/Spring.', 'Ho Chi Minh City', 25000000, 40000000, 4],
    [$emp2, 'Security Analyst', 'Monitor and triage security alerts for our banking platform.', 'Ha Noi', 18000000, 30000000, 2],
];
$jobIds = [];
foreach ($jobs as [$employerId, $title, $descr, $loc, $min, $max, $exp]) {
    $stmt = $mysqli->prepare("INSERT INTO jobs (employer_id, title, description, location, salary_min, salary_max, experience_required, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')");
    $stmt->bind_param('isssiii', $employerId, $title, $descr, $loc, $min, $max, $exp);
    $stmt->execute();
    $jobIds[] = $mysqli->insert_id;
}

// --- A couple of comments (stored XSS lab lives here) ---
if (isset($jobIds[0])) {
    $stmt = $mysqli->prepare('INSERT INTO job_comments (job_id, user_id, content) VALUES (?, ?, ?)');
    $c1 = 'Great company culture, applied last month!';
    $stmt->bind_param('iis', $jobIds[0], $cand1, $c1);
    $stmt->execute();
}

// --- CTF: Applications (IDOR lab) ---
// Normal application: alice applies to job 0 (PHP Backend Developer)
if (isset($jobIds[0])) {
    $stmt = $mysqli->prepare("INSERT INTO applications (job_id, candidate_id, cv_path, cover_letter, status) VALUES (?, ?, ?, ?, 'pending')");
    $cvPath = 'uploads/alice_cv.pdf';
    $coverLetter = 'I am excited to apply for this role. I have 3 years of PHP/MySQL experience and love clean code.';
    $stmt->bind_param('iiss', $jobIds[0], $cand1, $cvPath, $coverLetter);
    $stmt->execute();
}

// Hidden application: carol applies to job 3 (Java Backend Engineer) with IDOR flag in cover_letter.
// This application belongs to carol (cand3). If you access it as alice (cand1) via
//   /dashboard/candidate/cv_view.php?application_id=<carol's id>
// you leak another candidate's private data — that's the IDOR.
if (isset($jobIds[3])) {
    $stmt = $mysqli->prepare("INSERT INTO applications (job_id, candidate_id, cv_path, cover_letter, status) VALUES (?, ?, ?, ?, 'accepted')");
    $cvPath = 'uploads/carol_cv_confidential.pdf';
    $coverLetter = "Confidential — Carol Le's private application notes.\n\nSalary expectation: 38,000,000 VND\nReference contact: manager@prev-employer.vn / 0909123456\n\nFLAG{IDOR_other_candidates_data_exposed_2026}";
    $stmt->bind_param('iiss', $jobIds[3], $cand3, $cvPath, $coverLetter);
    $stmt->execute();
}

// --- CTF: Flags table (SQLi lab) ---
// This row is only reachable via SQL injection, e.g.:
//   ' UNION SELECT flag,2,3,4,5,6,7,8 FROM flags-- -
$stmt = $mysqli->prepare("INSERT IGNORE INTO flags (name, flag, hint) VALUES (?, ?, ?)");
$flagName  = 'sqli_flag';
$flagValue = 'FLAG{SQLi_jobhunt_data_extracted_2026}';
$flagHint  = 'You extracted this via SQL injection. Try UNION SELECT against the jobs search or login endpoint.';
$stmt->bind_param('sss', $flagName, $flagValue, $flagHint);
$stmt->execute();

echo "Seed complete.\n";
echo "Demo logins:\n";
echo "  admin@jobhunt.local / AdminP@ss1\n";
echo "  hr@techcorp.local   / Employer123\n";
echo "  alice@example.com   / Candidate123\n";
echo "\nCTF flags hidden at:\n";
echo "  RCE  → cat /flag_rce.txt  (after command injection)\n";
echo "  SQLi → SELECT flag FROM flags  (after SQL injection)\n";
echo "  LFI  → src/config/.secret  (after path traversal)\n";
echo "  IDOR → carol's cover_letter in applications table\n";
echo "  JWT  → /api/admin_stats.php response (after JWT forgery)\n";
