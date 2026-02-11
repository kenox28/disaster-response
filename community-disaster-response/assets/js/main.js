document.addEventListener("DOMContentLoaded", function () {
	setupMobileNav();
	setupHomepageForms();
	loadWeatherForecast();
	loadDisasterAlerts();
	loadEvacuationCenters();
	loadPreparednessGuides();
});

function setButtonLoading(btn) {
	if (!btn || btn.dataset.loading === "1") return;
	btn.dataset.loading = "1";
	btn.dataset.originalHtml = btn.innerHTML;
	btn.disabled = true;
	btn.classList.add("is-loading");
	btn.innerHTML = '<span class="btn-spinner"></span>';
}

function resetButtonLoading(btn) {
	if (!btn || btn.dataset.loading !== "1") return;
	btn.disabled = false;
	btn.classList.remove("is-loading");
	if (btn.dataset.originalHtml !== undefined) {
		btn.innerHTML = btn.dataset.originalHtml;
	}
	delete btn.dataset.loading;
	delete btn.dataset.originalHtml;
}

function showAlert(type, title, text) {
	if (typeof swal === "function") {
		swal({ title: title, text: text, icon: type });
	} else {
		// Fallback if SweetAlert is not loaded
		console.error("SweetAlert not loaded. Message:", title, text);
	}
}

function setupMobileNav() {
	var btn = document.getElementById("hamburgerMenu");
	var nav = document.getElementById("mobileNav");
	if (!btn || !nav) return;
	btn.addEventListener("click", function () {
		nav.classList.toggle("active");
	});
}

// Emergency photo upload preview
document.addEventListener("DOMContentLoaded", function () {
	var wrapper = document.getElementById("emergencyPhotoWrapper");
	var input = document.getElementById("emergency_photo");
	var preview = document.getElementById("emergencyPhotoPreview");
	var filenameEl = document.getElementById("emergencyPhotoFilename");

	if (!wrapper || !input) return;

	wrapper.addEventListener("click", function (e) {
		// Avoid infinite loop if clicking directly on input (unlikely since hidden)
		if (e.target === input) return;
		input.click();
	});

	input.addEventListener("change", function () {
		if (!input.files || !input.files[0]) {
			if (preview) {
				preview.innerHTML = "";
				preview.style.display = "none";
			}
			if (filenameEl) filenameEl.textContent = "";
			return;
		}

		var file = input.files[0];
		if (filenameEl) filenameEl.textContent = file.name;

		if (!preview) return;

		var reader = new FileReader();
		reader.onload = function (ev) {
			preview.innerHTML =
				'<img src="' +
				ev.target.result +
				'" alt="Selected image preview for emergency report">';
			preview.style.display = "block";
		};
		reader.readAsDataURL(file);
	});
});

function displayMessage(elementId, message) {
	var el = document.getElementById(elementId);
	if (el) {
		el.textContent = message;
	}
}

