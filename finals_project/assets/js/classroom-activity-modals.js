// Classroom activity modals helpers
// These functions are called directly from dashboard.php modal markup.

function studentSubmitTextFromModal() {
  // Submit handler for text+submission.
  // Implementation lives below inside this file to avoid missing bindings.
  const modalSubmitBtn = document.getElementById('student-submit-modal-submit-btn');

  if (modalSubmitBtn) modalSubmitBtn.disabled = true;

  const classworkId = window.__studentModalActiveClassworkId;
  const classId = window.__studentActiveClassId;

  // Fallback: try to derive from DOM/state.
  const activeClassIdEl = document.getElementById('student-submit-modal-classwork-id');
  const classworkHiddenIdEl = document.getElementById('student-submit-modal-classwork-id');

  // NOTE: in this project, submission POST is sent by calling api() from assets/js/script.js.
  // If api() isn't available, no-op.
  if (typeof window.api !== 'function') {
    if (modalSubmitBtn) modalSubmitBtn.disabled = false;
    if (typeof window.showToast === 'function') window.showToast('API unavailable.', 'error');
    return;
  }
  if (!classId || !classworkHiddenIdEl || !classworkId) {
    if (modalSubmitBtn) modalSubmitBtn.disabled = false;
    if (typeof window.showToast === 'function') window.showToast('Missing submission context.', 'error');
    return;
  }

  const submittedTextEl = document.getElementById('student-submit-modal-text');
  const submittedText = submittedTextEl ? submittedTextEl.value || '' : '';

  const payload = {
    classwork_id: Number(classworkHiddenIdEl.value || classworkId),
    class_id: Number(classId),
    submitted_text: submittedText
  };

  window.api('student_submit_classwork', 'POST', payload).then(res => {
    if (!res || !res.status) {
      if (modalSubmitBtn) modalSubmitBtn.disabled = false;
      if (typeof window.showToast === 'function') window.showToast(res?.message || 'Failed to submit.', 'error');
      return;
    }

    if (typeof window.showToast === 'function') window.showToast('Submitted!', 'success');

    // Update UI by re-rendering class detail (function defined in script.js)
    if (typeof window.renderClassDetail === 'function') {
      window.renderClassDetail(classId);
    }

    if (typeof window.closeModal === 'function') {
      window.closeModal('modal-student-submit-classwork');
    }

    if (modalSubmitBtn) modalSubmitBtn.disabled = false;
  });
}

function studentAttachFileFromModal() {
  // Attach one file to an existing submission (or require text submit first).
  const fileInput = document.getElementById('student-submit-modal-file-input');
  const submissionIdHidden = document.getElementById('student-submit-modal-submission-id');
  const classworkIdEl = document.getElementById('student-submit-modal-classwork-id');

  if (typeof window.api !== 'function') {
    if (typeof window.showToast === 'function') window.showToast('API unavailable.', 'error');
    return;
  }

  const file = fileInput?.files?.[0];
  if (!file) {
    if (typeof window.showToast === 'function') window.showToast('Select a file to attach.', 'error');
    return;
  }

  const classId = window.__studentActiveClassId;
  const classworkId = Number(classworkIdEl?.value || window.__studentModalActiveClassworkId);

  if (!classId || !classworkId) {
    if (typeof window.showToast === 'function') window.showToast('Missing context.', 'error');
    return;
  }

  // We rely on multipart upload path via API action.
  const formData = new FormData();
  formData.append('file', file);
  formData.append('class_id', classId);
  formData.append('classwork_id', classworkId);
  if (submissionIdHidden?.value) {
    formData.append('submission_id', submissionIdHidden.value);
  }

  const uploadUrl = new URL('../api/api.php', window.location.href);
  uploadUrl.searchParams.set('action', 'student_attach_file_to_submission');

  fetch(uploadUrl.toString(), {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      if (!res || !res.status) {
        if (typeof window.showToast === 'function') window.showToast(res?.message || 'File upload failed.', 'error');
        return;
      }
      if (typeof window.showToast === 'function') window.showToast('File attached!', 'success');
      if (fileInput) fileInput.value = '';
      if (typeof window.renderClassDetail === 'function') window.renderClassDetail(classId);
      // Re-open modal to refresh file list
      if (typeof window.studentOpenClassworkSubmissionModal === 'function') {
        window.studentOpenClassworkSubmissionModal(classworkId);
      }
    })
    .catch(() => {
      if (typeof window.showToast === 'function') window.showToast('File upload failed.', 'error');
    });
}


