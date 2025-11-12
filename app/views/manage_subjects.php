<?php
// manage_subjects_content.php

// Basic Authorization Check (Essential!)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("<div class='alert alert-danger'>Access Denied. Please log in as an administrator.</div>");
}
?>

<h1 class="mb-4">Manage Subjects and Prerequisites</h1>

<div class="alert alert-info">
    <p>This is the placeholder for the **Subject Management** module.</p>
    <p>Here, the admin will be able to:
    <ul>
        <li>Add new subject codes (e.g., CS101, MATH102).</li>
        <li>Edit subject names and credit units.</li>
        <li>**Define prerequisites** for each subject (e.g., CS102 requires CS101).</li>
    </ul>
</div>