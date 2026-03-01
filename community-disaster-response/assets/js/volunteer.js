document.addEventListener("DOMContentLoaded", function () {
	initVolunteerIdentity();
	initVolunteerLogin();
	initVolunteerRegister();
	initVolunteerDashboard();
	initVolunteerFeed();
	initForgotPassword();
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
		swal({
			title: title,
			text: text,
			icon: type,
		});
	} else {
		// Fallback if SweetAlert is not loaded
		console.error("SweetAlert not loaded. Message:", title, text);
	}
}

// Shared header identity (avatar + name) for logged-in pages
async function initVolunteerIdentity() {
	var nameEl = document.getElementById("profileHeaderName");
	var avatarEl = document.getElementById("profileAvatar");
	if (!nameEl && !avatarEl) return;

	try {
		var response = await fetch("../../api/volunteer/dashboard.php");
		var result = await response.json();
		if (result.status !== "success" || !result.data || !result.data.info)
			return;
		var info = result.data.info;
		if (nameEl && info.full_name) nameEl.textContent = info.full_name;
		if (avatarEl) {
			avatarEl.src = info.profile_picture
				? info.profile_picture
				: "../../assets/img/default-avatar.svg";
		}
	} catch (e) {
		// Silent fail for identity loading
	}
}

// LOGIN PAGE
function initVolunteerLogin() {
	var form = document.getElementById("volunteerLoginForm");
	if (!form) {
		return;
	}

	form.addEventListener("submit", async function (e) {
		e.preventDefault();
		var email_or_username = document.getElementById("email_or_username").value;
		var password = document.getElementById("password").value;
		var submitBtn =
			form.querySelector('button[type="submit"]') ||
			form.querySelector("button");

		setButtonLoading(submitBtn);
		try {
			var response = await fetch("../../api/volunteer/login.php", {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
				},
				body: JSON.stringify({
					email_or_username: email_or_username,
					password: password,
				}),
			});
			var data = await response.json();

			if (data.status === "success") {
				showAlert(
					"success",
					"Login Successful",
					data.message || "Welcome back!",
				);
				form.reset();
				window.location.href = "dashboard.php";
			} else {
				showAlert(
					"error",
					"Login Failed",
					data.message || "Invalid credentials. Please try again.",
				);
				form.reset();
			}
		} catch (error) {
			showAlert(
				"error",
				"Error",
				"An error occurred during login. Please try again.",
			);
			form.reset();
		} finally {
			resetButtonLoading(submitBtn);
		}
	});
}

function showOnlyVolunteerLoginView(view) {
	var loginForm = document.getElementById("volunteerLoginForm");
	var forgotEmailForm = document.getElementById("forgotPasswordEmailForm");
	var forgotOtpForm = document.getElementById("forgotPasswordOtpForm");
	var resetForm = document.getElementById("forgotPasswordResetForm");

	if (loginForm) loginForm.style.display = view === "login" ? "block" : "none";
	if (forgotEmailForm)
		forgotEmailForm.style.display = view === "forgotEmail" ? "block" : "none";
	if (forgotOtpForm)
		forgotOtpForm.style.display = view === "forgotOtp" ? "block" : "none";
	if (resetForm)
		resetForm.style.display = view === "forgotReset" ? "block" : "none";
}

