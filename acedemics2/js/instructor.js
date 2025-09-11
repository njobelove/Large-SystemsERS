3.+
function logout() {
    // Clear session and redirect to index.php
    fetch('../logout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
    })
    .then(() => {
        window.location.href = '../index.php';
    })
    .catch(() => {
        // Fallback if logout.php doesn't exist
        window.location.href = '../index.php';
    });
}
// Instructor-specific JavaScript functionality

// Course Management Functions
function createAssignment() {
    showSection('instructor/create-assignment.html');
}

function gradeAssignment(courseCode, title) {
    // Navigate to grading page with parameters
    window.location.href = `instructor/assignments.html?course=${courseCode}&assignment=${encodeURIComponent(title)}`;
}

function viewSubmissions(courseCode, title) {
    // Navigate to submissions page
    window.location.href = `instructor/assignment-submissions.html?course=${courseCode}&assignment=${encodeURIComponent(title)}`;
}

function uploadMaterial() {
    showSection('instructor/upload-material.html');
}

function downloadMaterial(fileName) {
    // Simulate download
    showMessage(`Downloading ${fileName}...`, 'info');
    setTimeout(() => {
        showMessage('Download completed!', 'success');
    }, 2000);
}

function deleteMaterial(fileName) {
    if (confirmAction(`Delete ${fileName}?`)) {
        showMessage('Material deleted successfully!', 'success');
        // Refresh materials list
        loadCourseMaterials();
    }
}

function createFolder() {
    const folderName = prompt('Enter folder name:');
    if (folderName && folderName.trim()) {
        showMessage(`Folder "${folderName}" created successfully!`, 'success');
        loadFolders();
    }
}

function editFolder(folderId) {
    const newName = prompt('Enter new folder name:');
    if (newName && newName.trim()) {
        showMessage(`Folder renamed to "${newName}"`, 'success');
        loadFolders();
    }
}

function deleteFolder(folderId) {
    if (confirmAction('Delete this folder and all its contents?')) {
        showMessage('Folder deleted successfully!', 'success');
        loadFolders();
    }
}

function addSubfolder(parentId) {
    const folderName = prompt('Enter subfolder name:');
    if (folderName && folderName.trim()) {
        showMessage(`Subfolder "${folderName}" created successfully!`, 'success');
        loadFolders();
    }
}

function viewFolder(folderId) {
    // Navigate to folder contents
    window.location.href = `instructor/course-materials.html?folder=${folderId}`;
}

function reorganizeFolders() {
    showMessage('Folder reorganization feature coming soon!', 'info');
}

function bulkMove() {
    showMessage('Bulk move feature coming soon!', 'info');
}

function setPermissions() {
    showMessage('Permissions management coming soon!', 'info');
}

function exportStructure() {
    showMessage('Exporting folder structure...', 'info');
    setTimeout(() => {
        showMessage('Structure exported successfully!', 'success');
    }, 2000);
}

// Assignment Management Functions
function gradeSubmission(studentId) {
    // Open grading modal or navigate to grading page
    showModal('gradeModal');
    // Load student submission data
    loadSubmissionData(studentId);
}

function viewSubmission(studentId) {
    // Open submission view modal
    showModal('submissionModal');
    loadSubmissionData(studentId);
}

function sendReminder(studentId) {
    if (confirmAction('Send reminder to student?')) {
        showMessage('Reminder sent successfully!', 'success');
    }
}

function gradeAllSubmissions() {
    if (confirmAction('Grade all pending submissions?')) {
        showMessage('Bulk grading completed!', 'success');
        loadSubmissions();
    }
}

function sendReminders() {
    if (confirmAction('Send reminders to all students with pending submissions?')) {
        showMessage('Reminders sent successfully!', 'success');
    }
}

function exportGrades() {
    showMessage('Exporting grades...', 'info');
    setTimeout(() => {
        showMessage('Grades exported successfully!', 'success');
    }, 2000);
}

