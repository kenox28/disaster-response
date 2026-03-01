<!--?php
session_start();
if (!isset($_SESSION['volunteer_id'])) {
    header('Location: login.php');
    exit;
}
?-->
<!doctype html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Volunteer Dashboard</title>
  <link rel="stylesheet" href="../../assets/css/main.css?v=1.0.2">
  <link rel="stylesheet" href="../../assets/css/volunteer.css?v=1.0.4">
  <link rel="stylesheet" href="../../assets/css/profile.css?v=1.0.3">

  <!-- <style>@view-transition { navigation: auto; }</style> -->
  <script src="/_sdk/data_sdk.js" type="text/javascript"></script>
  <script src="/_sdk/element_sdk.js" type="text/javascript"></script>
  <script src="https://cdn.tailwindcss.com" type="text/javascript"></script>
 </head>
 <style>
    .profile-header-infos{
        width:65%;
        display: flex;
        gap: 0.5rem;
        padding: 0.5rem;
        border-radius: 0.5rem;
        background-color: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
        color: #000;
        font-size: 0.8rem;
    }
    .profile-header-info-item{
        width: 50%;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding: 0.5rem;
        border-radius: 0.5rem;
        background-color: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
        box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
    }
    .profile-header-info-item label{
        font-size: 0.8rem;
        font-weight: 800;
        color: #000;
        margin: 0;
        padding: 0;
        width: 30%;

    }
    .profile-header-info-item-content{
        box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
        display: flex;
        flex-direction: row;
        gap: 0.5rem;
        padding: 0.5rem;
        border-radius: 0.5rem;
        background-color: #fff;
    }
    .profile-header-infos h1{
        font-size: 1rem;
        font-weight: 400;
        color: #111827 !important;
        margin: 0;
        padding: 0;
        width: 50%;
        text-align: center;
        text-transform: capitalize;
    }
 </style>
 <body>
  <header>
   <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
    <div style="display:flex; align-items:center; gap:0.75rem;"><img id="profileAvatar" src="../../assets/img/default-avatar.svg" alt="Profile" style="width:44px;height:44px;border-radius:999px;object-fit:cover;border:1px solid #ddd;">
     <div>
      <h1 style="margin:0;">Volunteer Dashboard</h1><small>Signed in as <strong id="volunteerNameHeader"><!--?php echo htmlspecialchars($_SESSION['volunteer_name']); ?--></strong></small>
     </div>
    </div>
    <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
     <nav class="dashboard-nav" style="display:flex; gap:0.5rem; flex-wrap:wrap;"><button class="nav-button active" data-target="feedSection" type="button">Volunteer Feed</button> <button class="nav-button" data-target="historySection" type="button">History</button> <button class="nav-button" data-target="profileSection" type="button">Profile</button>
     </nav><button id="volunteerLogoutButton" class="button button-secondary" type="button">Logout</button>
    </div>
   </div>
  </header>
  <main><img src="../../assets/bg2.jpg" alt="Community Disaster Response" style="width: 100%; height: 100%; object-fit: cover; position:fixed; top: 0; left: 0; z-index: -1; opacity: 0.5; filter: blur(10px); ">
   <section id="feedSection" class="dashboard-section active">
    <div class="card">
     <h2>Volunteer Feed</h2>
     <p style="margin-top:0;">Active emergency reports and help requests you can assist with.</p>
     <div id="feedContainer">
      <!-- Filled by AJAX -->
     </div>
    </div>
   </section>
   <section id="historySection" class="dashboard-section">
    <div class="card">
     <h2>History</h2>
     <div id="historyContent"><!-- Filled by AJAX -->
     </div>
    </div>
   </section>
   <section id="profileSection" class="dashboard-section"><!-- Profile Header Banner -->
    <div class="card"><!-- Header Top Section -->
     <div class="profile-header-top">
      <div class="profile-avatar-wrapper"><img id="profileAvatarLarge" src="../../assets/img/default-avatar.svg" alt="Profile" class="profile-avatar-large"> <button type="button" class="profile-picture-edit-btn" id="editPictureBtn" title="Edit Picture">📷</button>
      </div>
      <div class="profile-header-info">
       <h2 id="profileHeaderName">Volunteer Name</h2>
      </div>
      <div class="profile-header-infos">

       <div class="profile-header-info-item">
        <div class="profile-header-info-item-content">
        <label for="profileHeaderEmail">Email</label>
       <h1 id="profileHeaderEmail">Volunteer Email</h1>
       </div>
       <div class="profile-header-info-item-content">
       <label for="profileHeaderPhone">Gender</label>
       <h1 id="profileHeaderPhone">Volunteer gender</h1>
       </div>
        </div>
        
        <div class="profile-header-info-item">
        <div class="profile-header-info-item-content">
        <label for="profileHeaderBirthday">Birthday</label>
        <h1 id="profileHeaderBirthday">Volunteer birthday</h1>
        </div>
        <div class="profile-header-info-item-content">
        <label for="profileHeaderAge">Age</label>
        <h1 id="profileHeaderAge">Volunteer age</h1>
        </div>
      </div>
     </div><!-- Profile Content Grid -->
     <div class="profile-content-wrapper"><!-- Achievements Section -->
      <div class="profile-left-section">
       <div class="profile-card">
        <div class="profile-card-header">
         <h3>Achievements</h3><span class="card-icon-emoji">🏆</span>
        </div>
        <div class="achievements-grid">
         <div class="achievement-card">
          <div class="achievement-icon">
           🚨
          </div>
          <p class="achievement-title">Emergency</p>
          <p class="achievement-count" id="ach_emergency_help">0</p>
         </div>
         <div class="achievement-card">
          <div class="achievement-icon">
           ✅
          </div>
          <p class="achievement-title">Help Requests</p>
          <p class="achievement-count" id="ach_help_requests">0</p>
         </div>
         <div class="achievement-card">
          <div class="achievement-icon">
           💝
          </div>
          <p class="achievement-title">Donations</p>
          <p class="achievement-count" id="ach_donations">0</p>
         </div>
        </div>
       </div>
      </div><!-- Edit Profile Section -->
      <div class="profile-right-section">
       <div class="profile-card">
        <div class="profile-card-header">
         <h3>Edit Profile</h3><span class="card-icon-emoji">✏️</span>
        </div>
        <form id="profileForm" class="profile-form">
         <div class="form-group"><label for="profile_full_name">Full Name</label> <input type="text" id="profile_full_name" name="full_name" placeholder="Enter your full name" required>
         </div>
         <div class="form-group"><label for="profile_skills">Skills &amp; Expertise</label> <textarea id="profile_skills" name="skills" placeholder="e.g., First Aid, Rescue Operations, Medical Support" required style="min-height: 100px;"></textarea>
         </div>
         <div class="form-group"><label for="profile_availability">Availability</label> <input type="text" id="profile_availability" name="availability" placeholder="e.g., Weekends, Flexible Hours" required>
         </div>
         <div class="form-group"><label for="profile_password">Change Password</label> <input type="password" id="profile_password" name="password" placeholder="Leave blank to keep current password"> <small>Minimum 8 characters</small>
         </div><button type="submit" class="button button-primary" style="width: 100%;">Save Changes</button>
        </form>
       </div>
      </div>
     </div>
    </div>
   </section>
  </main>
  
  <!-- Hidden form for profile picture upload -->
  <form id="profilePictureForm" style="display: none;">
    <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
  </form>
  
  <footer>
   <p>© <!--?php echo date('Y'); ?--> Community Disaster Response</p>
  </footer>
  <script src="../../assets/libs/sweetalert.min.js"></script>
  <script>
        // Handle edit picture button click
        document.getElementById('editPictureBtn')?.addEventListener('click', function() {
            document.getElementById('profile_picture').click();
        });
        
        // Auto-submit when file is selected
        document.getElementById('profile_picture')?.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                document.getElementById('profilePictureForm').dispatchEvent(new Event('submit'));
            }
        });
    </script>
    <!-- SweetAlert v1 from CDN -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  <script src="../../assets/js/volunteer.js?v=1.0.4"></script>
 <script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'9cc04721846e06fc',t:'MTc3MDc3NTQ5MS4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>