// REGISTRATION PAGE
function initVolunteerRegister() {
	var form = document.getElementById("volunteerRegisterForm");
	if (!form) {
		return;
	}

	// Password strength indicator
	var pwdInput = document.getElementById("reg_password");
	var strengthEl = document.getElementById("passwordStrength");
	if (pwdInput && strengthEl) {
		pwdInput.addEventListener("input", function () {
			var v = pwdInput.value || "";
			var okLen = v.length >= 8;
			var okNum = /[0-9]/.test(v);
			var okSpec = /[^A-Za-z0-9]/.test(v);
			var lines = [];
			lines.push((okLen ? "✔" : "•") + " Minimum 8 characters");
			lines.push((okNum ? "✔" : "•") + " Must include a number");
			lines.push((okSpec ? "✔" : "•") + " Must include a special character");
			strengthEl.innerHTML = lines.join("<br>");
		});
	}

	form.addEventListener("submit", async function (e) {
		e.preventDefault();
		var username = document.getElementById("reg_username").value;
		var password = document.getElementById("reg_password").value;
		var email = document.getElementById("reg_email").value;
		var full_name = document.getElementById("reg_full_name").value;
		var gender = document.getElementById("reg_gender").value;
		var birthday = document.getElementById("reg_birthday").value;
		var skills = document.getElementById("reg_skills").value;
		var availability = document.getElementById("reg_availability").value;
		var submitBtn =
			form.querySelector('button[type="submit"]') ||
			form.querySelector("button");

		// Frontend age validation (must be at least 18)
		if (birthday) {
			var birthDate = new Date(birthday);
			var today = new Date();
			var age = today.getFullYear() - birthDate.getFullYear();
			var m = today.getMonth() - birthDate.getMonth();
			if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
				age--;
			}
			if (age < 18) {
				showAlert(
					"error",
					"Age Restriction",
					"You must be at least 18 years old to register as a volunteer.",
				);
				form.reset();
				return;
			}
		}

		setButtonLoading(submitBtn);
		try {
			var response = await fetch("../../api/volunteer/register_request.php", {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
				},
				body: JSON.stringify({
					username: username,
					password: password,
					email: email,
					full_name: full_name,
					gender: gender,
					birthday: birthday,
					skills: skills,
					availability: availability,
				}),
			});
			var data = await response.json();

			if (data.status === "success") {
				showAlert(
					"success",
					"OTP Sent",
					data.message ||
						"Please check your email for the OTP code to complete registration.",
				);
				var otpForm = document.getElementById("volunteerOtpForm");
				var otpEmail = document.getElementById("otp_email");
				if (otpForm && otpEmail) {
					otpEmail.value = email;
					otpForm.style.display = "block";
					form.style.display = "none";
				}
			} else {
				showAlert(
					"error",
					"Registration Failed",
					data.message || "Unable to register. Please try again.",
				);
			}
		} catch (error) {
			showAlert(
				"error",
				"Error",
				"An error occurred during registration. Please try again.",
			);
		} finally {
			resetButtonLoading(submitBtn);
		}
	});

	// OTP verification form
	var otpForm = document.getElementById("volunteerOtpForm");
	if (otpForm) {
		otpForm.addEventListener("submit", async function (e) {
			e.preventDefault();
			var email = document.getElementById("otp_email").value;
			var otp = document.getElementById("otp_code").value;
			try {
				var response = await fetch(
					"../../api/volunteer/register_verify_otp.php",
					{
						method: "POST",
						headers: {
							"Content-Type": "application/json",
						},
						body: JSON.stringify({
							email: email,
							otp: otp,
						}),
					},
				);
				var data = await response.json();
				if (data.status === "success") {
					showAlert(
						"success",
						"Registration Complete",
						data.message ||
							"Your registration has been completed successfully!",
					);
					otpForm.reset();
					setTimeout(function () {
						window.location.href = "login.php";
					}, 2000);
				} else {
					showAlert(
						"error",
						"OTP Verification Failed",
						data.message || "Invalid or expired OTP. Please try again.",
					);
				}
			} catch (error) {
				showAlert(
					"error",
					"Error",
					"An error occurred while verifying OTP. Please try again.",
				);
			}
		});
	}
}

// DASHBOARD PAGE
function initVolunteerDashboard() {
	var logoutButton = document.getElementById("volunteerLogoutButton");
	if (!logoutButton && !document.getElementById("feedSection")) {
		// Not on dashboard
		return;
	}

	if (logoutButton) {
		logoutButton.addEventListener("click", async function () {
			try {
				await fetch("../../api/volunteer/logout.php", {
					method: "POST",
				});
			} catch (error) {
				// ignore error
			}
			window.location.href = "login.php";
		});
	}

	setupDashboardNavigation();
	setupProfilePictureUpload();
	setupProfileForm();
	loadDashboardData();
	loadHistory();
}

function setupDashboardNavigation() {
	var buttons = document.querySelectorAll(".dashboard-nav .nav-button");
	buttons.forEach(function (btn) {
		btn.addEventListener("click", function () {
			var target = btn.getAttribute("data-target");
			var sections = document.querySelectorAll(".dashboard-section");
			sections.forEach(function (sec) {
				sec.classList.remove("active");
			});
			buttons.forEach(function (b) {
				b.classList.remove("active");
			});
			var t = document.getElementById(target);
			if (t) t.classList.add("active");
			btn.classList.add("active");
		});
	});
}

