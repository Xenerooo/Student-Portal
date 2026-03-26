<?php
// views/student/student_info.php
// Data provided by StudentController: $student
?>


<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12 col-md-7 mx-auto">
            <h2 class="mb-4 ">Student Information</h2>
            
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Personal Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>1x1 Image:</strong></div>
                        <div class="col-md-3 ">
                            <div class="atio ratio-1x1" ">
                                 <?= '<img style="width: 100%; height: 100%;" src="data:image/jpeg;base64,'.base64_encode($student['img'] ?? '') .'" alt="assets/images/person.svg" />' ?>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Student Number:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['student_number'] ?? 'N/A') ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Student Name:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['student_name']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Course/Program:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['course_name']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Birthday:</strong></div>
                        <div class="col-md-9">                                                                                                        
                            <?= htmlspecialchars(empty($student['birthday']) ? 'null' : date('F j, Y ', strtotime($student['birthday']))) ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Place of Birth:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['place_of_birth'] ?? 'N/A') ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Address:</strong></div>
                        <div class="col-md-9"><?= nl2br(htmlspecialchars($student['address'] ?? 'N/A')) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>School Last Attended:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['last_school_attended'] ?? 'N/A') ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Contact Number:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['contact_number'] ?? 'N/A') ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Email:</strong></div>
                        <div class="col-md-9"><?= htmlspecialchars($student['email'] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12 col-md-7 mx-auto mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Account Details</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Use the button below to update your password. On first login, this step is required before accessing the rest of the portal.</p>
                    <a href="/Student-Portal/student/change-password" class="btn btn-success w-100">Change Password</a>
                </div>
            </div>
        </div>
    </div>
</div>
