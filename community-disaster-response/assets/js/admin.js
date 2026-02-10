document.addEventListener('DOMContentLoaded', function () {
    setupAdminLogin();
    setupAdminDashboard();
});

function showAlert(type, title, text) {
    if (typeof swal === 'function') {
        swal({
            title: title,
            text: text,
            icon: type
        });
    } else {
        alert(title + ': ' + text);
    }
}

function setupAdminLogin() {
    var form = document.getElementById('adminLoginForm');
    if (!form) {
        return;
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        var username = document.getElementById('username').value;
        var password = document.getElementById('password').value;

        try {
            var response = await fetch('../api/admin/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            });
            var data = await response.json();
            if (data.status === 'success') {
                showAlert('success', 'Login Successful', data.message || 'Login successful');
                form.reset(); // global rule
                setTimeout(function() {
                    window.location.href = 'admin_dashboard.php';
                }, 1000);
            } else {
                showAlert('error', 'Login Failed', data.message || 'Invalid credentials');
                form.reset(); // global rule
            }
        } catch (error) {
            showAlert('error', 'Error', 'An error occurred during login.');
            form.reset(); // global rule
        }
    });
}

function setupAdminDashboard() {
    var logoutButton = document.getElementById('logoutButton');
    if (logoutButton) {
        logoutButton.addEventListener('click', async function () {
            try {
                var response = await fetch('../api/admin/logout.php', {
                    method: 'POST'
                });
                var data = await response.json();
                window.location.href = 'admin_login.php';
            } catch (error) {
                window.location.href = 'admin_login.php';
            }
        });
    }

    // If we are on the dashboard, load all sections
    if (document.getElementById('emergencyReportsContainer')) {
        setupAdminSidebarNav();
        loadDashboardStats();
        loadEmergencyReports();
        loadHelpRequests();
        loadVolunteers();
        loadEvacuationCentersAdmin();
        loadDonations();
        loadGuidesAdmin();
        setupEvacuationCentersModal();
        setupGenericEditModal();
        setupGuidesModal();
    }
}

function setupAdminSidebarNav() {
    var toggleBtn = document.getElementById('mobileSidebarToggle');
    var mobileSidebar = document.getElementById('mobileSidebar');
    if (toggleBtn && mobileSidebar) {
        toggleBtn.addEventListener('click', function () {
            mobileSidebar.classList.toggle('active');
        });
    }

    var links = document.querySelectorAll('.sidebar-link');
    if (!links || links.length === 0) return;
    links.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var target = link.getAttribute('data-target');
            if (!target) return;
            document.querySelectorAll('.dashboard-section').forEach(function (sec) { sec.classList.remove('active'); });
            links.forEach(function (l) { l.classList.remove('active'); });
            var t = document.getElementById(target);
            if (t) t.classList.add('active');
            link.classList.add('active');
            if (mobileSidebar) mobileSidebar.classList.remove('active');
        });
    });
}

var _genericEditType = '';
var _genericEditId = '';

