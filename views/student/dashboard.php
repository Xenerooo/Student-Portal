<?php require ROOT_PATH . '/views/layouts/header.php'; ?>

<div class="app-wrapper">
    <!-- Sidebar -->
    <aside class="app-sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?= APP_URL ?>/assets/images/icon.png" alt="School Logo" height="32" style="border-radius: 4px;">
            <div class="sidebar-brand-text">
                Colegio de Porta Vaga <span>Student Portal</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= APP_URL ?>/student/dashboard?view=get_overview" class="sidebar-link active" data-content="get_overview">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span class="link-text">Dashboard</span>
            </a>
            <a href="<?= APP_URL ?>/student/dashboard?view=get_student_info" class="sidebar-link" data-content="get_student_info">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span class="link-text">Student Information</span>
            </a>
            <a href="<?= APP_URL ?>/student/dashboard?view=get_student_grades" class="sidebar-link" data-content="get_student_grades">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <span class="link-text">Grades</span>
            </a>
            
            <div style="flex-grow: 1;"></div>
            
            <a href="<?= APP_URL ?>/logout" class="sidebar-link text-danger" style="color: #fca5a5 !important;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span class="link-text">Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="app-main-container">
        <!-- Header -->
        <header class="app-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <div class="breadcrumb-title">
                    <span class="d-none d-sm-inline">Student Portal</span>
                    <span class="d-none d-sm-inline separator">></span>
                    <span id="header-title" style="color: #64748b; font-weight: normal; font-size: 0.95em;">Dashboard</span>
                </div>
            </div>
            <div class="header-right">
                <button class="btn-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span class="badge"></span>
                </button>
                <div class="user-profile">
                    <div class="user-avatar text-white bg-primary">
                        S
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrollable Content View -->
        <div class="app-content-scrollable">
            <div id="main-content-area">
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const contentArea = document.getElementById('main-content-area');
        const navLinks = document.querySelectorAll('.sidebar-link[data-content]');
        
        const ajaxActionMap = {
            'get_overview': 'overview',
            'get_student_info': 'info',
            'get_student_grades': 'grades'
        };

        const apiBasePath = '<?= APP_URL ?>/student/api/';

        // Global Fetch Interceptor to include CSRF token in all POST/PUT/DELETE requests
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken && options.method && ['POST', 'PUT', 'DELETE'].includes(options.method.toUpperCase())) {
                options.headers = options.headers || {};
                if (options.headers instanceof Headers) {
                    options.headers.set('X-CSRF-TOKEN', csrfToken);
                } else {
                    options.headers['X-CSRF-TOKEN'] = csrfToken;
                }
            }
            return originalFetch(url, options);
        };

        // Sidebar Toggle Logic
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        
        // Create backdrop dynamically
        const backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);
        
        const sidebarNav = document.querySelector('.sidebar-nav');
        const indicator = document.createElement('div');
        indicator.className = 'sidebar-active-indicator';
        sidebarNav.appendChild(indicator);

        function updateIndicator(targetLink) {
            if (targetLink && indicator) {
                indicator.style.transform = `translateY(${targetLink.offsetTop}px)`;
                indicator.style.height = `${targetLink.offsetHeight}px`;
            }
        }
        
        window.addEventListener('resize', () => {
            const active = document.querySelector('.sidebar-link.active');
            if (active) updateIndicator(active);
        });
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth <= 991.98) {
                    const isShown = sidebar.classList.toggle('mobile-shown');
                    sidebar.classList.remove('mobile-hidden');
                    if (isShown) {
                        backdrop.classList.add('show');
                    } else {
                        backdrop.classList.remove('show');
                    }
                } else {
                    sidebar.classList.toggle('collapsed');
                }
                setTimeout(() => {
                    const active = document.querySelector('.sidebar-link.active');
                    if (active) updateIndicator(active);
                }, 310);
            });
        }

        // Close sidebar when clicking backdrop
        backdrop.addEventListener('click', function() {
            sidebar.classList.remove('mobile-shown');
            sidebar.classList.add('mobile-hidden');
            backdrop.classList.remove('show');
        });

        async function loadContent(action, targetLink) {
            contentArea.innerHTML = `
                <div class="d-flex justify-content-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
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
                        updateIndicator(targetLink);
                        // Update Header Text
                        const headerTitle = document.getElementById('header-title');
                        if (headerTitle) {
                            // Extract text ignoring svg 
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = targetLink.innerHTML;
                            const svg = tempDiv.querySelector('svg');
                            if (svg) svg.remove();
                            headerTitle.textContent = tempDiv.textContent.trim();
                        }
                        
                        // Auto-hide sidebar on mobile after clicking a link
                        if (window.innerWidth <= 991.98 && sidebar.classList.contains('mobile-shown')) {
                            sidebar.classList.remove('mobile-shown');
                            sidebar.classList.add('mobile-hidden');
                            backdrop.classList.remove('show');
                        }
                    }
                    
                    history.pushState(null, '', `<?= APP_URL ?>/student/dashboard?view=${action}`);
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

        const defaultContentAction = 'get_overview';
        const defaultLink = document.querySelector(`[data-content="${defaultContentAction}"]`);
        
        if(defaultLink) {
            defaultLink.classList.add('active');
        }
        loadContent(defaultContentAction, defaultLink);
    });
</script>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
