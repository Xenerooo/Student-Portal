<?php
// manage_curriculum_content.php

// Basic Authorization Check (Essential!)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("<div class='alert alert-danger'>Access Denied. Please log in as an administrator.</div>");
}
?>

<h1 class="mb-4">Manage Curriculum and Programs</h1>

<div class="alert alert-warning">
    <p>This is the placeholder for the **Curriculum Management** module.</p>
    <p>Here, the admin will be able to:
    <ul>
        <li>Create and edit degree programs (e.g., BS Computer Science).</li>
        <li>Define which subjects belong to a specific program.</li>
        <li>Arrange subjects by year/semester within a program.</li>
    </ul>
</div>

<div id="video-container" class="video-container">
    <div class="d-flex justify-content-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
    </div>
</div>

<script>
    function set_video() {
        var ytlink = '<iframe src="https://www.youtube.com/embed/L8XbI9aJOXk?si=urPNZoCPbDP4psp_&autoplay=1&mute=0&playsinline=1&controls=0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';
        var container = document.getElementById('video-container');
        if (container) {
            container.innerHTML = ytlink;
    }};
    // (function() {
    //     var ytlink = '<iframe width="560" height="315" src="https://www.youtube.com/embed/L8XbI9aJOXk?si=urPNZoCPbDP4psp_&autoplay=1&mute=0&playsinline=1&controls=0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';
    //     var container = document.getElementById('video-container');
    //     if (container) {
    //         container.innerHTML = ytlink;
    //     }
    // })();
    set_video();
    console.log(document.body);
</script>