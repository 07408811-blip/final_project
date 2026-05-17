<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}
$userRole = $_SESSION['role'] ?? 'student';
$userName = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-role" content="<?php echo htmlspecialchars($userRole); ?>">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body class="dashboard-body">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar__brand">
            <img src="../img/logo.png" alt="Logo" class="sidebar__logo">
            <span class="sidebar__title">Classroom</span>
        </div>

        <nav class="sidebar__nav">
            <button class="nav-item is-active" data-view="home" onclick="switchView('home')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Classes</span>
            </button>

            <?php if ($userRole === 'teacher'): ?>
                <button class="nav-item" data-view="class-detail" onclick="switchView('class-detail'); loadClassesForSelect();">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
                    <span>Class Detail</span>
                </button>
                <button class="nav-item" data-view="teacher-announcements" onclick="switchView('teacher-announcements')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span>Announcement</span>
                </button>
            <?php else: ?>
                <button class="nav-item" data-view="calendar" onclick="switchView('calendar')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span>Course Calendar</span>
                </button>
                <button class="nav-item" data-view="announcements" onclick="switchView('announcements')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span>Announcements</span>
                </button>
                <button class="nav-item" data-view="classwork" onclick="switchView('classwork')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span>Classwork</span>
                </button>
                <button class="nav-item" data-view="people" onclick="switchView('people')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>People</span>
                </button>
            <?php endif; ?>
        </nav>

        <div class="sidebar__footer">
            <button class="nav-item" data-view="profile" onclick="switchView('profile')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 16.33 9V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Profile Settings</span>
            </button>
            <a href="../../index.php?logout=1" class="nav-item nav-item--danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Log out</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main">
        <header class="topbar">
            <h1 class="topbar__greeting">Welcome back, <span id="userName"><?php echo htmlspecialchars($userName); ?></span></h1>
            <div class="topbar__meta">
                <span class="badge badge--role"><?php echo ucfirst($userRole); ?></span>
                <button class="icon-btn" onclick="switchView('profile')" title="Profile">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </button>
            </div>
        </header>

        <div class="views">
            <section class="view is-active" id="view-home">
                <?php if ($userRole === 'teacher'): ?>
                    <div class="section-header">
                        <h2 class="section-title">Your Classes</h2>
                        <button class="btn btn--primary" onclick="openModal('modal-create-class')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            New Class
                        </button>
                    </div>
                    <div class="grid grid--3" id="teacher-classes"></div>
                <?php else: ?>
                    <div class="student-home">
                        <div class="student-home__main">
                            <div class="section-header">
                                <h2 class="section-title">Your Classes</h2>
                            </div>
                            <div class="join-class-bar">
                                <input type="text" id="join-code-input" placeholder="Enter class code" maxlength="8">
                                <button class="btn btn--primary" onclick="joinClass()">Join Class</button>
                            </div>
                            <div class="grid grid--3" id="student-classes"></div>
                        </div>
                        <aside class="pending-panel">
                            <h3 class="pending-panel__title">Pending Activities</h3>
                            <div class="pending-list" id="pending-list"></div>
                        </aside>
                    </div>
                <?php endif; ?>
            </section>

            <section class="view" id="view-class-detail">


                <div class="section-header">
                    <h2 class="section-title">Class Detail</h2>
                    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                        <div class="filter-bar" style="margin:0;">
                            <select id="class-detail-select" onchange="renderClassDetailFromSelect()">
                                <option value="">Select a class</option>
                            </select>
                        </div>
                        <?php if ($userRole === 'teacher'): ?>
                            <div class="teacher-activity-fab-wrap">
                                <button class="teacher-activity-fab" type="button" onclick="teacherOpenCreateActivityModal()" aria-label="Add activity" title="Add activity">
                                    +
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>


                <div class="surface" style="display:flex;flex-direction:column;gap:16px;">
                    <?php if ($userRole === 'student'): ?>
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <h3 style="font-size:1rem;font-weight:700;color:var(--brand-900);">Student</h3>
                            <div class="classwork-grid" id="class-detail-classwork"></div>
                        </div>

                        <div style="padding:16px;border-radius:var(--radius);background:#ffffff;border:1px solid var(--neutral-200);display:none;" id="class-detail-submit-surface">
                            <!-- legacy surface kept hidden -->
                            <div style="font-size:0.85rem;color:var(--neutral-500);">Use the submission modal instead.</div>
                        </div>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <h3 style="font-size:1rem;font-weight:700;color:var(--brand-900);">Teacher</h3>
                            <div class="classwork-grid" id="class-detail-teacher-classwork"></div>
                            <div id="class-detail-grade-table" style="margin-top:16px; display:none;"></div>
                            <div style="padding:16px;border:1px solid var(--neutral-200);border-radius:var(--radius);background:#ffffff;display:flex;flex-direction:column;gap:12px;">
                                <h4 style="font-size:0.875rem;font-weight:700;color:var(--neutral-900);margin-bottom:0;">Class Resources</h4>
                                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                                    <input type="file" id="teacher-add-resource-file" />
                                    <button type="button" class="btn btn--secondary" onclick="teacherAddClassResource();">Upload</button>
                                </div>
                                <input type="hidden" id="teacher-resource-class-id" value="">
                                <div id="teacher-class-resources" style="display:flex;flex-direction:column;gap:8px;"></div>
                            </div>
                            <div style="margin-top:16px;">
                                <h4 style="font-size:0.875rem;font-weight:700;color:var(--neutral-900);margin-bottom:8px;">Submission Files</h4>
                                <div id="teacher-submission-files" style="display:flex;flex-direction:column;gap:12px;"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- STUDENT: COURSE CALENDAR -->
            <?php if ($userRole === 'student'): ?>
                <section class="view" id="view-calendar">
                    <div class="section-header">
                        <h2 class="section-title">Course Calendar</h2>
                        <div class="calendar-nav">
                            <button class="icon-btn" onclick="prevMonth()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                            </button>
                            <span class="calendar-month" id="calendar-month">January 2026</span>
                            <button class="icon-btn" onclick="nextMonth()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="surface calendar-grid" id="calendar-grid"></div>
                </section>

                <section class="view" id="view-announcements">
                    <div class="section-header">
                        <h2 class="section-title">Announcements</h2>
                    </div>
                    <div class="feed" id="announcement-feed"></div>
                </section>

                <section class="view" id="view-classwork">
                    <div class="section-header">
                        <h2 class="section-title">Classwork</h2>
                        <div class="filter-bar">
                            <select id="classwork-subject" onchange="renderClasswork()">
                                <option value="all">All Subjects</option>
                            </select>
                            <select id="classwork-type" onchange="renderClasswork()">
                                <option value="all">All Types</option>
                                <option value="lesson">Lessons</option>
                                <option value="lab">Labs</option>
                                <option value="quiz">Quizzes</option>
                            </select>
                        </div>
                    </div>
                    <div class="classwork-grid" id="classwork-grid"></div>
                </section>

                <section class="view" id="view-people">
                    <div class="section-header">
                        <h2 class="section-title">People</h2>
                    </div>
                    <div class="people-layout">
                        <div class="surface people-group">
                            <h3 class="people-group__label">Instructors</h3>
                            <div class="people-list" id="people-instructors"></div>
                        </div>
                        <div class="surface people-group">
                            <h3 class="people-group__label">Classmates</h3>
                            <div class="people-list" id="people-classmates"></div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- TEACHER: ANNOUNCEMENTS MANAGEMENT -->
            <?php if ($userRole === 'teacher'): ?>
                <section class="view" id="view-teacher-announcements">
                    <div class="section-header">
                        <h2 class="section-title">Announcement Manager</h2>
                    </div>

                    <div class="surface" style="display:flex;flex-direction:column;gap:16px;">
                        <div style="padding:16px;border:1px solid var(--neutral-200);border-radius:var(--radius);background:#ffffff;display:flex;flex-direction:column;gap:12px;">
                            <h4 style="font-size:0.875rem;font-weight:700;color:var(--neutral-900);">Create Announcement</h4>
                            <p style="margin:0;color:var(--neutral-500);font-size:0.875rem;">
                                Select a class first — then create your announcement in the modal.
                            </p>
                            <div id="teacher-announcement-class-grid" class="classwork-grid" style="margin-top:10px;"></div>
                            <div id="teacher-announcement-class-grid-empty" class="empty-state" style="display:none;">
                                <p class="empty-state__title">No classes found</p>
                                <p class="empty-state__desc">Create a class to post announcements.</p>
                            </div>
                        </div>
                        <div style="padding:16px;border:1px solid var(--neutral-200);border-radius:var(--radius);background:#ffffff;display:flex;flex-direction:column;gap:12px;">
                            <h4 style="font-size:0.875rem;font-weight:700;color:var(--neutral-900);">Your Announcements</h4>
                            <div id="teacher-announcement-manager-list" class="feed"></div>
                        </div>
                    </div>

                    <div class="modal" id="modal-teacher-create-announcement">
                        <div class="modal__backdrop" onclick="closeModal('modal-teacher-create-announcement')"></div>
                        <div class="modal__surface">
                            <div class="modal__header">
                                <h3>Create Announcement</h3>
                                <button class="icon-btn" onclick="closeModal('modal-teacher-create-announcement')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                            <form class="modal__body" onsubmit="event.preventDefault(); teacherSubmitAnnouncementFromModal();">
                                <input type="hidden" id="teacher-announcement-modal-class-id" value="">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" id="teacher-announcement-modal-title" required
                                           style="width:100%;padding:10px 12px;font-size:0.9375rem;font-family:inherit;color:var(--neutral-900);background:var(--neutral-100);border:1px solid var(--neutral-200);border-radius:var(--radius);outline:none;" />
                                </div>
                                <div class="form-group">
                                    <label>Content</label>
                                    <textarea id="teacher-announcement-modal-content" required
                                              style="width:100%;min-height:120px;padding:10px 12px;font-size:0.9375rem;font-family:inherit;color:var(--neutral-900);background:var(--neutral-100);border:1px solid var(--neutral-200);border-radius:var(--radius);outline:none;resize:vertical;"></textarea>
                                </div>
                                <div class="form-group" style="margin-top:2px;">
                                    <label>Publish Date (optional)</label>
                                    <input type="date" id="teacher-announcement-modal-publish-date"
                                           style="width:100%;padding:10px 12px;font-size:0.9375rem;font-family:inherit;color:var(--neutral-900);background:var(--neutral-100);border:1px solid var(--neutral-200);border-radius:var(--radius);outline:none;" />
                                </div>
                                <div class="form-group">
                                    <label>Image (optional)</label>
                                    <input type="file" id="teacher-announcement-modal-image" />
                                </div>
                                <div class="modal__actions">
                                    <button type="button" class="btn btn--ghost" onclick="closeModal('modal-teacher-create-announcement')">Cancel</button>
                                    <button type="submit" class="btn btn--primary">Create Announcement</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="view" id="view-profile">
                <div class="section-header">
                    <h2 class="section-title">Profile Settings</h2>
                </div>
                <div class="surface profile-card">
                    <div class="profile-card__avatar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div class="profile-card__info">
                        <h3 id="profile-name"><?php echo htmlspecialchars($userName); ?></h3>
                        <span class="badge badge--role"><?php echo ucfirst($userRole); ?></span>
                    </div>
                    <form class="profile-form" onsubmit="event.preventDefault(); saveProfile();">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Display Name</label>
                                <input type="text" id="profile-display-name" value="<?php echo htmlspecialchars($userName); ?>">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" id="profile-email" value="user@example.edu">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary">Save Changes</button>
                            <a href="../../index.php?logout=1" class="btn btn--danger">Log Out</a>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <!-- MODAL: Create Class (Faculty) -->
    <div class="modal" id="modal-create-class">
        <div class="modal__backdrop" onclick="closeModal('modal-create-class')"></div>
        <div class="modal__surface">
            <div class="modal__header">
                <h3>Create New Class</h3>
                <button class="icon-btn" onclick="closeModal('modal-create-class')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form class="modal__body" onsubmit="event.preventDefault(); createClass();">
                <div class="form-group">
                    <label for="class-subject">Subject Name</label>
                    <input type="text" id="class-subject" placeholder="e.g. Advanced Mathematics" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="class-section">Section</label>
                        <input type="text" id="class-section" placeholder="e.g. A" required>
                    </div>
                    <div class="form-group">
                        <label for="class-code">Course Code</label>
                        <input type="text" id="class-code" placeholder="e.g. MATH101" required>
                    </div>
                </div>
                <div class="form-group code-gen-group">
                    <label>Class Invite Code</label>
                    <div class="code-gen">
                        <input type="text" id="generated-code" readonly placeholder="Click generate">
                        <button type="button" class="btn btn--secondary" onclick="generateCode()">Generate</button>
                    </div>
                    <span class="hint">Share this code with students to join.</span>
                </div>
                <div class="modal__actions">
                    <button type="button" class="btn btn--ghost" onclick="closeModal('modal-create-class')">Cancel</button>
                    <button type="submit" class="btn btn--primary">Create Class</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Create Activity (Teacher) -->
    <?php if ($userRole === 'teacher'): ?>
