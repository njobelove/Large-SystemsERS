# Assignment Workflow Implementation Plan

## Overview
This document outlines the plan to implement a full assignment workflow in the academic system, covering instructor assignmejm*nt creation, student assignment access and submission, instructor grading, and student grade viewing.

## Tasks

### 1. Database Schema
- Review current schema for assignments, submissions, and grades.
- Add tables/columns if necessary to support:
  - Assignment submissions by students (file path, submission date, status).
  - Grades for each submission (score, feedback).

### 2. Instructor Side
- Enhance `instructor/create-assignment.php` for assignment creation (already mostly done).
- Implement `instructor/assignment-submissions.php` to:
  - List student submissions per assignment.
  - Allow download of submitted files.
  - Provide grading interface (score, comments).
  - Save grades to database.

### 3. Student Side
- Implement `student/assignments.php` to:
  - List assignments for enrolled courses.
  - Provide download links for assignment files.
- Implement `student/submit-assignment.php` to:
  - Allow students to upload assignment answers.
  - Validate file types and sizes.
  - Save submission info to database.
- Implement `student/view-grades.php` to:
  - Display grades and feedback for assignments.

### 4. Backend Functions
- Update `functions.php` with helper functions for:
  - Fetching assignments and submissions.
  - Saving submissions and grades.

### 5. Frontend & UX
- Ensure consistent UI/UX for assignment listing, submission, and grading.
- Provide notifications or status indicators where appropriate.

### 6. Testing
- Perform end-to-end testing of the workflow:
  - Instructor creates assignment.
  - Student views and downloads assignment.
  - Student submits assignment.
  - Instructor views submissions and grades.
  - Student views grades.

## Next Steps
- Begin with database schema review and updates.
- Proceed with student assignment listing and submission features.
- Follow with instructor submission management and grading.
- Complete with student grade viewing and testing.