function extendDeadline() {
    const newDeadline = prompt('Enter new deadline (YYYY-MM-DD):');
    if (newDeadline) {
        showMessage(`Deadline extended to ${newDeadline}`, 'success');
        loadAssignments();
    }
}

function downloadAllSubmissions() {
    showMessage('Downloading all submissions...', 'info');
    setTimeout(() => {
        showMessage('Download completed!', 'success');
    }, 3000);
}

// Attendance Management Functions
function saveAttendance() {
    if (confirmAction('Save attendance records?')) {
        showMessage('Attendance saved successfully!', 'success');
        // Reset form or refresh data
        loadAttendanceData();
    }
}

function recordAnotherDate() {
    // Navigate to attendance recording page
    window.location.href = 'instructor/attendance.html';
}

function viewAllRecords() {
    // Navigate to attendance records page
    window.location.href = 'instructor/attendance-records.html';
}

function viewAttendanceDetails(courseCode) {
    // Navigate to detailed attendance view
    window.location.href = `instructor/attendance-records.html?course=${courseCode}`;
}

function addStudentToCourse() {
    const form = document.getElementById('addStudentForm');
    if (form && validateForm('addStudentForm')) {
        showMessage('Student added to course successfully!', 'success');
        closeModal('addStudentModal');
        form.reset();
        loadCourseStudents();
    }
}

// Gradebook Functions
function exportGradebook() {
    showMessage('Exporting gradebook...', 'info');
    setTimeout(() => {
        showMessage('Gradebook exported successfully!', 'success');
    }, 2000);
}

function saveGradebook() {
    if (confirmAction('Save all grade changes?')) {
        showMessage('Gradebook saved successfully!', 'success');
    }
}

function calculateFinalGrades() {
    if (confirmAction('Calculate final grades for all students?')) {
        showMessage('Final grades calculated successfully!', 'success');
        loadGradebook();
    }
}

// Material Statistics Functions
function exportStats() {
    showMessage('Exporting statistics...', 'info');
    setTimeout(() => {
        showMessage('Statistics exported successfully!', 'success');
    }, 2000);
}

function viewDetailedStats(materialId) {
    showModal('statsModal');
    loadMaterialStats(materialId);
}

function viewStudentAccess(materialId) {
    showModal('accessModal');
    loadStudentAccessData(materialId);
}

function viewStudentDetails(studentId) {
    // Navigate to student details page
    window.location.href = `instructor/course-details.html?student=${studentId}`;
}

// Share Materials Functions
function shareSelectedMaterials() {
    const selectedMaterials = getSelectedMaterials();
    if (selectedMaterials.length === 0) {
        showMessage('Please select materials to share.', 'warning');
        return;
    }

    if (confirmAction(`Share ${selectedMaterials.length} material(s) with students?`)) {
        showMessage('Materials shared successfully!', 'success');
        loadSharedMaterials();
    }
}

function viewSharedStats(materialId) {
    showModal('shareStatsModal');
    loadShareStats(materialId);
}

function unshareItem(materialId) {
    if (confirmAction('Unshare this material?')) {
        showMessage('Material unshared successfully!', 'success');
        loadSharedMaterials();
    }
}

// Upload Material Functions
function uploadMaterialFile() {
    const form = document.getElementById('uploadForm');
    if (form && validateForm('uploadForm')) {
        showMessage('Material uploaded successfully!', 'success');
        form.reset();
        loadCourseMaterials();
    }
}

function removeFile(index) {
    if (confirmAction('Remove this file?')) {
        // Remove file from upload list
        showMessage('File removed from upload list.', 'info');
    }
}

// Office Hours Functions
function editOfficeHours() {
    showSection('instructor/edit-office-hours.html');
}

function saveOfficeHours() {
    const form = document.getElementById('officeHoursForm');
    if (form && validateForm('officeHoursForm')) {
        showMessage('Office hours saved successfully!', 'success');
        goBack();
    }
}

