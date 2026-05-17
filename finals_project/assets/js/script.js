
let currentView = 'home';
let calendarDate = new Date();
let userClasses = [];
let userRole = document.body?.dataset?.role || 'student';
let activeClassId = null;

const API_BASE = '../api/api.php';

// ─── UTILITY ───
async function api(action, method = 'GET', body = null) {
    try {
        const url = new URL(API_BASE, window.location.href);
        url.searchParams.set('action', action);

        const opts = { method };

        if (body && method === 'POST') {
            opts.headers = { 'Content-Type': 'application/json' };
            opts.body = JSON.stringify(body);
        }

        if (body && method === 'GET') {
            Object.entries(body).forEach(([k, v]) => {
                if (v !== null && typeof v !== 'undefined') {
                    url.searchParams.set(k, v);
                }
            });
        }

        const res = await fetch(url.toString(), opts);

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const contentType = res.headers.get('content-type');

        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Invalid JSON response from server');
        }

        return await res.json();

    } catch (err) {
        console.error(`API Error (${action}):`, err);

        showToast(
            err.message || 'Server error occurred.',
            'error'
        );

        return {
            status: false,
            message: err.message || 'Request failed'
        };
    }
}

function $(id) {
    return document.getElementById(id);
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');

    toast.className = `toast toast--${type}`;
    toast.textContent = message;

    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.add('is-visible');
    });

    setTimeout(() => {
        toast.classList.remove('is-visible');

        setTimeout(() => {
            toast.remove();
        }, 300);

    }, 3000);
}

// ─── VIEW SWITCHING ───
function switchView(viewName) {
    currentView = viewName;


    document.querySelectorAll('.view').forEach(v => {
        v.classList.remove('is-active');
    });

    const target = $(`view-${viewName}`);

    if (target) {
        target.classList.add('is-active');
    }

    document.querySelectorAll('.nav-item[data-view]').forEach(btn => {
        btn.classList.toggle(
            'is-active',
            btn.dataset.view === viewName
        );
    });

    // Refresh data
    if (viewName === 'home') {
        loadClasses();
    }

    if (viewName === 'calendar' && userRole === 'student') {
        renderCalendar();
    }

    if (viewName === 'announcements' && userRole === 'student') {
        loadAnnouncements();
    }

    if (viewName === 'teacher-announcements' && userRole === 'teacher') {
        loadTeacherClassesForAnnouncementModal();
        loadTeacherAnnouncements();
    }


    if (viewName === 'classwork' && userRole === 'student') {
        renderClasswork();
    }

    if (viewName === 'people' && userRole === 'student') {
        loadPeople();
    }

    if (viewName === 'profile') {
        loadProfile();
    }

    if (viewName === 'class-detail') {
        // Called from openClassDetail and also from teacher sidebar.
        // Ensure activeClassId is set from the select when we arrive here.
        const sel = $('class-detail-select');
        if (userRole === 'teacher' && sel) {
            // If activeClassId is missing, try select value first.
            const selVal = parseInt(sel.value, 10);
            if (selVal && !activeClassId) {
                activeClassId = selVal;
            }

            // If still missing, auto-pick the first non-empty option.
            if (!activeClassId) {
                const firstOpt = sel.querySelector('option[value]:not([value=""])');
                const firstVal = firstOpt ? parseInt(firstOpt.value, 10) : null;
                if (firstVal) {
                    activeClassId = firstVal;
                    sel.value = String(firstVal);
                }
            }
        }

        // If select value exists but renderClassDetail still ends up with null (async timing), try once more.
        if (!activeClassId && sel && sel.value) {
            const selVal2 = parseInt(sel.value, 10);
            if (selVal2) activeClassId = selVal2;
        }

        renderClassDetail(activeClassId);
    }

}

// ─── MODALS ───
function openModal(id) {
    const modal = $(id);
    if (!modal) return;

    // Ensure correct modal root is set open.
    modal.classList.add('is-open');

    // Scroll lock
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';

    // Initial focus (first focusable element inside modal)
    const focusable = modal.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    if (focusable && typeof focusable.focus === 'function') {
        focusable.focus();
    }

    // ESC to close (one handler per open)
    const escHandler = (e) => {
        if (e.key === 'Escape') {
            document.removeEventListener('keydown', escHandler);
            closeModal(id);
        }
    };
    document.addEventListener('keydown', escHandler);
}

function closeModal(id) {
    const modal = $(id);

    if (modal) {
        modal.classList.remove('is-open');
        // Some browsers may keep focus/overlay interactions; force-hide.
        modal.style.display = 'none';
    }

    // Restore scroll
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
}


// ─── CLASS CODE GENERATION ───
function generateCode() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    let code = '';

    for (let i = 0; i < 8; i++) {
        code += chars.charAt(
            Math.floor(Math.random() * chars.length)
        );
    }

    const input = $('generated-code');

    if (input) {
        input.value = code;
    }
}

function copyCode() {
    const codeEl = $('display-code-value');

    if (!codeEl) return;

    const code = codeEl.textContent;

    navigator.clipboard.writeText(code)
        .then(() => {
            showToast('Code copied!');
        })
        .catch(() => {
            showToast('Failed to copy code.', 'error');
        });
}

// ─── CREATE CLASS ───
async function createClass() {
    const subject = $('class-subject')?.value?.trim() || '';
    const section = $('class-section')?.value?.trim() || '';
    const courseCode = $('class-code')?.value?.trim() || '';
    const inviteCode = $('generated-code')?.value?.trim() || '';

    if (!subject || !section || !courseCode || !inviteCode) {
        showToast(
            'Please fill all fields and generate a code.',
            'error'
        );
        return;
    }

    const res = await api(
        'create_class',
        'POST',
        {
            subject,
            section,
            course_code: courseCode,
            invite_code: inviteCode
        }
    );

    if (res.status) {
        showToast('Class created successfully!');

        closeModal('modal-create-class');

        const displayCode = $('display-code-value');

        if (displayCode) {
            displayCode.textContent = inviteCode;
        }

        openModal('modal-show-code');

        loadClasses();

    } else {
        showToast(
            res.message || 'Failed to create class.',
            'error'
        );
    }
}

// ─── JOIN CLASS ───
async function joinClass() {
    const input = $('join-code-input');

    if (!input) return;

    const code = input.value.trim().toUpperCase();

    if (!code) {
        showToast(
            'Please enter a class code.',
            'error'
        );
        return;
    }

    const res = await api(
        'join_class',
        'POST',
        { invite_code: code }
    );

    if (res.status) {
        showToast('Joined class successfully!');

        input.value = '';

        loadClasses();

    } else {
        showToast(
            res.message || 'Failed to join class.',
            'error'
        );
    }
}

