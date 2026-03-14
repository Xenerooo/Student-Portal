<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<nav class="navbar navbar-expand-lg sticky-top navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php?page=admin_dashboard">
            <img src="/Student-Portal/assets/images/icon.png" alt="School Logo" height="32">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="#" data-content="get_student_list">Student List</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-content="get_manage_subjects">Manage Subjects</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-content="get_manage_curriculum">Manage Curriculum</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-content="get_create_student_form">Create Student</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-content="none">VIP Student</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="btn btn-outline-light" href="/Student-Portal/logout">Logout (<?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?>)</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div id="main-content-area">
        <div class="d-flex justify-content-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
    const defaultContentAction = 'get_student_list';
    const defaultLink = document.querySelector(`[data-content="${defaultContentAction}"]`);

    document.addEventListener('DOMContentLoaded', function() {
        const contentArea = document.getElementById('main-content-area');
        const navLinks = document.querySelectorAll('.nav-link[data-content]');
        const ajaxActionMap = {
            'get_student_list': 'students',
            'get_manage_subjects': 'subjects',
            'get_manage_curriculum': 'curriculum',
            'get_create_student_form': 'students/create'
        };

        const apiBasePath = '/Student-Portal/admin/api/';

        async function loadContent(action, targetLink) {
            contentArea.innerHTML = `
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            // Map the old action names to the new RESTful endpoints
            const endpoint = ajaxActionMap[action] || action;
            const url = `${apiBasePath}${endpoint}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok. Status: ' + response.status);
                    }
                    return response.text();
                })
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.body;
                    const scripts = doc.querySelectorAll('script');

                    contentArea.innerHTML = newContent.innerHTML;

                    const existingExternalScripts = new Set(
                        Array.from(document.querySelectorAll('script[src]')).map(s => s.src)
                    );
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        if (script.src) {
                            if (existingExternalScripts.has(script.src)) {
                                return;
                            }
                            newScript.src = script.src;
                            existingExternalScripts.add(script.src);
                        } else {
                            newScript.textContent = `(function(){\n${script.textContent}\n})();`;
                        }
                        document.body.appendChild(newScript);
                        newScript.remove();
                    });
                    
                    navLinks.forEach(link => link.classList.remove('active'));
                    if (targetLink) {
                        targetLink.classList.add('active');
                    }
                    
                    history.pushState(null, '', `/Student-Portal/admin/dashboard?view=${action}`);
                })
                .catch(error => {
                    contentArea.innerHTML = `<div class='alert alert-danger'>Error loading content: ${error.message}</div>`;
                    console.error('AJAX Error:', error);
                });
        }

        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault(); 
                const actionName = this.getAttribute('data-content'); 
                loadContent(actionName, this);
            });
        });

        if(defaultLink) {
             defaultLink.classList.add('active');
        }
        loadContent(defaultContentAction, defaultLink);
    });
</script>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