function resetToDefault() {
    if (confirmAction('Reset office hours to default?')) {
        // Reset form to default values
        showMessage('Office hours reset to default.', 'info');
    }
}

function previewSchedule() {
    showModal('previewModal');
    // Load and display schedule preview
}

// Student Management Functions
function viewStudentProfile(studentId) {
    // Navigate to student profile
    window.location.href = `instructor/course-details.html?student=${studentId}`;
}

function sendMessage(studentId) {
    // Open message modal
    showMessage('Messaging feature coming soon!', 'info');
}

// Data Loading Functions
function loadCourses() {
    const container = document.getElementById('coursesContainer');
    if (!container) return;

    const courses = getMockInstructorCourses();

    container.innerHTML = courses.map(course => `
        <div class="course-card">
            <h3>${course.code}: ${course.title}</h3>
            <p><strong>Semester:</strong> ${course.semester}</p>
            <p><strong>Enrolled:</strong> ${course.enrolled}/${course.capacity}</p>
            <div class="course-actions">
                <button class="btn-action" onclick="viewCourseDetails('${course.code}')">View Details</button>
                <button class="btn-action" onclick="manageAttendance('${course.code}')">Attendance</button>
                <button class="btn-action" onclick="viewAssignments('${course.code}')">Assignments</button>
            </div>
        </div>
    `).join('');
}

function loadCourseDetails(courseCode) {
    const course = getMockCourseDetails(courseCode);
    if (!course) return;

    // Update page title and content
    document.title = `${course.code}: ${course.title}`;

    // Load students
    loadCourseStudents(courseCode);

    // Load materials
    loadCourseMaterials(courseCode);

    // Load assignments
    loadCourseAssignments(courseCode);
}

function loadCourseStudents(courseCode) {
    const tableBody = document.querySelector('#studentsTable tbody');
    if (!tableBody) return;

    const students = getMockCourseStudents(courseCode);

    tableBody.innerHTML = students.map(student => `
        <tr>
            <td>${student.id}</td>
            <td>${student.name}</td>
            <td>${student.email}</td>
            <td>${student.grade || 'N/A'}</td>
            <td>
                <button class="btn-action" onclick="viewStudentProfile('${student.id}')">Profile</button>
                <button class="btn-action" onclick="sendMessage('${student.id}')">Message</button>
            </td>
        </tr>
    `).join('');
}

function loadCourseMaterials(courseCode) {
    const container = document.getElementById('materialsContainer');
    if (!container) return;

    const materials = getMockCourseMaterials(courseCode);

    container.innerHTML = materials.map(material => `
        <div class="material-item">
            <h4>${material.title}</h4>
            <p>${material.description}</p>
            <p><strong>Uploaded:</strong> ${material.uploadDate}</p>
            <div class="material-actions">
                <button class="btn-action" onclick="downloadMaterial('${material.fileName}')">Download</button>
                <button class="btn-action delete" onclick="deleteMaterial('${material.fileName}')">Delete</button>
            </div>
        </div>
    `).join('');
}

function loadFolders() {
    const container = document.getElementById('foldersContainer');
    if (!container) return;

    const folders = getMockFolders();

    container.innerHTML = folders.map(folder => `
        <div class="folder-item">
            <div class="folder-header">
                <h4>${folder.name}</h4>
                <div class="folder-actions">
                    <button class="btn-action" onclick="viewFolder('${folder.id}')">View</button>
                    <button class="btn-action" onclick="editFolder('${folder.id}')">Edit</button>
                    <button class="btn-action delete" onclick="deleteFolder('${folder.id}')">Delete</button>
                </div>
            </div>
            ${folder.subfolders ? `
                <div class="subfolders">
                    ${folder.subfolders.map(sub => `
                        <div class="subfolder">
                            <span>${sub.name}</span>
                            <button class="btn-action" onclick="viewFolder('${sub.id}')">View</button>
                        </div>
                    `).join('')}
                </div>
            ` : ''}
        </div>
    `).join('');
}