function openGenericEdit(type, id, data) {
    _genericEditType = type;
    _genericEditId = id;
    var modal = document.getElementById('genericEditModal');
    var title = document.getElementById('genericEditModalTitle');
    var body = document.getElementById('genericEditModalBody');
    if (!modal || !body) return;

    if (type === 'volunteer') {
        title.textContent = 'Edit Volunteer';
        body.innerHTML = '<div class="form-group"><label>Full Name</label><input type="text" id="genEditFullName" value="' + (data.full_name || '').replace(/"/g, '&quot;') + '"></div>' +
            '<div class="form-group"><label>Skills</label><input type="text" id="genEditSkills" value="' + (data.skills || '').replace(/"/g, '&quot;') + '"></div>' +
            '<div class="form-group"><label>Availability</label><input type="text" id="genEditAvailability" value="' + (data.availability || '').replace(/"/g, '&quot;') + '"></div>';
    } else if (type === 'emergency_report') {
        title.textContent = 'Edit Emergency Report Status';
        var opts = ['Pending', 'In Progress', 'Resolved', 'Closed', 'Done'];
        var sel = '<select id="genEditStatus"><option value="">Select</option>';
        opts.forEach(function(o) { sel += '<option value="' + o + '"' + (o === (data.status || '') ? ' selected' : '') + '>' + o + '</option>'; });
        sel += '</select>';
        body.innerHTML = '<div class="form-group"><label>Status</label>' + sel + '</div>';
    } else if (type === 'help_request' || type === 'donation') {
        title.textContent = type === 'help_request' ? 'Edit Help Request Status' : 'Edit Donation Status';
        var opts = ['Pending', 'Approved', 'Rejected'];
        var sel = '<select id="genEditStatus"><option value="">Select</option>';
        opts.forEach(function(o) { sel += '<option value="' + o + '"' + (o === (data.status || '') ? ' selected' : '') + '>' + o + '</option>'; });
        sel += '</select>';
        body.innerHTML = '<div class="form-group"><label>Status</label>' + sel + '</div>';
    }
    modal.style.display = 'flex';
}

function setupGenericEditModal() {
    var modal = document.getElementById('genericEditModal');
    var closeBtn = document.getElementById('genericEditModalClose');
    var cancelBtn = document.getElementById('genericEditModalCancel');
    var saveBtn = document.getElementById('genericEditModalSave');
    var body = document.getElementById('genericEditModalBody');

    function closeAndClear() {
        if (modal) modal.style.display = 'none';
        if (body) body.innerHTML = '';
        _genericEditType = '';
        _genericEditId = '';
    }

    if (closeBtn) closeBtn.addEventListener('click', closeAndClear);
    if (cancelBtn) cancelBtn.addEventListener('click', closeAndClear);
    if (modal) modal.addEventListener('click', function(e) { if (e.target === modal) closeAndClear(); });

    if (saveBtn) {
        saveBtn.addEventListener('click', async function() {
            var type = _genericEditType;
            var id = _genericEditId;
            if (!type || !id) return;

            if (type === 'volunteer') {
                var full_name = document.getElementById('genEditFullName');
                var skills = document.getElementById('genEditSkills');
                var availability = document.getElementById('genEditAvailability');
                if (!full_name || !skills || !availability) return;
                try {
                    var res = await fetch('../api/admin/volunteers.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'update', id: parseInt(id, 10), full_name: full_name.value.trim(), skills: skills.value.trim(), availability: availability.value.trim() })
                    });
                    var result = await res.json();
                    if (result.status === 'success') { showAlert('success', 'Updated', result.message); closeAndClear(); loadVolunteers(); loadDashboardStats(); }
                    else { showAlert('error', 'Error', result.message); closeAndClear(); }
                } catch (e) { showAlert('error', 'Error', 'Request failed.'); closeAndClear(); }
            } else if (type === 'emergency_report') {
                var statusEl = document.getElementById('genEditStatus');
                if (!statusEl || !statusEl.value) { showAlert('error', 'Error', 'Select a status'); return; }
                try {
                    var res = await fetch('../api/admin/emergency_reports.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: parseInt(id, 10), status: statusEl.value })
                    });
                    var result = await res.json();
                    if (result.status === 'success') { showAlert('success', 'Updated', result.message); closeAndClear(); loadEmergencyReports(); loadDashboardStats(); }
                    else { showAlert('error', 'Error', result.message); closeAndClear(); }
                } catch (e) { showAlert('error', 'Error', 'Request failed.'); closeAndClear(); }
            } else if (type === 'help_request') {
                var statusEl = document.getElementById('genEditStatus');
                if (!statusEl || !statusEl.value) { showAlert('error', 'Error', 'Select a status'); return; }
                try {
                    var res = await fetch('../api/admin/help_requests.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'update', id: parseInt(id, 10), status: statusEl.value })
                    });
                    var result = await res.json();
                    if (result.status === 'success') { showAlert('success', 'Updated', result.message); closeAndClear(); loadHelpRequests(); loadDashboardStats(); }
                    else { showAlert('error', 'Error', result.message); closeAndClear(); }
                } catch (e) { showAlert('error', 'Error', 'Request failed.'); closeAndClear(); }
            } else if (type === 'donation') {
                var statusEl = document.getElementById('genEditStatus');
                if (!statusEl || !statusEl.value) { showAlert('error', 'Error', 'Select a status'); return; }
                try {
                    var res = await fetch('../api/admin/donations.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'update', id: parseInt(id, 10), status: statusEl.value })
                    });
                    var result = await res.json();
                    if (result.status === 'success') { showAlert('success', 'Updated', result.message); closeAndClear(); loadDonations(); loadDashboardStats(); }
                    else { showAlert('error', 'Error', result.message); closeAndClear(); }
                } catch (e) { showAlert('error', 'Error', 'Request failed.'); closeAndClear(); }
            }
        });
    }
}

async function loadDashboardStats() {
    var statsGrid = document.getElementById('statsGrid');
    if (!statsGrid) return;

    try {
        var response = await fetch('../api/admin/dashboard.php');
        var result = await response.json();
        if (result.status === 'success' && result.data) {
            var stats = result.data.stats || {};
            statsGrid.innerHTML = '';
            
            var statCards = [
                { label: 'Approved Volunteers', value: stats.approved_volunteers || 0 },
                { label: 'Pending Volunteers', value: stats.pending_volunteers || 0 },
                { label: 'Active Emergencies', value: stats.active_emergencies || 0 },
                { label: 'Active Help Requests', value: stats.active_help_requests || 0 },
                { label: 'Total Donations', value: stats.total_donations || 0 }
            ];

            statCards.forEach(function(stat) {
                var card = document.createElement('div');
                card.className = 'stat-card';
                card.innerHTML = '<h3>' + stat.value + '</h3><p>' + stat.label + '</p>';
                statsGrid.appendChild(card);
            });
        }
    } catch (error) {
        statsGrid.innerHTML = '<p>Error loading dashboard stats.</p>';
    }
}

