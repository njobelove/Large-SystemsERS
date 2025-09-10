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
// Admin-specific JavaScript functionality

// Program Management Functions
function showAddProgramModal() {
    showModal('addProgramModal');
}

function createProgram() {
    const form = document.getElementById('addProgramForm');
    if (form && validateForm('addProgramForm')) {
        showMessage('Program created successfully!', 'success');
        closeModal('addProgramModal');
        form.reset();
        loadPrograms();
    }
}

function editProgram(programCode) {
    // Load program data and show edit modal
    const program = getProgramByCode(programCode);
    if (program) {
        document.getElementById('editProgramCode').value = program.code;
        document.getElementById('editProgramName').value = program.name;
        document.getElementById('editProgramDescription').value = program.description;
        showModal('editProgramModal');
    }
}

function updateProgram() {
    const form = document.getElementById('editProgramForm');
    if (form && validateForm('editProgramForm')) {
        showMessage('Program updated successfully!', 'success');
        closeModal('editProgramModal');
        loadPrograms();
    }
}

function deleteProgram(programCode) {
    if (confirmAction(`Delete program ${programCode}?`)) {
        showMessage('Program deleted successfully!', 'success');
        loadPrograms();
    }
}

// Semester Management Functions
function showAddSemesterModal() {
    showModal('addSemesterModal');
}

function createSemester() {
    const form = document.getElementById('addSemesterForm');
    if (form && validateForm('addSemesterForm')) {
        showMessage('Semester created successfully!', 'success');
        closeModal('addSemesterModal');
        form.reset();
        loadSemesters();
    }
}

function editSemester(semesterCode) {
    const semester = getSemesterByCode(semesterCode);
    if (semester) {
        document.getElementById('editSemesterCode').value = semester.code;
        document.getElementById('editSemesterName').value = semester.name;
        document.getElementById('editSemesterStartDate').value = semester.startDate;
        document.getElementById('editSemesterEndDate').value = semester.endDate;
        showModal('editSemesterModal');
    }
}

function updateSemester() {
    const form = document.getElementById('editSemesterForm');
    if (form && validateForm('editSemesterForm')) {
        showMessage('Semester updated successfully!', 'success');
        closeModal('editSemesterModal');
        loadSemesters();
    }
}

function deleteSemester(semesterCode) {
    if (confirmAction(`Delete semester ${semesterCode}?`)) {
        showMessage('Semester deleted successfully!', 'success');
        loadSemesters();
    }
}

function setSelectedAsCurrentSemester() {
    const selectedSemester = document.querySelector('input[name="currentSemester"]:checked');
    if (selectedSemester) {
        showMessage(`Current semester set to ${selectedSemester.value}`, 'success');
        loadSemesters();
    } else {
        showMessage('Please select a semester.', 'warning');
    }
}

// Course Management Functions
function showAddCourseModal() {
    showModal('addCourseModal');
}

function createCourse() {
    const form = document.getElementById('addCourseForm');
    if (form && validateForm('addCourseForm')) {
        showMessage('Course created successfully!', 'success');
        closeModal('addCourseModal');
        form.reset();
        loadCourses();
    }
}

function editCourse(courseCode) {
    const course = getCourseByCode(courseCode);
    if (course) {
        document.getElementById('editCourseCode').value = course.code;
        document.getElementById('editCourseTitle').value = course.title;
        document.getElementById('editCourseDescription').value = course.description;
        document.getElementById('editCourseCredits').value = course.credits;
        document.getElementById('editCourseDepartment').value = course.department;
        showModal('editCourseModal');
    }
}

function updateCourse() {
    const form = document.getElementById('editCourseForm');
    if (form && validateForm('editCourseForm')) {
        showMessage('Course updated successfully!', 'success');
        closeModal('editCourseModal');
        loadCourses();
    }
}

function deleteCourse(courseCode) {
    if (confirmAction(`Delete course ${courseCode}?`)) {
        showMessage('Course deleted successfully!', 'success');
        loadCourses();
    }
}

// Report Generation Functions
function generateCustomReport() {
    const form = document.getElementById('reportForm');
    if (form && validateForm('reportForm')) {
        showMessage('Generating report...', 'info');
        setTimeout(() => {
            showMessage('Report generated successfully!', 'success');
            // In a real app, this would download the report
        }, 2000);
    }
}

// Student Management Functions
function viewStudentDetails(studentId) {
    // Navigate to student details page
    window.location.href = `admin/student-info.html?student=${studentId}`;
}

function editStudent(studentId) {
    // Navigate to student edit page
    window.location.href = `admin/student-info.html?student=${studentId}&edit=true`;
}

