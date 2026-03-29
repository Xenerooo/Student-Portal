<?php
/**
 * Print-ready academic record view.
 * Data provided by StudentController:
 * - $student
 * - $selectedSchoolYear
 * - $selectedSemester
 * - $termGrades
 * - $termSummary
 * - $overallSummary
 * - $groupedHistory
 * - $generatedAt
 */

$studentName = htmlspecialchars($student['student_name'] ?? 'Student');
$studentNumber = htmlspecialchars($student['student_number'] ?? 'N/A');
$courseName = htmlspecialchars($student['course_name'] ?? 'N/A');
$photoData = !empty($student['img']) ? 'data:image/jpeg;base64,' . base64_encode($student['img']) : '/assets/images/person.svg';
$returnTo = trim($_GET['return_to'] ?? '') ?: '/student/dashboard?view=get_student_grades';

function academic_record_grade_label($grade, $remarks, $status = '') {
    $grade = $grade !== null && $grade !== '' ? (float)$grade : null;
    $remarks = (string)$remarks;
    $status = (string)$status;

    if ($remarks === 'Passed' || ($grade !== null && $grade <= 3.00)) {
        return ['label' => number_format($grade, 2), 'class' => 'bg-success'];
    }

    if ($remarks === 'Incomplete' || $status === 'incomplete') {
        return ['label' => 'INC', 'class' => 'bg-warning text-dark'];
    }

    if ($remarks === 'Failed' || $grade === 5.00) {
        return ['label' => '5.00', 'class' => 'bg-danger'];
    }

    if ($status === 'dropped') {
        return ['label' => 'Dropped', 'class' => 'bg-secondary'];
    }

    return ['label' => 'Pending', 'class' => 'bg-light text-dark'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Academic Record') ?></title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --record-ink: #111827;
            --record-muted: #5b6472;
            --record-line: #c9d3e1;
            --record-accent: #1f77c9;
            --record-accent-2: #0f4c81;
            --record-gold: #d8b64b;
            --record-green: #2f9e44;
            --record-surface: #ffffff;
            --record-bg: #eef2f7;
        }

        html, body {
            background: var(--record-bg);
            color: var(--record-ink);
        }

        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        }

        .print-shell {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px;
        }

        .print-letterhead {
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            padding: 10px 18px 14px;
            margin-bottom: 16px;
            text-align: center;
        }

        .print-letterhead .logo {
            width: 84px;
            height: 84px;
            object-fit: contain;
            flex: 0 0 auto;
        }

        .print-letterhead .letterhead-text {
            line-height: 1.15;
        }

        .print-letterhead .letterhead-top {
            font-family: "Times New Roman", Times, serif;
            font-size: 0.95rem;
            color: #666;
            letter-spacing: 0.02em;
        }

        .print-letterhead .letterhead-name {
            font-family: "Times New Roman", Times, serif;
            font-size: 1.45rem;
            font-weight: 700;
            color: #5b5b5b;
            letter-spacing: 0.03em;
        }

        .print-letterhead .letterhead-sub {
            font-family: "Times New Roman", Times, serif;
            font-size: 0.95rem;
            color: #666;
        }

        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .record-card {
            background: var(--record-surface);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-top: 4px solid var(--record-gold);
            border-radius: 14px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .record-header {
            background: linear-gradient(135deg, var(--record-accent-2) 0%, var(--record-accent) 60%, #2b9fd9 100%);
            color: #fff;
            padding: 20px 24px;
        }

        .record-header small {
            color: rgba(255, 255, 255, 0.82);
        }

        .profile-photo {
            width: 96px;
            height: 96px;
            border-radius: 14px;
            object-fit: cover;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.38);
        }

        .section-block {
            padding: 20px 24px;
        }

        .section-title {
            font-size: 0.9rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--record-accent-2);
            margin-bottom: 14px;
            font-weight: 700;
        }

        .metric {
            background: #f9fbfe;
            border: 1px solid var(--record-line);
            border-radius: 12px;
            padding: 14px 16px;
            height: 100%;
        }

        .metric-label {
            color: var(--record-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }

        .metric-value {
            font-size: 1.35rem;
            font-weight: 800;
            margin-top: 8px;
        }

        .subtle-note {
            color: var(--record-muted);
            font-size: 0.85rem;
        }

        .table thead th {
            background: #eef5fb;
            color: var(--record-accent-2);
            border-bottom: 1.5px solid #b9c3d0;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .table td, .table th {
            border-color: #b9c3d0;
        }

        .table {
            border: 1.5px solid #b9c3d0;
            border-radius: 10px;
            overflow: hidden;
        }

        .table > :not(caption) > * > * {
            border-bottom-width: 1.5px;
        }

        .term-band {
            background: linear-gradient(90deg, rgba(31, 119, 201, 0.10), rgba(216, 182, 75, 0.10));
            border-left: 4px solid var(--record-gold);
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 10px;
            font-weight: 700;
            color: #1f2937;
        }

        .page-break {
            page-break-before: always;
            break-before: page;
        }

        .hide-on-print {
            display: inline-flex;
        }

        @media print {
            html, body {
                background: #fff !important;
            }

            .print-shell {
                max-width: none;
                padding: 0;
            }

            .hide-on-print {
                display: none !important;
            }

            .record-card {
                box-shadow: none;
                border-radius: 0;
                overflow: visible;
            }

            .record-header {
                border-radius: 0;
            }

            .section-block {
                padding: 18px 20px;
            }

            .table {
                margin-bottom: 0;
            }

            a {
                text-decoration: none;
                color: inherit;
            }
        }
    </style>
</head>
<body>
    <div class="print-shell">
        <div class="top-actions hide-on-print">
            <div>
                <h4 class="mb-1">Academic Record</h4>
                <div class="subtle-note">Print-friendly version ready for PDF export.</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
                <button type="button" class="btn btn-outline-secondary" onclick="window.close()">Close</button>
            </div>
        </div>

            <div class="print-letterhead">
                <img class="logo" src="/assets/images/icon.png" alt="School logo">
                <div class="letterhead-text">
                    <div class="letterhead-top">Republic of the Philippines</div>
                    <div class="letterhead-name">COLEGIO DE PORTA VAGA</div>
                    <div class="letterhead-sub">YP-GEY Bldg., E. Aguinaldo Highway, Bayan Luma 7, Imus City, Cavite</div>
                <div class="letterhead-sub">(046) 4710496 / 09202247246</div>
            </div>
        </div>

        <div class="record-card">
            <div class="record-header">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        <img class="profile-photo" src="<?= htmlspecialchars($photoData) ?>" alt="Student photo">
                    </div>
                    <div class="col">
                        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                            <div>
                                <small class="d-block mb-2">Student Academic Record</small>
                                <h2 class="h3 mb-2"><?= $studentName ?></h2>
                                <div class="mb-1">Student Number: <strong><?= $studentNumber ?></strong></div>
                                <div>Course: <strong><?= $courseName ?></strong></div>
                            </div>
                            <div class="text-md-end">
                                <small class="d-block mb-2">Generated</small>
                                <div class="fw-bold"><?= htmlspecialchars($generatedAt ?? '') ?></div>
                                <div class="small mt-1">Term: <?= htmlspecialchars($selectedSchoolYear ?? '') ?> | <?= htmlspecialchars($selectedSemester ?? '') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-block border-bottom">
                <div class="section-title">Summary</div>
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Overall GWA</div>
                            <div class="metric-value"><?= $overallSummary['gwa'] !== null ? number_format($overallSummary['gwa'], 2) : '--' ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Units Earned</div>
                            <div class="metric-value"><?= (int)($overallSummary['total_units'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Subjects Passed</div>
                            <div class="metric-value"><?= (int)($overallSummary['passed_count'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Academic Standing</div>
                            <div class="metric-value" style="font-size: 1.1rem;">
                                <?= htmlspecialchars($overallSummary['standing'] ?? 'Good Standing') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-block border-bottom">
                <div class="section-title">Selected Term</div>
                <div class="term-band">
                    <?= htmlspecialchars($selectedSchoolYear ?? '') ?> | <?= htmlspecialchars($selectedSemester ?? '') ?>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Term GWA</div>
                            <div class="metric-value"><?= $termSummary['gwa'] !== null ? number_format($termSummary['gwa'], 2) : '--' ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Units</div>
                            <div class="metric-value"><?= (int)($termSummary['total_units'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Graded</div>
                            <div class="metric-value"><?= (int)($termSummary['graded_count'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Status</div>
                            <div class="metric-value" style="font-size: 1.1rem;">
                                <?= !empty($termSummary['has_incomplete']) ? 'Has INC' : 'Active' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($termGrades)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 14%;">Code</th>
                                    <th>Subject</th>
                                    <th class="text-center" style="width: 10%;">Units</th>
                                    <th class="text-center" style="width: 14%;">Average</th>
                                    <th class="text-center" style="width: 14%;">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($termGrades as $subject): ?>
                                    <?php $gradeCell = academic_record_grade_label($subject['grade'] ?? null, $subject['remarks'] ?? '', $subject['status'] ?? ''); ?>
                                    <tr>
                                        <td><code class="text-primary fw-bold"><?= htmlspecialchars($subject['subject_code'] ?? '') ?></code></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($subject['subject_name'] ?? '') ?></div>
                                            <?php if (!empty($subject['is_retake'])): ?>
                                                <span class="badge bg-warning text-dark mt-1">Retake</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars((string)($subject['units'] ?? '')) ?></td>
                                        <td class="text-center"><?= isset($subject['average_grade']) && $subject['average_grade'] !== null ? number_format((float)$subject['average_grade'], 1) : '—' ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= htmlspecialchars($gradeCell['class']) ?>">
                                                <?= htmlspecialchars($gradeCell['label']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border mb-0">No enrollment records found for this term.</div>
                <?php endif; ?>
            </div>

            <div class="section-block">
                <div class="section-title">Full Scholastic History</div>
                <?php if (!empty($groupedHistory)): ?>
                    <?php foreach ($groupedHistory as $index => $group): ?>
                        <div class="term-band">
                            <?= htmlspecialchars($group['school_year'] ?? '') ?> | <?= htmlspecialchars($group['semester'] ?? '') ?>
                        </div>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 14%;">Code</th>
                                        <th>Subject</th>
                                        <th class="text-center" style="width: 10%;">Units</th>
                                        <th class="text-center" style="width: 14%;">Grade</th>
                                        <th class="text-center" style="width: 14%;">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group['items'] as $row): ?>
                                        <?php $gradeCell = academic_record_grade_label($row['grade'] ?? null, $row['remarks'] ?? '', $row['status'] ?? ''); ?>
                                        <tr>
                                            <td><code class="text-primary fw-bold"><?= htmlspecialchars($row['subject_code'] ?? '') ?></code></td>
                                            <td><?= htmlspecialchars($row['subject_name'] ?? '') ?></td>
                                            <td class="text-center"><?= htmlspecialchars((string)($row['units'] ?? '')) ?></td>
                                            <td class="text-center"><?= htmlspecialchars($gradeCell['label']) ?></td>
                                            <td class="text-center"><?= htmlspecialchars($row['remarks'] ?? '--') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($index < count($groupedHistory) - 1): ?>
                            <div class="page-break"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-light border mb-0">No scholastic history found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const returnUrl = <?= json_encode($returnTo, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
            let hasPrinted = false;

            function goBack() {
                window.location.replace(returnUrl);
            }

            window.addEventListener('afterprint', goBack);

            window.addEventListener('load', function () {
                if (hasPrinted) return;
                hasPrinted = true;
                setTimeout(function () {
                    window.print();
                }, 300);
            });
        })();
    </script>
</body>
</html>