// ─── LOAD CLASSES ───
async function loadClasses() {
    const res = await api('get_classes');

    if (!res.status) return;

    userClasses = res.data || [];

    const containerId = userRole === 'teacher'
        ? 'teacher-classes'
        : 'student-classes';

    const container = $(containerId);

    if (!container) return;

    if (userClasses.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <p class="empty-state__title">
                    No classes yet
                </p>

                <p class="empty-state__desc">
                    ${
                        userRole === 'teacher'
                            ? 'Create your first class to get started.'
                            : 'Join a class using an invite code.'
                    }
                </p>
            </div>
        `;
        return;
    }

    container.innerHTML = userClasses.map(cls => `
        <div
            class="class-card"
            onclick="openClassDetail(${cls.id})"
        >
            <div class="class-card__header">
                <span class="class-card__subject">
                    ${escapeHtml(cls.subject)}
                </span>

                <span class="class-card__code">
                    ${escapeHtml(cls.course_code)}
                </span>
            </div>

            <div class="class-card__meta">
                Section ${escapeHtml(cls.section)}
            </div>

            <div class="class-card__footer">
                <span>
                    ${cls.student_count ?? 0} students
                </span>

                <span>
                    ${escapeHtml(cls.invite_code)}
                </span>
            </div>
        </div>
    `).join('');

    // Populate filters
    if (userRole === 'student') {
        const select = $('classwork-subject');

        if (select) {
            select.innerHTML =
                '<option value="all">All Subjects</option>' +
                userClasses.map(c => `
                    <option value="${c.id}">
                        ${escapeHtml(c.subject)}
                    </option>
                `).join('');
        }
    }
}

function loadClassesForSelect() {
    const sel = $('class-detail-select');
    if (!sel) return;

    sel.innerHTML = '<option value="">Select a class</option>';

    const fillFromState = () => {
        const classes = userClasses || [];
        if (classes.length === 0) return;

        sel.innerHTML += classes.map(c => `
            <option value="${c.id}">${escapeHtml(c.subject)} (${escapeHtml(c.course_code)}) — ${escapeHtml(c.section)}</option>
        `).join('');
    };

    // If classes already loaded, just fill.
    if (userClasses && userClasses.length > 0) {
        fillFromState();
        return;
    }

    // Otherwise load from API, then fill.
    api('get_classes')
        .then(res => {
            if (res && res.status) {
                userClasses = res.data || [];
                fillFromState();
            }
        })
        .catch(() => {
            // Keep select as-is.
        });
}

function openClassDetail(classId) {
    activeClassId = classId;

    // Keep modal class_id in sync so teacher can create an activity immediately.
    const modalClassIdEl = $('teacher-activity-class-id');
    if (modalClassIdEl && classId) {
        modalClassIdEl.value = String(classId);
    }

    const sel = $('class-detail-select');

    if (
        sel &&
        sel.querySelector(`option[value="${classId}"]`)
    ) {
        sel.value = String(classId);
    }

    switchView('class-detail');

    renderClassDetail(classId);
}

// ─── CALENDAR ───
async function renderCalendar() {
    const monthEl = $('calendar-month');
    const gridEl = $('calendar-grid');

    if (!monthEl || !gridEl) return;

    const year = calendarDate.getFullYear();
    const month = calendarDate.getMonth() + 1;

    monthEl.textContent =
        calendarDate.toLocaleDateString(
            'en-US',
            {
                month: 'long',
                year: 'numeric'
            }
        );

    const res = await api(
        'get_calendar',
        'GET',
        { month, year }
    );

    const events = res.status
        ? (res.data || [])
        : [];

    const firstDay =
        new Date(year, month - 1, 1).getDay();

    const daysInMonth =
        new Date(year, month, 0).getDate();

    const prevDays =
        new Date(year, month - 1, 0).getDate();

    let html = '';

    const dayNames = [
        'Sun',
        'Mon',
        'Tue',
        'Wed',
        'Thu',
        'Fri',
        'Sat'
    ];

    dayNames.forEach(d => {
        html += `
            <div
                class="calendar-cell"
                style="
                    min-height:auto;
                    font-weight:700;
                    color:var(--neutral-500);
                    text-align:center
                "
            >
                ${d}
            </div>
        `;
    });

    for (let i = firstDay - 1; i >= 0; i--) {
        html += `
            <div class="calendar-cell is-other-month">
                <span class="calendar-day-number">
                    ${prevDays - i}
                </span>
            </div>
        `;
    }

    const today = new Date();

    for (let day = 1; day <= daysInMonth; day++) {

        const isToday =
            today.getDate() === day &&
            today.getMonth() + 1 === month &&
            today.getFullYear() === year;

        const dayEvents = events.filter(e => {
            const d = new Date(
                String(e.event_date).replace(' ', 'T')
            );

            return d.getDate() === day;
        });

        const eventsHtml = dayEvents.map(e => `
            <div class="calendar-event">
                ${escapeHtml(e.title)}
            </div>
        `).join('');

        html += `
            <div class="calendar-cell ${isToday ? 'is-today' : ''}">
                <span class="calendar-day-number">
                    ${day}
                </span>

                ${eventsHtml}
            </div>
        `;
    }

    const totalCells = firstDay + daysInMonth;

    const remaining =
        (7 - (totalCells % 7)) % 7;

    for (let i = 1; i <= remaining; i++) {
        html += `
            <div class="calendar-cell is-other-month">
                <span class="calendar-day-number">
                    ${i}
                </span>
            </div>
        `;
    }

    gridEl.innerHTML = html;
}

function prevMonth() {
    calendarDate.setMonth(
        calendarDate.getMonth() - 1
    );

    renderCalendar();
}

function nextMonth() {
    calendarDate.setMonth(
        calendarDate.getMonth() + 1
    );

    renderCalendar();
}

// ─── ANNOUNCEMENTS (FIX) ───
async function loadAnnouncements() {
    const feed = $('announcement-feed');
    if (!feed) return;

    feed.innerHTML = `<div class="empty-state">Loading announcements...</div>`;

    const res = await api('get_announcements');

    if (!res.status) {
        feed.innerHTML = `<div class="empty-state">No announcements.</div>`;
        return;
    }

    const announcements = res.data || [];

    if (announcements.length === 0) {
        feed.innerHTML = `
            <div class="empty-state">
                <p class="empty-state__title">No announcements</p>
                <p class="empty-state__desc">Your teachers will post updates here.</p>
            </div>
        `;
        return;
    }

    feed.innerHTML = announcements.map(a => `
        <div class="announcement-card" style="padding:12px;border:1px solid var(--neutral-200);border-radius:var(--radius);background:#fff;margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                <div style="min-width:220px;">
                    <div style="font-weight:800;color:var(--brand-900);margin-bottom:6px;">
                        ${escapeHtml(a.title)}
                    </div>
                    <div style="color:var(--neutral-700);white-space:pre-wrap;">
                        ${escapeHtml(a.content)}
                    </div>
                </div>
                <div style="text-align:right;min-width:160px;">
                    <div style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:6px;">
                        ${escapeHtml(a.subject || '')}${a.section ? ` — ${escapeHtml(a.section)}` : ''}
                    </div>
                    <div style="font-size:0.85rem;color:var(--neutral-600);">
                        Posted by ${escapeHtml(a.teacher_name || a.username || '')}
                    </div>
                    <div style="font-size:0.75rem;color:var(--neutral-500);margin-top:4px;">
                        ${a.created_at ? timeAgo(a.created_at) : ''}
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Teacher announcements are managed from the dedicated Announcement Manager page.

async function loadTeacherClassesForAnnouncementModal() {
    const grid = $('teacher-announcement-class-grid');
    const emptyEl = $('teacher-announcement-class-grid-empty');
    if (!grid) return;

    grid.innerHTML = `<div class="empty-state">Loading classes...</div>`;

    const res = await api('get_classes');
    if (!res.status) {
        grid.innerHTML = `<div class="empty-state">Failed to load classes.</div>`;
        return;
    }

    const classes = res.data || [];
    if (classes.length === 0) {
        if (emptyEl) emptyEl.style.display = 'flex';
        grid.innerHTML = '';
        return;
    }

    if (emptyEl) emptyEl.style.display = 'none';

    grid.innerHTML = classes.map(c => `
        <div
            class="classwork-card"
            style="cursor:pointer;"
            onclick="openTeacherAnnouncementCreateModal(${c.id});"
        >
            <div style="font-weight:900;color:var(--brand-900);margin-bottom:6px;">
                ${escapeHtml(c.subject)}
            </div>
            <div style="font-size:0.875rem;color:var(--neutral-700);">
                Section ${escapeHtml(c.section)}
            </div>
            <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <span style="font-size:0.75rem;color:var(--neutral-500);background:var(--neutral-100);padding:2px 8px;border-radius:var(--radius-pill);font-weight:600;">
                    ${escapeHtml(c.course_code)}
                </span>
                <button type="button" class="btn btn--secondary" style="padding:8px 12px;" onclick="event.stopPropagation(); openTeacherAnnouncementCreateModal(${c.id});">
                    Create
                </button>
            </div>
        </div>
    `).join('');
}

function openTeacherAnnouncementCreateModal(classId) {
    const hidden = $('teacher-announcement-modal-class-id');
    if (!hidden) return;

    hidden.value = String(classId);

    // Reset fields for fresh create.
    const titleInput = $('teacher-announcement-modal-title');
    const contentInput = $('teacher-announcement-modal-content');
    const publishDateEl = $('teacher-announcement-modal-publish-date');
    const imageInput = $('teacher-announcement-modal-image');

    if (titleInput) titleInput.value = '';
    if (contentInput) contentInput.value = '';
    if (publishDateEl) publishDateEl.value = '';
    if (imageInput) imageInput.value = '';

    openModal('modal-teacher-create-announcement');
}

async function teacherSubmitAnnouncementFromModal() {
    const classId = parseInt($('teacher-announcement-modal-class-id')?.value || '0', 10);
    const titleInput = $('teacher-announcement-modal-title');
    const contentInput = $('teacher-announcement-modal-content');
    const publishDateEl = $('teacher-announcement-modal-publish-date');
    const imageInput = $('teacher-announcement-modal-image');

    if (!classId) {
        showToast('Select a class first.', 'error');
        return;
    }

    const title = titleInput?.value?.trim() || '';
    const content = contentInput?.value?.trim() || '';

    if (!title || !content) {
        showToast('Title and content are required.', 'error');
        return;
    }

    const publishDate = publishDateEl?.value ? publishDateEl.value : null;

    let imageUrl = null;
    const imageFile = imageInput?.files?.[0];
    if (imageFile) {
        const fd = new FormData();
        fd.append('file', imageFile);

        const uploadUrl = new URL('../api/upload.php', window.location.href);
        const upRes = await fetch(uploadUrl.toString(), { method: 'POST', body: fd }).then(r => r.json());

        if (!upRes.status) {
            showToast(upRes.message || 'Image upload failed.', 'error');
            return;
        }

        imageUrl = upRes.url || null;
    }

    const res = await api('create_announcement', 'POST', {
        class_id: classId,
        title,
        content,
        publish_date: publishDate,
        image_url: imageUrl
    });

    if (!res.status) {
        showToast(res.message || 'Failed to create announcement.', 'error');
        return;
    }

    closeModal('modal-teacher-create-announcement');
    await loadTeacherAnnouncements();
    await loadTeacherClassesForAnnouncementModal();
}

async function loadTeacherAnnouncements() {
    const list = $('teacher-announcement-manager-list');
    if (!list) return;

    list.innerHTML = `<div class="empty-state">Loading announcements...</div>`;

    const res = await api('get_teacher_announcements');

    if (!res.status) {
        list.innerHTML = `<div class="empty-state">${escapeHtml(res.message || 'Failed to load')}</div>`;
        return;
    }

    const items = res.data || [];

    if (items.length === 0) {
        list.innerHTML = `
            <div class="empty-state">
                <p class="empty-state__title">No announcements yet</p>
                <p class="empty-state__desc">Create one above.</p>
            </div>
        `;
        return;
    }

    list.innerHTML = items.map(a => `
        <div class="announcement-card" style="padding:14px;border:1px solid var(--neutral-200);border-radius:var(--radius);background:#fff;margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                <div style="min-width:240px;">
                    <div style="font-weight:900;color:var(--brand-900);margin-bottom:6px;">
                        ${escapeHtml(a.title)}
                    </div>

                    ${a.image_url ? `
                        <div style="margin:8px 0;">
                            <img src="${escapeHtml(a.image_url)}" alt="Announcement image" style="max-width:100%;height:auto;border-radius:var(--radius);" />
                        </div>
                    ` : ''}

                    <div style="color:var(--neutral-700);white-space:pre-wrap;">
                        ${escapeHtml(a.content)}
                    </div>

                    <div style="font-size:0.75rem;color:var(--neutral-500);margin-top:8px;">
                        ${escapeHtml(a.subject || '')}${a.section ? ` — ${escapeHtml(a.section)}` : ''}
                        ${a.publish_date ? ` • Publish: ${escapeHtml(String(a.publish_date).slice(0,10))}` : ''}
                    </div>
                </div>

                <div style="text-align:right;min-width:220px;">
                    <div style="font-size:0.75rem;color:var(--neutral-500);margin-bottom:10px;">
                        <span>Actions</span>
                    </div>

                    <div class="form-actions" style="justify-content:flex-end;gap:8px;">
                        <button class="btn btn--secondary" type="button" onclick="teacherLoadAnnouncementIntoForm(${a.id})">Edit</button>
                        <button class="btn btn--danger" type="button" onclick="teacherDeleteAnnouncement(${a.id})">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Legacy teacher announcement functions removed.
// The teacher announcement workflow in this page is handled by:
// - loadTeacherClassesForAnnouncementModal()
// - openTeacherAnnouncementCreateModal(classId)
// - teacherSubmitAnnouncementFromModal()
//
// Keeping old functions that referenced non-existent DOM ids caused inconsistent behavior.

async function teacherDeleteAnnouncement(announcementId) {
    if (!announcementId) return;
    if (!confirm('Delete this announcement?')) return;

    const res = await api('delete_announcement', 'POST', { announcement_id: announcementId });

    if (!res.status) {
        showToast(res.message || 'Delete failed.', 'error');
        return;
    }

    showToast('Announcement deleted.', 'success');
    await loadTeacherAnnouncements();
}

async function teacherLoadAnnouncementIntoForm(announcementId) {
    // Editing via this legacy function referenced non-existent DOM ids.
    // The teacher announcement UI on this page uses the modal:
    // - #modal-teacher-create-announcement
    // - teacherSubmitAnnouncementFromModal()
    //
    // Keeping this handler as a safe no-op to prevent breaking the create flow.
    showToast('Edit not supported in this announcement modal (yet).', 'error');
}

async function teacherUpdateAnnouncement() {
    const editId = $('teacher-announcement-edit-id')?.value;
    if (!editId) {
        showToast('No announcement selected for editing.', 'error');
        return;
    }

    const classSel = $('teacher-announcement-class-id');
    const publishDateEl = $('teacher-announcement-publish-date');
    const titleInput = $('teacher-announcement-title');
    const contentInput = $('teacher-announcement-content');
    const imageInput = $('teacher-announcement-image');

    const classId = parseInt(classSel.value, 10);
    if (!classId) {
        showToast('Select a class first.', 'error');
        return;
    }

    const title = titleInput.value.trim();
    const content = contentInput.value.trim();
    if (!title || !content) {
        showToast('Title and content are required.', 'error');
        return;
    }

    const publishDate = publishDateEl?.value ? publishDateEl.value : null;

    let imagePayload = null;
    const imageFile = imageInput?.files?.[0];
    if (imageFile) {
        const fd = new FormData();
        fd.append('file', imageFile);
        const uploadUrl = new URL('../api/upload.php', window.location.href);
        const upRes = await fetch(uploadUrl.toString(), { method: 'POST', body: fd }).then(r => r.json());
        if (!upRes.status) {
            showToast(upRes.message || 'Image upload failed.', 'error');
            return;
        }
        imagePayload = { url: upRes.url || null };
    }

    const res = await api('update_announcement', 'POST', {
        announcement_id: parseInt(editId, 10),
        class_id: classId,
        title,
        content,
        publish_date: publishDate,
        image_url: imagePayload?.url || null
    });

    if (!res.status) {
        showToast(res.message || 'Update failed.', 'error');
        return;
    }

    // Reset form back to create mode.
    titleInput.value = '';
    contentInput.value = '';
    if (publishDateEl) publishDateEl.value = '';
    if (imageInput) imageInput.value = '';
    $('teacher-announcement-edit-id').value = '';

    const updateBtn = document.querySelector('#view-teacher-announcements button');
    const btn = document.querySelector('#view-teacher-announcements button[onclick="teacherUpdateAnnouncement();"]');
    if (btn) {
        btn.setAttribute('onclick', 'teacherCreateAnnouncement();');
        btn.textContent = 'Create';
    }

    showToast('Announcement updated!', 'success');
    await loadTeacherAnnouncements();
}


// ─── CLASSWORK (FIX) ───
async function loadPending() {
    const panel = $('pending-list');
    if (!panel) return;

    panel.innerHTML = `<div class="empty-state">Loading pending activities...</div>`;

    const res = await api('get_pending');

    if (!res.status) {
        panel.innerHTML = `<div class="empty-state">No pending activities.</div>`;
        return;
    }

    const items = res.data || [];

    if (items.length === 0) {
        panel.innerHTML = `<div class="empty-state"><p class="empty-state__title">No pending activities</p></div>`;
        return;
    }

    panel.innerHTML = items.map(i => {
        const due = i.due_date ? escapeHtml(String(i.due_date)) : '';
        return `
            <div style="padding:12px;border:1px solid var(--neutral-200);border-radius:var(--radius);background:#fff;">
                <div style="font-weight:800;color:var(--brand-900);margin-bottom:6px;">
                    ${escapeHtml(i.title)}
                </div>
                <div style="font-size:0.85rem;color:var(--neutral-700);">
                    ${escapeHtml(i.subject || '')} ${i.section ? '— ' + escapeHtml(i.section) : ''}
                </div>
                <div style="font-size:0.75rem;color:var(--neutral-500);margin-top:6px;">
                    Due: ${due || '—'}
                </div>
            </div>
        `;
    }).join('');
}

function getClassworkFilters() {
    const subjectSel = $('classwork-subject');
    const typeSel = $('classwork-type');

    const subject = subjectSel ? subjectSel.value : 'all';
    const type = typeSel ? typeSel.value : 'all';

    return { subject, type };
}

async function renderClasswork() {
    const grid = $('classwork-grid');
    if (!grid) return;

    grid.innerHTML = `<div class="empty-state">Loading classwork...</div>`;

    const { subject, type } = getClassworkFilters();

    const res = await api('get_classwork', 'GET', {
        subject: subject,
        type: type
    });

    if (!res.status) {
        grid.innerHTML = `<div class="empty-state">No classwork found.</div>`;
        return;
    }

    const items = res.data || [];

    if (items.length === 0) {
        grid.innerHTML = `<div class="empty-state"><p class="empty-state__title">No classwork</p></div>`;
        return;
    }

    grid.innerHTML = items.map(item => {
        const due = item.due_date ? escapeHtml(String(item.due_date)) : '';
        const mySubmission = item.my_submission;
        return `
            <div class="classwork-card" style="padding:14px;border:1px solid var(--neutral-200);border-radius:var(--radius);background:#fff;">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                    <div>
                        <div style="font-weight:900;color:var(--brand-900);margin-bottom:6px;">
                            ${escapeHtml(item.title)}
                        </div>
                        <div style="font-size:0.85rem;color:var(--neutral-700);">
                            ${escapeHtml(item.subject || '')} ${item.section ? '— ' + escapeHtml(item.section) : ''}
                        </div>
                        <div style="font-size:0.75rem;color:var(--neutral-500);margin-top:6px;">
                            Type: ${escapeHtml(item.type)} • Due: ${due || '—'}
                        </div>
                    </div>
                    <div style="text-align:right;min-width:140px;">
                        <div style="font-size:0.75rem;color:var(--neutral-500);margin-bottom:6px;">
                            ${mySubmission ? 'Submitted' : 'Not submitted'}
                        </div>
                        <button class="btn btn--secondary" type="button" onclick="openClassDetail(${activeClassId || item.class_id || 0})" style="width:100%;">
                            View
                        </button>
                    </div>
                </div>
                ${mySubmission ? `
                    <div style="margin-top:10px;color:var(--neutral-700);white-space:pre-wrap;font-size:0.9rem;">
                        ${escapeHtml(mySubmission.submitted_text || '')}
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');
}

// ─── CLASS DETAIL (student/student-side rendering) ───
async function renderClassDetail(classId) {

    const classworkGrid = $('class-detail-classwork');
    const teacherClassworkGrid = $('class-detail-teacher-classwork');


    if (!classId) return;

    // Fetch full class detail from backend
    const res = await api('get_class_detail', 'GET', { class_id: classId });

    if (!res.status) {
        const target = classworkGrid || teacherClassworkGrid;
        if (target) {
            target.innerHTML = `<div class="empty-state">${escapeHtml(res.message || 'Unable to load classwork')}</div>`;
        }
        return;
    }

    const classwork = res.data?.classwork || [];

    // ── STUDENT UI ──
    if (userRole === 'student') {
        if (!classworkGrid) return;

        classworkGrid.innerHTML = classwork.map(cw => {
            const due = cw.due_date ? escapeHtml(String(cw.due_date)) : '';
            const submitted = cw.my_submission?.submitted_text ? escapeHtml(cw.my_submission.submitted_text) : '';
            return `
                <div class="classwork-item" style="padding:12px;border:1px solid var(--neutral-200);border-radius:var(--radius);background:#fff;">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                        <div>
                            <div style="font-weight:900;color:var(--brand-900);margin-bottom:6px;">
                                ${escapeHtml(cw.title)}
                            </div>
                            <div style="font-size:0.8rem;color:var(--neutral-700);">
                                ${escapeHtml(cw.subject || '')}
                                ${cw.subject ? '' : ''}
                            </div>
                            <div style="font-size:0.75rem;color:var(--neutral-500);margin-top:6px;">
                                Type: ${escapeHtml(cw.type)} • Due: ${due || '—'}
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <button type="button" class="btn btn--secondary" onclick="studentOpenClassworkSubmissionModal(${cw.id})" style="width:100%;">Select</button>

                        </div>
                    </div>
                    ${submitted ? `
                        <div style="margin-top:10px;font-size:0.9rem;color:var(--neutral-700);white-space:pre-wrap;">
                            <b>Your submission:</b>
                            <div>${submitted}</div>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');

        return;
    }

    // ── TEACHER UI ──
    if (userRole === 'teacher') {
        if (!teacherClassworkGrid) return;

        // Render classwork list as small cards (teacher)
        teacherClassworkGrid.innerHTML = classwork.map(cw => {
            const due = cw.due_date ? escapeHtml(String(cw.due_date)) : '—';
            const maxScore = (cw.max_score !== null && typeof cw.max_score !== 'undefined') ? cw.max_score : null;
            const typeLabel = cw.type ? escapeHtml(cw.type) : 'lesson';

            // Map to existing CSS type styles where possible.
            let typeBadgeClass = '';
            if (typeLabel === 'quiz') typeBadgeClass = 'classwork-card__type--quiz';
            if (typeLabel === 'lesson') typeBadgeClass = 'classwork-card__type--lesson';

            return `
                <div class="classwork-item" style="padding:12px;border:1px solid var(--neutral-200);border-radius:var(--radius);background:#fff;cursor:pointer;" onclick="teacherOpenGradeModal(${cw.id});">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                        <div style="min-width:0;">
                            <div style="font-weight:900;color:var(--brand-900);margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(cw.title)}</div>

                            <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                                <span class="classwork-card__type ${typeBadgeClass}" style="text-transform:capitalize;">
                                    ${typeLabel}
                                </span>
                                <span style="font-size:0.75rem;color:var(--neutral-500);">
                                    Due: ${due}
                                </span>
                            </div>

                            ${maxScore !== null ? `
                                <div style="font-size:0.75rem;color:var(--neutral-500);margin-top:6px;">
                                    Max: ${escapeHtml(String(maxScore))}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Remove inline grade table from teacher view
        const gradeTable = $('class-detail-grade-table');
        if (gradeTable) {
            gradeTable.innerHTML = '';
            gradeTable.style.display = 'none';
        }

        // Keep submission files panel hidden/placeholder
        const submissionFilesContainer = $('teacher-submission-files');
        if (submissionFilesContainer) {
            submissionFilesContainer.innerHTML = '<div class="empty-state">Use the grade modal to enter scores and feedback. (File listing not wired yet)</div>';
        }

        return;
    }
}


function selectClasswork(classworkId) {

    // Legacy method (now replaced by modal flow). Keep for backward compatibility.
    if ($('selected-classwork-id')) {
        $('selected-classwork-id').value = String(classworkId);
    }
}

function teacherOpenCreateActivityModal() {
    // Teacher: open modal + reset inputs/dropzone state
    try {
        if (userRole !== 'teacher') {
            showToast('Teacher role required.', 'error');
            return;
        }

        const modal = $('modal-create-activity');
        const modalClassIdEl = $('teacher-activity-class-id');
        const sel = $('class-detail-select');

        if (!modal) {
            showToast('Create Activity modal missing in DOM.', 'error');
            return;
        }

        let classId = activeClassId;
        if (sel && sel.value) {
            const v = parseInt(sel.value, 10);
            if (!Number.isNaN(v) && v > 0) classId = v;
        }

        if (modalClassIdEl) {
            modalClassIdEl.value = classId ? String(classId) : '';
        }

        if (!classId) {
            showToast('Select a class first.', 'error');
            return;
        }

        // Open modal
        modal.classList.add('is-open');
        modal.style.display = 'flex';

        // Reset modal form state
        const titleInput = $('teacher-activity-title');
        const descInput = $('teacher-activity-description');
        const typeInput = $('teacher-activity-type');
        const dueDateInput = $('teacher-activity-due-date');
        const maxScoreInput = $('teacher-activity-max-score');
        const fileInput = $('teacher-activity-file-input');
        const namesEl = $('teacher-activity-file-names');
        const dropzone = $('teacher-activity-dropzone');

        if (titleInput) titleInput.value = '';
        if (descInput) descInput.value = '';
        if (typeInput) typeInput.value = 'assignment';
        if (dueDateInput) dueDateInput.value = '';
        if (maxScoreInput) maxScoreInput.value = '';

        if (fileInput) fileInput.value = '';
        if (namesEl) {
            namesEl.innerHTML = '<span style="color:var(--neutral-500);font-size:0.875rem;">No files selected</span>';
        }

        if (dropzone) dropzone.classList.remove('is-dragover');

        if (titleInput) titleInput.focus();
    } catch (e) {
        console.error(e);
        showToast('Could not open Create Activity modal.', 'error');
    }
}






// --- Student Classwork Submission Modal (modal-based flow) ---
let studentModalActiveClassworkId = null;

async function studentOpenClassworkSubmissionModal(classworkId) {

    if (userRole !== 'student') return;

    // Expose modal impl hooks for classroom-activity-modals.js wrappers
    window.__studentSubmitTextFromModalImpl = studentSubmitTextFromModal;
    window.__studentAttachFileFromModalImpl = studentAttachFileFromModal;


    if (!activeClassId) {
        showToast('Select a class first.', 'error');
        return;
    }

    studentModalActiveClassworkId = classworkId;
    window.__studentModalActiveClassworkId = classworkId;
    window.__studentActiveClassId = activeClassId;


    const modalId = 'modal-student-submit-classwork';
    const modal = $(modalId);
    if (!modal) {
        showToast('Submission modal missing in UI.', 'error');
        return;
    }

    // Reset modal UI
    const metaEl = $('student-submit-modal-meta');
    const descEl = $('student-submit-modal-description');
    const dueEl = $('student-submit-modal-due');
    const submittedTextEl = $('student-submit-modal-text');
    const fileInputEl = $('student-submit-modal-file-input');
    const attachedFilesEl = $('student-submit-modal-files');
    const existingStatusEl = $('student-submit-modal-status');
    const submitBtnEl = $('student-submit-modal-submit-btn');

    if (metaEl) metaEl.textContent = 'Loading...';
    if (descEl) descEl.textContent = '';
    if (dueEl) dueEl.textContent = '—';
    if (submittedTextEl) submittedTextEl.value = '';
    if (fileInputEl) fileInputEl.value = '';
    if (attachedFilesEl) attachedFilesEl.innerHTML = '';
    if (existingStatusEl) existingStatusEl.textContent = '—';

    if (submitBtnEl) submitBtnEl.disabled = true;

    openModal(modalId);

    const res = await api('get_class_detail', 'GET', { class_id: activeClassId });
    if (!res.status) {
        if (metaEl) metaEl.textContent = res.message || 'Unable to load activity.';
        if (submitBtnEl) submitBtnEl.disabled = false;
        return;
    }

    const cw = (res.data?.classwork || []).find(x => Number(x.id) === Number(classworkId));
    if (!cw) {
        if (metaEl) metaEl.textContent = 'Activity not found.';
        if (submitBtnEl) submitBtnEl.disabled = false;
        return;
    }

    if (metaEl) metaEl.textContent = cw.title || 'Activity';
    if (descEl) descEl.textContent = cw.description || '';
    if (dueEl) dueEl.textContent = cw.due_date ? String(cw.due_date).slice(0, 10) : '—';

    const submission = cw.my_submission || null;
    // Fill existing submission text if any
    if (submittedTextEl) {
        submittedTextEl.value = submission?.submitted_text ? submission.submitted_text : '';
    }

    // Store submission_id for attach/file listing
    const hiddenSubmissionIdEl = $('student-submit-modal-submission-id');
    if (hiddenSubmissionIdEl) {
        hiddenSubmissionIdEl.value = submission?.id ? String(submission.id) : '';
    }

    if (existingStatusEl) {
        existingStatusEl.textContent = submission ? 'Submitted' : 'Not submitted yet';
    }

    // Load existing attached files if we already have a submission row
    if (attachedFilesEl) {
        attachedFilesEl.innerHTML = '<div class="empty-state">Loading uploaded files...</div>';
    }

    if (submission?.id) {
        const filesRes = await api('get_submission_files_for_submission', 'GET', {
            submission_id: submission.id,
            class_id: activeClassId
        });

        if (filesRes.status) {
            const files = filesRes.data || [];
            attachedFilesEl.innerHTML = files.length === 0
                ? '<div class="empty-state">No files attached.</div>'
                : files.map(f => `
                    <div style="padding:10px;border:1px solid var(--neutral-200);border-radius:var(--radius);background:#fff;display:flex;justify-content:space-between;gap:12px;align-items:center;">
                        <div style="min-width:0;">
                            <div style="font-weight:800;color:var(--neutral-900);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:420px;">${escapeHtml(f.original_name || f.filename)}</div>
                            <div style="font-size:0.75rem;color:var(--neutral-500);margin-top:4px;">${escapeHtml(f.mime_type || '')}</div>
                        </div>
                        <div style="font-size:0.75rem;color:var(--neutral-500;)">
                            ${f.file_size ? escapeHtml(String(f.file_size)) + ' bytes' : ''}
                        </div>
                    </div>
                `).join('');
        } else {
            attachedFilesEl.innerHTML = `<div class="empty-state">${escapeHtml(filesRes.message || 'Failed to load files')}</div>`;
        }
    } else {
        if (attachedFilesEl) attachedFilesEl.innerHTML = '<div class="empty-state">Attach a file to your submission.</div>';
    }

    if (submitBtnEl) submitBtnEl.disabled = false;
}


// Placeholder hooks to avoid runtime errors if still referenced elsewhere
function renderClassworkFromSelect() {
    renderClasswork();
}

function renderClassDetailFromSelect() {
    const sel = $('class-detail-select');
    if (!sel) return;
    const classId = parseInt(sel.value, 10);
    if (!classId) return;
    openClassDetail(classId);
}

// ─── TEACHER: Open grading modal for a classwork ───
let modalGradeActiveClassworkId = null;

async function teacherOpenGradeModal(classworkId) {
    modalGradeActiveClassworkId = classworkId;

    if (!activeClassId) {
        showToast('Select a class first.', 'error');
        return;
    }

    const metaEl = $('modal-grade-activity-meta');
    const rowsEl = $('modal-grade-activity-rows');
    const gradeModal = $('modal-grade-activity');

    if (!metaEl || !rowsEl || !gradeModal) return;

    metaEl.textContent = 'Loading students...';
    rowsEl.innerHTML = '';
    openModal('modal-grade-activity');

    const res = await api('get_class_detail', 'GET', {
        class_id: activeClassId
    });

    if (!res.status) {
        metaEl.textContent = res.message || 'Unable to load grading data.';
        return;
    }

    const classwork = (res.data?.classwork || []).find(cw => Number(cw.id) === Number(classworkId));
    if (!classwork) {
        metaEl.textContent = 'Activity not found.';
        return;
    }

    const title = classwork.title || 'Activity';
    const type = classwork.type ? String(classwork.type).toUpperCase() : '';
    const due = classwork.due_date ? String(classwork.due_date).slice(0, 10) : '—';
    const maxScore = (classwork.max_score !== null && typeof classwork.max_score !== 'undefined') ? classwork.max_score : null;

    metaEl.textContent = `${title}${type ? ' • ' + type : ''} • Due: ${due}${maxScore !== null ? ' • Max: ' + maxScore : ''}`;

    const subs = classwork.submissions_with_grades || [];

    if (subs.length === 0) {
        rowsEl.innerHTML = `<tr><td colspan="3" style="color:var(--neutral-500);">No student submissions yet.</td></tr>`;
        return;
    }

    rowsEl.innerHTML = subs.map((s, idx) => {
        const scoreVal = (s.score === null || typeof s.score === 'undefined') ? '' : String(s.score);
        const feedbackVal = s.feedback ? String(s.feedback) : '';
        const inputIdScore = `grade-modal-score-${s.id}-${idx}`;
        const inputIdFeedback = `grade-modal-feedback-${s.id}-${idx}`;

        return `
            <tr>
                <td style="font-weight:600;color:var(--neutral-900);">${escapeHtml(s.student_username)}</td>
                <td>
                    <input
                        type="number"
                        min="0"
                        data-submission-id="0"

                        style="width:140px;padding:10px 12px;background:var(--neutral-100);border:1px solid var(--neutral-200);border-radius:var(--radius);outline:none;"
                        id="${inputIdScore}"
                        value="${escapeHtml(scoreVal)}"
                    >
                    <input type="hidden" id="grade-modal-submission-id-${s.id}-${idx}" value="${s.id}">
                </td>
                <td>
                    <textarea
                        style="width:100%;min-height:70px;padding:10px 12px;font-size:0.9375rem;font-family:inherit;color:var(--neutral-900);background:var(--neutral-100);border:1px solid var(--neutral-200);border-radius:var(--radius);outline:none;resize:vertical;"
                        id="${inputIdFeedback}"
                        placeholder="Enter feedback"
                    >${escapeHtml(feedbackVal)}</textarea>
                </td>
            </tr>
        `;
    }).join('');
}


async function teacherSaveGradesFromModal() {
    const classId = activeClassId;
    const classworkId = modalGradeActiveClassworkId;

    if (!classId || !classworkId) {
        showToast('Missing grading context.', 'error');
        return;
    }

    const rowsEl = $('modal-grade-activity-rows');
    if (!rowsEl) return;

    const hiddenEls = rowsEl.querySelectorAll('input[type="hidden"][id^="grade-modal-submission-id-"]');

    if (!hiddenEls || hiddenEls.length === 0) {
        showToast('No rows to save.', 'error');
        return;
    }

    // Build save promises per row.
    const savePromises = Array.from(hiddenEls).map(async hiddenEl => {
        const submissionId = parseInt(hiddenEl.value, 10);
        if (!submissionId) return { ok: false };

        // Derive idx key from element id.
        // id format: grade-modal-submission-id-<submissionId>-<idx>
        const parts = hiddenEl.id.split('-');
        const idx = parts[parts.length - 1];
        const baseSubmissionId = parts[parts.length - 2];

        const scoreInput = $(`grade-modal-score-${baseSubmissionId}-${idx}`);
        const feedbackInput = $(`grade-modal-feedback-${baseSubmissionId}-${idx}`);

        const scoreRaw = scoreInput?.value;
        const score = (scoreRaw === '' || typeof scoreRaw === 'undefined') ? null : parseInt(scoreRaw, 10);
        const feedback = feedbackInput?.value || '';

        const res = await api('teacher_grade_submission', 'POST', {
            class_id: classId,
            classwork_id: classworkId,
            submission_id: submissionId,
            score: score,
            feedback: feedback
        });

        return { ok: !!res.status };
    });

    const results = await Promise.all(savePromises);
    const anyOk = results.some(r => r && r.ok);

    if (anyOk) {
        showToast('Grades saved!', 'success');
        closeModal('modal-grade-activity');
        await renderClassDetail(classId);
    } else {
        showToast('Failed to save grades.', 'error');
    }
}


function loadPeople() { /* existing project may override; keep empty to prevent crash */ }
function loadProfile() { /* keep empty to prevent crash */ }

function teacherCreateClasswork() {
    // Create activity/classwork from modal fields.
    // NOTE: keep UI + input ids aligned with #modal-create-activity in assets/pages/dashboard.php
    let classId = activeClassId;

    const modalClassIdEl = $('teacher-activity-class-id');

    const sel = $('class-detail-select');

    // Priority: class detail select, then modal hidden input.
    if ((!classId || classId <= 0) && sel && sel.value) {
        const v = parseInt(sel.value, 10);
        if (!Number.isNaN(v) && v > 0) classId = v;
    }

    if ((!classId || classId <= 0) && modalClassIdEl && modalClassIdEl.value) {
        const v = parseInt(modalClassIdEl.value, 10);
        if (!Number.isNaN(v) && v > 0) classId = v;
    }

    if (!classId || classId <= 0) {
        showToast('Select a class first (class_id missing).', 'error');
        return;
    }

    const title = $('teacher-activity-title')?.value?.trim() || '';
    const type = $('teacher-activity-type')?.value || 'lesson';
    const dueDate = $('teacher-activity-due-date')?.value || null;
    const description = $('teacher-activity-description')?.value?.trim() || '';

    const maxScoreRaw = $('teacher-activity-max-score')?.value;
    const maxScore = (maxScoreRaw === '' || maxScoreRaw === null || typeof maxScoreRaw === 'undefined')
        ? null
        : parseInt(maxScoreRaw, 10);

    if (!title) {
        showToast('Title is required.', 'error');
        return;
    }

    const fileInput = $('teacher-activity-file-input');
    const files = fileInput ? Array.from(fileInput.files || []) : [];

    const payload = {
        class_id: classId,
        title,
        type: type,
        description: description || '',
        due_date: dueDate || null
    };

    if (maxScore !== null && !Number.isNaN(maxScore)) {
        payload.max_score = maxScore;
    }

    (async () => {
        const res = await api('create_classwork', 'POST', payload);

        if (!res.status) {
            showToast(res.message || 'Failed to create activity.', 'error');
            return;
        }

        const createdClassworkId = res.id ? parseInt(res.id, 10) : null;

        // Upload attached files (if any) and attach them to the activity.
        // Current backend stores them as CLASS-LEVEL resources (preserves existing DB).
        if (createdClassworkId && files.length > 0) {
            const uploadPromises = files.map(async (file) => {
                const fd = new FormData();
                fd.append('file', file);
                fd.append('class_id', classId);

                // classwork_id is currently not supported by the existing upload-to-resource architecture.
                // keeping it for forward compatibility if the backend schema is later extended.
                fd.append('classwork_id', createdClassworkId);



                const url = new URL(API_BASE, window.location.href);
                url.searchParams.set('action', 'teacher_add_class_resource');


                const upRes = await fetch(url.toString(), { method: 'POST', body: fd }).then(r => r.json());
                if (!upRes.status) throw new Error(upRes.message || 'File upload failed');
                return upRes;
            });

            try {
                await Promise.all(uploadPromises);
            } catch (e) {
                console.error(e);
                showToast('Activity created, but some files failed to upload.', 'error');
            }
        }


closeModal('modal-create-activity');

        // Reset modal fields

        const setVal = (id, val) => {
            const el = $(id);
            if (el) el.value = val;
        };
        setVal('teacher-activity-title', '');
        setVal('teacher-activity-type', 'assignment');
        setVal('teacher-activity-due-date', '');
        setVal('teacher-activity-max-score', '');
        setVal('teacher-activity-description', '');
        if (fileInput) fileInput.value = '';

        await renderClassDetail(classId);
        showToast('Activity created!', 'success');
    })();
}




async function submitClassworkForSelected() {
    const classworkIdEl = $('selected-classwork-id');
    const submittedTextEl = $('selected-submitted-text');

    if (!classworkIdEl || !submittedTextEl) {
        showToast('Submission form not found.', 'error');
        return;
    }

    const classworkId = parseInt(classworkIdEl.value, 10);
    const classId = activeClassId;
    const submittedText = submittedTextEl.value ?? '';

    if (!classId) {
        showToast('Select a class first.', 'error');
        return;
    }

    if (!classworkId) {
        showToast('Select a classwork first.', 'error');
        return;
    }

    const res = await api('student_submit_classwork', 'POST', {
        classwork_id: classworkId,
        class_id: classId,
        submitted_text: submittedText
    });

    if (!res.status) {
        showToast(res.message || 'Failed to submit.', 'error');
        return;
    }

    showToast('Submitted!', 'success');

    // Refresh class detail so "my submission" appears
    await renderClassDetail(classId);
}

async function studentAttachFileForSelected() {
    const classworkIdEl = $('selected-classwork-id');
    const fileInputEl = $('student-attach-file-input');

    if (!classworkIdEl || !fileInputEl) {
        showToast('Attachment form not found.', 'error');
        return;
    }

    const classworkId = parseInt(classworkIdEl.value, 10);
    const classId = activeClassId;

    if (!classId) {
        showToast('Select a class first.', 'error');
        return;
    }

    if (!classworkId) {
        showToast('Select a classwork first.', 'error');
        return;
    }

    const file = fileInputEl?.files?.[0];

    if (!file) {
        showToast('Select a file to attach.', 'error');
        return;
    }

    const fd = new FormData();
    fd.append('file', file);
    fd.append('class_id', classId);
    fd.append('classwork_id', classworkId);

    // action expects multipart; send directly to same api.php
    const url = new URL(API_BASE, window.location.href);
    url.searchParams.set('action', 'student_attach_file_to_submission');

    try {
        const res = await fetch(url.toString(), {
            method: 'POST',
            body: fd
        }).then(r => r.json());

        if (!res.status) {
            showToast(res.message || 'File upload failed.', 'error');
            return;
        }

        showToast('File attached!', 'success');
        fileInputEl.value = '';

        await renderClassDetail(classId);

    } catch (err) {
        console.error(err);
        showToast('File upload failed.', 'error');
    }
}

// ─── CLASS RESOURCE UPLOAD FIX ───
async function teacherAddClassResource() {
    const classId = activeClassId;

    if (!classId) {
        showToast('Select a class first.', 'error');
        return;
    }

    const input = $('teacher-add-resource-file');
    const file = input?.files?.[0];

    if (!file) {
        showToast('Select a file to upload.', 'error');
        return;
    }

    const fd = new FormData();
    fd.append('file', file);
    fd.append('class_id', classId);

    const url = new URL(API_BASE, window.location.href);
    url.searchParams.set('action', 'teacher_add_class_resource');

    try {
        const res = await fetch(url.toString(), {
            method: 'POST',
            body: fd
        }).then(r => r.json());

        if (res.status) {
            showToast('Resource uploaded!', 'success');
            input.value = '';
            if (typeof teacherFetchAndRenderResources === 'function') {
                await teacherFetchAndRenderResources(classId);
            }
        } else {
            showToast(res.message || 'Upload failed.', 'error');
        }

    } catch (err) {
        console.error(err);
        showToast('Upload failed.', 'error');
    }
}

// ─── FIXED GRADE TABLES ───
function buildGradeTablesHtml(classwork) {
    if (!classwork || classwork.length === 0) {
        return '';
    }

    return classwork.map(w => {
        const subs = w.submissions_with_grades || [];

        const rows = subs.map(s => `
            <tr>
                <td>
                    ${escapeHtml(s.student_username)}
                </td>
                <td>
                    ${
                        s.score === null ||
                        typeof s.score === 'undefined'
                            ? '—'
                            : escapeHtml(String(s.score))
                    }
                </td>
                <td>
                    ${escapeHtml(s.feedback || '')}
                </td>
            </tr>
        `).join('');

        return `
            <div style="margin-top:16px;">
                <h3
                    style="
                        font-size:1rem;
                        font-weight:700;
                        color:var(--brand-900);
                        margin-bottom:10px;
                    "
                >
                    ${escapeHtml(w.title)}
                </h3>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Score</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:10px;">
                    <select
                        id="grade-submission-id-${w.id}"
                        style="
                            padding:8px 12px;
                            border-radius:var(--radius);
                            border:1px solid var(--neutral-200);
                            background:#fff;
                        "
                    >
                        ${subs.map(s => `
                            <option value="${s.id}">
                                ${escapeHtml(s.student_username)}
                            </option>
                        `).join('')}
                    </select>

                    <div
                        class="form-row"
                        style="
                            grid-template-columns:1fr 1fr;
                            margin-top:10px;
                        "
                    >
                        <div
                            class="form-group"
                            style="gap:4px;"
                        >
                            <label>Score</label>
                            <input
                                type="number"
                                id="grade-score-input-${w.id}"
                                min="0"
                                max="${escapeHtml(String(w.max_score || 100))}"
                                style="background:var(--neutral-100);"
                                placeholder="Enter score"
                            >
                        </div>

                        <div
                            class="form-group"
                            style="gap:4px;"
                        >
                            <label>Due Date</label>
                            <input
                                type="date"
                                id="grade-due-date-input-${w.id}"
                                style="background:var(--neutral-100);"
                                value="${
                                    w.due_date
                                        ? escapeHtml(String(w.due_date).slice(0, 10))
                                        : ''
                                }"
                            >
                        </div>

                    </div>

                    <div
                        class="form-group"
                        style="margin-top:10px;"
                    >
                        <label>Feedback</label>
                        <textarea
                            id="grade-feedback-input-${w.id}"
                            style="
                                width:100%;
                                min-height:90px;
                                padding:10px 12px;
                                font-size:0.9375rem;
                                font-family:inherit;
                                color:var(--neutral-900);
                                background:var(--neutral-100);
                                border:1px solid var(--neutral-200);
                                border-radius:var(--radius);
                                outline:none;
                                resize:vertical;
                            "
                            placeholder="Enter feedback"
                        ></textarea>
                    </div>

                    <div
                        class="form-actions"
                        style="
                            margin-top:12px;
                            justify-content:flex-end;
                        "
                    >
                        <button
                            type="button"
                            class="btn btn--primary"
                            onclick="submitGradeForSelectedSubmission(${w.id})"
                        >
                            Save Grade
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// ─── FIXED GRADE SUBMIT ───
async function submitGradeForSelectedSubmission(classworkId) {
    const classId = activeClassId;

    if (!classId) return;

    const submissionSelect = $(`grade-submission-id-${classworkId}`);
    const scoreInput = $(`grade-score-input-${classworkId}`);
    const feedbackInput = $(`grade-feedback-input-${classworkId}`);

    if (!submissionSelect || !scoreInput || !feedbackInput) {
        showToast('Missing grading form.', 'error');
        return;
    }

    const submissionId = parseInt(submissionSelect.value, 10);
    const score = scoreInput.value;
    const feedback = feedbackInput.value || '';

    if (!submissionId) {
        showToast('Select a student submission.', 'error');
        return;
    }

    const payload = {
        class_id: classId,
        classwork_id: classworkId,
        submission_id: submissionId,
        score: score === '' ? null : parseInt(score, 10),
        feedback
    };

    const res = await api('teacher_grade_submission', 'POST', payload);

    if (res.status) {
        showToast('Grade saved!', 'success');
        renderClassDetail(classId);
    } else {
        showToast(res.message || 'Failed to save grade.', 'error');
    }
}

// ─── HELPERS ───
function escapeHtml(text) {
    if (text === null || typeof text === 'undefined') {
        return '';
    }

    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '<')
        .replace(/>/g, '>')
        .replace(/"/g, '"')
        .replace(/'/g, '&#039;');
}

function formatDate(dateStr) {
    if (!dateStr) return '';

    const d = new Date(String(dateStr).replace(' ', 'T'));

    if (isNaN(d.getTime())) {
        return '';
    }

    return d.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

function timeAgo(dateStr) {
    if (!dateStr) return '';

    const d = new Date(String(dateStr).replace(' ', 'T'));

    if (isNaN(d.getTime())) {
        return '';
    }

    const diff = Date.now() - d.getTime();
    const minutes = Math.floor(diff / 60000);

    if (minutes < 1) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;

    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;

    const days = Math.floor(hours / 24);
    if (days < 30) return `${days}d ago`;

    return formatDate(dateStr);
}

// ─── INIT ───
document.addEventListener('DOMContentLoaded', () => {
    const roleMeta = document.querySelector('meta[name="user-role"]');

    // Teacher activity modal: drag/drop + multi-file names
    const dropzone = $('teacher-activity-dropzone');
    const fileInput = $('teacher-activity-file-input');
    const namesEl = $('teacher-activity-file-names');

    if (dropzone && fileInput) {
        const renderNames = (files) => {
            const list = files ? Array.from(files) : [];
            if (!namesEl) return;

            if (list.length === 0) {
                namesEl.innerHTML = '<span style="color:var(--neutral-500);font-size:0.875rem;">No files selected</span>';
                return;
            }

            namesEl.innerHTML = list.map(f => `
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:var(--neutral-700);font-weight:700;">•</span>
                    <span style="color:var(--neutral-900);font-size:0.875rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(f.name)}</span>
                </div>
            `).join('');
        };

        const setFilesFromList = (fileList) => {
            if (!fileList) return;
            // File input is read-only; use DataTransfer to populate it
            const dt = new DataTransfer();
            Array.from(fileList).forEach(f => dt.items.add(f));
            fileInput.files = dt.files;
            renderNames(dt.files);
        };

        const openBrowse = () => fileInput.click();

        dropzone.addEventListener('click', openBrowse);
        dropzone.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openBrowse();
            }
        });

        dropzone.addEventListener('dragenter', (e) => {
            e.preventDefault();
            dropzone.classList.add('is-dragover');
        });

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('is-dragover');
        });

        dropzone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropzone.classList.remove('is-dragover');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('is-dragover');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                setFilesFromList(e.dataTransfer.files);
            }
        });

        fileInput.addEventListener('change', () => {
            renderNames(fileInput.files);
        });

        // Initial render
        renderNames(fileInput.files);
    }


    if (roleMeta) {
        userRole = roleMeta.content;
    }

    loadClasses();

    if (userRole === 'student') {
        loadPending();
        renderClasswork();
    }
});

