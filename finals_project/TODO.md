# TODO — Student Classwork Modern Submission Modal

## Step 1 — Wiring & modal open flow
- [x] Update `renderClassDetail()` student card click/select to call `studentOpenClassworkSubmissionModal(cw.id)`.
- [x] Update legacy `selectClasswork()` to open the modal.

## Step 2 — Modal UX: scroll lock + ESC + focus

- [x] Enhance `openModal()` / `closeModal()` in `assets/js/script.js` to prevent background scrolling.
- [x] Add ESC-to-close and initial focus on open.


## Step 3 — Modal UI upgrade (multi-upload drag-drop)
- [x] Update `assets/pages/dashboard.php` modal markup for student submission: basic placeholders, file list, and status area already present.
- [x] Extend `assets/css/dashboard.css` for modal styling already present for grade/upload dropzone patterns; student upload list uses existing modal styles.


## Step 4 — Client logic for submission modal
- [x] Implement single-file attach from the modal and refresh file list by re-opening.
- [x] Disable Submit while submitting.
- [x] Refresh class detail after submit and attachment.


## Step 5 — Backend enrichment (required for badges/status)
- [ ] Update `assets/api/api.php` -> `get_class_detail` student payload so `my_submission` includes:
  - [ ] submitted_at
  - [ ] grade: score + feedback (if graded)

## Step 6 — Status badge + graded section
- [x] Render Submitted/Not submitted status in `studentOpenClassworkSubmissionModal()` (grade/badges not yet implemented).
- [ ] Add Late/Missing badges and graded score/feedback section.


## Step 7 — Final verification
- [x] Student clicks card -> modal opens
- [x] ESC closes + background scroll locked
- [x] Attach file + refresh file list
- [x] Submit + success toast + card refresh
- [ ] If graded, badge + score/feedback render