async function loadDashboardData() {
	var fullNameInput = document.getElementById("profile_full_name");
	var skillsInput = document.getElementById("profile_skills");
	var availabilityInput = document.getElementById("profile_availability");
	var headerName = document.getElementById("volunteerNameHeader");
	var profileHeaderName = document.getElementById("profileHeaderName");
	var avatar = document.getElementById("profileAvatar");
	var avatarLarge = document.getElementById("profileAvatarLarge");
	var headerEmail = document.getElementById("profileHeaderEmail");
	var headerGender = document.getElementById("profileHeaderPhone");
	var headerBirthday = document.getElementById("profileHeaderBirthday");
	var headerAge = document.getElementById("profileHeaderAge");

	var ach1 = document.getElementById("ach_emergency_help");
	var ach2 = document.getElementById("ach_help_requests");
	var ach3 = document.getElementById("ach_donations");

	if (!fullNameInput || !skillsInput || !availabilityInput) {
		return;
	}

	try {
		var response = await fetch("../../api/volunteer/dashboard.php");
		var result = await response.json();
		if (result.status !== "success") {
			return;
		}

		var info = result.data.info || {};
		var ach = result.data.achievements || {};

		// Fill profile form with current values
		if (fullNameInput) fullNameInput.value = info.full_name || "";
		if (skillsInput) skillsInput.value = info.skills || "";
		if (availabilityInput) availabilityInput.value = info.availability || "";
		if (headerName)
			headerName.textContent = info.full_name || headerName.textContent || "";
		if (profileHeaderName)
			profileHeaderName.textContent =
				info.full_name || profileHeaderName.textContent || "";
		if (headerEmail) headerEmail.textContent = info.email || "";
		if (headerGender) headerGender.textContent = info.gender || "";
		if (headerBirthday) headerBirthday.textContent = info.birthday || "";
		if (headerAge) {
			var ageVal =
				typeof info.age === "number" && info.age > 0
					? info.age + " years old"
					: "";
			headerAge.textContent = ageVal;
		}

		if (avatar) {
			var pic = info.profile_picture || "";
			if (pic) {
				avatar.src = pic;
			} else {
				avatar.src = "../../assets/img/default-avatar.svg";
			}
		}

		if (ach1) ach1.textContent = ach.total_emergency_help || 0;
		if (ach2) ach2.textContent = ach.total_help_requests || 0;
		if (ach3) ach3.textContent = ach.total_donations || 0;

		if (avatarLarge) {
			var pic = info.profile_picture || "";
			if (pic) {
				avatarLarge.src = pic;
			} else {
				avatarLarge.src = "../../assets/img/default-avatar.svg";
			}
		}
	} catch (error) {
		// silent fail for dashboard data loading
	}
}

function setupProfilePictureUpload() {
	var form = document.getElementById("profilePictureForm");
	if (!form) return;

	form.addEventListener("submit", async function (e) {
		e.preventDefault();
		var fileInput = document.getElementById("profile_picture");
		if (!fileInput || !fileInput.files || !fileInput.files[0]) {
			showAlert("error", "No File Selected", "Please choose a file to upload.");
			form.reset();
			return;
		}

		var fd = new FormData();
		fd.append("profile_picture", fileInput.files[0]);

		try {
			var resp = await fetch("../../api/volunteer/upload_profile_picture.php", {
				method: "POST",
				body: fd,
			});
			var data = await resp.json();
			if (data.status === "success") {
				showAlert(
					"success",
					"Upload Successful",
					data.message || "Profile picture uploaded successfully!",
				);
				form.reset();
				loadDashboardData();
			} else {
				showAlert(
					"error",
					"Upload Failed",
					data.message || "Failed to upload profile picture. Please try again.",
				);
				form.reset();
			}
		} catch (err) {
			showAlert(
				"error",
				"Error",
				"An error occurred while uploading. Please try again.",
			);
			form.reset();
		}
	});
}

async function loadHistory() {
	var historyContainer = document.getElementById("historyContent");
	if (!historyContainer) {
		return;
	}

	try {
		var response = await fetch("../../api/volunteer/get_history.php");
		var result = await response.json();
		historyContainer.innerHTML = "";

		if (result.status !== "success") {
			historyContainer.textContent =
				result.message || "Unable to load history.";
			return;
		}

		if (!Array.isArray(result.data) || result.data.length === 0) {
			historyContainer.textContent = "No history records.";
			return;
		}

		result.data.forEach(function (item) {
			var div = document.createElement("div");
			div.className = "history-item";
			div.innerHTML =
				"<strong>" +
				item.type +
				"</strong><span>" +
				item.description +
				"</span><br><small>" +
				item.date_submitted +
				"</small>";
			historyContainer.appendChild(div);
		});
	} catch (error) {
		historyContainer.textContent = "Error loading history.";
	}
}