function setupHomepageForms() {
	var reportForm = document.getElementById("reportEmergencyForm");
	if (reportForm) {
		reportForm.addEventListener("submit", async function (e) {
			e.preventDefault();
			var location = document.getElementById("location").value;
			var emergencyType = document.getElementById("emergency_type").value;
			var description = document.getElementById("description").value;
			var photoInput = document.getElementById("emergency_photo");
			var submitBtn =
				reportForm.querySelector('button[type="submit"]') ||
				reportForm.querySelector("button");

			setButtonLoading(submitBtn);
			try {
				var formData = new FormData();
				formData.append("location", location);
				formData.append("emergency_type", emergencyType);
				formData.append("description", description);
				if (photoInput && photoInput.files && photoInput.files[0]) {
					formData.append("photo", photoInput.files[0]);
				}

				var response = await fetch("../api/report_emergency.php", {
					method: "POST",
					body: formData,
				});
				var data = await response.json();

				if (data.status === "success") {
					showAlert(
						"success",
						"Emergency Report Submitted",
						data.message ||
							"Your emergency report has been submitted successfully. Help is on the way.",
					);
					displayMessage(
						"reportEmergencyMessage",
						data.message || "Emergency report submitted successfully.",
					);
				} else {
					showAlert(
						"error",
						"Submission Failed",
						data.message ||
							"Failed to submit emergency report. Please try again.",
					);
					displayMessage(
						"reportEmergencyMessage",
						data.message || "Failed to submit emergency report.",
					);
				}
				reportForm.reset();
			} catch (error) {
				showAlert(
					"error",
					"Error",
					"An error occurred while submitting your emergency report. Please try again.",
				);
				displayMessage(
					"reportEmergencyMessage",
					"Error submitting emergency report",
				);
				reportForm.reset();
			} finally {
				resetButtonLoading(submitBtn);
			}
		});
	}

	var helpForm = document.getElementById("requestHelpForm");
	if (helpForm) {
		helpForm.addEventListener("submit", async function (e) {
			e.preventDefault();
			var helpType = document.getElementById("help_type").value;
			var description = document.getElementById("help_description").value;
			var photoInput = document.getElementById("help_photo");
			var submitBtn =
				helpForm.querySelector('button[type="submit"]') ||
				helpForm.querySelector("button");

			setButtonLoading(submitBtn);
			try {
				var formData = new FormData();
				formData.append("help_type", helpType);
				formData.append("description", description);
				if (photoInput && photoInput.files && photoInput.files[0]) {
					formData.append("photo", photoInput.files[0]);
				}

				var response = await fetch("../api/request_help.php", {
					method: "POST",
					body: formData,
				});
				var data = await response.json();

				if (data.status === "success") {
					showAlert(
						"success",
						"Help Request Submitted",
						data.message ||
							"Your help request has been submitted successfully. We will connect you with available volunteers.",
					);
					displayMessage(
						"requestHelpMessage",
						data.message || "Help request submitted successfully.",
					);
				} else {
					showAlert(
						"error",
						"Submission Failed",
						data.message || "Failed to submit help request. Please try again.",
					);
					displayMessage(
						"requestHelpMessage",
						data.message || "Failed to submit help request.",
					);
				}
				helpForm.reset();
			} catch (error) {
				showAlert(
					"error",
					"Error",
					"An error occurred while submitting your help request. Please try again.",
				);
				displayMessage("requestHelpMessage", "Error submitting help request");
				helpForm.reset();
			} finally {
				resetButtonLoading(submitBtn);
			}
		});
	}

	var volunteerForm = document.getElementById("volunteerForm");
	if (volunteerForm) {
		volunteerForm.addEventListener("submit", async function (e) {
			e.preventDefault();
			var fullName = document.getElementById("full_name").value;
			var skills = document.getElementById("skills").value;
			var availability = document.getElementById("availability").value;

			try {
				var response = await fetch("../api/volunteer_register.php", {
					method: "POST",
					headers: {
						"Content-Type": "application/json",
					},
					body: JSON.stringify({
						full_name: fullName,
						skills: skills,
						availability: availability,
					}),
				});
				var data = await response.json();

				if (data.status === "success") {
					showAlert(
						"success",
						"Volunteer Registration Submitted",
						data.message ||
							"Thank you for volunteering! Your registration has been submitted successfully.",
					);
					displayMessage(
						"volunteerMessage",
						data.message || "Volunteer registration submitted successfully.",
					);
				} else {
					showAlert(
						"error",
						"Registration Failed",
						data.message ||
							"Failed to submit volunteer registration. Please try again.",
					);
					displayMessage(
						"volunteerMessage",
						data.message || "Failed to submit volunteer registration.",
					);
				}
				volunteerForm.reset();
			} catch (error) {
				showAlert(
					"error",
					"Error",
					"An error occurred while submitting your volunteer registration. Please try again.",
				);
				displayMessage(
					"volunteerMessage",
					"Error submitting volunteer registration",
				);
				volunteerForm.reset();
			}
		});
	}

	var donationForm = document.getElementById("donationForm");
	if (donationForm) {
		donationForm.addEventListener("submit", async function (e) {
			e.preventDefault();
			var donorName = document.getElementById("donor_name").value;
			var donationType = document.getElementById("donation_type").value;
			var quantity = document.getElementById("quantity").value;

			try {
				var response = await fetch("../api/donate.php", {
					method: "POST",
					headers: {
						"Content-Type": "application/json",
					},
					body: JSON.stringify({
						donor_name: donorName,
						donation_type: donationType,
						quantity: quantity,
					}),
				});
				var data = await response.json();

				if (data.status === "success") {
					showAlert(
						"success",
						"Donation Submitted",
						data.message ||
							"Thank you for your generous donation! Your contribution has been recorded successfully.",
					);
					displayMessage(
						"donationMessage",
						data.message || "Donation submitted successfully.",
					);
				} else {
					showAlert(
						"error",
						"Submission Failed",
						data.message || "Failed to submit donation. Please try again.",
					);
					displayMessage(
						"donationMessage",
						data.message || "Failed to submit donation.",
					);
				}
				donationForm.reset();
			} catch (error) {
				showAlert(
					"error",
					"Error",
					"An error occurred while submitting your donation. Please try again.",
				);
				displayMessage("donationMessage", "Error submitting donation");
				donationForm.reset();
			}
		});
	}
}

