<div class="container-fluid p-4">
    <div class="row g-4">
        <!-- Welcome Card -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 16px; background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white;">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="fw-bold mb-2">Welcome back, <?= htmlspecialchars($student['student_name'] ?? 'Student') ?>!</h2>
                        <p class="opacity-75 mb-4">Stay updated with your school schedule and upcoming events. Your academic journey is our priority.</p>
                        <div class="d-flex gap-3">
                            <div class="stats-item">
                                <span class="d-block small opacity-50">Course</span>
                                <span class="fw-bold"><?= htmlspecialchars($student['course_name'] ?? 'N/A') ?></span>
                            </div>
                            <div class="stats-item border-start ps-3">
                                <span class="d-block small opacity-50">Student ID</span>
                                <span class="fw-bold"><?= htmlspecialchars($student['student_number'] ?? 'N/A') ?></span>
                            </div>
                            <div class="stats-item border-start ps-3">
                                <span class="d-block small opacity-50">Year Level</span>
                                <span class="fw-bold">Year <?= htmlspecialchars($student['year_level'] ?? '1') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-none d-md-block text-end">
                        <img src="<?= APP_URL ?>/assets/images/icon.png" alt="Logo" style="height: 120px; opacity: 0.2; filter: brightness(0) invert(1);">
                    </div>
                </div>
            </div>
            
            <!-- Other dashboard content can go here (e.g., Quick Links, Recent Grades) -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">Quick Actions</h6>
                            <div class="d-grid gap-2">
                                <button class="btn btn-light text-start border-0 py-3 px-3 rounded-4" onclick="document.querySelector('[data-content=\'get_student_info\']').click()">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    View Full Profile
                                </button>
                                <button class="btn btn-light text-start border-0 py-3 px-3 rounded-4" onclick="document.querySelector('[data-content=\'get_student_grades\']').click()">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                    Check Grades
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mini Calendar Widget -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; background: #ffffff; color: #1e293b; border: 1px solid #f1f5f9 !important;">
                <div class="card-header border-0 bg-transparent pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold" id="cal-month-year" style="color: #1e293b;">March 2026</h6>
                        <span class="small text-muted" id="cal-current-day">Friday, March 27</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-link p-0 text-muted hover-opacity-100" id="prevMonth" style="text-decoration: none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        </button>
                        <button class="btn btn-link p-0 text-muted hover-opacity-100" id="nextMonth" style="text-decoration: none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="mini-calendar">
                        <div class="weekdays d-flex text-center mb-2 opacity-50 small fw-bold">
                            <div class="flex-fill">Su</div>
                            <div class="flex-fill">Mo</div>
                            <div class="flex-fill">Tu</div>
                            <div class="flex-fill">We</div>
                            <div class="flex-fill">Th</div>
                            <div class="flex-fill">Fr</div>
                            <div class="flex-fill">Sa</div>
                        </div>
                        <div id="calendar-days" class="days-grid">
                            <!-- Days generated by JS -->
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-top border-light">
                        <h6 class="small fw-bold mb-3 text-muted">UPCOMING EVENTS</h6>
                        <div id="upcoming-list" class="upcoming-events-list">
                            <!-- Events list -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .mini-calendar { color: #1e293b; }
    .days-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
    .day-cell { 
        height: 38px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-size: 0.85rem; 
        cursor: default; 
        border-radius: 50%;
        position: relative;
        transition: all 0.2s;
        font-weight: 500;
        color: #64748b;
    }
    .day-cell.other-month { opacity: 0.3; }
    .day-cell.has-event { 
        background: rgba(46, 204, 113, 0.12) !important; 
        color: #2ecc71 !important; 
        cursor: help;
        font-weight: 700;
    }
    .day-cell.today { background: #2ecc71 !important; color: #fff !important; box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3); }
    
    .hover-opacity-100:hover { opacity: 1 !important; color: #1e293b !important; }
    
    .tippy-box[data-theme~='calendar'] {
        background-color: #1e293b;
        color: white;
        border-radius: 12px;
        padding: 5px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .event-indicator {
        position: absolute;
        bottom: 5px;
        width: 4px;
        height: 4px;
        background: #2ecc71;
        border-radius: 50%;
    }
    
    .upcoming-item {
        padding-left: 14px;
        border-left: 3px solid #f1f5f9;
        margin-bottom: 20px;
    }
    .upcoming-item.highlight { border-left-color: #2ecc71; background: linear-gradient(90deg, #f0fdf4 0%, transparent 100%); padding-top: 4px; padding-bottom: 4px; border-radius: 0 4px 4px 0; }
    .upcoming-events-list { max-height: 250px; overflow-y: auto; }
</style>

<script>
    (function() {
        // Dynamic script loader for AJAX environments
        function loadScript(url, id) {
            return new Promise((resolve, reject) => {
                if (document.getElementById(id)) {
                    if (window.tippy && id === 'tippy-js') return resolve();
                    return resolve();
                }
                const script = document.createElement('script');
                script.src = url;
                script.id = id;
                script.async = true;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        let currentDate = new Date();
        let eventsData = [];

        async function initDashboard() {
            try {
                // Load Dependencies
                await loadScript('https://unpkg.com/@popperjs/core@2', 'popper-js');
                await loadScript('https://unpkg.com/tippy.js@6', 'tippy-js');
                
                await fetchEvents();
            } catch (err) {
                console.error("Dashboard initialization failed:", err);
            }
        }

        async function fetchEvents() {
            const year = currentDate.getFullYear();
            const month = String(currentDate.getMonth() + 1).padStart(2, '0');
            const lastDay = new Date(year, currentDate.getMonth() + 1, 0).getDate();
            
            const start = `${year}-${month}-01`;
            const end = `${year}-${month}-${lastDay}`;
            
            try {
                const response = await fetch(`<?= APP_URL ?>/student/api/events?start=${start}&end=${end}`);
                const data = await response.json();
                if (data.success) {
                    eventsData = data.events;
                } else {
                    console.error("API Error (Student):", data.message);
                    eventsData = [];
                }
            } catch (e) {
                console.error("Failed to fetch events", e);
                eventsData = [];
            }
            renderCalendar();
        }

        function renderCalendar() {
            const monthYear = document.getElementById('cal-month-year');
            const daysGrid = document.getElementById('calendar-days');
            const todaySpan = document.getElementById('cal-current-day');
            
            if (!daysGrid || !monthYear || !todaySpan) {
                console.error("Calendar elements not found in DOM");
                return;
            }

            todaySpan.textContent = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
            monthYear.textContent = currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            
            daysGrid.innerHTML = '';
            
            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1).getDay();
            const lastDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate();
            const lastMonthLastDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0).getDate();
            
            // Previous month days
            for (let i = firstDay; i > 0; i--) {
                const cell = document.createElement('div');
                cell.className = 'day-cell other-month';
                cell.textContent = lastMonthLastDate - i + 1;
                daysGrid.appendChild(cell);
            }
            
            // Current month days
            const today = new Date();
            const upcomingList = document.getElementById('upcoming-list');
            upcomingList.innerHTML = '';
            
            for (let i = 1; i <= lastDate; i++) {
                const cell = document.createElement('div');
                cell.className = 'day-cell';
                cell.textContent = i;
                
                const dateString = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                
                // Track Today
                if (i === today.getDate() && currentDate.getMonth() === today.getMonth() && currentDate.getFullYear() === today.getFullYear()) {
                    cell.classList.add('today');
                }
                
                // Check events
                const dayEvents = eventsData.filter(e => e.start_date.split(' ')[0] === dateString);
                if (dayEvents.length > 0) {
                    cell.classList.add('has-event');
                    // Add indicator
                    const dot = document.createElement('div');
                    dot.className = 'event-indicator';
                    cell.appendChild(dot);
                    
                    // Add Tippy tooltip
                    const tooltipContent = dayEvents.map(e => `<div class="p-2"><div class="fw-bold fs-7">${e.title}</div><div class="x-small opacity-75">${e.location || ''}</div></div>`).join('');
                    tippy(cell, {
                        content: tooltipContent,
                        allowHTML: true,
                        theme: 'calendar',
                        placement: 'top',
                    });

                    // Add to upcoming list for current month
                    dayEvents.forEach(e => {
                        const item = document.createElement('div');
                        item.className = 'upcoming-item' + (cell.classList.contains('today') ? ' highlight' : '');
                        item.innerHTML = `
                            <div class="small fw-bold">${e.title}</div>
                            <div class="x-small opacity-50">${new Date(e.start_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} • ${e.location || 'No location'}</div>
                        `;
                        upcomingList.appendChild(item);
                    });
                }
                
                daysGrid.appendChild(cell);
            }
            
            if (upcomingList.innerHTML === '') {
                upcomingList.innerHTML = '<div class="x-small opacity-25">No events scheduled for this month.</div>';
            }
        }

        document.getElementById('prevMonth').onclick = () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            fetchEvents();
        };
        
        document.getElementById('nextMonth').onclick = () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            fetchEvents();
        };

        initDashboard();
    })();
</script>