function setupProfileForm() {
	var form = document.getElementById("profileForm");
	if (!form) {
		return;
	}

	form.addEventListener("submit", async function (e) {
		e.preventDefault();
		var fullName = document.getElementById("profile_full_name").value;
		var skills = document.getElementById("profile_skills").value;
		var availability = document.getElementById("profile_availability").value;
		var password = document.getElementById("profile_password").value;

		try {
			var response = await fetch("../../api/volunteer/update_profile.php", {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
				},
				body: JSON.stringify({
					full_name: fullName,
					skills: skills,
					availability: availability,
					password: password,
				}),
			});
			var result = await response.json();
			if (result.status === "success") {
				showAlert(
					"success",
					"Profile Updated",
					result.message || "Your profile has been updated successfully!",
				);
				form.reset();
				loadDashboardData();
			} else {
				showAlert(
					"error",
					"Update Failed",
					result.message || "Unable to update profile. Please try again.",
				);
				form.reset();
			}
		} catch (error) {
			showAlert(
				"error",
				"Error",
				"An error occurred while updating profile. Please try again.",
			);
			form.reset();
		}
	});
}

// VOLUNTEER FEED PAGE
function initVolunteerFeed() {
	var feedContainer = document.getElementById("feedContainer");
	if (!feedContainer) {
		return;
	}

	// Load feed on initialization
	loadVolunteerFeed();
}

async function loadVolunteerFeed() {
	var feedContainer = document.getElementById("feedContainer");
	if (!feedContainer) {
		return;
	}

	try {
		var response = await fetch("../../api/volunteer/volunteer_feed.php");
		var result = await response.json();

		feedContainer.innerHTML = "";

		if (result.status !== "success") {
			feedContainer.innerHTML =
				'<p class="error">' +
				(result.message || "Unable to load feed.") +
				"</p>";
			return;
		}

		if (!Array.isArray(result.data) || result.data.length === 0) {
			feedContainer.innerHTML =
				'<div class="card"><p>No active emergencies or help requests at this time.</p></div>';
			return;
		}

		result.data.forEach(function (item) {
			var card = document.createElement("div");
			card.className = "card";
			card.id = "feed-item-" + item.type + "-" + item.id;

			var typeLabel =
				item.type === "EmergencyReport" ? "Emergency Report" : "Help Request";
			var typeBadge = item.type === "EmergencyReport" ? "emergency" : "help";
			var statusClass = item.assigned_count > 0 ? "assigned" : "pending";

			var locationHtml = "";
			if (item.location) {
				locationHtml =
					"<p><strong>Location:</strong> " + item.location + "</p>";
			}

			var imageHtml = "";
			if (item.image_url) {
				imageHtml =
					'<div class="feed-image-wrapper"><img src="' +
					item.image_url +
					'" alt="' +
					typeLabel +
					' image" class="feed-image"></div>';
			}

			var typeInfo = "";
			if (item.emergency_type) {
				typeInfo = "<p><strong>Type:</strong> " + item.emergency_type + "</p>";
			} else if (item.help_type) {
				typeInfo = "<p><strong>Type:</strong> " + item.help_type + "</p>";
			}

			var assignedInfo = "";
			if (item.assigned_count > 0) {
				assignedInfo =
					"<p><strong>Volunteers Assigned:</strong> " +
					item.assigned_count +
					"</p>";
			}

			var buttonHtml = "";
			if (item.is_assigned) {
				buttonHtml =
					'<button class="button btn-assigned" disabled>Assigned</button> ' +
					'<button class="button btn-unassign" data-type="' +
					item.type +
					'" data-id="' +
					item.id +
					'">Unassign</button>';
			} else {
				buttonHtml =
					'<button class="button btn-help" data-type="' +
					item.type +
					'" data-id="' +
					item.id +
					"\">I'll Help</button>";
			}

			card.innerHTML =
				imageHtml +
				'<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">' +
				"<h3>" +
				typeLabel +
				" #" +
				item.id +
				"</h3>" +
				'<span class="badge badge-' +
				statusClass +
				'">' +
				(item.assigned_count > 0
					? item.assigned_count + " assigned"
					: "No volunteers") +
				"</span>" +
				"</div>" +
				typeInfo +
				locationHtml +
				"<p><strong>Description:</strong> " +
				item.description +
				"</p>" +
				assignedInfo +
				"<p><small><strong>Submitted:</strong> " +
				item.created_at +
				"</small></p>" +
				'<div style="margin-top: 1rem;">' +
				buttonHtml +
				"</div>";

			feedContainer.appendChild(card);
		});

		// Add event listeners for buttons
		feedContainer.querySelectorAll(".btn-help").forEach(function (btn) {
			btn.addEventListener("click", function () {
				assignToRequest(
					btn.getAttribute("data-type"),
					btn.getAttribute("data-id"),
				);
			});
		});

		feedContainer.querySelectorAll(".btn-unassign").forEach(function (btn) {
			btn.addEventListener("click", function () {
				unassignFromRequest(
					btn.getAttribute("data-type"),
					btn.getAttribute("data-id"),
				);
			});
		});
	} catch (error) {
		feedContainer.innerHTML =
			'<p class="error">Error loading feed. Please try again.</p>';
	}
}

