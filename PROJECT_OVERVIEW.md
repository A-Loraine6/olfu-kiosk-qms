# OLFU Kiosk Queue Management System (QMS)

A kiosk-based queue ticketing system for **Our Lady of Fatima University (OLFU)**. Students and visitors obtain queue numbers from touch-screen kiosk terminals, track their queue position in real-time via QR code, and staff manage queues, serve tickets, and generate reports.

---

## Technology Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2 (vanilla, no framework) |
| Frontend | HTML5, CSS3, vanilla JavaScript |
| Database | MySQL/MariaDB 10.4.32 |
| Web Server | Apache (XAMPP) |
| Package Manager | Composer |
| PDF Export | setasign/fpdf ^1.8 |
| Charts | Chart.js 4.4.0 |
| QR Codes | qrcodejs 1.0.0 |
| Campus Map | Custom SVG overlay with geolocation calibration |
| Notifications | Web Push API + Service Worker |
| Audio | Web Audio API (oscillator-based sounds) |
| Fonts | Plus Jakarta Sans, DM Sans, DM Mono (Google Fonts) |
| Icons | Font Awesome 6.4.0 |

---

## Directory Structure

```
olfu-kiosk-qms/
├── api/                    # Backend API handlers
│   ├── utils/              # Helper classes (TicketGenerator)
│   ├── activity_logger.php
│   ├── archive_*.php
│   ├── display_data.php
│   ├── export_pdf.php
│   ├── feedback_*.php
│   ├── get_staff*.php
│   ├── issue_ticket.php
│   ├── kiosk_status.php
│   ├── preview_ticket.php
│   ├── profile_actions.php
│   ├── queue_handler.php
│   ├── reports_actions.php
│   ├── role_actions.php
│   ├── service_*.php
│   ├── settings_*.php
│   ├── staff_*.php
│   └── track_status.php
├── config/
│   └── config.php          # DB credentials and constants
├── css/                    # Stylesheets (14 files)
├── img/                    # Images (logos, backgrounds)
├── includes/               # Shared PHP includes
│   ├── auth.php            # Authentication, sessions, RBAC
│   ├── db.php              # MySQLi database wrapper
│   ├── empty_state.php     # Reusable empty-state component
│   ├── queue_functions.php # Queue summary/trend helpers
│   └── sidebar.php         # Permission-aware navigation
├── map/                    # SVG campus map tiles
├── scripts/                # JavaScript files (14 files)
├── vendor/                 # Composer dependencies
├── !db/                    # Database SQL dumps & exported PDFs
├── composer.json
└── README.md
```

---

## Pages & Views

### Public-Facing

| File | Purpose |
|---|---|
| `index.php` | Kiosk touch-screen interface. 4-step wizard (priority → office → service → confirm), issues ticket with QR code, idle screen overlay with auto-reset countdown. |
| `track.php` | Customer ticket tracking via QR code (64-char hex token). Shows queue position, estimated wait, now-serving number, cancel option, campus map tab, push notification subscription. |
| `display.php` | Public display monitor. Shows "Now Serving", "Next Up", and "Recently Done" per office. Auto-refreshes every 5 seconds. |

### Staff

| File | Purpose |
|---|---|
| `staff_dashboard.php` | Staff-scoped dashboard with queue stats and trend charts for assigned office. |
| `queue.php` | Staff queue management panel. Serve next, skip, hold, recall, complete tickets. Supports unified and service-based queue modes with overflow toggle. |

### Admin

| File | Purpose |
|---|---|
| `dashboard.php` | Admin dashboard with queue summary cards, office distribution pie chart, trend line chart. |
| `staff.php` | Staff CRUD management. Add, edit, activate/deactivate, soft-delete, reset passwords. Search, filter, pagination. |
| `services.php` | Service/transaction type CRUD per office. Assign services to window numbers. Categories: simple/regular/complicated. Soft-delete. |
| `roles.php` | Role & permission management. Create/edit/delete roles with 15+ fine-grained permissions. |
| `settings.php` | System settings per office: schedule (open/close/break times), queue config (prefix, start number, mode, limits), kiosk settings (enable/disable, idle timeout). |
| `reports.php` | Reports & analytics with date range filtering, per-office breakdowns, staff performance metrics, PDF export. Three access tiers: chain-full, office-scoped, personal. |
| `archive.php` | Restore soft-deleted staff and services. |
| `profile.php` | User profile: edit name/email, change password, view activity audit logs with infinite scroll. |