async function loadEmergencyReports() {
    var container = document.getElementById('emergencyReportsContainer');
    if (!container) {
        return;
    }

    try {
        var response = await fetch('../api/admin/emergency_reports.php');
        var result = await response.json();
        container.innerHTML = '';
        if (Array.isArray(result.data) && result.data.length > 0) {
            var table = document.createElement('table');
            var thead = document.createElement('thead');
            thead.innerHTML = '<tr><th>ID</th><th>Type</th><th>Location</th><th>Description</th><th>Status</th><th>Date</th><th>Actions</th></tr>';
            table.appendChild(thead);
            var tbody = document.createElement('tbody');
            
            result.data.forEach(function (report) {
                var assignedVolunteersHtml = '';
                if (report.assigned_volunteers && report.assigned_volunteers.length > 0) {
                    assignedVolunteersHtml = '<div style="margin-top: 0.5rem;"><strong>Assigned Volunteers (' + report.assigned_count + '):</strong><ul style="margin: 0.25rem 0; padding-left: 1.5rem;">';
                    report.assigned_volunteers.forEach(function(vol) {
                        assignedVolunteersHtml += '<li>' + vol.full_name + ' (' + vol.skills + ') - Assigned: ' + vol.date_assigned + '</li>';
                    });
                    assignedVolunteersHtml += '</ul></div>';
                } else {
                    assignedVolunteersHtml = '<div style="margin-top: 0.5rem; color: #999;">No volunteers assigned yet</div>';
                }
                
                var tr = document.createElement('tr');
                tr.innerHTML = 
                    '<td>' + report.id + '</td>' +
                    '<td>' + report.emergency_type + '</td>' +
                    '<td>' + report.location + '</td>' +
                    '<td>' + report.description.substring(0, 50) + '...' + assignedVolunteersHtml + '</td>' +
                    '<td>' + report.status + '</td>' +
                    '<td>' + (report.created_at || '') + '</td>' +
                    '<td><div class="action-buttons">' +
                    '<button class="button btn-edit report-edit" data-id="' + report.id + '" data-status="' + (report.status || '') + '">Edit</button> ' +
                    (report.status === 'Pending' ? '<button class="button btn-approve" data-id="' + report.id + '" data-action="In Progress">In Progress</button> ' : '') +
                    (report.status !== 'Done' ? '<button class="button btn-done" data-id="' + report.id + '" data-type="EmergencyReport">Done</button> ' : '<span>Completed</span> ') +
                    '<button class="button btn-reject report-delete" data-id="' + report.id + '">Delete</button>' +
                    '</div></td>';
                tbody.appendChild(tr);
            });
            
            table.appendChild(tbody);
            container.appendChild(table);
            
            // Add event listeners
            container.querySelectorAll('.btn-approve, .btn-reject').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    updateEmergencyReportStatus(btn.getAttribute('data-id'), btn.getAttribute('data-action'));
                });
            });
            container.querySelectorAll('.btn-done').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    markRequestDone(btn.getAttribute('data-type'), btn.getAttribute('data-id'));
                });
            });
            container.querySelectorAll('.report-edit').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    openGenericEdit('emergency_report', btn.getAttribute('data-id'), { status: btn.getAttribute('data-status') });
                });
            });
            container.querySelectorAll('.report-delete').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    confirmDeleteEmergencyReport(btn.getAttribute('data-id'));
                });
            });
        } else {
            container.innerHTML = '<p>No emergency reports.</p>';
        }
    } catch (error) {
        container.innerHTML = '<p>Error loading emergency reports.</p>';
    }
}

async function updateEmergencyReportStatus(id, status) {
    try {
        var response = await fetch('../api/admin/emergency_reports.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                status: status
            })
        });
        var result = await response.json();
        if (result.status === 'success') {
            showAlert('success', 'Updated', 'Emergency report status updated successfully.');
            loadEmergencyReports();
            loadDashboardStats();
        } else {
            showAlert('error', 'Error', result.message || 'Failed to update status.');
        }
    } catch (error) {
        showAlert('error', 'Error', 'An error occurred while updating status.');
    }
}

async function confirmDeleteEmergencyReport(id) {
    if (typeof swal === 'function') {
        swal({
            title: 'Delete Emergency Report?',
            text: 'This will permanently remove the report and its volunteer assignments.',
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (willDelete) {
            if (willDelete) deleteEmergencyReport(id);
        });
    } else {
        if (confirm('Delete this emergency report?')) deleteEmergencyReport(id);
    }
}

async function deleteEmergencyReport(id) {
    try {
        var response = await fetch('../api/admin/emergency_reports.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: parseInt(id, 10) })
        });
        var result = await response.json();
        if (result.status === 'success') {
            showAlert('success', 'Deleted', result.message || 'Emergency report deleted.');
            loadEmergencyReports();
            loadDashboardStats();
        } else {
            showAlert('error', 'Error', result.message || 'Failed to delete report.');
        }
    } catch (e) {
        showAlert('error', 'Error', 'Request failed.');
    }
}