async function assignToRequest(requestType, requestId) {
	try {
		var response = await fetch("../../api/volunteer/assign_help.php", {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
			},
			body: JSON.stringify({
				request_type: requestType,
				request_id: parseInt(requestId),
				action: "assign",
			}),
		});
		var result = await response.json();

		if (result.status === "success") {
			showAlert(
				"success",
				"Successfully Assigned",
				result.message ||
					"You are now assigned to this request. The admin has been notified.",
			);
			// Reload feed to update button states and counts
			loadVolunteerFeed();
		} else {
			showAlert(
				"error",
				"Assignment Failed",
				result.message || "Failed to assign to request. Please try again.",
			);
		}
	} catch (error) {
		showAlert(
			"error",
			"Error",
			"An error occurred while assigning to request. Please try again.",
		);
	}
}

async function unassignFromRequest(requestType, requestId) {
	try {
		var response = await fetch("../../api/volunteer/assign_help.php", {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
			},
			body: JSON.stringify({
				request_type: requestType,
				request_id: parseInt(requestId),
				action: "unassign",
			}),
		});
		var result = await response.json();

		if (result.status === "success") {
			showAlert(
				"success",
				"Successfully Unassigned",
				result.message || "You have been unassigned from this request.",
			);
			// Reload feed to update button states
			loadVolunteerFeed();
		} else {
			showAlert(
				"error",
				"Unassignment Failed",
				result.message || "Failed to unassign from request. Please try again.",
			);
		}
	} catch (error) {
		showAlert(
			"error",
			"Error",
			"An error occurred while unassigning from request. Please try again.",
		);
	}
}

