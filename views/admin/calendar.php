<div class="calendar-container p-4">
    <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold" style="color: #1e293b;">School Events Calendar</h5>
            <button class="btn btn-primary btn-sm px-3" style="background: #2ecc71 !important; border: none; border-radius: 8px;" onclick="window.openAddEventModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Add Event
            </button>
        </div>
        <div class="card-body p-0">
            <div id='calendar' style="min-height: 600px; background: #fff;">
                <div id="calendar-loading" class="p-5 text-center text-muted">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Loading Calendar...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Add New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    <input type="hidden" id="event_id" name="id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Event Title</label>
                        <input type="text" class="form-control" name="title" required placeholder="e.g. Midterm Exams">
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label small fw-bold text-muted">Start Date</label>
                            <input type="datetime-local" class="form-control" name="start_date" required id="start_date">
                        </div>
                        <div class="col">
                            <label class="form-label small fw-bold text-muted">End Date</label>
                            <input type="datetime-local" class="form-control" name="end_date" required id="end_date">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Location (Optional)</label>
                        <input type="text" class="form-control" name="location" placeholder="e.g. Room 301">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3 d-flex align-items-center justify-content-between">
                        <div>
                            <label class="form-label small fw-bold text-muted d-block">Label Color</label>
                            <div class="d-flex gap-2 mt-1">
                                <input type="radio" class="btn-check" name="color" id="color1" value="#2ecc71" checked>
                                <label class="btn btn-outline-success p-2 rounded-circle" for="color1" style="width: 30px; height: 30px; background: #2ecc71; border: none;"></label>
                                
                                <input type="radio" class="btn-check" name="color" id="color2" value="#3498db">
                                <label class="btn btn-outline-primary p-2 rounded-circle" for="color2" style="width: 30px; height: 30px; background: #3498db; border: none;"></label>
                                
                                <input type="radio" class="btn-check" name="color" id="color3" value="#e74c3c">
                                <label class="btn btn-outline-danger p-2 rounded-circle" for="color3" style="width: 30px; height: 30px; background: #e74c3c; border: none;"></label>
                                
                                <input type="radio" class="btn-check" name="color" id="color4" value="#f1c40f">
                                <label class="btn btn-outline-warning p-2 rounded-circle" for="color4" style="width: 30px; height: 30px; background: #f1c40f; border: none;"></label>
                            </div>
                        </div>
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" name="all_day" id="all_day">
                            <label class="form-check-label small fw-bold text-muted" for="all_day">All Day</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Recurrence (Optional - RRule)</label>
                        <input type="text" class="form-control" name="rrule" placeholder="e.g. FREQ=WEEKLY;BYDAY=MO">
                        <div class="form-text x-small">Example: FREQ=WEEKLY;BYDAY=MO for every Monday.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger d-none" id="deleteBtn" onclick="deleteEvent()">Delete</button>
                <button type="button" class="btn btn-primary" style="background: #1e293b; border: none;" onclick="saveEvent()">Save Event</button>
            </div>
        </div>
    </div>
</div>

