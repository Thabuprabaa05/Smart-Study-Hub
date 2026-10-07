/**
 * Smart Study Hub - Core JavaScript
 * Handles AJAX actions, dynamic filtering, file preview, modals, and toasts.
 */

document.addEventListener('DOMContentLoaded', () => {
    initDropdowns();
    initToasts();
    initFilePreview();
    initInstantFilters();
    initStarRating();
    initModalHandlers();
});

// CSRF Token Helper
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// -------------------------------------------------------------
// Toast Notifications
// -------------------------------------------------------------
function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';

    toast.innerHTML = `
        <i class="fas fa-${icon} text-${type === 'error' ? 'danger' : type}" style="font-size: 1.25rem;"></i>
        <div class="toast-message">${message}</div>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function initToasts() {
    // Check if flash messages exist on page load
    const flashElements = document.querySelectorAll('.flash-data');
    flashElements.forEach(el => {
        const type = el.dataset.type || 'info';
        const msg = el.dataset.message || '';
        if (msg) showToast(msg, type);
    });
}

// -------------------------------------------------------------
// Dropdown menus
// -------------------------------------------------------------
function initDropdowns() {
    const dropdownToggles = document.querySelectorAll('[data-toggle="dropdown"]');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const parent = toggle.closest('.dropdown');
            
            // Close other open dropdowns
            document.querySelectorAll('.dropdown.open').forEach(d => {
                if (d !== parent) d.classList.remove('open');
            });

            if (parent) parent.classList.toggle('open');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
    });
}

// -------------------------------------------------------------
// File Preview for Notes Upload
// -------------------------------------------------------------
function initFilePreview() {
    const fileInput = document.getElementById('note_file');
    const previewContainer = document.getElementById('file-preview-card');
    const dropzone = document.getElementById('upload-dropzone');

    if (!fileInput || !previewContainer) return;

    function handleFile(file) {
        if (!file) return;

        const fileName = file.name;
        const fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
        const ext = fileName.split('.').pop().toLowerCase();
        const allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];

        let iconClass = 'fa-file-alt text-muted';
        if (ext === 'pdf') iconClass = 'fa-file-pdf text-danger';
        else if (['doc', 'docx'].includes(ext)) iconClass = 'fa-file-word text-primary';
        else if (['ppt', 'pptx'].includes(ext)) iconClass = 'fa-file-powerpoint text-warning';

        const isValid = allowedExts.includes(ext) && file.size <= 25 * 1024 * 1024;

        previewContainer.innerHTML = `
            <div style="font-size: 2.2rem; margin-right: 0.5rem;"><i class="fas ${iconClass}"></i></div>
            <div style="flex: 1;">
                <div class="font-semibold text-main" style="word-break: break-all;">${fileName}</div>
                <div class="text-muted" style="font-size: 0.85rem;">Size: ${fileSize} &bull; Type: .${ext.toUpperCase()}</div>
                ${!isValid ? `<div class="badge badge-danger" style="margin-top: 4px;">Invalid file: Allowed only PDF, DOC, DOCX, PPT, PPTX (Max 25MB)</div>` : `<div class="badge badge-accent" style="margin-top: 4px;"><i class="fas fa-check"></i> Ready to upload</div>`}
            </div>
            <button type="button" class="btn btn-sm btn-secondary" id="clear-file-btn"><i class="fas fa-times"></i></button>
        `;
        previewContainer.style.display = 'flex';

        document.getElementById('clear-file-btn').addEventListener('click', () => {
            fileInput.value = '';
            previewContainer.innerHTML = '';
            previewContainer.style.display = 'none';
        });
    }

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            handleFile(fileInput.files[0]);
        }
    });

    if (dropzone) {
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });
        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                handleFile(fileInput.files[0]);
            }
        });
    }
}

// -------------------------------------------------------------
// Dynamic Instant Filters (Subjects, Notes, Groups)
// -------------------------------------------------------------
function initInstantFilters() {
    // 1. Subjects Instant Filter
    const subjectSearch = document.getElementById('subject-search');
    const deptFilter = document.getElementById('dept-filter');
    const semFilter = document.getElementById('sem-filter');
    const subjectCards = document.querySelectorAll('.subject-card');

    function filterSubjects() {
        if (!subjectCards.length) return;
        const query = (subjectSearch ? subjectSearch.value : '').toLowerCase().trim();
        const dept = (deptFilter ? deptFilter.value : '').toLowerCase();
        const sem = (semFilter ? semFilter.value : '').toLowerCase();

        let visibleCount = 0;
        subjectCards.forEach(card => {
            const name = (card.dataset.name || '').toLowerCase();
            const code = (card.dataset.code || '').toLowerCase();
            const cardDept = (card.dataset.dept || '').toLowerCase();
            const cardSem = (card.dataset.sem || '').toLowerCase();

            const matchesQuery = !query || name.includes(query) || code.includes(query);
            const matchesDept = !dept || cardDept === dept;
            const matchesSem = !sem || cardSem === sem;

            if (matchesQuery && matchesDept && matchesSem) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        const emptyMsg = document.getElementById('no-subjects-found');
        if (emptyMsg) emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    if (subjectSearch) subjectSearch.addEventListener('input', filterSubjects);
    if (deptFilter) deptFilter.addEventListener('change', filterSubjects);
    if (semFilter) semFilter.addEventListener('change', filterSubjects);

    // 2. Notes Instant Filter
    const noteSearch = document.getElementById('note-search');
    const noteSubjectFilter = document.getElementById('note-subject-filter');
    const noteTypeFilter = document.getElementById('note-type-filter');
    const noteCards = document.querySelectorAll('.note-card');

    function filterNotes() {
        if (!noteCards.length) return;
        const query = (noteSearch ? noteSearch.value : '').toLowerCase().trim();
        const subId = (noteSubjectFilter ? noteSubjectFilter.value : '');
        const type = (noteTypeFilter ? noteTypeFilter.value : '').toLowerCase();

        let visibleCount = 0;
        noteCards.forEach(card => {
            const title = (card.dataset.title || '').toLowerCase();
            const tags = (card.dataset.tags || '').toLowerCase();
            const cardSubId = card.dataset.subjectId || '';
            const cardType = (card.dataset.fileType || '').toLowerCase();

            const matchesQuery = !query || title.includes(query) || tags.includes(query);
            const matchesSub = !subId || cardSubId === subId;
            const matchesType = !type || cardType === type;

            if (matchesQuery && matchesSub && matchesType) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        const emptyMsg = document.getElementById('no-notes-found');
        if (emptyMsg) emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    if (noteSearch) noteSearch.addEventListener('input', filterNotes);
    if (noteSubjectFilter) noteSubjectFilter.addEventListener('change', filterNotes);
    if (noteTypeFilter) noteTypeFilter.addEventListener('change', filterNotes);

    // 3. Groups Instant Filter
    const groupSearch = document.getElementById('group-search');
    const groupDeptFilter = document.getElementById('group-dept-filter');
    const groupMeetingFilter = document.getElementById('group-meeting-filter');
    const groupCards = document.querySelectorAll('.group-card');

    function filterGroups() {
        if (!groupCards.length) return;
        const query = (groupSearch ? groupSearch.value : '').toLowerCase().trim();
        const dept = (groupDeptFilter ? groupDeptFilter.value : '').toLowerCase();
        const mode = (groupMeetingFilter ? groupMeetingFilter.value : '').toLowerCase();

        let visibleCount = 0;
        groupCards.forEach(card => {
            const name = (card.dataset.name || '').toLowerCase();
            const sub = (card.dataset.subject || '').toLowerCase();
            const cardDept = (card.dataset.dept || '').toLowerCase();
            const cardMode = (card.dataset.mode || '').toLowerCase();

            const matchesQuery = !query || name.includes(query) || sub.includes(query);
            const matchesDept = !dept || cardDept === dept;
            const matchesMode = !mode || cardMode === mode;

            if (matchesQuery && matchesDept && matchesMode) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        const emptyMsg = document.getElementById('no-groups-found');
        if (emptyMsg) emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    if (groupSearch) groupSearch.addEventListener('input', filterGroups);
    if (groupDeptFilter) groupDeptFilter.addEventListener('change', filterGroups);
    if (groupMeetingFilter) groupMeetingFilter.addEventListener('change', filterGroups);
}

// -------------------------------------------------------------
// Interactive Star Rating
// -------------------------------------------------------------
function initStarRating() {
    const starContainer = document.querySelector('.star-rating-interactive');
    if (!starContainer) return;

    const stars = starContainer.querySelectorAll('i');
    const ratingInput = document.getElementById('rating_value');
    const noteId = starContainer.dataset.noteId;

    stars.forEach(star => {
        star.addEventListener('mouseover', () => {
            const val = parseInt(star.dataset.value);
            stars.forEach((s, idx) => {
                if (idx < val) s.classList.add('hover');
                else s.classList.remove('hover');
            });
        });

        star.addEventListener('mouseout', () => {
            stars.forEach(s => s.classList.remove('hover'));
        });

        star.addEventListener('click', async () => {
            const val = parseInt(star.dataset.value);
            if (ratingInput) ratingInput.value = val;
            stars.forEach((s, idx) => {
                if (idx < val) s.classList.add('active');
                else s.classList.remove('active');
            });

            if (noteId) {
                // Submit rating via AJAX
                try {
                    const res = await fetch('/smart_study_hub/api/rate_note.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken()
                        },
                        body: JSON.stringify({ note_id: noteId, rating: val })
                    });
                    const data = await res.json();
                    if (data.success) {
                        showToast(`Thank you! You rated this note ${val} stars.`, 'success');
                        const avgDisplay = document.getElementById('avg-rating-text');
                        if (avgDisplay && data.new_average) {
                            avgDisplay.textContent = `★ ${data.new_average} / 5.0`;
                        }
                    } else {
                        showToast(data.message || 'Could not submit rating.', 'error');
                    }
                } catch (err) {
                    showToast('Network error while rating.', 'error');
                }
            }
        });
    });
}

// -------------------------------------------------------------
// Bookmark Toggle (AJAX)
// -------------------------------------------------------------
async function toggleBookmark(noteId, buttonEl) {
    if (!noteId) return;

    try {
        const res = await fetch('/smart_study_hub/api/bookmark.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ note_id: noteId })
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            if (buttonEl) {
                const icon = buttonEl.querySelector('i');
                if (data.action === 'added') {
                    buttonEl.classList.add('bookmarked');
                    if (icon) {
                        icon.classList.remove('far');
                        icon.classList.add('fas', 'text-warning');
                    }
                    if (buttonEl.querySelector('.btn-text')) {
                        buttonEl.querySelector('.btn-text').textContent = 'Bookmarked';
                    }
                } else {
                    buttonEl.classList.remove('bookmarked');
                    if (icon) {
                        icon.classList.remove('fas', 'text-warning');
                        icon.classList.add('far');
                    }
                    if (buttonEl.querySelector('.btn-text')) {
                        buttonEl.querySelector('.btn-text').textContent = 'Bookmark';
                    }
                }
            }
        } else {
            showToast(data.message || 'Error updating bookmark.', 'error');
        }
    } catch (err) {
        showToast('Network error while updating bookmark.', 'error');
    }
}

// -------------------------------------------------------------
// Group Modal & Join/Leave AJAX Actions
// -------------------------------------------------------------
function initModalHandlers() {
    // Backdrop close handler
    const backdrop = document.getElementById('group-modal-backdrop');
    if (backdrop) {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) closeGroupModal();
        });
    }

    const reportBackdrop = document.getElementById('report-modal-backdrop');
    if (reportBackdrop) {
        reportBackdrop.addEventListener('click', (e) => {
            if (e.target === reportBackdrop) closeReportModal();
        });
    }
}

async function openGroupDetailsModal(groupId) {
    const backdrop = document.getElementById('group-modal-backdrop');
    const container = document.getElementById('group-modal-content-area');
    if (!backdrop || !container) return;

    container.innerHTML = `
        <div style="padding: 2.5rem; text-align: center;">
            <i class="fas fa-spinner fa-spin text-primary" style="font-size: 2rem;"></i>
            <p class="text-muted" style="margin-top: 1rem;">Loading group details...</p>
        </div>
    `;
    backdrop.classList.add('show');

    try {
        const res = await fetch(`/smart_study_hub/api/group_details.php?id=${groupId}`);
        const data = await res.json();

        if (data.success) {
            const g = data.group;
            const members = data.members || [];
            const isMember = data.is_member;
            const isOwner = data.is_owner;
            const isFull = data.is_full;

            let memberListHtml = members.map(m => `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div class="avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">${m.name.charAt(0)}</div>
                        <div>
                            <div class="font-semibold" style="font-size: 0.9rem;">${m.name}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">${m.department} &bull; ${m.semester}</div>
                        </div>
                    </div>
                    <div>
                        ${m.role === 'owner' ? '<span class="badge badge-primary">Creator / Admin</span>' : '<span class="badge badge-neutral">Member</span>'}
                    </div>
                </div>
            `).join('');

            container.innerHTML = `
                <div class="modal-header">
                    <div>
                        <span class="badge badge-secondary" style="margin-bottom: 0.35rem;">${g.subject_code} - ${g.subject_name}</span>
                        <h3 class="modal-title">${g.group_name}</h3>
                    </div>
                    <button type="button" class="btn btn-icon btn-secondary" onclick="closeGroupModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p style="color: var(--text-muted); margin-bottom: 1.25rem;">${g.description}</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; background: var(--bg-subtle); padding: 1rem; border-radius: var(--radius-md);">
                        <div>
                            <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Meeting Mode</div>
                            <div class="font-semibold text-main"><i class="fas fa-video text-primary"></i> ${g.meeting_preference}</div>
                        </div>
                        <div>
                            <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Capacity</div>
                            <div class="font-semibold text-main">${g.member_count} / ${g.max_members} members</div>
                        </div>
                        <div>
                            <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Department</div>
                            <div class="font-semibold text-main">${g.department}</div>
                        </div>
                        <div>
                            <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Created By</div>
                            <div class="font-semibold text-main">${g.creator_name}</div>
                        </div>
                    </div>

                    <h5 style="margin-bottom: 0.75rem;">Group Members (${members.length})</h5>
                    <div style="max-height: 180px; overflow-y: auto;">
                        ${memberListHtml}
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="/smart_study_hub/group.php?id=${g.id}" class="btn btn-secondary">
                        <i class="fas fa-external-link-alt"></i> Open Full Group Page
                    </a>
                    ${isOwner ? `
                        <span class="badge badge-primary" style="padding: 0.5rem 1rem;">Group Creator</span>
                    ` : isMember ? `
                        <button type="button" class="btn btn-danger" onclick="leaveGroupAjax(${g.id}, this)">
                            <i class="fas fa-sign-out-alt"></i> Leave Group
                        </button>
                    ` : isFull ? `
                        <button type="button" class="btn btn-secondary" disabled>Group Full</button>
                    ` : `
                        <button type="button" class="btn btn-primary" onclick="joinGroupAjax(${g.id}, this)">
                            <i class="fas fa-user-plus"></i> Join Group
                        </button>
                    `}
                </div>
            `;
        } else {
            container.innerHTML = `
                <div class="modal-body" style="text-align: center; padding: 2rem;">
                    <i class="fas fa-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                    <p style="margin-top: 1rem;">${data.message || 'Could not load group.'}</p>
                    <button class="btn btn-secondary" style="margin-top: 1rem;" onclick="closeGroupModal()">Close</button>
                </div>
            `;
        }
    } catch (err) {
        container.innerHTML = `
            <div class="modal-body" style="text-align: center; padding: 2rem;">
                <p class="text-danger">Failed to load group details.</p>
                <button class="btn btn-secondary" onclick="closeGroupModal()">Close</button>
            </div>
        `;
    }
}

function closeGroupModal() {
    const backdrop = document.getElementById('group-modal-backdrop');
    if (backdrop) backdrop.classList.remove('show');
}

// Join Group AJAX
async function joinGroupAjax(groupId, buttonEl) {
    if (buttonEl) buttonEl.disabled = true;
    try {
        const res = await fetch('/smart_study_hub/api/join_group.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ group_id: groupId })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            openGroupDetailsModal(groupId); // Refresh modal view
            // If on groups.php, update card button
            const cardBtn = document.getElementById(`join-btn-${groupId}`);
            if (cardBtn) {
                cardBtn.outerHTML = `<a href="/smart_study_hub/group.php?id=${groupId}" class="btn btn-sm btn-outline-primary"><i class="fas fa-check"></i> Joined</a>`;
            }
        } else {
            showToast(data.message || 'Could not join group.', 'error');
            if (buttonEl) buttonEl.disabled = false;
        }
    } catch (err) {
        showToast('Network error while joining group.', 'error');
        if (buttonEl) buttonEl.disabled = false;
    }
}

// Leave Group AJAX
async function leaveGroupAjax(groupId, buttonEl) {
    if (!confirm('Are you sure you want to leave this study group?')) return;
    if (buttonEl) buttonEl.disabled = true;

    try {
        const res = await fetch('/smart_study_hub/api/leave_group.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ group_id: groupId })
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'info');
            openGroupDetailsModal(groupId); // Refresh modal
            // If on groups.php, refresh or update button
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast(data.message || 'Could not leave group.', 'error');
            if (buttonEl) buttonEl.disabled = false;
        }
    } catch (err) {
        showToast('Network error while leaving group.', 'error');
        if (buttonEl) buttonEl.disabled = false;
    }
}

// -------------------------------------------------------------
// Report Content Modal & AJAX
// -------------------------------------------------------------
function openReportModal(contentType, contentId, contentTitle = '') {
    const backdrop = document.getElementById('report-modal-backdrop');
    const typeInput = document.getElementById('report_content_type');
    const idInput = document.getElementById('report_content_id');
    const titleLabel = document.getElementById('report_content_title_display');

    if (!backdrop || !typeInput || !idInput) return;

    typeInput.value = contentType;
    idInput.value = contentId;
    if (titleLabel) {
        titleLabel.textContent = contentTitle ? `"${contentTitle}"` : `${contentType} #${contentId}`;
    }

    backdrop.classList.add('show');
}