function deleteStudent(studentId) {
    if (confirmAction('Delete this student? This action cannot be undone.')) {
        showMessage('Student deleted successfully!', 'success');
        loadStudents();
    }
}

// Instructor Management Functions
function viewInstructorDetails(instructorId) {
    // Navigate to instructor details page
    window.location.href = `admin/instructor-info.html?instructor=${instructorId}`;
}

function editInstructor(instructorId) {
    // Navigate to instructor edit page
    window.location.href = `admin/instructor-info.html?instructor=${instructorId}&edit=true`;
}

function deleteInstructor(instructorId) {
    if (confirmAction('Delete this instructor? This action cannot be undone.')) {
        showMessage('Instructor deleted successfully!', 'success');
        loadInstructors();
    }
}

// Dashboard Functions
function loadDashboardStats() {
    // Load various statistics for admin dashboard
    const stats = getMockDashboardStats();

    // Update stat cards
    document.getElementById('totalStudents').textContent = stats.totalStudents;
    document.getElementById('totalInstructors').textContent = stats.totalInstructors;
    document.getElementById('totalCourses').textContent = stats.totalCourses;
    document.getElementById('activePrograms').textContent = stats.activePrograms;
}

// Data Loading Functions
function loadPrograms() {
    const tableBody = document.querySelector('#programsTable tbody');
    if (!tableBody) return;

    const programs = getMockPrograms();

    tableBody.innerHTML = programs.map(program => `
        <tr>
            <td>${program.code}</td>
            <td>${program.name}</td>
            <td>${program.description}</td>
            <td>${program.departments}</td>
            <td>
                <button class="btn-action edit" onclick="editProgram('${program.code}')">Edit</button>
                <button class="btn-action delete" onclick="deleteProgram('${program.code}')">Delete</button>
            </td>
        </tr>
    `).join('');
}

function loadSemesters() {
    const tableBody = document.querySelector('#semestersTable tbody');
    if (!tableBody) return;

    const semesters = getMockSemesters();

    tableBody.innerHTML = semesters.map(semester => `
        <tr>
            <td>
                <input type="radio" name="currentSemester" value="${semester.code}"
                       ${semester.isCurrent ? 'checked' : ''}>
            </td>
            <td>${semester.code}</td>
            <td>${semester.name}</td>
            <td>${semester.startDate}</td>
            <td>${semester.endDate}</td>
            <td><span class="status ${semester.status.toLowerCase()}">${semester.status}</span></td>
            <td>
                <button class="btn-action edit" onclick="editSemester('${semester.code}')">Edit</button>
                <button class="btn-action delete" onclick="deleteSemester('${semester.code}')">Delete</button>
            </td>
        </tr>
    `).join('');
}

function loadCourses() {
    const tableBody = document.querySelector('#coursesTable tbody');
    if (!tableBody) return;

    const courses = getMockCourses();

    tableBody.innerHTML = courses.map(course => `
        <tr>
            <td>${course.code}</td>
            <td>${course.title}</td>
            <td>${course.credits}</td>
            <td>${course.department}</td>
            <td>${course.instructor || 'Not assigned'}</td>
            <td>${course.enrolled}/${course.capacity}</td>
            <td>
                <button class="btn-action edit" onclick="editCourse('${course.code}')">Edit</button>
                <button class="btn-action delete" onclick="deleteCourse('${course.code}')">Delete</button>
            </td>
        </tr>
    `).join('');
}

function loadStudents() {
    const tableBody = document.querySelector('#studentsTable tbody');
    if (!tableBody) return;

    const students = getMockStudents();

    tableBody.innerHTML = students.map(student => `
        <tr>
            <td>${student.id}</td>
            <td>${student.name}</td>
            <td>${student.email}</td>
            <td>${student.program}</td>
            <td>${student.status}</td>
            <td>${student.gpa}</td>
            <td>
                <button class="btn-action" onclick="viewStudentDetails('${student.id}')">View</button>
                <button class="btn-action edit" onclick="editStudent('${student.id}')">Edit</button>
                <button class="btn-action delete" onclick="deleteStudent('${student.id}')">Delete</button>
            </td>
        </tr>
    `).join('');
}

function loadInstructors() {
    const tableBody = document.querySelector('#instructorsTable tbody');
    if (!tableBody) return;

    const instructors = getMockInstructors();

    tableBody.innerHTML = instructors.map(instructor => `
        <tr>
            <td>${instructor.id}</td>
            <td>${instructor.name}</td>
            <td>${instructor.email}</td>
            <td>${instructor.department}</td>
            <td>${instructor.courses}</td>
            <td>${instructor.status}</td>
            <td>
                <button class="btn-action" onclick="viewInstructorDetails('${instructor.id}')">View</button>
                <button class="btn-action edit" onclick="editInstructor('${instructor.id}')">Edit</button>
                <button class="btn-action delete" onclick="deleteInstructor('${instructor.id}')">Delete</button>
            </td>
        </tr>
    `).join('');
}