function loadAssignments() {
    const container = document.getElementById('assignmentsContainer');
    if (!container) return;

    const assignments = getMockAssignments();

    container.innerHTML = assignments.map(assignment => `
        <div class="assignment-item">
            <h4>${assignment.title}</h4>
            <p>${assignment.description}</p>
            <p><strong>Due:</strong> ${assignment.dueDate}</p>
            <p><strong>Submissions:</strong> ${assignment.submissions}/${assignment.totalStudents}</p>
            <div class="assignment-actions">
                <button class="btn-action" onclick="gradeAssignment('${assignment.course}', '${assignment.title}')">Grade</button>
                <button class="btn-action" onclick="viewSubmissions('${assignment.course}', '${assignment.title}')">View Submissions</button>
            </div>
        </div>
    `).join('');
}

function loadSubmissions() {
    const tableBody = document.querySelector('#submissionsTable tbody');
    if (!tableBody) return;

    const submissions = getMockSubmissions();

    tableBody.innerHTML = submissions.map(submission => `
        <tr>
            <td>${submission.studentName}</td>
            <td>${submission.submittedDate}</td>
            <td><span class="status ${submission.status.toLowerCase()}">${submission.status}</span></td>
            <td>${submission.grade || 'Not graded'}</td>
            <td>
                ${submission.status === 'submitted' ?
                    `<button class="btn-action" onclick="gradeSubmission('${submission.studentId}')">Grade</button>
                     <button class="btn-action" onclick="viewSubmission('${submission.studentId}')">View</button>` :
                    `<button class="btn-action" onclick="sendReminder('${submission.studentId}')">Remind</button>`}
            </td>
        </tr>
    `).join('');
}

function loadAttendanceData() {
    const tableBody = document.querySelector('#attendanceTable tbody');
    if (!tableBody) return;

    const students = getMockCourseStudents();

    tableBody.innerHTML = students.map(student => `
        <tr>
            <td>${student.name}</td>
            <td><input type="checkbox" id="present-${student.id}" checked></td>
            <td><input type="checkbox" id="late-${student.id}"></td>
            <td><input type="checkbox" id="absent-${student.id}"></td>
        </tr>
    `).join('');
}

function loadGradebook() {
    const tableBody = document.querySelector('#gradebookTable tbody');
    if (!tableBody) return;

    const gradebook = getMockGradebook();

    tableBody.innerHTML = gradebook.map(entry => `
        <tr>
            <td>${entry.studentName}</td>
            <td><input type="text" value="${entry.assignment1 || ''}" class="grade-input"></td>
            <td><input type="text" value="${entry.assignment2 || ''}" class="grade-input"></td>
            <td><input type="text" value="${entry.midterm || ''}" class="grade-input"></td>
            <td><input type="text" value="${entry.final || ''}" class="grade-input"></td>
            <td>${entry.finalGrade || 'N/A'}</td>
        </tr>
    `).join('');
}

// Mock Data Functions
function getMockInstructorCourses() {
    return [
        {
            code: 'CS101',
            title: 'Introduction to Computer Science',
            semester: 'Fall 2024',
            enrolled: 28,
            capacity: 30
        },
        {
            code: 'CS201',
            title: 'Data Structures',
            semester: 'Fall 2024',
            enrolled: 25,
            capacity: 25
        }
    ];
}

function getMockCourseStudents() {
    return [
        { id: 'S001', name: 'Alice Johnson', email: 'alice@example.com', grade: 'A' },
        { id: 'S002', name: 'Bob Smith', email: 'bob@example.com', grade: 'B+' },
        { id: 'S003', name: 'Carol Davis', email: 'carol@example.com', grade: 'A-' }
    ];
}

