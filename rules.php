<?php
// ==============================================================
// Smart Study Hub - Community Guidelines & Rules
// ==============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Community Guidelines & Code of Conduct';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container container-narrow" style="padding-top: 3.5rem; padding-bottom: 4rem;">
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 3.5rem;">
        <span class="badge badge-primary" style="margin-bottom: 0.75rem;"><i class="fas fa-shield-alt"></i> Academic Integrity</span>
        <h1 style="font-size: 2.3rem; margin-bottom: 0.5rem;">Community Rules & Guidelines</h1>
        <p class="text-muted" style="font-size: 1.05rem;">
            To ensure Smart Study Hub remains a safe, collaborative, and highly valuable academic resource, all members must abide by these college portal guidelines.
        </p>
    </div>

    <!-- Rules List -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 3.5rem;">
        <div class="card" style="border-left: 5px solid var(--primary); padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <span class="badge badge-primary" style="font-size: 0.85rem; width: 28px; height: 28px; border-radius: 50%; justify-content: center;">1</span>
                <h3 style="font-size: 1.15rem; margin: 0;">Share Exclusively Educational & Curriculum Content</h3>
            </div>
            <p class="text-muted" style="font-size: 0.95rem; line-height: 1.6; margin-left: 2.75rem;">
                Every uploaded document, discussion post, and announcement must relate directly to university coursework, laboratory experiments, syllabus review, or semester exam preparation. Off-topic, promotional, or non-academic uploads are strictly prohibited.
            </p>
        </div>

        <div class="card" style="border-left: 5px solid var(--danger); padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <span class="badge badge-danger" style="font-size: 0.85rem; width: 28px; height: 28px; border-radius: 50%; justify-content: center;">2</span>
                <h3 style="font-size: 1.15rem; margin: 0;">Respect Copyright & Intellectual Property</h3>
            </div>
            <p class="text-muted" style="font-size: 0.95rem; line-height: 1.6; margin-left: 2.75rem;">
                Do not upload pirated textbook PDFs, commercially licensed test banks, or paid courseware. We encourage original handwritten notes, student summaries, formula cheatsheets, and self-authored tutorial solutions.
            </p>
        </div>

        <div class="card" style="border-left: 5px solid var(--danger); padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <span class="badge badge-danger" style="font-size: 0.85rem; width: 28px; height: 28px; border-radius: 50%; justify-content: center;">3</span>
                <h3 style="font-size: 1.15rem; margin: 0;">Zero Tolerance for Harmful or Malicious Files</h3>
            </div>
            <p class="text-muted" style="font-size: 0.95rem; line-height: 1.6; margin-left: 2.75rem;">
                Uploading executable files, corrupted scripts, malware, or sexually explicit/offensive material will result in immediate permanent account termination and campus disciplinary escalation.
            </p>
        </div>

        <div class="card" style="border-left: 5px solid var(--accent); padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <span class="badge badge-accent" style="font-size: 0.85rem; width: 28px; height: 28px; border-radius: 50%; justify-content: center;">4</span>
                <h3 style="font-size: 1.15rem; margin: 0;">Maintain Collegial Respect & Constructive Dialogue</h3>
            </div>
            <p class="text-muted" style="font-size: 0.95rem; line-height: 1.6; margin-left: 2.75rem;">
                Treat fellow students with courtesy. Harassment, condescending remarks towards academic doubts, discrimination, or abusive language in study squad chats will lead to removal from the portal.
            </p>
        </div>

        <div class="card" style="border-left: 5px solid var(--warning); padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <span class="badge badge-warning" style="font-size: 0.85rem; width: 28px; height: 28px; border-radius: 50%; justify-content: center;">5</span>
                <h3 style="font-size: 1.15rem; margin: 0;">No Spamming or Duplicate Material Flooding</h3>
            </div>
            <p class="text-muted" style="font-size: 0.95rem; line-height: 1.6; margin-left: 2.75rem;">
                Verify if a note or study squad for your subject already exists before creating duplicates. Do not post advertisements, referral links, or unverified external promotions in group discussion threads.
            </p>
        </div>

        <div class="card" style="border-left: 5px solid var(--secondary); padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <span class="badge badge-secondary" style="font-size: 0.85rem; width: 28px; height: 28px; border-radius: 50%; justify-content: center;">6</span>
                <h3 style="font-size: 1.15rem; margin: 0;">Protect Personal & Academic Privacy</h3>
            </div>
            <p class="text-muted" style="font-size: 0.95rem; line-height: 1.6; margin-left: 2.75rem;">
                Never publish phone numbers, confidential university credentials, or private financial details on public forums. Keep group discussions focused on study collaboration.
            </p>
        </div>

        <div class="card" style="border-left: 5px solid var(--primary); padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <span class="badge badge-primary" style="font-size: 0.85rem; width: 28px; height: 28px; border-radius: 50%; justify-content: center;">7</span>
                <h3 style="font-size: 1.15rem; margin: 0;">Proactively Report Inappropriate Material</h3>
            </div>
            <p class="text-muted" style="font-size: 0.95rem; line-height: 1.6; margin-left: 2.75rem;">
                If you encounter miscategorized notes, offensive messages, or suspicious documents, click the <i class="far fa-flag text-danger"></i> Report button immediately. Our administrators review flagged items around the clock.
            </p>
        </div>
    </div>

    <!-- Enforcement Policy Box -->
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-md);">
        <h3 style="font-size: 1.25rem; margin-bottom: 0.75rem;"><i class="fas fa-gavel text-primary"></i> Moderation & Disciplinary Action</h3>
        <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 1.25rem;">
            Violation of community policies will result in tiered enforcement:
        </p>
        <ul style="font-size: 0.9rem; color: var(--text-main); margin-left: 1.5rem; line-height: 1.8;">
            <li><strong>First Infraction:</strong> Warning and removal of violating note or discussion post.</li>
            <li><strong>Second Infraction:</strong> 14-day temporary restriction on uploading and study squad creation.</li>
            <li><strong>Severe or Repeated Violations:</strong> Permanent account deactivation and campus academic dean reporting.</li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
