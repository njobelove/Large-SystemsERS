// Student-specific JavaScript functions

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        // Clear any local storage data
        localStorage.removeItem('studentName');
        localStorage.removeItem('studentId');

        // Clear session and redirect
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
            window.location.href = '../index.php';
        });
    }
}

// Load student information
function loadStudentInfo() {
    fetch('../api/get-student-info.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update user info in header
                const userInfo = document.querySelector('.user-info');
                if (userInfo) {
                    const img = userInfo.querySelector('img');
                    const span = userInfo.querySelector('span');
                    if (img) img.src = data.student.avatar || '../images/user-avatar.png';
                    if (span) span.textContent = data.student.name;
                }

                // Store in localStorage for transcript
                localStorage.setItem('studentName', data.student.name);
                localStorage.setItem('studentId', data.student.id);
            }
        })
        .catch(error => {
            console.error('Error loading student info:', error);
        });
}

// Course registration functions
function registerForCourse(courseId) {
    if (confirm('Are you sure you want to register for this course?')) {
        fetch('../api/register-course.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ course_id: courseId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Successfully registered for the course!');
                location.reload();
            } else {
                alert('Registration failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Registration error:', error);
            alert('An error occurred during registration. Please try again.');
        });
    }
}

// View course details
function viewCourseDetails(courseId) {
    window.location.href = `course-details.php?course_id=${courseId}`;
}

// Submit assignment
function submitAssignment(assignmentId) {
    const fileInput = document.getElementById(`assignment-file-${assignmentId}`);
    const formData = new FormData();

    if (fileInput && fileInput.files[0]) {
        formData.append('assignment_id', assignmentId);
        formData.append('file', fileInput.files[0]);

        fetch('../api/submit-assignment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Assignment submitted successfully!');
                location.reload();
            } else {
                alert('Submission failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Submission error:', error);
            alert('An error occurred during submission. Please try again.');
        });
    } else {
        alert('Please select a file to submit.');
    }
}

// Mark attendance
function markAttendance(courseId, status) {
    fetch('../api/mark-attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            course_id: courseId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Attendance marked successfully!');
        } else {
            alert('Failed to mark attendance: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Attendance marking error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Load dashboard data
function loadDashboardData() {
    // Load enrolled courses
    fetch('../api/get-student-courses.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCourses(data.courses);
            }
        })
        .catch(error => {
            console.error('Error loading courses:', error);
        });

    // Load upcoming assignments
    fetch('../api/get-student-assignments.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAssignments(data.assignments);
            }
        })
        .catch(error => {
            console.error('Error loading assignments:', error);
        });

    // Load attendance summary
    fetch('../api/get-student-attendance.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAttendance(data.attendance);
            }
        })
        .catch(error => {
            console.error('Error loading attendance:', error);
        });
}

// Display functions
function displayCourses(courses) {
    const container = document.getElementById('coursesContainer');
    if (!container) return;

    container.innerHTML = '';
    courses.forEach(course => {
        const courseDiv = document.createElement('div');
        courseDiv.className = 'course-card';
        courseDiv.innerHTML = `
            <h3>${course.code} - ${course.title}</h3>
            <p><strong>Instructor:</strong> ${course.instructor_name}</p>
            <p><strong>Credits:</strong> ${course.credits}</p>
            <p><strong>Schedule:</strong> ${course.schedule || 'TBD'}</p>
            <button onclick="viewCourseDetails(${course.id})" class="btn">View Details</button>
        `;
        container.appendChild(courseDiv);
    });
}

function displayAssignments(assignments) {
    const container = document.getElementById('assignmentsContainer');
    if (!container) return;

    container.innerHTML = '';
    assignments.forEach(assignment => {
        const assignmentDiv = document.createElement('div');
        assignmentDiv.className = 'assignment-card';
        assignmentDiv.innerHTML = `
            <h4>${assignment.title}</h4>
            <p><strong>Course:</strong> ${assignment.course_code}</p>
            <p><strong>Due Date:</strong> ${new Date(assignment.due_date).toLocaleDateString()}</p>
            <p><strong>Description:</strong> ${assignment.description}</p>
            ${assignment.submitted ? '<p class="submitted">✓ Submitted</p>' :
              `<input type="file" id="assignment-file-${assignment.id}" accept=".pdf,.doc,.docx,.txt">
               <button onclick="submitAssignment(${assignment.id})" class="btn">Submit</button>`}
        `;
        container.appendChild(assignmentDiv);
    });
}

function displayAttendance(attendance) {
    const container = document.getElementById('attendanceContainer');
    if (!container) return;

    container.innerHTML = '';
    attendance.forEach(record => {
        const attendanceDiv = document.createElement('div');
        attendanceDiv.className = 'attendance-card';
        attendanceDiv.innerHTML = `
            <h4>${record.course_code}</h4>
            <p><strong>Date:</strong> ${new Date(record.date).toLocaleDateString()}</p>
            <p><strong>Status:</strong> <span class="status-${record.status.toLowerCase()}">${record.status}</span></p>
        `;
        container.appendChild(attendanceDiv);
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Load student info
    loadStudentInfo();

    // Load dashboard data if on dashboard page
    if (document.getElementById('coursesContainer')) {
        loadDashboardData();
    }
});