function getMockCourseMaterials() {
    return [
        {
            title: 'Lecture 1: Introduction',
            description: 'Introduction to the course',
            fileName: 'lecture1.pdf',
            uploadDate: '2024-01-15'
        },
        {
            title: 'Assignment 1',
            description: 'First programming assignment',
            fileName: 'assignment1.pdf',
            uploadDate: '2024-01-20'
        }
    ];
}

function getMockFolders() {
    return [
        {
            id: 'lectures',
            name: 'Lectures',
            subfolders: [
                { id: 'week1', name: 'Week 1' },
                { id: 'week2', name: 'Week 2' }
            ]
        },
        { id: 'assignments', name: 'Assignments' },
        { id: 'readings', name: 'Readings' }
    ];
}

function getMockAssignments() {
    return [
        {
            course: 'CS101',
            title: 'Programming Assignment 1',
            description: 'Implement a simple calculator',
            dueDate: '2024-02-01',
            submissions: 25,
            totalStudents: 28
        }
    ];
}

function getMockSubmissions() {
    return [
        {
            studentId: 'S001',
            studentName: 'Alice Johnson',
            submittedDate: '2024-01-30',
            status: 'submitted',
            grade: '95'
        },
        {
            studentId: 'S002',
            studentName: 'Bob Smith',
            submittedDate: '2024-01-29',
            status: 'submitted',
            grade: '88'
        }
    ];
}

function getMockGradebook() {
    return [
        {
            studentName: 'Alice Johnson',
            assignment1: '95',
            assignment2: '92',
            midterm: '88',
            final: '90',
            finalGrade: 'A'
        },
        {
            studentName: 'Bob Smith',
            assignment1: '88',
            assignment2: '85',
            midterm: '82',
            final: '86',
            finalGrade: 'B+'
        }
    ];
}

// Utility Functions
function getSelectedMaterials() {
    const checkboxes = document.querySelectorAll('input[name="materials"]:checked');
    return Array.from(checkboxes).map(checkbox => checkbox.value);
}

function loadSubmissionData(studentId) {
    // Load submission data for grading
    console.log('Loading submission data for student:', studentId);
}

function loadMaterialStats(materialId) {
    // Load statistics for material
    console.log('Loading stats for material:', materialId);
}

function loadStudentAccessData(materialId) {
    // Load student access data
    console.log('Loading access data for material:', materialId);
}

function loadShareStats(materialId) {
    // Load sharing statistics
    console.log('Loading share stats for material:', materialId);
}

function loadSharedMaterials() {
    // Load shared materials list
    console.log('Loading shared materials');
}

function manageAttendance(courseCode) {
    window.location.href = `instructor/attendance.html?course=${courseCode}`;
}

function viewAssignments(courseCode) {
    window.location.href = `instructor/assignments.html?course=${courseCode}`;
}

function viewCourseAssignments(courseCode) {
    // Load assignments for specific course
    console.log('Loading assignments for course:', courseCode);
}

// Initialize instructor-specific functionality
document.addEventListener('DOMContentLoaded', function() {
    // Load data based on current page
    const currentPage = window.location.pathname.split('/').pop();

    switch(currentPage) {
        case 'dashboard.html':
            loadCourses();
            break;
        case 'course-details.html':
            const urlParams = new URLSearchParams(window.location.search);
            const courseCode = urlParams.get('course');
            if (courseCode) {
                loadCourseDetails(courseCode);
            }
            break;
        case 'course-materials.html':
            loadCourseMaterials();
            loadFolders();
            break;
        case 'assignments.html':
            loadAssignments();
            break;
        case 'assignment-submissions.html':
            loadSubmissions();
            break;
        case 'attendance.html':
            loadAttendanceData();
            break;
        case 'gradebook.html':
            loadGradebook();
            break;
        case 'manage-folders.html':
            loadFolders();
            break;
        case 'share-materials.html':
            loadSharedMaterials();
            break;
    }
});
