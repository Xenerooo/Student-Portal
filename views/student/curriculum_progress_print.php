<?php
/**
 * Print-ready curriculum progress view.
 * Data provided by StudentController:
 * - $student
 * - $curriculumProgress
 * - $summary
 * - $generatedAt
 * - $returnTo
 */

$studentName = htmlspecialchars($student['student_name'] ?? 'Student');
$studentNumber = htmlspecialchars($student['student_number'] ?? 'N/A');
$courseName = htmlspecialchars($student['course_name'] ?? 'N/A');
$photoData = !empty($student['img']) ? 'data:image/jpeg;base64,' . base64_encode($student['img']) : '/assets/images/person.svg';

function curriculum_progress_badge($grade, $remarks, $status = '') {
    $grade = $grade !== null && $grade !== '' ? (float)$grade : null;
    $remarks = (string)$remarks;
    $status = (string)$status;

    if ($remarks === 'Passed' || ($grade !== null && $grade <= 3.00)) {
        return ['label' => number_format($grade, 2), 'class' => 'bg-success'];
    }
    if ($remarks === 'Incomplete' || $status === 'incomplete') {
        return ['label' => 'INC', 'class' => 'bg-warning text-dark'];
    }
    if ($remarks === 'Failed' || $grade === 5.00 || $status === 'failed') {
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
    <title><?= htmlspecialchars($pageTitle ?? 'Curriculum Progress') ?></title>
    <link href="<?= APP_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #c9d3e1;
            --accent: #1f77c9;
            --accent-dark: #0f4c81;
            --gold: #d8b64b;
            --green: #2f9e44;
            --surface: #ffffff;
            --bg: #f4f7fb;
        }

        @page {
            margin: 0.55in;
        }

        html, body {
            background: var(--bg);
            color: var(--ink);
        }

        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        }

        .shell {
            max-width: 980px;
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

        .card-shell {
            background: var(--surface);
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-top: 4px solid var(--gold);
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent) 60%, #2b9fd9 100%);
            color: #fff;
            padding: 20px 24px;
        }

        .header small {
            color: rgba(255, 255, 255, 0.82);
        }

        .profile-photo {
            width: 96px;
            height: 96px;
            border-radius: 18px;
            object-fit: cover;
            background: rgba(255, 255, 255, 0.18);
            border: 2px solid rgba(255, 255, 255, 0.4);
        }

        .section {
            padding: 20px 24px;
        }

        .section-title {
            font-size: 0.9rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--accent-dark);
            margin-bottom: 12px;
            font-weight: 700;
        }

        .metric {
            background: #f9fbfe;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px 16px;
            height: 100%;
        }

        .metric-label {
            color: var(--muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }

        .metric-value {
            font-size: 1.2rem;
            font-weight: 800;
            margin-top: 8px;
        }

        .term-band {
            background: linear-gradient(90deg, rgba(31, 119, 201, 0.10), rgba(216, 182, 75, 0.10));
            border-left: 4px solid var(--gold);
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .curriculum-block {
            break-inside: avoid;
            page-break-inside: avoid;
            margin-bottom: 18px;
        }

        .table thead th {
            background: #eef5fb;
            color: var(--accent-dark);
            border-bottom: 1px solid var(--line);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .table td, .table th {
            border-color: var(--line);
        }

        .table {
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        .table > :not(caption) > * > * {
            border-bottom-width: 1px;
        }

        .page-break {
            page-break-before: always;
            break-before: page;
        }

        @media print {
            html, body {
                background: #fff !important;
            }

            .shell {
                max-width: none;
                padding: 0;
            }

            .top-actions,
            .hide-on-print {
                display: none !important;
            }

            .card-shell {
                box-shadow: none;
                border-radius: 0;
                overflow: visible;
            }

            .header {
                border-radius: 0;
            }

            .section {
                padding: 16px 18px;
            }

            .table {
                margin-bottom: 0;
                font-size: 0.78rem;
            }

            .shell {
                max-width: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="top-actions hide-on-print">
            <div>
                <h4 class="mb-1">Curriculum Progress</h4>
                <div class="text-muted small">Print-friendly curriculum roadmap for PDF export.</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
                <button type="button" class="btn btn-outline-secondary" onclick="goBack()">Close</button>
            </div>
        </div>

        <div class="print-letterhead">
            <img class="logo" src="<?= APP_URL ?>/assets/images/icon.png" alt="School logo">
            <div class="letterhead-text">
                <div class="letterhead-top">Republic of the Philippines</div>
                <div class="letterhead-name">COLEGIO DE PORTA VAGA</div>
                <div class="letterhead-sub">YP-GEY Bldg., E. Aguinaldo Highway, Bayan Luma 7, Imus City, Cavite</div>
                <div class="letterhead-sub">(046) 4710496 / 09202247246</div>
            </div>
        </div>

        <div class="card-shell">
            <div class="header">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        <img class="profile-photo" src="<?= htmlspecialchars($photoData) ?>" alt="Student photo">
                    </div>
                    <div class="col">
                        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                            <div>
                                <small class="d-block mb-2">Curriculum Progress Report</small>
                                <h2 class="h3 mb-2"><?= $studentName ?></h2>
                                <div class="mb-1">Student Number: <strong><?= $studentNumber ?></strong></div>
                                <div>Course: <strong><?= $courseName ?></strong></div>
                            </div>
                            <div class="text-md-end">
                                <small class="d-block mb-2">Generated</small>
                                <div class="fw-bold"><?= htmlspecialchars($generatedAt ?? '') ?></div>
                                <div class="small mt-1">Full curriculum road map</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section border-bottom">
                <div class="section-title">Summary</div>
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Curriculum Groups</div>
                            <div class="metric-value"><?= (int)($summary['year_groups'] ?? 0) ?> years</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Semester Blocks</div>
                            <div class="metric-value"><?= (int)($summary['semester_groups'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Subjects</div>
                            <div class="metric-value"><?= (int)($summary['subjects_total'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Passed Units</div>
                            <div class="metric-value"><?= (int)($summary['passed_units'] ?? 0) ?> / <?= (int)($summary['total_units'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mt-0">
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Passed</div>
                            <div class="metric-value"><?= (int)($summary['subjects_passed'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Pending</div>
                            <div class="metric-value"><?= (int)($summary['subjects_pending'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Incomplete</div>
                            <div class="metric-value"><?= (int)($summary['subjects_incomplete'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="metric">
                            <div class="metric-label">Failed</div>
                            <div class="metric-value"><?= (int)($summary['subjects_failed'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Curriculum Road Map</div>
                <?php if (!empty($curriculumProgress)): ?>
                    <?php foreach ($curriculumProgress as $year => $semesters): ?>
                        <?php foreach ($semesters as $sem => $subjects): ?>
                            <div class="curriculum-block">
                                <div class="term-band">
                                    <?= htmlspecialchars($year) ?> Year | Semester <?= htmlspecialchars($sem) ?>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead>
                                            <tr>
                                                <th style="width: 14%;">Code</th>
                                                <th>Subject</th>
                                                <th class="text-center" style="width: 8%;">Units</th>
                                                <th class="text-center" style="width: 12%;">Average</th>
                                                <th class="text-center" style="width: 12%;">Grade</th>
                                                <th class="text-center" style="width: 12%;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subjects as $row): ?>
                                                <?php $badge = curriculum_progress_badge($row['grade'] ?? null, $row['remarks'] ?? '', $row['enrollment_status'] ?? ''); ?>
                                                <tr>
                                                    <td><code class="text-primary fw-bold"><?= htmlspecialchars($row['subject_code'] ?? '') ?></code></td>
                                                    <td>
                                                        <div class="fw-semibold"><?= htmlspecialchars($row['subject_name'] ?? '') ?></div>
                                                        <?php if (!empty($row['retake_count']) && (int)$row['retake_count'] > 0): ?>
                                                            <span class="badge bg-warning text-dark mt-1">Retake</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center"><?= htmlspecialchars((string)($row['units'] ?? '')) ?></td>
                                                    <td class="text-center"><?= isset($row['average_grade']) && $row['average_grade'] !== null ? number_format((float)$row['average_grade'], 1) : '—' ?></td>
                                                    <td class="text-center"><span class="badge <?= htmlspecialchars($badge['class']) ?>"><?= htmlspecialchars($badge['label']) ?></span></td>
                                                    <td class="text-center"><?= htmlspecialchars(ucfirst((string)($row['enrollment_status'] ?? ''))) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-light border mb-0">No curriculum progress found.</div>
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
