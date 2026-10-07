# Smart Study Hub – Study Group & Peer Notes Sharing Portal

An academic collaboration web portal engineered for college students to organize course-specific study groups, upload and download peer-reviewed lecture notes and PDFs, discuss curriculum doubts, and prepare effectively for semester examinations.

---

## 1. Technology Stack

* **Frontend:** HTML5, CSS3 (CSS Variables, Flexbox, CSS Grid), Vanilla JavaScript (ES6+), Font Awesome 6.4, Google Fonts (Plus Jakarta Sans & JetBrains Mono).
* **Backend:** PHP 8+ (PDO with prepared statements, session management, CSRF tokens).
* **Database:** MySQL 8+ / MariaDB (`smart_study_hub`).
* **Server Environment:** Apache (via XAMPP).
* **Security Layer:** Prepared SQL statements, `password_hash()` / `password_verify()`, `htmlspecialchars()` XSS escaping, CSRF verification tokens, strict MIME-type and extension validation, `.htaccess` upload script execution restriction.

---

## 2. Default Login Credentials (Demo & Viva)

The system comes pre-seeded with realistic sample accounts and data for demonstration:

### Administrator Account
* **URL:** `http://localhost/smart_study_hub/admin/login.php`
* **Email:** `admin@smartstudy.edu`
* **Password:** `Admin@123`
* **Role:** System Administrator

### Student Test Accounts
* **URL:** `http://localhost/smart_study_hub/login.php`
* **Student 1 (Rahul Sharma):** `rahul.sharma@college.edu` | `Student@123` (Dept: CSE, Sem 5)
* **Student 2 (Priya Patel):** `priya.patel@college.edu` | `Student@123` (Dept: CSE, Sem 5)
* **Student 3 (Ananya Sen):** `ananya.sen@college.edu` | `Student@123` (Dept: IT, Sem 5)
* **Student 4 (Arjun Verma):** `arjun.verma@college.edu` | `Student@123` (Dept: ECE, Sem 3)

*(Note: The login pages also feature one-click demo credential autofill buttons for fast grading and testing)*

---

## 3. Project Directory Structure

```
smart_study_hub/
├── index.php                 # Attractive landing page with metrics & popular materials
├── login.php                 # Student login with session authentication
├── register.php              # Student registration with validation
├── logout.php                # Session destroyer and logout handler
├── dashboard.php             # Personalized student dashboard & activity feed
├── subjects.php              # College curriculum subjects directory with instant JS filtering
├── notes.php                 # Notes & PDFs directory with sorting, filtering, bookmarks
├── upload_note.php           # Upload portal with client-side drag-and-drop preview
├── note_details.php          # Note inspection, ratings (1-5 stars), download counter
├── download.php              # Secure file downloader with download counter increment
├── bookmarks.php             # Personal saved bookmarks collection
├── groups.php                # Study groups directory with capacity meters & modal view
├── create_group.php          # Form to launch new study groups (creator becomes owner)
├── group.php                 # Dedicated group portal with notices & threaded discussions
├── profile.php               # Student profile editor, stats, password update
├── notifications.php         # Real-time activity notifications center
├── search.php                # Global search across subjects, notes, and study groups
├── study_tips.php            # Evidence-based learning guide + Interactive Pomodoro timer
├── rules.php                 # Academic integrity and community code of conduct
├── config/
│   └── database.php          # Database PDO connection & global configuration
├── includes/
│   ├── header.php            # Global responsive header, navbar, notification bell
│   ├── footer.php            # Global footer, group modal, report modal, toast container
│   ├── auth.php              # Access control, role verification, session helpers
│   └── functions.php         # Helper functions (CSRF, sanitization, relative time, etc.)
├── api/
│   ├── join_group.php        # AJAX: Join study group
│   ├── leave_group.php       # AJAX: Leave study group
│   ├── bookmark.php          # AJAX: Toggle note bookmark
│   ├── rate_note.php         # AJAX: Rate note 1-5 stars & update average
│   ├── notifications.php     # AJAX: Mark notifications read
│   ├── group_details.php     # AJAX: Modal data provider
│   ├── group_post.php        # AJAX: Post discussion messages & replies
│   └── report.php            # AJAX: Submit content violation reports
├── admin/
│   ├── login.php             # Admin authentication gateway
│   ├── logout.php            # Admin session termination
│   ├── dashboard.php         # System analytics, pending flags, registration stats
│   ├── users.php             # Student roster management (activate / suspend / delete)
│   ├── subjects.php          # College curriculum CRUD management
│   ├── notes.php             # Uploaded materials management & file purger
│   ├── groups.php            # Study squad administration & archival
│   ├── reports.php           # Moderation queue to review flags & resolve disputes
│   ├── settings.php          # Portal preferences & server diagnostics
│   ├── header.php            # Admin sidebar navigation layout
│   └── footer.php            # Admin footer container
├── assets/
│   ├── css/
│   │   ├── style.css         # Modern design system (CSS variables, responsive UI)
│   │   └── admin.css         # Dedicated admin sidebar and table layout
│   └── js/
│       └── script.js         # Client-side dynamic filters, file preview, modals, toasts
├── uploads/
│   ├── notes/                # Stored note files (PDF, DOCX, PPTX)
│   └── .htaccess             # Apache security block preventing script execution
└── database/
    ├── smart_study_hub.sql   # Complete database schema, foreign keys, & sample records
    └── generate_sample_files.php # Script to populate valid demo PDF files
```

