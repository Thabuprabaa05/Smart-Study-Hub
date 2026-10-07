<?php
// ==============================================================
// Smart Study Hub - Admin: Portal & System Settings
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Portal Settings & Diagnostics';
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($token)) {
        $portalName   = trim($_POST['portal_name'] ?? 'Smart Study Hub');
        $collegeName  = trim($_POST['college_name'] ?? '');
        $academicYear = trim($_POST['academic_year'] ?? '');
        $maxUpload    = (int)($_POST['max_upload_size_mb'] ?? 25);
        $allowReg     = isset($_POST['allow_registrations']) ? '1' : '0';

        $settings = [
            'portal_name'         => $portalName,
            'college_name'        => $collegeName,
            'academic_year'       => $academicYear,
            'max_upload_size_mb'  => (string)$maxUpload,
            'allow_registrations' => $allowReg
        ];

        $stmt = $pdo->prepare("
            INSERT INTO portal_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        foreach ($settings as $k => $v) {
            $stmt->execute([$k, $v]);
        }

        setFlashMessage('success', 'Portal configuration settings updated successfully.');
        redirect(BASE_URL . '/admin/settings.php');
    }
}

// Fetch current settings
$rows = $pdo->query("SELECT setting_key, setting_value FROM portal_settings")->fetchAll();
$currentSettings = [];
foreach ($rows as $r) {
    $currentSettings[$r['setting_key']] = $r['setting_value'];
}

$uploadDirWritable = is_writable(UPLOAD_DIR);
?>

<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; margin-bottom: 0.25rem;">Portal Configuration & Server Diagnostics</h2>
    <p class="text-muted" style="font-size: 0.9rem;">Configure institutional branding, registration policies, and view environment diagnostics</p>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- Settings Form -->
    <div class="card" style="box-shadow: var(--shadow-md);">
        <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;"><i class="fas fa-sliders-h text-primary"></i> General Portal Preferences</h3>

        <form method="POST" action="<?= BASE_URL ?>/admin/settings.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label" for="portal_name">Portal Display Name</label>
                <input type="text" name="portal_name" id="portal_name" class="form-control" value="<?= e($currentSettings['portal_name'] ?? 'Smart Study Hub') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="college_name">University / College Institution Name</label>
                <input type="text" name="college_name" id="college_name" class="form-control" value="<?= e($currentSettings['college_name'] ?? 'National Institute of Technology & Engineering') ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="academic_year">Active Academic Year</label>
                    <input type="text" name="academic_year" id="academic_year" class="form-control" value="<?= e($currentSettings['academic_year'] ?? '2026-2027') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="max_upload_size_mb">Max File Upload Limit (MB)</label>
                    <input type="number" name="max_upload_size_mb" id="max_upload_size_mb" class="form-control" min="5" max="100" value="<?= e($currentSettings['max_upload_size_mb'] ?? '25') ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                    <input type="checkbox" name="allow_registrations" value="1" <?= ($currentSettings['allow_registrations'] ?? '1') === '1' ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                    <div>
                        <div class="font-semibold">Allow New Student Registrations</div>
                        <div class="text-muted" style="font-size: 0.8rem;">When unchecked, student registration is paused for semester end.</div>
                    </div>
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Configuration
                </button>
            </div>
        </form>
    </div>

    <!-- System Diagnostics -->
    <div>
        <div class="card">
            <h4 style="font-size: 1.1rem; margin-bottom: 1.25rem;"><i class="fas fa-server text-accent"></i> System Health & Environment</h4>
            <div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.875rem;">
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">PHP Runtime</div>
                    <div class="font-semibold"><?= phpversion() ?> (FastCGI/Apache)</div>
                </div>

                <div>
                    <div class="text-muted" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Database Server</div>
                    <div class="font-semibold">MySQL / MariaDB via PDO</div>
                </div>

                <div>
                    <div class="text-muted" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Server OS</div>
                    <div class="font-semibold"><?= PHP_OS_FAMILY ?> (<?= php_uname('s') ?>)</div>
                </div>

                <div>
                    <div class="text-muted" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Uploads Directory</div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 2px;">
                        <span class="badge <?= $uploadDirWritable ? 'badge-accent' : 'badge-danger' ?>">
                            <?= $uploadDirWritable ? 'Writable & Protected' : 'Not Writable' ?>
                        </span>
                    </div>
                </div>

                <div>
                    <div class="text-muted" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Max POST Size (php.ini)</div>
                    <div class="font-semibold"><?= ini_get('post_max_size') ?></div>
                </div>

                <div>
                    <div class="text-muted" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Max File Upload (php.ini)</div>
                    <div class="font-semibold"><?= ini_get('upload_max_filesize') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