/**
 * Live Weather Forecast – Philippines (current conditions)
 */
/**
 * Live Weather Forecast – Philippines (current conditions)
 * Updated with modern UI and weather icons
 */
async function loadWeatherForecast() {
	var container = document.getElementById("weatherForecastContainer");
	if (!container) return;

	try {
		var response = await fetch("../api/get_weather_ph.php");
		var result = await response.json();
		container.innerHTML = "";

		if (!result || result.status !== "success" || !result.data) {
			container.innerHTML = `
				<div class="weather-error">
					<i class="weather-icon">⚠️</i>
					<p>Weather data temporarily unavailable.</p>
				</div>
			`;
			return;
		}

		var w = result.data;

		// Determine weather condition icon and text
		var weatherCondition = getWeatherCondition(w);

		var html = `
			<div class="weather-display">
				<div class="weather-main">
					<div class="weather-icon-large">${weatherCondition.icon}</div>
					<div class="weather-temp">
						${
							w.temperature_c !== null
								? `
							<span class="temp-value">${w.temperature_c.toFixed(1)}</span>
							<span class="temp-unit">°C</span>
						`
								: '<span class="temp-value">--</span>'
						}
					</div>
					<div class="weather-condition">${weatherCondition.text}</div>
					<div class="weather-location">
						<i class="location-icon">📍</i>
						Philippines
					</div>
				</div>
				
				<div class="weather-details">
					${
						w.rain_probability !== null
							? `
						<div class="weather-detail-item">
							<div class="detail-icon">💧</div>
							<div class="detail-content">
								<div class="detail-label">Rain Probability</div>
								<div class="detail-value">${w.rain_probability}%</div>
							</div>
						</div>
					`
							: ""
					}
					
					${
						w.wind_speed !== null
							? `
						<div class="weather-detail-item">
							<div class="detail-icon">💨</div>
							<div class="detail-content">
								<div class="detail-label">Wind Speed</div>
								<div class="detail-value">${w.wind_speed} m/s</div>
							</div>
						</div>
					`
							: ""
					}
					
					${
						w.thunderstorm_risk
							? `
						<div class="weather-detail-item">
							<div class="detail-icon">⚡</div>
							<div class="detail-content">
								<div class="detail-label">Thunderstorm Risk</div>
								<div class="detail-value ${getThunderstormClass(w.thunderstorm_risk)}">${w.thunderstorm_risk}</div>
							</div>
						</div>
					`
							: ""
					}
				</div>
			</div>
		`;

		container.innerHTML = html;
	} catch (e) {
		container.innerHTML = `
			<div class="weather-error">
				<i class="weather-icon">⚠️</i>
				<p>Weather data temporarily unavailable.</p>
			</div>
		`;
	}
}

function getWeatherCondition(weatherData) {
	var rainProb = weatherData.rain_probability || 0;
	var temp = weatherData.temperature_c;

	if (rainProb >= 70) {
		return { icon: "🌧️", text: "Rainy" };
	} else if (rainProb >= 40) {
		return { icon: "⛅", text: "Partly Cloudy" };
	} else if (temp !== null && temp > 32) {
		return { icon: "☀️", text: "Hot & Sunny" };
	} else if (temp !== null && temp > 28) {
		return { icon: "🌤️", text: "Sunny" };
	} else {
		return { icon: "☁️", text: "Cloudy" };
	}
}

function getThunderstormClass(risk) {
	var riskLower = (risk || "").toLowerCase();
	if (riskLower === "high") return "risk-high";
	if (riskLower === "moderate") return "risk-moderate";
	return "risk-low";
}