---

## 4. XAMPP Setup & Installation

### Step 1: Copy Project Folder
The application is located at:
`C:\xampp\htdocs\smart_study_hub\`

If running from a clone or download, copy the `smart_study_hub` folder into your XAMPP Apache root:
`C:\xampp\htdocs\`

### Step 2: Start Apache and MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 3: Database Import (via phpMyAdmin or MySQL CLI)

#### Option A: Using phpMyAdmin
1. Open your browser and navigate to: `http://localhost/phpmyadmin/`
2. Click on the **Import** tab in the top navigation.
3. Click **Choose File** and select:
   `C:\xampp\htdocs\smart_study_hub\database\smart_study_hub.sql`
4. Click **Go** (or **Import**) at the bottom.
5. The database `smart_study_hub` will be created with all 12 tables and realistic seed data.

#### Option B: Using Command Line (Fast)
Open Command Prompt and run:
```cmd
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\smart_study_hub\database\smart_study_hub.sql
```

### Step 4: Access the Website
Open your web browser and navigate to:
* **Student Portal:** `http://localhost/smart_study_hub/`
* **Admin Portal:** `http://localhost/smart_study_hub/admin/login.php`

---

## 5. Major Features Walkthrough (For College Viva & Presentation)

1. **Authentication & Session Security:**
   * Uses standard `password_hash()` with BCRYPT.
   * `session_regenerate_id()` is invoked on login to prevent session fixation attacks.
   * Role-based access control separates `student` and `admin` privileges.

2. **Course Subjects Directory:**
   * Filter subjects instantly by typing keywords or selecting department and semester dropdowns without reloading the page.
   * Displays course code, department, total notes count, and active study groups count.

3. **Notes Upload & Live File Preview:**
   * Students can upload PDF, DOC, DOCX, PPT, PPTX up to 25 MB.
   * Client-side JavaScript provides immediate file preview (file name, formatted size, type icon, ready-to-upload indicator).
   * Backend verifies MIME type, sanitizes filenames, stores securely in `uploads/notes/`, and records metadata in MySQL.

4. **Secure File Downloader:**
   * Handled via `download.php` which increments the database download counter each time a document is fetched, and streams the file with appropriate binary transfer headers.

5. **Study Squads (Groups) & AJAX Modal:**
   * Students can browse squads, check member capacity progress bars, and click "View Details" to open a modal with the roster and meeting preferences.
   * "Join" and "Leave" actions use AJAX to update MySQL without full page refresh.
   * Creators are automatically granted `owner` admin status for their group.

6. **Interactive Group Hub (`group.php`):**
   * **Announcements:** Squad owners can post important meeting dates or exam notices. Members receive automated notifications.
   * **Threaded Discussions:** Members can post questions, reply directly to specific peers (parent-child comment nesting), and delete their own messages.
   * **Course Notes Integration:** Displays direct download links to notes matching that subject.

7. **Ratings & Bookmarks:**
   * 1–5 star interactive rating system with dynamic average recalculation.
   * Bookmarking system prevents duplicate entries using MySQL UNIQUE constraints.

8. **Notification Engine:**
   * Real-time notifications alert users when someone rates their note, joins their study group, replies to their discussion thread, or posts an announcement.
   * Navbar features a notification bell with an unread badge counter and quick-view dropdown.

9. **Interactive Study Tools:**
   * `study_tips.php` includes an interactive JavaScript Pomodoro Timer widget with 25-minute study sprints and 5-minute break cycles.

10. **Admin Oversight & Moderation Center:**
    * Comprehensive admin dashboard displaying system-wide metrics and curriculum charts.
    * Full CRUD management for students, subjects, uploaded notes, and study squads.
    * Moderation queue (`admin/reports.php`) allows administrators to review flagged notes or discussions, dismiss reports, or purge offending files from disk directly.
