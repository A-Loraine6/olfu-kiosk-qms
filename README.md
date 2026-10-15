# olfu-kiosk-qms

Welcome to our group project repository!  

## Project Overview

OLFU Kiosk QMS is a PHP and MySQL queue management system. Students get a
ticket at the kiosk and can track it; staff manage queues from their queue
page, while administrators configure offices, services, staff, and windows.

Offices can use one of three queue modes:

- **Unified:** issue tickets without asking the student to choose a service.
- **Service-based:** the student selects a service, and window/service
  assignments determine which staff can serve the ticket.
- **Level-based:** the kiosk asks whether the student is SHS or College.
  Office windows are assigned SHS, College, or Both and serve matching tickets.

Department heads are scoped to their own office. Staff queue and dashboard
views are personal where appropriate; administrators retain cross-office
access. Queue history records ticket events, and a scheduled cleanup can
cancel active tickets left over from a previous day.

### Recent Changes to Review

- Added the level-based queue mode, per-window level assignments, and the
  assignment modal on the Services page.
- Added level selection and level labels to the kiosk, queue, public display,
  and tracker. Queue eligibility, transfers, positions, and estimates account
  for the ticket's level.
- Restricted department-head staff editing and staff lists to their office,
  and adjusted role-specific dashboard/queue access.
- Updated queue history and stale-ticket cleanup behavior. Services search,
  office/category/status filters, and pagination are loaded asynchronously.

### Database Setup for These Changes

Apply these migrations to the application database before testing the related
features:

- `migrations/20261015_create_ticket_events.sql` for ticket event history.
- `migrations/20261016_add_level_based_queue_mode.sql` for level-based queues.

The queue-history migration is safe to rerun. Verify the level-mode migration
against the target database before deploying, and take a backup before schema
changes.

### Manual Test Cases

Use test accounts for admin, department head, and staff roles, plus test
offices in each queue mode. Reset test data or use fresh tickets between cases.

| ID | Area | Test | Expected result |
|---|---|---|---|
| T1 | Services filters | Search by a service name, then combine office, category, and status filters. Change page size/page; clear the search. | Each selection refreshes the rows and result count; combined filters narrow results; clearing restores matching rows. No results shows an empty state. |
| T2 | Services filter permissions | As a department head, try changing the office filter in the request or browser. | Results remain limited to the signed-in user's office. |
| T3 | Staff scope | As a department head, list staff, edit a staff member in the office, then attempt to edit a staff member from another office. Try posting role/office/status fields as well. | Only own-office staff are listed and editable; foreign-office edits are rejected; only name, email, and window assignment can change. Admin retains cross-office controls. |
| T4 | Role access | Sign in as staff and department head with their configured queue permissions. Try direct navigation and submit a queue-management API request from a view-only account. | Authorized queue pages load; view-only users cannot call, skip, or otherwise manage tickets through either the UI or a crafted request. |
| T5 | Personal dashboard | Compare staff dashboard metrics and average service time with department-head metrics. | Staff metrics/average reflect that staff member; department-head office summaries cover their office only. |
| T6 | Existing queue modes | Issue and serve tickets in unified and service-based offices, including services assigned to specific windows and services available at all windows. | Existing ticket issuance and routing still work; an unassigned service remains available to all eligible windows. |
| T7 | Level-mode configuration | As admin, switch an idle office to level-based, open the window-level modal, assign SHS/College/Both, save, reopen, and verify saved selections. Also try switching modes while today's queue has active tickets. | Assignments persist; the mode change is blocked while today's active queue is non-empty and allowed when it is clear. |
| T8 | Level-mode issuance and routing | Issue SHS and College tickets. Use SHS-only, College-only, and Both windows to view/call next. Attempt issuance without a level. | Level is required and stored; each window only sees/calls matching tickets; Both can serve either level. Ticket numbering remains one sequence per office. |
| T9 | Level-mode tracking and transfer | Check ticket level on the queue card, public display, and tracker. Compare position/estimate with tickets in the other level. Transfer a ticket into a level-based office and out to another mode. | Level is visible; position and estimate count only the same level lane in queue order; transfer requires/selects a destination level where applicable and keeps ticket data valid. |
| T10 | Peak Hours and completed history | Enable Peak Hours, serve a ticket whose service is not assigned to the staff member's window, then check the completed tab. | The ticket is served according to Peak Hours and remains visible in that staff member's completed history. |
| T11 | Ticket lifecycle/history | Put a ticket on hold, resume it, skip it, cancel it, and complete it. Inspect the relevant queue tabs/history. | Tickets remain visible in the appropriate state/tab and event history reflects each transition. |
| T12 | Stale-ticket cleanup | In a test database, create active tickets dated before today and run `scripts/cancel_stale_tickets.php`. | Prior-day active tickets are cancelled; current-day tickets and already completed/cancelled tickets remain unchanged. |

