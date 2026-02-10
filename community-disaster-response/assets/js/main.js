document.addEventListener('DOMContentLoaded', function () {
    setupMobileNav();
    setupHomepageForms();
    loadWeatherForecast();
    loadDisasterAlerts();
    loadEvacuationCenters();
    loadPreparednessGuides();
});

function showAlert(type, title, text) {
    if (typeof swal === 'function') {
        swal({ title: title, text: text, icon: type });
    }
}

function setupMobileNav() {
    var btn = document.getElementById('hamburgerMenu');
    var nav = document.getElementById('mobileNav');
    if (!btn || !nav) return;
    btn.addEventListener('click', function () {
        nav.classList.toggle('active');
    });
}

function displayMessage(elementId, message) {
    var el = document.getElementById(elementId);
    if (el) {
        el.textContent = message;
    }
}

function setupHomepageForms() {
    var reportForm = document.getElementById('reportEmergencyForm');
    if (reportForm) {
        reportForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            var location = document.getElementById('location').value;
            var emergencyType = document.getElementById('emergency_type').value;
            var description = document.getElementById('description').value;

            try {
                var response = await fetch('../api/report_emergency.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        location: location,
                        emergency_type: emergencyType,
                        description: description
                    })
                });
                var data = await response.json();
                displayMessage('reportEmergencyMessage', data.message || 'Unexpected response');
                showAlert(data.status === 'success' ? 'success' : 'error', data.status === 'success' ? 'Success' : 'Error', data.message || '');
                reportForm.reset();
            } catch (error) {
                displayMessage('reportEmergencyMessage', 'Error submitting emergency report');
                showAlert('error', 'Error', 'Error submitting emergency report');
                reportForm.reset();
            }
        });
    }

    var helpForm = document.getElementById('requestHelpForm');
    if (helpForm) {
        helpForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            var helpType = document.getElementById('help_type').value;
            var description = document.getElementById('help_description').value;

            try {
                var response = await fetch('../api/request_help.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        help_type: helpType,
                        description: description
                    })
                });
                var data = await response.json();
                displayMessage('requestHelpMessage', data.message || 'Unexpected response');
                showAlert(data.status === 'success' ? 'success' : 'error', data.status === 'success' ? 'Success' : 'Error', data.message || '');
                helpForm.reset();
            } catch (error) {
                displayMessage('requestHelpMessage', 'Error submitting help request');
                showAlert('error', 'Error', 'Error submitting help request');
                helpForm.reset();
            }
        });
    }

    var volunteerForm = document.getElementById('volunteerForm');
    if (volunteerForm) {
        volunteerForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            var fullName = document.getElementById('full_name').value;
            var skills = document.getElementById('skills').value;
            var availability = document.getElementById('availability').value;

            try {
                var response = await fetch('../api/volunteer_register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        full_name: fullName,
                        skills: skills,
                        availability: availability
                    })
                });
                var data = await response.json();
                displayMessage('volunteerMessage', data.message || 'Unexpected response');
                showAlert(data.status === 'success' ? 'success' : 'error', data.status === 'success' ? 'Success' : 'Error', data.message || '');
                volunteerForm.reset();
            } catch (error) {
                displayMessage('volunteerMessage', 'Error submitting volunteer registration');
                showAlert('error', 'Error', 'Error submitting volunteer registration');
                volunteerForm.reset();
            }
        });
    }

    var donationForm = document.getElementById('donationForm');
    if (donationForm) {
        donationForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            var donorName = document.getElementById('donor_name').value;
            var donationType = document.getElementById('donation_type').value;
            var quantity = document.getElementById('quantity').value;

            try {
                var response = await fetch('../api/donate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        donor_name: donorName,
                        donation_type: donationType,
                        quantity: quantity
                    })
                });
                var data = await response.json();
                displayMessage('donationMessage', data.message || 'Unexpected response');
                showAlert(data.status === 'success' ? 'success' : 'error', data.status === 'success' ? 'Success' : 'Error', data.message || '');
                donationForm.reset();
            } catch (error) {
                displayMessage('donationMessage', 'Error submitting donation');
                showAlert('error', 'Error', 'Error submitting donation');
                donationForm.reset();
            }
        });
    }
}

/**
 * Live Weather Forecast – Philippines (current conditions)
 */
async function loadWeatherForecast() {
    var container = document.getElementById('weatherForecastContainer');
    if (!container) return;

    try {
        var response = await fetch('../api/get_weather_ph.php');
        var result = await response.json();
        container.innerHTML = '';

        if (!result || result.status !== 'success' || !result.data) {
            container.innerHTML = '<p>Weather data temporarily unavailable.</p>';
            return;
        }

        var w = result.data;
        var html = '<ul>';
        if (w.temperature_c !== null) {
            html += '<li>Temperature: ' + w.temperature_c.toFixed(1) + ' °C</li>';
        }
        if (w.rain_probability !== null) {
            html += '<li>Rain probability (next few hours): ' + w.rain_probability + '%</li>';
        }
        if (w.wind_speed !== null) {
            html += '<li>Wind speed: ' + w.wind_speed + ' m/s</li>';
        }
        if (w.thunderstorm_risk) {
            html += '<li>Thunderstorm risk: ' + w.thunderstorm_risk + '</li>';
        }
        html += '</ul>';

        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<p>Weather data temporarily unavailable.</p>';
    }
}

