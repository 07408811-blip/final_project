
<?php
session_start();
require_once "../../vendor/autoload.php";

use App\Model\UserModel;
use App\Model\ClassModel;
use App\Model\EnrollmentModel;
use App\Model\AnnouncementModel;
use App\Model\ClassworkModel;
use App\Model\CalendarModel;
use App\Model\ClassResourceModel;
use App\Model\SubmissionModel;
use App\Model\GradeModel;

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$userRole = $_SESSION['role'] ?? null;

if (!$userId || !$userRole) {
    echo json_encode([
        'status' => false,
        'message' => 'Not authenticated'
    ]);
    exit;
}

$response = ['status' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        // ─── CLASSES ───
        case 'get_classes':
            $classModel = new ClassModel();
            if ($userRole === 'teacher') {
                $response = ['status' => true, 'data' => $classModel->getClassesByTeacher($userId)];
            } else {
                $response = ['status' => true, 'data' => $classModel->getClassesByStudent($userId)];
            }
            break;

        case 'create_class':
            if ($method !== 'POST' || $userRole !== 'teacher') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $classModel = new ClassModel();
            $id = $classModel->createClass([
                'teacher_id' => $userId,
                'subject' => $data['subject'] ?? '',
                'section' => $data['section'] ?? '',
                'course_code' => $data['course_code'] ?? '',
                'invite_code' => $data['invite_code'] ?? ''
            ]);
            $response = ['status' => true, 'id' => $id, 'message' => 'Class created'];
            break;

        case 'join_class':
            if ($method !== 'POST' || $userRole !== 'student') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $classModel = new ClassModel();
            $enrollmentModel = new EnrollmentModel();
            $class = $classModel->getClassByCode(strtoupper($data['invite_code'] ?? ''));
            if (!$class) {
                $response = ['status' => false, 'message' => 'Invalid class code'];
                break;
            }
            $existing = $enrollmentModel->isEnrolled($userId, (int)$class['id']);
            if ($existing) {
                $response = ['status' => false, 'message' => 'Already enrolled'];
                break;
            }
            $enrollmentModel->enroll($userId, (int)$class['id']);
            $response = ['status' => true, 'message' => 'Joined class successfully'];
            break;

        // ─── ANNOUNCEMENTS ───
        case 'get_announcements':
            $announcementModel = new AnnouncementModel();
            if ($userRole === 'teacher') {
                $response = ['status' => true, 'data' => []];
            } else {
                $response = ['status' => true, 'data' => $announcementModel->getForStudent($userId)];
            }
            break;

        case 'create_announcement':
            if ($method !== 'POST' || $userRole !== 'teacher') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $announcementModel = new AnnouncementModel();

            $id = $announcementModel->create([
                'class_id' => (int)($data['class_id'] ?? 0),
                'posted_by' => (int)$userId,
                'title' => (string)($data['title'] ?? ''),
                'content' => (string)($data['content'] ?? ''),
                'image_url' => $data['image_url'] ?? null,
                'publish_date' => $data['publish_date'] ?? null,
            ]);

            if ($id === false) {
                $response = ['status' => false, 'message' => 'Failed to create announcement'];
                break;
            }

            $response = ['status' => true, 'id' => $id, 'message' => 'Announcement posted'];
            break;

        // TEACHER: dedicated announcement manager APIs
        case 'get_teacher_announcements':
            $announcementModel = new AnnouncementModel();
            $response = ['status' => true, 'data' => $announcementModel->getForTeacher((int)$userId)];
            break;

        case 'get_teacher_announcement_one':
            if ($method !== 'GET' || $userRole !== 'teacher') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }
            $announcementId = (int)($_GET['announcement_id'] ?? 0);
            if ($announcementId <= 0) {
                $response = ['status' => false, 'message' => 'Invalid announcement id'];
                break;
            }
            $announcementModel = new AnnouncementModel();
            $one = $announcementModel->getOneForTeacher($announcementId, (int)$userId);
            if (!$one) {
                $response = ['status' => false, 'message' => 'Announcement not found'];
                break;
            }
            $response = ['status' => true, 'data' => $one];
            break;

        case 'update_announcement':
            if ($method !== 'POST' || $userRole !== 'teacher') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $announcementId = (int)($data['announcement_id'] ?? 0);
            $announcementModel = new AnnouncementModel();

            $ok = $announcementModel->updateForTeacher($announcementId, (int)$userId, [
                'class_id' => (int)($data['class_id'] ?? 0),
                'title' => (string)($data['title'] ?? ''),
                'content' => (string)($data['content'] ?? ''),
                'image_url' => $data['image_url'] ?? null,
                'publish_date' => $data['publish_date'] ?? null,
            ]);

            if (!$ok) {
                $response = ['status' => false, 'message' => 'Failed to update announcement'];
                break;
            }

            $response = ['status' => true, 'message' => 'Announcement updated'];
            break;

        case 'delete_announcement':
            if ($method !== 'POST' || $userRole !== 'teacher') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $announcementId = (int)($data['announcement_id'] ?? 0);
            if ($announcementId <= 0) {
                $response = ['status' => false, 'message' => 'Invalid announcement id'];
                break;
            }
            $announcementModel = new AnnouncementModel();
            $ok = $announcementModel->deleteForTeacher($announcementId, (int)$userId);
            if (!$ok) {
                $response = ['status' => false, 'message' => 'Failed to delete announcement'];
                break;
            }
            $response = ['status' => true, 'message' => 'Announcement deleted'];
            break;


        // ─── CLASSWORK ───
        case 'get_classwork':
            $classworkModel = new ClassworkModel();
            $subject = $_GET['subject'] ?? null;
            $type = $_GET['type'] ?? null;
            if ($userRole === 'teacher') {
                $response = ['status' => true, 'data' => []];
            } else {
                $response = ['status' => true, 'data' => $classworkModel->getForStudent($userId, $subject, $type)];
            }
            break;

        case 'create_classwork':
            if ($method !== 'POST' || $userRole !== 'teacher') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $classworkModel = new ClassworkModel();

            $maxScore = $data['max_score'] ?? null;
            if ($maxScore === '') $maxScore = null;

            $id = $classworkModel->create([
                'class_id' => (int)($data['class_id'] ?? 0),
                'title' => (string)($data['title'] ?? ''),
                'type' => (string)($data['type'] ?? 'lesson'),
                'description' => (string)($data['description'] ?? ''),
                'due_date' => $data['due_date'] ?? null,
                'max_score' => $maxScore !== null ? (int)$maxScore : 100
            ]);

            if ($id === false) {
                $response = ['status' => false, 'message' => 'Failed to create classwork'];
                break;
            }

            $response = ['status' => true, 'id' => $id, 'message' => 'Classwork created'];
            break;


        // ─── CALENDAR ───
        case 'get_calendar':
            $calendarModel = new CalendarModel();
            $month = $_GET['month'] ?? date('n');
            $year = $_GET['year'] ?? date('Y');
            if ($userRole === 'teacher') {
                $data = $calendarModel->getByTeacher($userId, (int)$month, (int)$year);
            } else {
                $data = $calendarModel->getByStudent($userId, (int)$month, (int)$year);
            }
            $response = ['status' => true, 'month' => (int)$month, 'year' => (int)$year, 'data' => $data];
            break;

        case 'create_event':
            if ($method !== 'POST') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $calendarModel = new CalendarModel();
            $id = $calendarModel->createEvent([
                'class_id' => $data['class_id'] ?? 0,
                'title' => $data['title'] ?? '',
                'event_date' => $data['event_date'] ?? date('Y-m-d'),
                'event_type' => $data['event_type'] ?? 'general'
            ]);
            $response = ['status' => true, 'id' => $id, 'message' => 'Event created'];
            break;

        // ─── PEOPLE ───
        case 'get_people':
            $enrollmentModel = new EnrollmentModel();
            $classId = $_GET['class_id'] ?? 0;
            $instructors = $enrollmentModel->getTeacherForClass($classId);
            $classmates = $enrollmentModel->getClassmates($classId, $userId);
            $response = ['status' => true, 'instructors' => $instructors, 'classmates' => $classmates];
            break;



        // ─── PENDING ACTIVITIES ───
        case 'get_pending':
            $classworkModel = new ClassworkModel();
            $response = ['status' => true, 'data' => $classworkModel->getPending($userId)];
            break;

        // ─── PROFILE ───
        case 'get_profile':
            $userModel = new UserModel();
            $user = $userModel->getUserById($userId);
            if ($user) unset($user['password']);
            $response = ['status' => true, 'data' => $user];
            break;

        case 'update_profile':
            if ($method !== 'POST') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $userModel = new UserModel();
            $updateData = [];
            if (!empty($data['username'])) $updateData['username'] = $data['username'];
            if (!empty($data['email'])) $updateData['email'] = $data['email'];
            if (!empty($data['password'])) $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            if (!empty($updateData)) {
                $userModel->updateProfile($userId, $updateData);
                if (!empty($data['username'])) $_SESSION['username'] = $data['username'];
            }
            $response = ['status' => true, 'message' => 'Profile updated'];
            break;

        // ─── CLASS DETAIL ───
        case 'get_class_detail':
            $classId = (int)($_GET['class_id'] ?? 0);
            if ($classId <= 0) {
                $response = ['status' => false, 'message' => 'Invalid class id'];
                break;
            }

            $isEnrolled = false;
            if ($userRole === 'student') {
                $enrollmentModel = new EnrollmentModel();
                $isEnrolled = $enrollmentModel->isEnrolled($userId, $classId);
                if (!$isEnrolled) {
                    $response = ['status' => false, 'message' => 'Not enrolled'];
                    break;
                }
            } else {
                $classModel = new ClassModel();
                $class = $classModel->getClassById($classId);
                if (!$class || (int)$class['teacher_id'] !== $userId) {
                    $response = ['status' => false, 'message' => 'Unauthorized'];
                    break;
                }
            }

            $classworkModel = new ClassworkModel();
            $classworkItems = $classworkModel->getByClass($classId);

            $payloadClasswork = [];
            if ($userRole === 'student') {
                $studentId = $userId;
                foreach ($classworkItems as $cw) {
                    $submission = $submissionModel = new SubmissionModel();
                    $mySub = $submissionModel->getSubmissionForStudent((int)$cw['id'], $classId, $studentId);
                    $payloadClasswork[] = [
                        'id' => (int)$cw['id'],
                        'title' => $cw['title'],
                        'description' => $cw['description'] ?? '',
                        'type' => $cw['type'],
                        'subject' => $cw['subject'] ?? null,
                        'due_date' => $cw['due_date'] ?? null,
                        'my_submission' => $mySub ? [
                            'id' => (int)$mySub['id'],
                            'submitted_text' => $mySub['submitted_text']
                        ] : null
                    ];
                }
            } else {
                // teacher: include submissions + grades for each classwork
                foreach ($classworkItems as $cw) {
                    $cwId = (int)$cw['id'];
                    $submissions = [];
                    $subsModel = new SubmissionModel();
                    $gradesModel = new GradeModel();
                    $subsWithGrades = $gradesModel->getSubmissionsWithGradesForClasswork($cwId, $classId);

                    // If there is no grade row, score/feedback may be null; still return student submissions.
                    $payloadSubs = [];
                    foreach ($subsWithGrades as $s) {
                        $payloadSubs[] = [
                            'id' => (int)$s['id'],
                            'student_username' => $s['student_username'],
                            'score' => $s['score'],
                            'feedback' => $s['feedback']
                        ];
                    }

                    $payloadClasswork[] = [
                        'id' => (int)$cw['id'],
                        'title' => $cw['title'],
                        'description' => $cw['description'] ?? '',
                        'type' => $cw['type'],
                        'subject' => $cw['subject'] ?? null,
                        'due_date' => $cw['due_date'] ?? null,
                        'max_score' => $cw['max_score'] ?? 100,
                        'submissions_with_grades' => $payloadSubs
                    ];
                }
            }

            $response = ['status' => true, 'data' => ['class' => ['id' => $classId], 'classwork' => $payloadClasswork]];
            break;

        // ─── STUDENT SUBMIT CLASSWORK (TEXT) ───
        case 'student_submit_classwork':
            if ($userRole !== 'student' || $method !== 'POST') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $classworkId = (int)($data['classwork_id'] ?? 0);
            $classId = (int)($data['class_id'] ?? 0);
            $submittedText = $data['submitted_text'] ?? null;

            if ($classworkId <= 0 || $classId <= 0) {
                $response = ['status' => false, 'message' => 'Invalid payload'];
                break;
            }

            $enrollmentModel = new EnrollmentModel();
            if (!$enrollmentModel->isEnrolled($userId, $classId)) {
                $response = ['status' => false, 'message' => 'Not enrolled'];
                break;
            }

            $submissionModel = new SubmissionModel();
            $sid = $submissionModel->upsertTextSubmission($classworkId, $classId, $userId, $submittedText);
            if ($sid === false) {
                $response = ['status' => false, 'message' => 'Submission failed'];
            } else {
                $response = ['status' => true, 'id' => $sid, 'message' => 'Submitted'];
            }
            break;

        // ─── TEACHER GRADE SUBMISSION (TEXT) ───
        case 'teacher_grade_submission':
            if ($userRole !== 'teacher' || $method !== 'POST') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $classId = (int)($data['class_id'] ?? 0);
            $classworkId = (int)($data['classwork_id'] ?? 0);
            $submissionId = (int)($data['submission_id'] ?? 0);
            $score = $data['score'] ?? null;
            $feedback = $data['feedback'] ?? '';
            $dueDate = $data['due_date'] ?? null;

            if ($classId <= 0 || $classworkId <= 0 || $submissionId <= 0) {
                $response = ['status' => false, 'message' => 'Invalid payload'];
                break;
            }

            // Verify teacher owns class
            $classModel = new ClassModel();
            $class = $classModel->getClassById($classId);
            if (!$class || (int)$class['teacher_id'] !== $userId) {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }

            $gradeModel = new GradeModel();
            $gradeId = $gradeModel->upsertGrade(
                $submissionId,
                $classworkId,
                $classId,
                $userId,
                $score !== null ? (int)$score : null,
                $feedback
            );

            if ($gradeId === false) {
                $response = ['status' => false, 'message' => 'Grade failed'];
                break;
            }

            // Option B: also update classwork due_date when provided in the grading form
            if ($dueDate !== null) {
                $dueDate = is_string($dueDate) ? trim($dueDate) : $dueDate;
                if ($dueDate === '') {
                    $dueDate = null;
                }
                // Expect due_date from <input type="date"> in format YYYY-MM-DD
                $db = \App\Helpers\Database::getInstance();
                $db->update(
                    'classwork',
                    ['due_date' => $dueDate],
                    'id = :id AND class_id = :class_id',
                    ['id' => $classworkId, 'class_id' => $classId]
                );
            }

            $response = ['status' => true, 'id' => $gradeId, 'message' => 'Grade saved'];
            break;

        // ─── CLASS DETAIL: ANNOUNCEMENTS (BY CLASS) ───
        case 'get_class_announcements':
            if ($method !== 'GET') {
                $response = ['status' => false, 'message' => 'Invalid request method'];
                break;
            }
            $classId = (int)($_GET['class_id'] ?? 0);
            if ($classId <= 0) {
                $response = ['status' => false, 'message' => 'Invalid class id'];
                break;
            }

            // Authorization: student must be enrolled; teacher must own class.
            if ($userRole === 'student') {
                $enrollmentModel = new EnrollmentModel();
                if (!$enrollmentModel->isEnrolled($userId, $classId)) {
                    $response = ['status' => false, 'message' => 'Not enrolled'];
                    break;
                }
            } else {
                $classModel = new ClassModel();
                $class = $classModel->getClassById($classId);
                if (!$class || (int)$class['teacher_id'] !== $userId) {
                    $response = ['status' => false, 'message' => 'Unauthorized'];
                    break;
                }
            }

            $announcementModel = new AnnouncementModel();
            $response = ['status' => true, 'data' => $announcementModel->getByClass($classId)];
            break;

        // ─── CLASS DETAIL: CLASS RESOURCES (LIST) ───
        case 'get_class_resources':
            if ($method !== 'GET') {
                $response = ['status' => false, 'message' => 'Invalid request method'];
                break;
            }

            $classId = (int)($_GET['class_id'] ?? 0);
            if ($classId <= 0) {
                $response = ['status' => false, 'message' => 'Invalid class id'];
                break;
            }

            // Authorization: student must be enrolled; teacher must own class.
            if ($userRole === 'student') {
                $enrollmentModel = new EnrollmentModel();
                if (!$enrollmentModel->isEnrolled($userId, $classId)) {
                    $response = ['status' => false, 'message' => 'Not enrolled'];
                    break;
                }
            } else {
                $classModel = new ClassModel();
                $class = $classModel->getClassById($classId);
                if (!$class || (int)$class['teacher_id'] !== $userId) {
                    $response = ['status' => false, 'message' => 'Unauthorized'];
                    break;
                }
            }

            $resourceModel = new ClassResourceModel();
            $response = ['status' => true, 'data' => $resourceModel->getResourcesForClass($classId)];
            break;

        // ─── CLASS DETAIL: SUBMISSION FILES (LIST) ───
        case 'get_submission_files_for_submission':
            if ($method !== 'GET') {
                $response = ['status' => false, 'message' => 'Invalid request method'];
                break;
            }

            $submissionId = (int)($_GET['submission_id'] ?? 0);
            $classId = (int)($_GET['class_id'] ?? 0);
            if ($submissionId <= 0 || $classId <= 0) {
                $response = ['status' => false, 'message' => 'Invalid payload'];
                break;
            }

            // Authorization: teacher must own class; student must be enrolled.
            if ($userRole === 'student') {
                $enrollmentModel = new EnrollmentModel();
                if (!$enrollmentModel->isEnrolled($userId, $classId)) {
                    $response = ['status' => false, 'message' => 'Not enrolled'];
                    break;
                }
            } else {
                $classModel = new ClassModel();
                $class = $classModel->getClassById($classId);
                if (!$class || (int)$class['teacher_id'] !== $userId) {
                    $response = ['status' => false, 'message' => 'Unauthorized'];
                    break;
                }
            }

            $submissionModel = new SubmissionModel();
            $response = ['status' => true, 'data' => $submissionModel->listSubmissionFiles($submissionId)];
            break;

        // ─── CLASS DETAIL: FILE ATTACHMENTS (SUBMISSION FILES) ───
        case 'student_attach_file_to_submission':

            if ($userRole !== 'student' || $method !== 'POST') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }

            // Payload: class_id, classwork_id, (optional) submission_id; plus file in multipart `file`.
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $classId = (int)($data['class_id'] ?? 0);
            $classworkId = (int)($data['classwork_id'] ?? 0);
            $submissionId = (int)($data['submission_id'] ?? 0);

            if ($classId <= 0 || $classworkId <= 0) {
                $response = ['status' => false, 'message' => 'Invalid payload'];
                break;
            }

            $enrollmentModel = new EnrollmentModel();
            if (!$enrollmentModel->isEnrolled($userId, $classId)) {
                $response = ['status' => false, 'message' => 'Not enrolled'];
                break;
            }

            // If submission_id not provided, attempt to locate submission.
            if ($submissionId <= 0) {
                $submissionModel = new SubmissionModel();
                $existing = $submissionModel->getSubmissionForStudent($classworkId, $classId, $userId);
                if (!$existing || empty($existing['id'])) {
                    $response = ['status' => false, 'message' => 'No submission found. Submit text first.'];
                    break;
                }
                $submissionId = (int)$existing['id'];
            }

            if (!isset($_FILES['file'])) {
                $response = ['status' => false, 'message' => 'No file uploaded'];
                break;
            }

            // Delegate storage validation to upload.php by calling it internally (use cURL).
            // To avoid server-side complexity, accept that upload.php already stores the file and returns metadata.
            $uploadResponse = null;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']) . '/api/upload.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($_FILES['file']['tmp_name'], $_FILES['file']['type'] ?? 'application/octet-stream', $_FILES['file']['name'] ?? 'file')]);
            $uploadResponse = curl_exec($ch);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($uploadResponse === false) {
                $response = ['status' => false, 'message' => 'Upload failed'];
                break;
            }

            $uploadJson = json_decode($uploadResponse, true);
            if (!$uploadJson || !($uploadJson['status'] ?? false)) {
                $response = ['status' => false, 'message' => $uploadJson['message'] ?? 'Upload rejected'];
                break;
            }

            $submissionModel = new SubmissionModel();
            $savedId = $submissionModel->addSubmissionFile($submissionId, [
                'stored_filename' => $uploadJson['stored_filename'],
                'original_name' => $uploadJson['original_name'],
                'mime_type' => $uploadJson['mime_type'] ?? null,
                'file_size' => $uploadJson['file_size'] ?? 0
            ]);

            if ($savedId === false) {
                $response = ['status' => false, 'message' => 'Failed to attach file'];
                break;
            }

            $response = ['status' => true, 'id' => $savedId, 'message' => 'File attached'];
            break;

        case 'teacher_detach_submission_file':
            if ($userRole !== 'teacher' || $method !== 'POST') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $classId = (int)($data['class_id'] ?? 0);
            $classworkId = (int)($data['classwork_id'] ?? 0);
            $submissionFileId = (int)($data['submission_file_id'] ?? 0);

            if ($classId <= 0 || $classworkId <= 0 || $submissionFileId <= 0) {
                $response = ['status' => false, 'message' => 'Invalid payload'];
                break;
            }

            $classModel = new ClassModel();
            $class = $classModel->getClassById($classId);
            if (!$class || (int)$class['teacher_id'] !== $userId) {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }

            $submissionModel = new SubmissionModel();
            $ok = $submissionModel->deleteSubmissionFileIfTeacherOwns($classworkId, $classId, $submissionFileId, $userId);
            if (!$ok) {
                $response = ['status' => false, 'message' => 'Delete failed'];
                break;
            }

            $response = ['status' => true, 'message' => 'File deleted'];
            break;

        // ─── CLASS DETAIL: CLASS RESOURCES ───
        case 'teacher_add_class_resource':
            if ($userRole !== 'teacher' || $method !== 'POST') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $classId = (int)($data['class_id'] ?? 0);

            if ($classId <= 0) {
                $response = ['status' => false, 'message' => 'Invalid class id'];
                break;
            }

            if (!isset($_FILES['file'])) {
                $response = ['status' => false, 'message' => 'No file uploaded'];
                break;
            }

            // Authorization: teacher must own class.
            $classModel = new ClassModel();
            $class = $classModel->getClassById($classId);
            if (!$class || (int)$class['teacher_id'] !== $userId) {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }

            // Store file using upload.php (same internal cURL approach)
            $uploadResponse = null;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']) . '/api/upload.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($_FILES['file']['tmp_name'], $_FILES['file']['type'] ?? 'application/octet-stream', $_FILES['file']['name'] ?? 'file')]);
            $uploadResponse = curl_exec($ch);
            curl_close($ch);

            $uploadJson = json_decode($uploadResponse, true);
            if (!$uploadJson || !($uploadJson['status'] ?? false)) {
                $response = ['status' => false, 'message' => $uploadJson['message'] ?? 'Upload rejected'];
                break;
            }

            $resourceModel = new ClassResourceModel();
            $id = $resourceModel->create([
                'class_id' => $classId,
                'filename' => $uploadJson['stored_filename'],
                'original_name' => $uploadJson['original_name'],
                'mime_type' => $uploadJson['mime_type'] ?? null,
                'file_size' => $uploadJson['file_size'] ?? 0,
                'uploaded_by' => $userId
            ]);

            if ($id === false) {
                $response = ['status' => false, 'message' => 'Failed to save resource'];
                break;
            }

            $response = ['status' => true, 'id' => $id, 'message' => 'Resource added'];
            break;

        case 'teacher_delete_class_resource':
            if ($userRole !== 'teacher' || $method !== 'POST') {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }

            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $classId = (int)($data['class_id'] ?? 0);
            $resourceId = (int)($data['resource_id'] ?? 0);

            if ($classId <= 0 || $resourceId <= 0) {
                $response = ['status' => false, 'message' => 'Invalid payload'];
                break;
            }

            $classModel = new ClassModel();
            $class = $classModel->getClassById($classId);
            if (!$class || (int)$class['teacher_id'] !== $userId) {
                $response = ['status' => false, 'message' => 'Unauthorized'];
                break;
            }

            $resourceModel = new ClassResourceModel();
            $ok = $resourceModel->deleteIfTeacherOwns($classId, $resourceId, $userId);
            if (!$ok) {
                $response = ['status' => false, 'message' => 'Delete failed'];
                break;
            }

            $response = ['status' => true, 'message' => 'Resource deleted'];
            break;

        default:
            $response = ['status' => false, 'message' => 'Unknown action: ' . $action];


    }
} catch (Exception $e) {
    $response = ['status' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);