async function loadHelpRequests() {
    var container = document.getElementById('helpRequestsContainer');
    if (!container) {
        return;
    }

    try {
        var response = await fetch('../api/admin/help_requests.php');
        var result = await response.json();
        container.innerHTML = '';
        if (Array.isArray(result.data) && result.data.length > 0) {
            var table = document.createElement('table');
            var thead = document.createElement('thead');
            thead.innerHTML = '<tr><th>ID</th><th>Type</th><th>Description</th><th>Status</th><th>Date</th><th>Actions</th></tr>';
            table.appendChild(thead);
            var tbody = document.createElement('tbody');
            
            result.data.forEach(function (request) {
                var assignedVolunteersHtml = '';
                if (request.assigned_volunteers && request.assigned_volunteers.length > 0) {
                    assignedVolunteersHtml = '<div style="margin-top: 0.5rem;"><strong>Assigned Volunteers (' + request.assigned_count + '):</strong><ul style="margin: 0.25rem 0; padding-left: 1.5rem;">';
                    request.assigned_volunteers.forEach(function(vol) {
                        assignedVolunteersHtml += '<li>' + vol.full_name + ' (' + vol.skills + ') - Assigned: ' + vol.date_assigned + '</li>';
                    });
                    assignedVolunteersHtml += '</ul></div>';
                } else {
                    assignedVolunteersHtml = '<div style="margin-top: 0.5rem; color: #999;">No volunteers assigned yet</div>';
                }
                
                var tr = document.createElement('tr');
                tr.innerHTML = 
                    '<td>' + request.id + '</td>' +
                    '<td>' + request.help_type + '</td>' +
                    '<td>' + request.description.substring(0, 50) + '...' + assignedVolunteersHtml + '</td>' +
                    '<td>' + request.status + '</td>' +
                    '<td>' + (request.created_at || '') + '</td>' +
                    '<td><div class="action-buttons">' +
                    '<button class="button btn-edit help-edit" data-id="' + request.id + '" data-status="' + (request.status || '') + '">Edit</button> ' +
                    (request.status === 'Pending' ? '<button class="button btn-approve" data-id="' + request.id + '">Approve</button> ' : '') +
                    (request.status === 'Pending' ? '<button class="button btn-reject" data-id="' + request.id + '">Reject</button> ' : '') +
                    (request.status !== 'Done' ? '<button class="button btn-done" data-id="' + request.id + '" data-type="HelpRequest">Done</button> ' : '<span>Completed</span> ') +
                    '<button class="button btn-reject help-delete" data-id="' + request.id + '">Delete</button>' +
                    '</div></td>';
                tbody.appendChild(tr);
            });
            
            table.appendChild(tbody);
            container.appendChild(table);
            
            container.querySelectorAll('.btn-approve').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    updateHelpRequestStatus(btn.getAttribute('data-id'), 'approve');
                });
            });
            container.querySelectorAll('.btn-reject').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    updateHelpRequestStatus(btn.getAttribute('data-id'), 'reject');
                });
            });
            container.querySelectorAll('.btn-done').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    markRequestDone(btn.getAttribute('data-type'), btn.getAttribute('data-id'));
                });
            });
            container.querySelectorAll('.help-edit').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    openGenericEdit('help_request', btn.getAttribute('data-id'), { status: btn.getAttribute('data-status') });
                });
            });
            container.querySelectorAll('.help-delete').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    confirmDeleteHelpRequest(btn.getAttribute('data-id'));
                });
            });
        } else {
            container.innerHTML = '<p>No help requests.</p>';
        }
    } catch (error) {
        container.innerHTML = '<p>Error loading help requests.</p>';
    }
}

async function updateHelpRequestStatus(id, action) {
    try {
        var response = await fetch('../api/admin/help_requests.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                action: action
            })
        });
        var result = await response.json();
        if (result.status === 'success') {
            showAlert('success', 'Updated', 'Help request ' + action + 'd successfully.');
            loadHelpRequests();
            loadDashboardStats();
        } else {
            showAlert('error', 'Error', result.message || 'Failed to update status.');
        }
    } catch (error) {
        showAlert('error', 'Error', 'An error occurred while updating status.');
    }
}

async function confirmDeleteHelpRequest(id) {
    if (typeof swal === 'function') {
        swal({
            title: 'Delete Help Request?',
            text: 'This will permanently remove the request and its volunteer assignments.',
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (willDelete) {
            if (willDelete) deleteHelpRequest(id);
        });
    } else {
        if (confirm('Delete this help request?')) deleteHelpRequest(id);
    }
}

async function deleteHelpRequest(id) {
    try {
        var response = await fetch('../api/admin/help_requests.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: parseInt(id, 10) })
        });
        var result = await response.json();
        if (result.status === 'success') {
            showAlert('success', 'Deleted', result.message || 'Help request deleted.');
            loadHelpRequests();
            loadDashboardStats();
        } else {
            showAlert('error', 'Error', result.message || 'Failed to delete help request.');
        }
    } catch (e) {
        showAlert('error', 'Error', 'Request failed.');
    }
}