<div class="modal" id="modal-create-activity">
            <div class="modal__backdrop" onclick="closeModal('modal-create-activity')"></div>
            <div class="modal__surface">
                <div class="modal__header">
                    <h3>Create Activity</h3>
                    <button class="icon-btn" type="button" onclick="closeModal('modal-create-activity'); event.preventDefault();" aria-label="Close">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>


                <form class="modal__body" onsubmit="event.preventDefault(); teacherCreateClasswork();">

                    <input type="hidden" id="teacher-activity-class-id" value="">

                    <div class="form-group">
                        <label>Activity Title</label>
                        <input type="text" id="teacher-activity-title" required
                               style="width:100%;padding:10px 12px;font-size:0.9375rem;font-family:inherit;color:var(--neutral-900);background:var(--neutral-100);border:1px solid var(--neutral-200);border-radius:var(--radius);outline:none;" />
                    </div>

                    <div class="form-group" style="margin-top:10px;">
                        <label>Description</label>
                        <textarea id="teacher-activity-description" required
                                  style="width:100%;min-height:120px;padding:10px 12px;font-size:0.9375rem;font-family:inherit;color:var(--neutral-900);background:var(--neutral-100);border:1px solid var(--neutral-200);border-radius:var(--radius);outline:none;resize:vertical;"></textarea>
                    </div>

                    <div class="form-row" style="margin-top:10px;">
                        <div class="form-group">
                            <label>Type</label>
                            <select id="teacher-activity-type"
                                    style="width:100%;padding:10px 12px;font-size:0.9375rem;font-family:inherit;color:var(--neutral-900);background:var(--neutral-100);border:1px solid var(--neutral-200);border-radius:var(--radius);outline:none;">
                                <option value="assignment">Assignment</option>
                                <option value="quiz">Quiz</option>
                                <option value="lesson">Lesson</option>
                                <option value="activity">Activity</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Due Date</label>
                            <input type="date" id="teacher-activity-due-date"
                                   style="width:100%;padding:10px 12px;font-size:0.9375rem;font-family:inherit;color:var(--neutral-900);background:var(--neutral-100);border:1px solid var(--neutral-200);border-radius:var(--radius);outline:none;" />
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:10px;">
                        <label>Max Score</label>
                        <input type="number" min="0" id="teacher-activity-max-score"
                               placeholder="Optional"
                               style="width:100%;padding:10px 12px;font-size:0.9375rem;font-family:inherit;color:var(--neutral-900);background:var(--neutral-100);border:1px solid var(--neutral-200);border-radius:var(--radius);outline:none;" />
                    </div>

                    <div class="form-group" style="margin-top:12px;">
                        <label>Attach Files (optional)</label>

                        <div class="upload-dropzone" id="teacher-activity-dropzone" tabindex="0" role="button" aria-label="Upload files" style="border:1px dashed var(--neutral-300);border-radius:var(--radius);background:var(--neutral-100);padding:14px;display:flex;flex-direction:column;gap:10px;transition:background var(--duration-fast) var(--ease-out), border-color var(--duration-fast) var(--ease-out);">
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                <span style="font-weight:800;color:var(--brand-900);">Drag & drop files</span>
                                <span style="color:var(--neutral-500);font-size:0.875rem;">or click to browse</span>
                            </div>
                            <input type="file" id="teacher-activity-file-input" multiple style="display:none;" />
                            <div id="teacher-activity-file-names" style="display:flex;flex-direction:column;gap:6px;">
                                <span style="color:var(--neutral-500);font-size:0.875rem;">No files selected</span>
                            </div>
                        </div>

                        <div style="font-size:0.75rem;color:var(--neutral-500);margin-top:6px;">Files will be uploaded and attached to the activity.</div>
                    </div>

                    <div class="modal__actions" style="margin-top:14px;">
                        <button type="button" class="btn btn--ghost" onclick="closeModal('modal-create-activity')">Cancel</button>
                        <button type="submit" class="btn btn--primary">Create Activity</button>
                    </div>
                </form>

            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL: Grade Activity (Teacher) -->
    <?php if ($userRole === 'teacher'): ?>
        <div class="modal" id="modal-grade-activity">
            <div class="modal__backdrop" onclick="closeModal('modal-grade-activity')"></div>
            <div class="modal__surface">

                <div class="modal__header">
                    <h3>Grade Activity</h3>
                    <button class="icon-btn" onclick="closeModal('modal-grade-activity')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="modal__body">
                    <div id="modal-grade-activity-meta" style="font-weight:900;color:var(--brand-900);margin-bottom:6px;">Loading...</div>
                    <div class="table-wrap">
                        <table class="grade-modal-table">
                            <thead>
                                <tr>
                                    <th style="width:34%;">Student</th>
                                    <th style="width:18%;">Score</th>
                                    <th>Feedback</th>
                                </tr>
                            </thead>
                            <tbody id="modal-grade-activity-rows"></tbody>
                        </table>
                    </div>

                    <div class="modal__actions">
                        <button type="button" class="btn btn--primary" onclick="teacherSaveGradesFromModal();">Save Grades</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL: Student Submit Classwork -->
    <?php if ($userRole === 'student'): ?>
        <div class="modal" id="modal-student-submit-classwork">
            <div class="modal__backdrop" onclick="closeModal('modal-student-submit-classwork')"></div>
            <div class="modal__surface">
                <div class="modal__header">
                    <h3>Submit Classwork</h3>
                    <button class="icon-btn" onclick="closeModal('modal-student-submit-classwork')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form class="modal__body" onsubmit="event.preventDefault(); studentSubmitTextFromModal();">
                    <input type="hidden" id="student-submit-modal-classwork-id" value="">
                    <input type="hidden" id="student-submit-modal-submission-id" value="">

                    <div id="student-submit-modal-meta" style="font-weight:900;color:var(--brand-900);margin-bottom:6px;">Loading...</div>

                    <div class="form-group">
                        <label>Instructions / Description</label>
                        <div id="student-submit-modal-description" style="white-space:pre-wrap;color:var(--neutral-700);font-size:0.95rem;"></div>
                    </div>

                    <div style="font-size:0.8rem;color:var(--neutral-500);margin-top:8px;">
                        Due date: <span id="student-submit-modal-due">—</span>
                    </div>

                    <div class="form-group" style="margin-top:12px;">
                        <label>Submission text (optional)</label>
                        <textarea id="student-submit-modal-text" style="width:100%;min-height:140px;padding:10px 12px;font-size:0.9375rem;font-family:inherit;color:var(--neutral-900);background:var(--neutral-100);border:1px solid var(--neutral-200);border-radius:var(--radius);outline:none;resize:vertical;" placeholder="Write your submission here..."></textarea>
                    </div>

                    <div class="form-group" style="margin-top:12px;">
                        <label>Attach file (optional)</label>
                        <input type="file" id="student-submit-modal-file-input" />
                        <div style="font-size:0.75rem;color:var(--neutral-500);margin-top:6px;">Attach your file, or just submit text.</div>
                    </div>

                    <div style="margin-top:10px;">
                        Status: <b id="student-submit-modal-status">—</b>
                    </div>

                    <div style="margin-top:14px;">
                        <h4 style="font-size:0.875rem;font-weight:700;color:var(--neutral-900);margin-bottom:8px;">Uploaded files</h4>
                        <div id="student-submit-modal-files" style="display:flex;flex-direction:column;gap:8px;"></div>
                    </div>

                    <div class="modal__actions" style="margin-top:14px;">
                        <button type="button" class="btn btn--secondary" onclick="studentAttachFileFromModal();">Attach</button>
                        <button id="student-submit-modal-submit-btn" type="submit" class="btn btn--primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script src="../js/script.js"></script>
    <script src="../js/classroom-activity-modals.js"></script>

</body>
</html>