// FORGOT PASSWORD
function initForgotPassword() {
	var forgotLink = document.getElementById("forgotPasswordLink");
	if (!forgotLink) return;

	var emailForm = document.getElementById("forgotPasswordEmailForm");
	var otpForm = document.getElementById("forgotPasswordOtpForm");
	var resetForm = document.getElementById("forgotPasswordResetForm");

	var back1 = document.getElementById("forgotBackToLogin1");
	var back2 = document.getElementById("forgotBackToLogin2");
	var back3 = document.getElementById("forgotBackToLogin3");

	function backToLogin() {
		showOnlyVolunteerLoginView("login");
		if (emailForm) emailForm.reset();
		if (otpForm) otpForm.reset();
		if (resetForm) resetForm.reset();
		var loginForm = document.getElementById("volunteerLoginForm");
		if (loginForm) loginForm.reset();
	}

	forgotLink.addEventListener("click", function (e) {
		e.preventDefault();
		showOnlyVolunteerLoginView("forgotEmail");
		if (emailForm) emailForm.reset();
		if (otpForm) otpForm.reset();
		if (resetForm) resetForm.reset();
	});

	if (back1) back1.addEventListener("click", backToLogin);
	if (back2) back2.addEventListener("click", backToLogin);
	if (back3) back3.addEventListener("click", backToLogin);

	// Send OTP
	if (emailForm) {
		emailForm.addEventListener("submit", async function (e) {
			e.preventDefault();
			var email = document.getElementById("forgot_email").value;
			try {
				var response = await fetch("../../api/volunteer/forgot_request.php", {
					method: "POST",
					headers: { "Content-Type": "application/json" },
					body: JSON.stringify({ email: email }),
				});
				var data = await response.json();
				if (data.status === "success") {
					showAlert(
						"success",
						"OTP Sent",
						data.message || "An OTP has been sent to your email address.",
					);
					emailForm.reset();
					var otpEmail = document.getElementById("forgot_otp_email");
					if (otpEmail) otpEmail.value = email;
					showOnlyVolunteerLoginView("forgotOtp");
				} else {
					showAlert(
						"error",
						"Failed to Send OTP",
						data.message ||
							"Failed to send OTP. Please check your email address and try again.",
					);
					emailForm.reset();
				}
			} catch (err) {
				showAlert(
					"error",
					"Error",
					"An error occurred while sending OTP. Please try again.",
				);
				emailForm.reset();
			}
		});
	}

	// Verify OTP
	if (otpForm) {
		otpForm.addEventListener("submit", async function (e) {
			e.preventDefault();
			var email = document.getElementById("forgot_otp_email").value;
			var otp = document.getElementById("forgot_otp").value;
			try {
				var response = await fetch(
					"../../api/volunteer/forgot_verify_otp.php",
					{
						method: "POST",
						headers: { "Content-Type": "application/json" },
						body: JSON.stringify({ email: email, otp: otp }),
					},
				);
				var data = await response.json();
				if (data.status === "success") {
					showAlert(
						"success",
						"OTP Verified",
						data.message ||
							"OTP verified successfully. Please set your new password.",
					);
					otpForm.reset();
					var resetEmail = document.getElementById("reset_email");
					var resetOtp = document.getElementById("reset_otp");
					if (resetEmail) resetEmail.value = email;
					if (resetOtp) resetOtp.value = otp;
					showOnlyVolunteerLoginView("forgotReset");
				} else {
					showAlert(
						"error",
						"OTP Verification Failed",
						data.message || "Invalid or expired OTP. Please try again.",
					);
					otpForm.reset();
					backToLogin();
				}
			} catch (err) {
				showAlert(
					"error",
					"Error",
					"An error occurred while verifying OTP. Please try again.",
				);
				otpForm.reset();
				backToLogin();
			}
		});
	}

	// Password strength indicator for reset
	var resetPwd = document.getElementById("reset_password");
	var resetStrength = document.getElementById("resetPasswordStrength");
	if (resetPwd && resetStrength) {
		resetPwd.addEventListener("input", function () {
			var v = resetPwd.value || "";
			var okLen = v.length >= 8;
			var okNum = /[0-9]/.test(v);
			var okSpec = /[^A-Za-z0-9]/.test(v);
			var lines = [];
			lines.push((okLen ? "✔" : "•") + " Minimum 8 characters");
			lines.push((okNum ? "✔" : "•") + " Must include a number");
			lines.push((okSpec ? "✔" : "•") + " Must include a special character");
			resetStrength.innerHTML = lines.join("<br>");
		});
	}

	// Reset password
	if (resetForm) {
		resetForm.addEventListener("submit", async function (e) {
			e.preventDefault();
			var email = document.getElementById("reset_email").value;
			var otp = document.getElementById("reset_otp").value;
			var pass = document.getElementById("reset_password").value;
			var pass2 = document.getElementById("reset_password_confirm").value;

			if (pass !== pass2) {
				showAlert(
					"error",
					"Passwords Don't Match",
					"The passwords you entered do not match. Please try again.",
				);
				resetForm.reset();
				backToLogin();
				return;
			}

			try {
				var response = await fetch("../../api/volunteer/reset_password.php", {
					method: "POST",
					headers: { "Content-Type": "application/json" },
					body: JSON.stringify({ email: email, otp: otp, password: pass }),
				});
				var data = await response.json();
				if (data.status === "success") {
					showAlert(
						"success",
						"Password Reset Successful",
						data.message ||
							"Your password has been reset successfully. You can now log in with your new password.",
					);
					resetForm.reset();
					backToLogin();
				} else {
					showAlert(
						"error",
						"Password Reset Failed",
						data.message || "Failed to reset password. Please try again.",
					);
					resetForm.reset();
					backToLogin();
				}
			} catch (err) {
				showAlert(
					"error",
					"Error",
					"An error occurred while resetting password. Please try again.",
				);
				resetForm.reset();
				backToLogin();
			}
		});
	}
}

function volunteer_logout() {
	window.location.href = "volunteer_logout.php";
}