async function loadVolunteers() {
    var container = document.getElementById('volunteersContainer');
    if (!container) {
        return;
    }

    try {
        var response = await fetch('../api/admin/volunteers.php');
        var result = await response.json();
        container.innerHTML = '';
        if (Array.isArray(result.data) && result.data.length > 0) {
            var table = document.createElement('table');
            var thead = document.createElement('thead');
            thead.innerHTML = '<tr><th>ID</th><th>Name</th><th>Skills</th><th>Availability</th><th>Status</th><th>Date</th><th>Actions</th></tr>';
            table.appendChild(thead);
            var tbody = document.createElement('tbody');
            
            result.data.forEach(function (volunteer) {
                var tr = document.createElement('tr');
                var isPending = (volunteer.status === 'Pending');
                tr.innerHTML = 
                    '<td>' + volunteer.id + '</td>' +
                    '<td>' + volunteer.full_name + '</td>' +
                    '<td>' + volunteer.skills + '</td>' +
                    '<td>' + volunteer.availability + '</td>' +
                    '<td>' + volunteer.status + '</td>' +
                    '<td>' + (volunteer.created_at || '') + '</td>' +
                    '<td><div class="action-buttons">' +
                    '<button class="button btn-edit volunteer-edit" data-id="' + volunteer.id + '" data-name="' + (volunteer.full_name || '').replace(/"/g, '&quot;') + '" data-skills="' + (volunteer.skills || '').replace(/"/g, '&quot;') + '" data-availability="' + (volunteer.availability || '').replace(/"/g, '&quot;') + '">Edit</button> ' +
                    (isPending ? '<button class="button btn-approve" data-id="' + volunteer.id + '">Approve</button> ' : '') +
                    (isPending ? '<button class="button btn-reject" data-id="' + volunteer.id + '">Reject</button> ' : '') +
                    '<button class="button btn-reject volunteer-delete" data-id="' + volunteer.id + '">Delete</button>' +
                    '</div></td>';
                tbody.appendChild(tr);
            });
            
            table.appendChild(tbody);
            container.appendChild(table);
            
            container.querySelectorAll('.volunteer-edit').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    openGenericEdit('volunteer', btn.getAttribute('data-id'), {
                        full_name: btn.getAttribute('data-name'),
                        skills: btn.getAttribute('data-skills'),
                        availability: btn.getAttribute('data-availability')
                    });
                });
            });
            container.querySelectorAll('.btn-approve').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    updateVolunteerStatus(btn.getAttribute('data-id'), 'approve');
                });
            });
            container.querySelectorAll('.btn-reject').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    updateVolunteerStatus(btn.getAttribute('data-id'), 'reject');
                });
            });
            container.querySelectorAll('.volunteer-delete').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    confirmDeleteVolunteer(btn.getAttribute('data-id'));
                });
            });
        } else {
            container.innerHTML = '<p>No volunteers.</p>';
        }
    } catch (error) {
        container.innerHTML = '<p>Error loading volunteers.</p>';
    }
}

// Sample admin approval logic for volunteers
async function updateVolunteerStatus(id, action) {
    try {
        var response = await fetch('../api/admin/volunteers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                action: action
            })
        });
        var result = await response.json();
        if (result.status === 'success') {
            showAlert('success', 'Updated', 'Volunteer ' + action + 'd successfully.');
            loadVolunteers();
            loadDashboardStats();
        } else {
            showAlert('error', 'Error', result.message || 'Failed to update volunteer status.');
        }
    } catch (error) {
        showAlert('error', 'Error', 'An error occurred while updating volunteer status.');
    }
}

async function confirmDeleteVolunteer(id) {
    if (typeof swal === 'function') {
        swal({
            title: 'Delete Volunteer?',
            text: 'This will permanently remove the volunteer and related history/assignments.',
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (willDelete) {
            if (willDelete) deleteVolunteer(id);
        });
    } else {
        if (confirm('Delete this volunteer?')) deleteVolunteer(id);
    }
}

async function deleteVolunteer(id) {
    try {
        var response = await fetch('../api/admin/volunteers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: parseInt(id, 10) })
        });
        var result = await response.json();
        if (result.status === 'success') {
            showAlert('success', 'Deleted', result.message || 'Volunteer deleted.');
            loadVolunteers();
            loadDashboardStats();
        } else {
            showAlert('error', 'Error', result.message || 'Failed to delete volunteer.');
        }
    } catch (e) {
        showAlert('error', 'Error', 'Request failed.');
    }
}