## How to Set Up the Project (First Time)  

### 1. Clone the Repository  
Make sure you have **Git** installed on your computer.

Download Git:
https://git-scm.com/

Open **Git Bash** and go to your `htdocs` folder (inside your XAMPP directory):  

change d to c if your xampp is in c drive.

`cd /d/xampp/htdocs`

`git clone https://github.com/A-Loraine6/olfu-kiosk-qms.git`  

### 2. Open the Project in VS Code  
Open Visual Studio Code.  
Go to File → Open Folder.  
Choose the folder SugarScoop.

## Commands  

### Queue history schema

Before deploying the queue history changes, apply
`migrations/20261015_create_ticket_events.sql` to the application database.
The migration is safe to rerun: it adds/backfills `original_created_at` only
when needed and creates `ticket_events` if it does not already exist.

### Expire Queue Tickets (Hostinger Cron)

Tickets still waiting, on hold, skipped, or serving from a previous day are
marked cancelled by `scripts/cancel_stale_tickets.php`. In Hostinger hPanel,
open **Advanced → Cron Jobs** and create a daily job shortly after midnight
(for example, 12:05 AM). Use a command like:

```sh
/usr/bin/php /home/USERNAME/domains/DOMAIN/public_html/scripts/cancel_stale_tickets.php
```

Replace `USERNAME`, `DOMAIN`, and the script path with the absolute paths for
your Hostinger account and deployment. If Hostinger specifies a different PHP
CLI path, use that path instead. Set the schedule to `5 0 * * *` (server time)
and check the cron job output/logs after its first run. The cleanup uses the
database session timezone configured by the application.

### Making Changes and Uploading to GitHub  
Check your current branch  
`git branch`  

### Pull the latest changes before editing  
`git pull origin main`  

## Working with Branches (For the Project / Backlog)  

### 1. Create a new branch
If you are assigned a task, create a branch for it:  
<img width="1360" height="642" alt="image" src="https://github.com/user-attachments/assets/b58e8205-29b4-469d-814b-f5ee01bef2b2" />
<img width="1360" height="637" alt="image" src="https://github.com/user-attachments/assets/469d0648-d3ab-48fd-8177-1c92ed1d79cf" />

Assign yourself if no one is assigned  
<img width="1347" height="645" alt="image" src="https://github.com/user-attachments/assets/f6a7de8f-1305-4d0c-957d-508242ab382a" />

Click create a branch  
<img width="1347" height="631" alt="image" src="https://github.com/user-attachments/assets/79b2885f-896a-4f46-bab9-662c4b0ea1ad" />
<img width="1350" height="628" alt="image" src="https://github.com/user-attachments/assets/0dc36a19-eade-4884-b4d4-7f02719e42c2" />

Paste this in your terminal
<img width="1345" height="632" alt="image" src="https://github.com/user-attachments/assets/17a1a2ad-be6a-4f76-af05-112d9ad45dc8" />

### 2. Make your Changes

### 3. Add and Commit your changes
`git add .`  
`git commit -m "Describe what you changed / Put the branch of the title."`  

### 4. Push your branch to GitHub
`git push origin branch-name`  

### 5. Switching Back to Main
`git checkout main`  
`git pull origin main`  