<style>
    .fc { font-family: 'Inter', sans-serif; --fc-border-color: #f1f5f9; --fc-today-bg-color: #f8fafc; }
    .fc-header-toolbar { padding: 1.5rem; margin-bottom: 0 !important; }
    .fc-toolbar-title { font-size: 1.25rem !important; font-weight: 700 !important; color: #1e293b; }
    .fc-button-primary { background-color: #f1f5f9 !important; border-color: #f1f5f9 !important; color: #475569 !important; text-transform: capitalize; font-weight: 600; font-size: 0.85rem; border-radius: 8px !important; }
    .fc-button-primary:not(:disabled):active, .fc-button-primary:not(:disabled).fc-button-active { background-color: #1e293b !important; color: white !important; }
    .fc-daygrid-day-number { color: #64748b; font-size: 0.9rem; padding: 10px !important; }
    .fc-event { border-radius: 6px; border: none; padding: 2px 6px; font-weight: 500; cursor: pointer; }
    .fc-col-header-cell { background: #f8fafc; padding: 12px 0 !important; font-weight: 600 !important; color: #64748b !important; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
    .x-small { font-size: 0.75rem; }
</style>

<script>
    (function() {
        // Essential for AJAX-based loading systems
        function loadScript(url, id) {
            return new Promise((resolve, reject) => {
                if (document.getElementById(id)) {
                    if (window.FullCalendar && id === 'fullcalendar-js') return resolve();
                    if (window.moment && id === 'moment-js') return resolve();
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

        function loadStyle(url, id) {
            if (document.getElementById(id)) return;
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            link.id = id;
            document.head.appendChild(link);
        }

        window.fullCalendarInstance = null;
        window.calendarModalInstance = null;

        window.initCalendarModal = function() {
            var modalEl = document.getElementById('eventModal');
            if (modalEl && typeof bootstrap !== 'undefined' && !window.calendarModalInstance) {
                window.calendarModalInstance = new bootstrap.Modal(modalEl);
            }
        }

        window.initCalendar = async function() {
            try {
                // Load Dependencies Dynamically
                loadStyle('https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css', 'fullcalendar-css');
                await loadScript('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js', 'moment-js');
                await loadScript('https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js', 'fullcalendar-js');

                const calendarEl = document.getElementById('calendar');
                if (!calendarEl || typeof FullCalendar === 'undefined') {
                    console.error("Calendar deps not ready");
                    return;
                }

                // Remove loading indicator
                const loadingEl = document.getElementById('calendar-loading');
                if (loadingEl) loadingEl.remove();
                
                window.fullCalendarInstance = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    themeSystem: 'bootstrap5',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: function(fetchInfo, successCallback, failureCallback) {
                            fetch('/Student-Portal/admin/api/events?start=' + fetchInfo.startStr + '&end=' + fetchInfo.endStr)
                            .then(response => {
                                if (!response.ok) throw new Error("Network response was not ok. Status: " + response.status);
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    const events = data.events.map(e => ({
                                        id: e.id,
                                        title: e.title,
                                        start: e.start_date,
                                        end: e.end_date,
                                        description: e.description,
                                        location: e.location,
                                        rrule: e.rrule,
                                        backgroundColor: e.color,
                                        borderColor: e.color,
                                        allDay: parseInt(e.all_day) === 1
                                    }));
                                    successCallback(events);
                                } else {
                                    console.error("API Error:", data.message);
                                    failureCallback(data.message);
                                }
                            })
                            .catch(error => {
                                console.error("Fetch Error:", error);
                                failureCallback(error);
                            });
                    },
                    dateClick: function(info) {
                        window.openAddEventModal(info.dateStr);
                    },
                    eventClick: function(info) {
                        window.openEditEventModal(info.event);
                    }
                });
                window.fullCalendarInstance.render();
                window.initCalendarModal();
            } catch (err) {
                console.error("Failed to initialize calendar:", err);
                const calendarEl = document.getElementById('calendar');
                if (calendarEl) calendarEl.innerHTML = '<div class="p-5 text-center text-danger">Failed to load calendar dependencies. Please check your connection.</div>';
            }
        }

        window.openAddEventModal = function(date = null) {
            window.initCalendarModal();
            if (!window.calendarModalInstance) {
                alert("Calendar system is still loading. Please wait a second.");
                return;
            }
            document.getElementById('eventForm').reset();
            document.getElementById('event_id').value = '';
            document.getElementById('modalTitle').textContent = 'Add New Event';
            document.getElementById('deleteBtn').classList.add('d-none');
            
            if (date) {
                document.getElementById('start_date').value = date + 'T08:00';
                document.getElementById('end_date').value = date + 'T09:00';
            }
            window.calendarModalInstance.show();
        }

        window.openEditEventModal = function(event) {
            window.initCalendarModal();
            document.getElementById('modalTitle').textContent = 'Edit Event';
            document.getElementById('event_id').value = event.id;
            document.querySelector('#eventForm [name="title"]').value = event.title;
            document.getElementById('start_date').value = moment(event.start).format('YYYY-MM-DDTHH:mm');
            document.getElementById('end_date').value = moment(event.end).format('YYYY-MM-DDTHH:mm');
            document.querySelector('#eventForm [name="location"]').value = event.extendedProps.location || '';
            document.querySelector('#eventForm [name="description"]').value = event.extendedProps.description || '';
            document.querySelector('#eventForm [name="rrule"]').value = event.extendedProps.rrule || '';
            document.getElementById('all_day').checked = event.allDay;
            
            document.querySelectorAll('#eventForm [name="color"]').forEach(radio => {
                if (radio.value === event.backgroundColor) radio.checked = true;
            });
            
            document.getElementById('deleteBtn').classList.remove('d-none');
            window.calendarModalInstance.show();
        }

        window.saveEvent = function() {
            const form = document.getElementById('eventForm');
            const formData = new FormData(form);
            const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';

            // Convert FormData to a plain object for JSON.stringify
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }

            fetch('/Student-Portal/admin/api/events/save', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data) // Use the plain object here
            })
                .then(response => {
                    if (!response.ok) throw new Error("Network response was not ok. Status: " + response.status);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (window.calendarModalInstance) window.calendarModalInstance.hide();
                        
                        // Show success feedback
                        const toast = document.createElement('div');
                        toast.className = 'position-fixed bottom-0 end-0 p-3';
                        toast.style.zIndex = '1060';
                        toast.innerHTML = `<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            ${data.message || 'Event saved successfully!'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>`;
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 3000);

                        if (window.fullCalendarInstance) {
                            window.fullCalendarInstance.refetchEvents();
                        }
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Error saving event:", error);
                    alert("Error saving event. Please check the console.");
                });
        }

        window.deleteEvent = function() {
            const id = document.getElementById('event_id').value;
            if (!id) return;

            if (confirm('Are you sure you want to delete this event?')) {
                const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
                fetch('/Student-Portal/admin/api/events/delete', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ id: id })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (window.calendarModalInstance) window.calendarModalInstance.hide();
                            if (window.fullCalendarInstance) {
                                window.fullCalendarInstance.refetchEvents();
                            }
                        } else {
                            alert("Error: " + data.message);
                        }
                    });
            }
        }

        // Start initialization
        window.initCalendar();
    })();
</script>