async function loadDonations() {
    var container = document.getElementById('donationsContainer');
    if (!container) {
        return;
    }

    try {
        var response = await fetch('../api/admin/donations.php');
        var result = await response.json();
        container.innerHTML = '';
        if (Array.isArray(result.data) && result.data.length > 0) {
            var table = document.createElement('table');
            var thead = document.createElement('thead');
            thead.innerHTML = '<tr><th>ID</th><th>Donor</th><th>Type</th><th>Quantity</th><th>Status</th><th>Date</th><th>Actions</th></tr>';
            table.appendChild(thead);
            var tbody = document.createElement('tbody');
            
            result.data.forEach(function (donation) {
                var tr = document.createElement('tr');
                var isPending = (donation.status === 'Pending');
                tr.innerHTML = 
                    '<td>' + donation.id + '</td>' +
                    '<td>' + donation.donor_name + '</td>' +
                    '<td>' + donation.donation_type + '</td>' +
                    '<td>' + donation.quantity + '</td>' +
                    '<td>' + donation.status + '</td>' +
                    '<td>' + (donation.created_at || '') + '</td>' +
                    '<td><div class="action-buttons">' +
                    '<button class="button btn-edit donation-edit" data-id="' + donation.id + '" data-status="' + (donation.status || '') + '">Edit</button> ' +
                    (isPending ? '<button class="button btn-approve" data-id="' + donation.id + '">Approve</button> ' : '') +
                    (isPending ? '<button class="button btn-reject" data-id="' + donation.id + '">Reject</button> ' : '') +
                    '<button class="button btn-reject donation-delete" data-id="' + donation.id + '">Delete</button>' +
                    '</div></td>';
                tbody.appendChild(tr);
            });
            
            table.appendChild(tbody);
            container.appendChild(table);
            
            container.querySelectorAll('.donation-edit').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    openGenericEdit('donation', btn.getAttribute('data-id'), { status: btn.getAttribute('data-status') });
                });
            });
            container.querySelectorAll('.btn-approve').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    updateDonationStatus(btn.getAttribute('data-id'), 'approve');
                });
            });
            container.querySelectorAll('.btn-reject').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    updateDonationStatus(btn.getAttribute('data-id'), 'reject');
                });
            });
            container.querySelectorAll('.donation-delete').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    confirmDeleteDonation(btn.getAttribute('data-id'));
                });
            });
        } else {
            container.innerHTML = '<p>No donations.</p>';
        }
    } catch (error) {
        container.innerHTML = '<p>Error loading donations.</p>';
    }
}

async function updateDonationStatus(id, action) {
    try {
        var response = await fetch('../api/admin/donations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                action: action
            })
        });
        var result = await response.json();
        if (result.status === 'success') {
            showAlert('success', 'Updated', 'Donation ' + action + 'd successfully.');
            loadDonations();
            loadDashboardStats();
        } else {
            showAlert('error', 'Error', result.message || 'Failed to update donation status.');
        }
    } catch (error) {
        showAlert('error', 'Error', 'An error occurred while updating donation status.');
    }
}

async function confirmDeleteDonation(id) {
    if (typeof swal === 'function') {
        swal({
            title: 'Delete Donation?',
            text: 'This will permanently remove the donation record.',
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (willDelete) {
            if (willDelete) deleteDonation(id);
        });
    } else {
        if (confirm('Delete this donation?')) deleteDonation(id);
    }
}

async function deleteDonation(id) {
    try {
        var response = await fetch('../api/admin/donations.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: parseInt(id, 10) })
        });
        var result = await response.json();
        if (result.status === 'success') {
            showAlert('success', 'Deleted', result.message || 'Donation deleted.');
            loadDonations();
            loadDashboardStats();
        } else {
            showAlert('error', 'Error', result.message || 'Failed to delete donation.');
        }
    } catch (e) {
        showAlert('error', 'Error', 'Request failed.');
    }
}

// Evacuation Centers Admin – full CRUD
async function loadEvacuationCentersAdmin() {
    var container = document.getElementById('evacuationCentersContainer');
    if (!container) return;

    try {
        var response = await fetch('../api/admin/evacuation_centers.php');
        var result = await response.json();
        container.innerHTML = '';
        if (result.status !== 'success' || !Array.isArray(result.data)) {
            container.innerHTML = '<p>Unable to load evacuation centers.</p>';
            return;
        }
        var list = result.data;
        if (list.length === 0) {
            container.innerHTML = '<p>No evacuation centers. Click "Add Evacuation Center" to add one.</p>';
            return;
        }
        var table = document.createElement('table');
        table.innerHTML = '<thead><tr><th>Name</th><th>Address</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead><tbody></tbody>';
        var tbody = table.querySelector('tbody');
        list.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + (row.name || '') + '</td>' +
                '<td>' + (row.address || '') + '</td>' +
                '<td>' + (row.capacity || 0) + '</td>' +
                '<td>' + (row.status || '') + '</td>' +
                '<td><div class="action-buttons">' +
                '<button class="button btn-edit evac-edit" data-id="' + row.id + '" data-name="' + (row.name || '').replace(/"/g, '&quot;') + '" data-address="' + (row.address || '').replace(/"/g, '&quot;') + '" data-capacity="' + (row.capacity || 0) + '" data-status="' + (row.status || '') + '">Edit</button> ' +
                '<button class="button btn-reject evac-delete" data-id="' + row.id + '">Delete</button>' +
                '</div></td>';
            tbody.appendChild(tr);
        });
        container.appendChild(table);

        container.querySelectorAll('.evac-edit').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openEvacCenterModal({
                    id: btn.getAttribute('data-id'),
                    name: btn.getAttribute('data-name'),
                    address: btn.getAttribute('data-address'),
                    capacity: btn.getAttribute('data-capacity'),
                    status: btn.getAttribute('data-status')
                });
            });
        });
        container.querySelectorAll('.evac-delete').forEach(function(btn) {
            btn.addEventListener('click', function() {
                confirmDeleteEvacCenter(btn.getAttribute('data-id'));
            });
        });
    } catch (error) {
        container.innerHTML = '<p>Error loading evacuation centers.</p>';
    }
}