/**
 * Real-Time Disaster Alerts (API-based). Shows most severe; no admin input.
 * On API failure shows "No active disaster alerts at this time" and continues normally.
 */
async function loadDisasterAlerts() {
	var container = document.getElementById("disasterAlertsContainer");
	var lastEl = document.getElementById("disasterAlertsLastUpdated");
	if (!container) return;

	try {
		var response = await fetch("../api/get_disaster_alerts.php");
		var result = await response.json();
		container.innerHTML = "";
		if (lastEl) lastEl.textContent = "";

		var data = result.data || [];
		var lastUpdated = result.last_updated || "";

		if (data.length === 0) {
			container.innerHTML =
				"<p>No active disaster alerts in the Philippines.</p>";
			if (lastUpdated && lastEl)
				lastEl.textContent = "Last updated: " + lastUpdated;
			return;
		}

		var html = "";
		data.forEach(function (a) {
			var severityClass = "alert-severity-low";
			var sev = (a.severity || "").toLowerCase();
			if (sev === "high" || sev === "red")
				severityClass = "alert-severity-high";
			else if (sev === "moderate" || sev === "orange")
				severityClass = "alert-severity-moderate";

			var code = a.code || "";
			var nameType =
				a.name_type || (a.type || "") + " " + (a.title || "Disaster Alert");
			var locText = a.location_text || a.location || "";

			html += '<div class="alert-item">';
			if (code) html += "<p><strong>" + code + "</strong></p>";
			html += "<p>" + nameType + "</p>";
			html +=
				'<p class="' +
				severityClass +
				'">Severity: ' +
				(a.severity || "N/A") +
				"</p>";
			if (locText) html += "<p>Location: " + locText + "</p>";
			if (a.effective_date)
				html += "<p>Effective: " + a.effective_date + "</p>";
			html += "</div>";
		});

		container.innerHTML = html;
		if (lastUpdated && lastEl)
			lastEl.textContent = "Last updated: " + lastUpdated;
	} catch (error) {
		container.innerHTML =
			"<p>No active disaster alerts in the Philippines.</p>";
		if (lastEl) lastEl.textContent = "";
	}
}

async function loadEvacuationCenters() {
	var container = document.getElementById("evacuationCentersContainer");
	if (!container) return;

	try {
		var response = await fetch("../api/get_evacuation_centers.php");
		var result = await response.json();
		container.innerHTML = "";

		if (Array.isArray(result.data) && result.data.length > 0) {
			var html = '<div class="evac-centers-grid">';

			result.data.forEach(function (center) {
				var statusClass = "status-available";
				var statusIcon = "✓";
				var statusLower = (center.status || "").toLowerCase();

				if (statusLower.includes("full") || statusLower.includes("closed")) {
					statusClass = "status-full";
					statusIcon = "✕";
				} else if (statusLower.includes("limited")) {
					statusClass = "status-limited";
					statusIcon = "!";
				}

				html += `
					<div class="evac-center-card">
						<div class="evac-header">
							<div class="evac-icon">🏢</div>
							<div class="evac-status ${statusClass}">
								<span class="status-icon">${statusIcon}</span>
								${center.status}
							</div>
						</div>
						<h3 class="evac-name">${center.name}</h3>
						<div class="evac-details">
							<div class="evac-detail">
								<span class="detail-icon">📍</span>
								<span class="detail-text">${center.address}</span>
							</div>
							<div class="evac-detail">
								<span class="detail-icon">👥</span>
								<span class="detail-text">Capacity: <strong>${center.capacity}</strong></span>
							</div>
						</div>
					</div>
				`;
			});

			html += "</div>";
			container.innerHTML = html;
		} else {
			container.innerHTML = `
				<div class="empty-state">
					<div class="empty-icon">🏢</div>
					<p>No evacuation centers available at this time.</p>
				</div>
			`;
		}
	} catch (error) {
		container.innerHTML = `
			<div class="error-state">
				<div class="error-icon">⚠️</div>
				<p>Unable to load evacuation centers. Please try again later.</p>
			</div>
		`;
	}
}