### Authentication

| File | Purpose |
|---|---|
| `login.php` | Staff/admin login page with split-panel layout. CSRF-protected. |
| `forgot_password.php` | Requests a six-digit, time-limited password recovery OTP and sets a new password. |
| `reset_password.php` | Legacy reset-link endpoint that redirects to the OTP recovery page. |
| `logout.php` | Logs activity, destroys session, redirects to login. |

### Password recovery email configuration

Password recovery uses PHPMailer over SMTP to send a six-digit OTP. Configure these environment variables on the web server before enabling OTP recovery:

`QMS_MAIL_HOST`, `QMS_MAIL_PORT`, `QMS_MAIL_USERNAME`, `QMS_MAIL_PASSWORD`, `QMS_MAIL_ENCRYPTION`, `QMS_MAIL_FROM_ADDRESS`, and `QMS_MAIL_FROM_NAME`.

If environment variables are not available, edit the matching `MAIL_*` constants directly in the ignored `config/config.php` file. Use `config/mail.example.php` as a template. For Gmail, use a Google app password, not the normal account password. Never commit real SMTP credentials.

The database dump already includes the `users.password_reset_token` and `users.password_reset_expires` columns used by the flow. OTPs are stored as SHA-256 hashes and expire after 10 minutes.

---

## API Endpoints

| Endpoint | Method | Auth | Purpose |
|---|---|---|---|
| `api/issue_ticket.php` | POST | No | Issue queue ticket from kiosk. Validates office hours, breaks, cutoffs, queue limits. Named lock prevents race conditions. |
| `api/preview_ticket.php` | POST | No | Preview next ticket number before issuing. |
| `api/queue_handler.php` | POST | Staff | Queue operations: get_stats, get_active, get_queue, call_next, serve_ticket, complete_ticket, skip_ticket, recall_ticket, hold_ticket, resume_ticket, toggle_overflow. |
| `api/track_status.php` | GET/POST | Token | Track ticket status by token. Shows position, now-serving, estimated wait. Also handles ticket cancellation. |
| `api/display_data.php` | GET | No | Per-office display data: now serving, next up, recently done, waiting count. |
| `api/kiosk_status.php` | GET | No | Returns kiosk enabled/disabled state and idle timeout. |
| `api/profile_actions.php` | POST | Yes | Update profile and change password. |
| `api/role_actions.php` | POST | Admin | Role CRUD and permission management. |
| `api/staff_actions.php` | POST | Yes | Staff CRUD: add, edit, toggle_status, delete, reset_password. |
| `api/service_actions.php` | POST | Yes | Service CRUD: add, edit, toggle_status, delete. |
| `api/settings_actions.php` | POST | Manage Settings | Schedule, queue config, kiosk settings, window count updates. |
| `api/reports_actions.php` | POST | Yes | Queue data, staff performance, service breakdowns, office stats by date range. |
| `api/export_pdf.php` | POST | Yes | PDF report generation (office, queue, staff reports) via FPDF. |
| `api/feedback_actions.php` | POST | Token | Submit customer feedback (rating, comment). |
| `api/archive_actions.php` | POST | Admin | Restore deleted staff or services. |
| `api/get_staff.php` | POST | Yes | Get single staff member by ID. |
| `api/get_staff_logs.php` | POST | Yes | Get activity logs for a staff member. |

---

## Database Schema

### Core Tables

| Table | Purpose |
|---|---|
| `offices` | University offices with queue config, schedule, and location info |
| `users` | Staff/admin accounts with role-based access, office/window assignment |
| `tickets` | Queue tickets with tracking token, status (waiting/serving/completed/skipped/on-hold), timing data |
| `transaction_types` | Service types per office (simple/regular/complicated categories) |
| `service_windows` | Maps services to specific window numbers for service-based queues |
| `sessions` | PHP session tracking in DB |
| `kiosk_settings` | Global kiosk configuration (single row) |

### RBAC Tables