function openEvacCenterModal(data) {
    var modal = document.getElementById('evacCenterModal');
    var title = document.getElementById('evacCenterModalTitle');
    document.getElementById('evacCenterId').value = data && data.id ? data.id : '';
    document.getElementById('evacCenterName').value = data && data.name ? data.name : '';
    document.getElementById('evacCenterAddress').value = data && data.address ? data.address : '';
    document.getElementById('evacCenterCapacity').value = data && data.capacity !== undefined ? data.capacity : '0';
    document.getElementById('evacCenterStatus').value = data && data.status ? data.status : 'Available';
    title.textContent = data && data.id ? 'Edit Evacuation Center' : 'Add Evacuation Center';
    if (modal) modal.style.display = 'flex';
}

function setupEvacuationCentersModal() {
    var addBtn = document.getElementById('addEvacuationCenterBtn');
    var modal = document.getElementById('evacCenterModal');
    var closeBtn = document.getElementById('evacCenterModalClose');
    var cancelBtn = document.getElementById('evacCenterModalCancel');
    var form = document.getElementById('evacCenterForm');

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            openEvacCenterModal(null);
        });
    }
    function closeAndReset() {
        if (modal) modal.style.display = 'none';
        if (form) form.reset(); // global rule
    }
    if (closeBtn) closeBtn.addEventListener('click', closeAndReset);
    if (cancelBtn) cancelBtn.addEventListener('click', closeAndReset);
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeAndReset();
        });
    }

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            var id = document.getElementById('evacCenterId').value;
            var name = document.getElementById('evacCenterName').value.trim();
            var address = document.getElementById('evacCenterAddress').value.trim();
            var capacity = parseInt(document.getElementById('evacCenterCapacity').value, 10) || 0;
            var status = document.getElementById('evacCenterStatus').value;

            var payload = id ? { id: parseInt(id, 10), name: name, address: address, capacity: capacity, status: status } : { action: 'add', name: name, address: address, capacity: capacity, status: status };

            try {
                var response = await fetch('../api/admin/evacuation_centers.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                var result = await response.json();
                if (result.status === 'success') {
                    showAlert('success', 'Saved', result.message || 'Evacuation center saved.');
                    closeAndReset();
                    loadEvacuationCentersAdmin();
                } else {
                    showAlert('error', 'Error', result.message || 'Failed to save.');
                    form.reset(); // global rule
                }
            } catch (err) {
                showAlert('error', 'Error', 'An error occurred while saving.');
                form.reset(); // global rule
            }
        });
    }
}

async function confirmDeleteEvacCenter(id) {
    if (typeof swal === 'function') {
        swal({
            title: 'Delete Evacuation Center?',
            text: 'This cannot be undone.',
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function(willDelete) {
            if (willDelete) deleteEvacCenter(id);
        });
    } else {
        if (confirm('Delete this evacuation center?')) deleteEvacCenter(id);
    }
}

async function deleteEvacCenter(id) {
    try {
        var response = await fetch('../api/admin/evacuation_centers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: parseInt(id, 10) })
        });
        var result = await response.json();
        if (result.status === 'success') {
            showAlert('success', 'Deleted', result.message || 'Evacuation center removed.');
            loadEvacuationCentersAdmin();
        } else {
            showAlert('error', 'Error', result.message || 'Failed to delete.');
        }
    } catch (error) {
        showAlert('error', 'Error', 'An error occurred while deleting.');
    }
}

// Preparedness Guides Admin
async function loadGuidesAdmin() {
    var container = document.getElementById('guidesContainer');
    if (!container) return;
    try {
        var response = await fetch('../api/admin/preparedness_guides.php');
        var result = await response.json();
        container.innerHTML = '';
        if (result.status !== 'success' || !Array.isArray(result.data)) {
            container.innerHTML = '<p>Unable to load guides.</p>';
            return;
        }
        var list = result.data;
        if (list.length === 0) {
            container.innerHTML = '<p>No guides. Click "Add Guide" to add one.</p>';
            return;
        }
        var table = document.createElement('table');
        table.innerHTML = '<thead><tr><th>Title</th><th>Category</th><th>Content</th><th>Archived</th><th>Actions</th></tr></thead><tbody></tbody>';
        var tbody = table.querySelector('tbody');
        list.forEach(function (row) {
            var content = (row.content || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '&#10;');
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + (row.title || '') + '</td>' +
                '<td>' + (row.category || '') + '</td>' +
                '<td>' + (row.content || '').substring(0, 80) + '...</td>' +
                '<td>' + (row.is_archived ? 'Yes' : 'No') + '</td>' +
                '<td><div class="action-buttons">' +
                '<button class="button btn-edit guide-edit" data-id="' + row.id + '" data-title="' + (row.title || '').replace(/"/g, '&quot;') + '" data-content="' + content + '" data-category="' + (row.category || 'Other') + '">Edit</button> ' +
                '<button class="button btn-reject guide-archive" data-id="' + row.id + '">Archive</button> ' +
                '<button class="button btn-reject guide-delete" data-id="' + row.id + '">Delete</button>' +
                '</div></td>';
            tbody.appendChild(tr);
        });
        container.appendChild(table);
        container.querySelectorAll('.guide-edit').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openGuideModal({
                    id: btn.getAttribute('data-id'),
                    title: btn.getAttribute('data-title'),
                    content: (btn.getAttribute('data-content') || '').replace(/&#10;/g, '\n'),
                    category: btn.getAttribute('data-category')
                });
            });
        });
        container.querySelectorAll('.guide-archive').forEach(function(btn) {
            btn.addEventListener('click', function() {
                confirmArchiveGuide(btn.getAttribute('data-id'));
            });
        });
        container.querySelectorAll('.guide-delete').forEach(function(btn) {
            btn.addEventListener('click', function() {
                confirmDeleteGuide(btn.getAttribute('data-id'));
            });
        });
    } catch (e) {
        container.innerHTML = '<p>Error loading guides.</p>';
    }
}