function loadReports() {
    const container = document.getElementById('reportsContainer');
    if (!container) return;

    const reports = getMockReports();

    container.innerHTML = reports.map(report => `
        <div class="report-item">
            <h4>${report.title}</h4>
            <p>${report.description}</p>
            <p><strong>Generated:</strong> ${report.generatedDate}</p>
            <button class="btn-action" onclick="downloadReport('${report.id}')">Download</button>
        </div>
    `).join('');
}

// Mock Data Functions
function getMockPrograms() {
    return [
        {
            code: 'CS',
            name: 'Computer Science',
            description: 'Bachelor of Science in Computer Science',
            departments: 'Computer Science'
        },
        {
            code: 'MATH',
            name: 'Mathematics',
            description: 'Bachelor of Science in Mathematics',
            departments: 'Mathematics'
        }
    ];
}

function getMockSemesters() {
    return [
        {
            code: 'F2024',
            name: 'Fall 2024',
            startDate: '2024-08-26',
            endDate: '2024-12-20',
            status: 'Active',
            isCurrent: true
        },
        {
            code: 'S2025',
            name: 'Spring 2025',
            startDate: '2025-01-21',
            endDate: '2025-05-16',
            status: 'Upcoming',
            isCurrent: false
        }
    ];
}

function getMockCourses() {
    return [
        {
            code: 'CS101',
            title: 'Introduction to Computer Science',
            credits: 3,
            department: 'Computer Science',
            instructor: 'Dr. Smith',
            enrolled: 28,
            capacity: 30
        },
        {
            code: 'MATH201',
            title: 'Calculus II',
            credits: 4,
            department: 'Mathematics',
            instructor: 'Prof. Johnson',
            enrolled: 25,
            capacity: 25
        }
    ];
}

function getMockStudents() {
    return [
        {
            id: 'S001',
            name: 'Alice Johnson',
            email: 'alice@example.com',
            program: 'Computer Science',
            status: 'Active',
            gpa: '3.8'
        },
        {
            id: 'S002',
            name: 'Bob Smith',
            email: 'bob@example.com',
            program: 'Mathematics',
            status: 'Active',
            gpa: '3.6'
        }
    ];
}

function getMockInstructors() {
    return [
        {
            id: 'I001',
            name: 'Dr. Smith',
            email: 'smith@example.com',
            department: 'Computer Science',
            courses: 3,
            status: 'Active'
        },
        {
            id: 'I002',
            name: 'Prof. Johnson',
            email: 'johnson@example.com',
            department: 'Mathematics',
            courses: 2,
            status: 'Active'
        }
    ];
}

function getMockReports() {
    return [
        {
            id: 'R001',
            title: 'Enrollment Report - Fall 2024',
            description: 'Student enrollment statistics for Fall 2024 semester',
            generatedDate: '2024-09-01'
        },
        {
            id: 'R002',
            title: 'Grade Distribution Report',
            description: 'Grade distribution across all courses',
            generatedDate: '2024-08-30'
        }
    ];
}

function getMockDashboardStats() {
    return {
        totalStudents: 1250,
        totalInstructors: 45,
        totalCourses: 78,
        activePrograms: 12
    };
}

// Utility Functions
function getProgramByCode(code) {
    const programs = getMockPrograms();
    return programs.find(p => p.code === code);
}

function getSemesterByCode(code) {
    const semesters = getMockSemesters();
    return semesters.find(s => s.code === code);
}

function getCourseByCode(code) {
    const courses = getMockCourses();
    return courses.find(c => c.code === code);
}

function downloadReport(reportId) {
    showMessage('Downloading report...', 'info');
    setTimeout(() => {
        showMessage('Report downloaded successfully!', 'success');
    }, 2000);
}

// Initialize admin-specific functionality
document.addEventListener('DOMContentLoaded', function() {
    // Load data based on current page
    const currentPage = window.location.pathname.split('/').pop();

    switch(currentPage) {
        case 'dashboard.html':
            loadDashboardStats();
            break;
        case 'programs.html':
            loadPrograms();
            break;
        case 'semester.html':
            loadSemesters();
            break;
        case 'courses.html':
            loadCourses();
            break;
        case 'student-info.html':
            loadStudents();
            break;
        case 'instructor-info.html':
            loadInstructors();
            break;
        case 'reports.html':
            loadReports();
            break;
    }
});