async function loadPreparednessGuides() {
	var container = document.getElementById("preparednessGuidesContainer");
	if (!container) return;

	try {
		var response = await fetch("../api/get_preparedness_guides.php");
		var result = await response.json();
		container.innerHTML = "";

		if (Array.isArray(result.data) && result.data.length > 0) {
			var html = '<div class="guides-list">';

			result.data.forEach(function (guide, index) {
				var categoryColor = getCategoryColor(guide.category);
				var categoryIcon = getCategoryIcon(guide.category);

				html += `
					<div class="guide-card" data-guide-id="${index}">
						<div class="guide-header">
							<div class="guide-category ${categoryColor}">
								<span class="category-icon">${categoryIcon}</span>
								<span class="category-text">${guide.category || "General"}</span>
							</div>
							<button class="guide-toggle-btn" data-guide-id="${index}">
								<span class="toggle-icon">▼</span>
							</button>
						</div>
						<h3 class="guide-title">${guide.title}</h3>
						<div class="guide-content" id="guideContent${index}">
							<div class="guide-text">${guide.content}</div>
						</div>
					</div>
				`;
			});

			html += "</div>";
			container.innerHTML = html;

			// Add click handlers for toggle buttons
			document.querySelectorAll(".guide-toggle-btn").forEach(function (btn) {
				btn.addEventListener("click", function () {
					var guideId = this.getAttribute("data-guide-id");
					var content = document.getElementById("guideContent" + guideId);
					var icon = this.querySelector(".toggle-icon");
					var card = this.closest(".guide-card");

					if (content.classList.contains("expanded")) {
						content.classList.remove("expanded");
						icon.textContent = "▼";
						card.classList.remove("active");
					} else {
						content.classList.add("expanded");
						icon.textContent = "▲";
						card.classList.add("active");
					}
				});
			});
		} else {
			container.innerHTML = `
				<div class="empty-state">
					<div class="empty-icon">📚</div>
					<p>No preparedness guides available at this time.</p>
				</div>
			`;
		}
	} catch (error) {
		container.innerHTML = `
			<div class="error-state">
				<div class="error-icon">⚠️</div>
				<p>Unable to load preparedness guides. Please try again later.</p>
			</div>
		`;
	}
}

function getCategoryColor(category) {
	var cat = (category || "").toLowerCase();
	if (cat.includes("earthquake") || cat.includes("seismic"))
		return "category-earthquake";
	if (cat.includes("flood") || cat.includes("water")) return "category-flood";
	if (cat.includes("fire")) return "category-fire";
	if (cat.includes("typhoon") || cat.includes("storm"))
		return "category-typhoon";
	if (cat.includes("health") || cat.includes("medical"))
		return "category-health";
	return "category-general";
}

function getCategoryIcon(category) {
	var cat = (category || "").toLowerCase();
	if (cat.includes("earthquake") || cat.includes("seismic")) return "🌍";
	if (cat.includes("flood") || cat.includes("water")) return "🌊";
	if (cat.includes("fire")) return "🔥";
	if (cat.includes("typhoon") || cat.includes("storm")) return "🌀";
	if (cat.includes("health") || cat.includes("medical")) return "⚕️";
	return "📋";
}
// ============================================================
// ADD THIS CODE TO YOUR SCRIPT.JS FILE
// Place it after the emergency photo upload code (around line 76)
// ============================================================

// Help photo upload preview
document.addEventListener("DOMContentLoaded", function () {
	var wrapper = document.getElementById("helpPhotoWrapper");
	var input = document.getElementById("help_photo");
	var preview = document.getElementById("helpPhotoPreview");
	var filenameEl = document.getElementById("helpPhotoFilename");

	if (!wrapper || !input) return;

	wrapper.addEventListener("click", function (e) {
		// Avoid infinite loop if clicking directly on input (unlikely since hidden)
		if (e.target === input) return;
		input.click();
	});

	input.addEventListener("change", function () {
		if (!input.files || !input.files[0]) {
			if (preview) {
				preview.innerHTML = "";
				preview.style.display = "none";
			}
			if (filenameEl) filenameEl.textContent = "";
			return;
		}

		var file = input.files[0];
		if (filenameEl) filenameEl.textContent = file.name;

		if (!preview) return;

		var reader = new FileReader();
		reader.onload = function (ev) {
			preview.innerHTML =
				'<img src="' +
				ev.target.result +
				'" alt="Selected image preview for help request">';
			preview.style.display = "block";
		};
		reader.readAsDataURL(file);
	});
});