| Table | Purpose |
|---|---|
| `roles` | Role definitions (admin, Staff, Supervisor + custom) |
| `permissions` | 15+ fine-grained permissions grouped by module |
| `role_permissions` | Many-to-many mapping of roles to permissions |

### Audit Table

| Table | Purpose |
|---|---|
| `activity_logs` | Comprehensive audit trail with old/new JSON diff, IP, user agent |

### Entity Relationships

```
offices ──1:N──> users
offices ──1:N──> tickets
offices ──1:N──> transaction_types
offices ──1:N──> service_windows
users   ──1:N──> tickets (served_by)
users   ──1:N──> activity_logs
roles   ──1:N──> users
roles   ──N:M──> permissions (via role_permissions)
transaction_types ──1:N──> service_windows
```

---

## Key Features

- **Kiosk Ticket Issuance** — 4-step wizard (priority → office → service → confirm), QR code with tracking token, auto-reset countdown
- **Idle Screen** — Full-screen overlay with university branding, clock, and "Tap anywhere to begin" prompt
- **Queue Management** — Call Next, Serve, Skip, Hold/Resume, Complete, Recall. Two modes: unified (single queue) and service-based (window-filtered)
- **Overflow/Peak Hours Mode** — Staff toggle to pull from all services regardless of window assignment
- **Real-Time Display** — Public monitor auto-refreshes every 5s showing "Now Serving" per office
- **Customer Tracking** — Mobile-friendly page via QR code with position, wait time, push notifications, campus map, feedback, ticket cancellation
- **Campus Map** — Custom SVG map of OLFU Valenzuela campus with GPS geolocation, building search
- **RBAC** — 3 default roles + custom roles with 15+ fine-grained permissions
- **Reports & Analytics** — Date-range reports, staff performance, service breakdowns, PDF export
- **Settings** — Per-office schedule, queue config, queue mode, kiosk enable/disable, idle timeout
- **Activity Audit** — Every admin/staff action logged with old/new JSON diff
- **Notification Sounds** — Web Audio API oscillator-based queue alerts

---

## Security

- CSRF protection on all state-changing requests
- Session fingerprinting (user-agent + IP hash)
- Password hashing with Argon2id (fallback to bcrypt with auto-rehash)
- Prepared statements (MySQLi parameterized queries)
- Named DB locks (`GET_LOCK`/`RELEASE_LOCK`) for concurrent ticket generation
- Role-based access control with 15+ permissions
- Rate limiting via file-based cache on track_status.php (5-second cache)
- Login attempt throttling (5 attempts within 15-minute window)
- Session timeout (absolute 3600s + inactivity 3600s)
- Input sanitization (htmlspecialchars, strip_tags, trim, validation)

---

## Architecture Overview

```
┌─────────────────────────────────────────────────┐
│  KIOSK TERMINAL (index.php)                     │
│  Public touch screen → ticket + QR code         │
└───────────────────┬─────────────────────────────┘
                    │ QR code (64-char token)
                    ▼
┌─────────────────────────────────────────────────┐
│  CUSTOMER TRACKING (track.php)                  │
│  Phone → polls track_status.php every 8s        │
│  Position, wait time, map, push notifications   │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  PUBLIC DISPLAY (display.php)                   │
│  Large monitor → polls display_data.php every 5s│
│  "Now Serving" per office                       │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  STAFF QUEUE (queue.php)                        │
│  Call Next / Serve / Skip / Hold / Complete     │
│  Polls queue every 5s via queue_handler.php     │
│  Unified or service-based with overflow mode    │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  ADMIN PANEL (dashboard.php + others)           │
│  Charts, staff/services/roles CRUD, settings,   │
│  reports with PDF export, activity audit        │
└─────────────────────────────────────────────────┘
```

### Data Flow Pattern

All admin/staff pages follow a consistent pattern:
1. **PHP page** includes `config.php` → `db.php` → `auth.php`
2. Checks authentication and RBAC permissions
3. Loads initial data server-side (rendered as JSON in `<script>` tags or inline HTML)
4. **Dedicated CSS file** + shared `style.css` + `dashboard.css`
5. **Sidebar** included via `includes/sidebar.php` (permission-aware)
6. **Dedicated JS file** handles client-side interactions
7. JS calls **API endpoints** via `fetch()` with CSRF token
8. API returns JSON, JS updates DOM and shows toasts