function openGuideModal(data) {
    var modal = document.getElementById('guideModal');
    document.getElementById('guideId').value = data && data.id ? data.id : '';
    document.getElementById('guideTitle').value = data && data.title ? data.title : '';
    document.getElementById('guideContent').value = data && data.content ? data.content : '';
    document.getElementById('guideCategory').value = data && data.category ? data.category : 'Other';
    document.getElementById('guideModalTitle').textContent = data && data.id ? 'Edit Guide' : 'Add Guide';
    if (modal) modal.style.display = 'flex';
}

function setupGuidesModal() {
    var addBtn = document.getElementById('addGuideBtn');
    var modal = document.getElementById('guideModal');
    var closeBtn = document.getElementById('guideModalClose');
    var cancelBtn = document.getElementById('guideModalCancel');
    var form = document.getElementById('guideForm');
    if (addBtn) addBtn.addEventListener('click', function() { openGuideModal(null); });
    function closeAndReset() {
        if (modal) modal.style.display = 'none';
        if (form) form.reset(); // global rule
    }
    if (closeBtn) closeBtn.addEventListener('click', closeAndReset);
    if (cancelBtn) cancelBtn.addEventListener('click', closeAndReset);
    if (modal) modal.addEventListener('click', function(e) { if (e.target === modal) closeAndReset(); });
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            var id = document.getElementById('guideId').value;
            var title = document.getElementById('guideTitle').value.trim();
            var content = document.getElementById('guideContent').value.trim();
            var category = document.getElementById('guideCategory').value;
            var action = id ? 'edit' : 'add';
            var payload = id ? { action: 'edit', id: parseInt(id, 10), title: title, content: content, category: category } : { action: 'add', title: title, content: content, category: category };
            try {
                var res = await fetch('../api/admin/preparedness_guides.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                var result = await res.json();
                if (result.status === 'success') { showAlert('success', 'Saved', result.message); modal.style.display = 'none'; loadGuidesAdmin(); }
                else showAlert('error', 'Error', result.message);
                form.reset(); // global rule
            } catch (err) {
                showAlert('error', 'Error', 'Request failed.');
                form.reset(); // global rule
            }
        });
    }
}

function confirmArchiveGuide(id) {
    if (typeof swal === 'function') {
        swal({ title: 'Archive Guide?', text: 'It will be hidden from the public.', icon: 'warning', buttons: true, dangerMode: true }).then(function(will) {
            if (will) archiveGuide(id);
        });
    } else {
        if (confirm('Archive this guide?')) archiveGuide(id);
    }
}

async function archiveGuide(id) {
    try {
        var res = await fetch('../api/admin/preparedness_guides.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'archive', id: parseInt(id, 10) }) });
        var result = await res.json();
        if (result.status === 'success') { showAlert('success', 'Archived', result.message); loadGuidesAdmin(); }
        else showAlert('error', 'Error', result.message);
    } catch (e) { showAlert('error', 'Error', 'Request failed.'); }
}

function confirmDeleteGuide(id) {
    if (typeof swal === 'function') {
        swal({ title: 'Delete Guide?', text: 'This will permanently remove the guide.', icon: 'warning', buttons: true, dangerMode: true }).then(function(will) {
            if (will) deleteGuide(id);
        });
    } else {
        if (confirm('Delete this guide?')) deleteGuide(id);
    }
}

async function deleteGuide(id) {
    try {
        var res = await fetch('../api/admin/preparedness_guides.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete', id: parseInt(id, 10) }) });
        var result = await res.json();
        if (result.status === 'success') { showAlert('success', 'Deleted', result.message); loadGuidesAdmin(); }
        else showAlert('error', 'Error', result.message);
    } catch (e) { showAlert('error', 'Error', 'Request failed.'); }
}

async function markRequestDone(requestType, requestId) {
    try {
        var response = await fetch('../api/admin/mark_done.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                request_type: requestType,
                request_id: parseInt(requestId)
            })
        });
        var result = await response.json();
        if (result.status === 'success') {
            showAlert('success', 'Done', 'Request marked as done. Volunteer totals have been updated.');
            // Reload the appropriate section
            if (requestType === 'EmergencyReport') {
                loadEmergencyReports();
            } else {
                loadHelpRequests();
            }
            // Reload dashboard stats
            loadDashboardStats();
        } else {
            showAlert('error', 'Error', result.message || 'Failed to mark request as done.');
        }
    } catch (error) {
        showAlert('error', 'Error', 'An error occurred while marking request as done.');
    }
}