/**
 * Real-Time Disaster Alerts (API-based). Shows most severe; no admin input.
 * On API failure shows "No active disaster alerts at this time" and continues normally.
 */
async function loadDisasterAlerts() {
    var container = document.getElementById('disasterAlertsContainer');
    var lastEl = document.getElementById('disasterAlertsLastUpdated');
    if (!container) return;

    try {
        var response = await fetch('../api/get_disaster_alerts.php');
        var result = await response.json();
        container.innerHTML = '';
        if (lastEl) lastEl.textContent = '';

        var data = result.data || [];
        var lastUpdated = result.last_updated || '';

        if (data.length === 0) {
            container.innerHTML = '<p>No active disaster alerts in the Philippines.</p>';
            if (lastUpdated && lastEl) lastEl.textContent = 'Last updated: ' + lastUpdated;
            return;
        }

        var html = '';
        data.forEach(function (a) {
            var severityClass = 'alert-severity-low';
            var sev = (a.severity || '').toLowerCase();
            if (sev === 'high' || sev === 'red') severityClass = 'alert-severity-high';
            else if (sev === 'moderate' || sev === 'orange') severityClass = 'alert-severity-moderate';

            var code = a.code || '';
            var nameType = a.name_type || ((a.type || '') + ' ' + (a.title || 'Disaster Alert'));
            var locText = a.location_text || a.location || '';

            html += '<div class="alert-item">';
            if (code) html += '<p><strong>' + code + '</strong></p>';
            html += '<p>' + nameType + '</p>';
            html += '<p class="' + severityClass + '">Severity: ' + (a.severity || 'N/A') + '</p>';
            if (locText) html += '<p>Location: ' + locText + '</p>';
            if (a.effective_date) html += '<p>Effective: ' + a.effective_date + '</p>';
            html += '</div>';
        });

        container.innerHTML = html;
        if (lastUpdated && lastEl) lastEl.textContent = 'Last updated: ' + lastUpdated;
    } catch (error) {
        container.innerHTML = '<p>No active disaster alerts in the Philippines.</p>';
        if (lastEl) lastEl.textContent = '';
    }
}

async function loadEvacuationCenters() {
    var container = document.getElementById('evacuationCentersContainer');
    if (!container) return;

    try {
        var response = await fetch('../api/get_evacuation_centers.php');
        var result = await response.json();
        container.innerHTML = '';
        if (Array.isArray(result.data) && result.data.length > 0) {
            result.data.forEach(function (center) {
                var div = document.createElement('div');
                div.className = 'evac-center-item';
                div.innerHTML = '<strong>' + center.name + '</strong><br>' +
                    center.address + '<br>' +
                    'Capacity: ' + center.capacity + ' &middot; Status: ' + center.status;
                container.appendChild(div);
            });
        } else {
            container.innerHTML = '<p>No evacuation centers available.</p>';
        }
    } catch (error) {
        container.innerHTML = '<p>Unable to load evacuation centers.</p>';
    }
}

async function loadPreparednessGuides() {
    var container = document.getElementById('preparednessGuidesContainer');
    if (!container) return;

    try {
        var response = await fetch('../api/get_preparedness_guides.php');
        var result = await response.json();
        container.innerHTML = '';
        if (Array.isArray(result.data) && result.data.length > 0) {
            result.data.forEach(function (guide) {
                var wrap = document.createElement('div');
                wrap.className = 'guide-item';
                var titleBtn = document.createElement('button');
                titleBtn.type = 'button';
                titleBtn.className = 'guide-title-btn';
                titleBtn.innerHTML = (guide.category ? '[' + guide.category + '] ' : '') + guide.title + ' <span class="guide-toggle">&#9660;</span>';
                var content = document.createElement('div');
                content.className = 'guide-content';
                content.style.display = 'none';
                content.textContent = guide.content;
                titleBtn.addEventListener('click', function () {
                    var open = content.style.display !== 'none';
                    content.style.display = open ? 'none' : 'block';
                    titleBtn.querySelector('.guide-toggle').textContent = open ? '\u9660' : '\u9650';
                });
                wrap.appendChild(titleBtn);
                wrap.appendChild(content);
                container.appendChild(wrap);
            });
        } else {
            container.innerHTML = '<p>No preparedness guides available.</p>';
        }
    } catch (error) {
        container.innerHTML = '<p>Unable to load preparedness guides.</p>';
    }
}