function closeReportModal() {
    const backdrop = document.getElementById('report-modal-backdrop');
    if (backdrop) backdrop.classList.remove('show');
}

async function submitReportAjax(formEl) {
    const formData = new FormData(formEl);
    const dataObj = {
        content_type: formData.get('content_type'),
        content_id: formData.get('content_id'),
        reason: formData.get('reason'),
        description: formData.get('description')
    };

    try {
        const res = await fetch('/smart_study_hub/api/report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify(dataObj)
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message || 'Report submitted for review. Thank you!', 'success');
            closeReportModal();
            formEl.reset();
        } else {
            showToast(data.message || 'Failed to submit report.', 'error');
        }
    } catch (err) {
        showToast('Network error while submitting report.', 'error');
    }
    return false;
}

// -------------------------------------------------------------
// Mark Notification Read (AJAX)
// -------------------------------------------------------------
async function markNotificationRead(notifId, el) {
    try {
        const res = await fetch('/smart_study_hub/api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ action: 'mark_read', id: notifId })
        });
        const data = await res.json();
        if (data.success) {
            if (el) el.classList.remove('unread');
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const count = Math.max(0, parseInt(badge.textContent || '1') - 1);
                if (count === 0) badge.remove();
                else badge.textContent = count;
            }
        }
    } catch (err) {
        console.error(err);
    }
}
