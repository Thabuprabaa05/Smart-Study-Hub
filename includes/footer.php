</main>

<!-- Global Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                    <div class="logo-icon" style="width: 36px; height: 36px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <span style="color: #ffffff; font-weight: 800; font-size: 1.25rem;">Smart Study Hub</span>
                </div>
                <p style="font-size: 0.875rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    An all-in-one academic collaboration portal designed for college students to organize study groups, share peer-reviewed lecture notes, and learn together effectively.
                </p>
                <div style="display: flex; gap: 0.75rem;">
                    <span class="badge badge-primary"><i class="fas fa-university"></i> College Portal</span>
                    <span class="badge badge-accent"><i class="fas fa-shield-alt"></i> Verified Peer Notes</span>
                </div>
            </div>

            <div class="footer-col">
                <h5>Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/index.php"><i class="fas fa-angle-right"></i> Home</a></li>
                    <li><a href="<?= BASE_URL ?>/subjects.php"><i class="fas fa-angle-right"></i> Browse Subjects</a></li>
                    <li><a href="<?= BASE_URL ?>/notes.php"><i class="fas fa-angle-right"></i> Notes & Materials</a></li>
                    <li><a href="<?= BASE_URL ?>/groups.php"><i class="fas fa-angle-right"></i> Active Study Groups</a></li>
                    <li><a href="<?= BASE_URL ?>/upload_note.php"><i class="fas fa-angle-right"></i> Upload Notes</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h5>Student Resources</h5>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/study_tips.php"><i class="fas fa-angle-right"></i> Study Tips & Pomodoro</a></li>
                    <li><a href="<?= BASE_URL ?>/rules.php"><i class="fas fa-angle-right"></i> Community Guidelines</a></li>
                    <li><a href="<?= BASE_URL ?>/search.php"><i class="fas fa-angle-right"></i> Global Resource Search</a></li>
                    <li><a href="<?= BASE_URL ?>/dashboard.php"><i class="fas fa-angle-right"></i> Student Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/login.php"><i class="fas fa-angle-right"></i> Admin Access</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h5>Academic Support</h5>
                <p style="font-size: 0.85rem; margin-bottom: 0.75rem;">
                    Have questions, feedback, or need moderation assistance?
                </p>
                <p style="font-size: 0.85rem; color: #cbd5e1;">
                    <i class="far fa-envelope text-primary"></i> support@smartstudy.edu<br>
                    <i class="fas fa-map-marker-alt text-primary"></i> Main Campus, Academic Block B
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                &copy; <?= date('Y') ?> <strong>Smart Study Hub</strong>. Built for College Academic Excellence.
            </div>
            <div style="display: flex; gap: 1.5rem;">
                <a href="<?= BASE_URL ?>/rules.php" style="color: #94a3b8;">Terms & Guidelines</a>
                <a href="<?= BASE_URL ?>/study_tips.php" style="color: #94a3b8;">Study Techniques</a>
            </div>
        </div>
    </div>
</footer>

<!-- Group Details Modal Backdrop & Container -->
<div class="modal-backdrop" id="group-modal-backdrop">
    <div class="modal-content" id="group-modal-content-area">
        <!-- Injected via JavaScript AJAX -->
    </div>
</div>

<!-- Report Content Modal Backdrop & Container -->
<div class="modal-backdrop" id="report-modal-backdrop">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h4 class="modal-title text-danger"><i class="fas fa-flag"></i> Report Inappropriate Content</h4>
                <div class="text-muted" style="font-size: 0.85rem;" id="report_content_title_display"></div>
            </div>
            <button type="button" class="btn btn-icon btn-secondary" onclick="closeReportModal()">&times;</button>
        </div>
        <form id="report-form" onsubmit="return submitReportAjax(this)">
            <input type="hidden" name="content_type" id="report_content_type" value="">
            <input type="hidden" name="content_id" id="report_content_id" value="">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="report_reason">Reason for reporting</label>
                    <select name="reason" id="report_reason" class="form-control" required>
                        <option value="">-- Select a reason --</option>
                        <option value="Spam">Spam or Advertisement</option>
                        <option value="Inappropriate content">Inappropriate / Harmful Content</option>
                        <option value="Copyright issue">Copyright Infringement</option>
                        <option value="Wrong subject">Wrong Subject / Mislabeled Material</option>
                        <option value="Offensive content">Offensive / Abusive Language</option>
                        <option value="Other">Other Violation</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="report_description">Additional Details</label>
                    <textarea name="description" id="report_description" class="form-control" rows="3" placeholder="Please provide specific details so our student coordinators can review this quickly..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeReportModal()">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane"></i> Submit Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container"></div>

<!-- Mobile menu toggle styling helper -->
<style>
@media (max-width: 900px) {
    #mobile-menu-toggle { display: inline-flex !important; }
}
</style>

<!-- Main Client Script -->
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>

</body>
</html>
