<div >
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-4" >Dashboard Overview</h1>
    </div>

    <!-- Metrics Cards -->
    <div class="overview-summary-grid">
        <div class="overview-card card-students">
            <div class="card-title">Total Students</div>
            <div class="card-value"><?= htmlspecialchars($totalStudents ?? 0) ?></div>
        </div>
        <div class="overview-card card-enrolled">
            <div class="card-title">Total Enrollments</div>
            <div class="card-value"><?= htmlspecialchars($totalEnrolled ?? 0) ?></div>
        </div>
        <div class="overview-card card-subjects">
            <div class="card-title">Active Subjects</div>
            <div class="card-value"><?= htmlspecialchars($totalSubjects ?? 0) ?></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <h3 class="overview-section-title">Quick Actions</h3>
    <div class="quick-actions-grid">
        <a href="#" class="quick-action-btn" onclick="document.querySelector('[data-content=\'get_create_student_form\']').click(); return false;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            Enroll New Student
        </a>
        <a href="#" class="quick-action-btn" onclick="document.querySelector('[data-content=\'get_manage_subjects\']').click(); return false;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
            Manage Subjects
        </a>
        <a href="#" class="quick-action-btn" onclick="document.querySelector('[data-content=\'get_manage_curriculum\']').click(); return false;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            Curriculum
        </a>
        <a href="#" class="quick-action-btn" onclick="document.querySelector('[data-content=\'get_student_list\']').click(); return false;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            View All Students
        </a>
    </div>

    <!-- Recent Activity -->
    <h3 class="overview-section-title">Recently Added Students</h3>
    <div class="recent-students-card table-responsive">
        <table class="table align-middle table-hover">
            <thead class="table-light">
                <tr>
                    <th>Student Name</th>
                    <th>Student Number</th>
                    <th>Course</th>
                    <th>Date Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentStudents)): ?>
                    <?php foreach ($recentStudents as $student): ?>
                        <tr>
                            <td class="fw-medium text-dark"><?= htmlspecialchars($student['student_name']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($student['student_number']) ?></td>
                            <td><?= htmlspecialchars($student['course_name'] ?? 'N/A') ?></td>
                            <td class="text-muted"><?= date('M d, Y', strtotime($student['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center text-muted">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
