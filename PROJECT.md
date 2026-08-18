# Departmental Project Library System

## Project Objective

Build a centralized, secure digital repository where students can submit their final academic projects, supervisors can review them, and the public can easily search and access approved materials using **PHP 8.x + MySQL + PDO**. This is a real, production-ready software project.

## Core Requirements

- One continuous codebase.
- Never regenerate previous modules; extend the existing project.
- Final deliverable: complete ZIP with source, SQL, assets, documentation.

## Technology

- PHP 8.x
- MySQL
- PDO
- HTML5
- CSS3
- Vanilla JavaScript
- Local Server Disk (for isolated secure storage)

## UI / UX Design System Constraints

**Aesthetic Goal**: The UI must be a minimalist, premium, handcrafted interface heavily inspired by Notion, Linear, and Vercel.

**STRICT "DO NOT" RULES (Zero Exceptions)**:

- NO box-shadows (surfaces must remain entirely flat).
- NO gradients (use solid colors exclusively).
- NO glassmorphism, blurring, or translucent overlays.
- NO CSS frameworks (Do NOT use Bootstrap, Tailwind, or Material UI).
- NO heavy or dark borders for layout boundaries.
- NO "weird grey" backgrounds in light mode. The application canvas MUST be pure white (`#ffffff`).
- NO "kissing" components. Never let layout elements visually touch; ensure generous margins and grid gaps (e.g., `gap: 32px`).
- NO horizontal scrolling for list/board data. Use CSS Grid `auto-fit` to wrap cards gracefully.

**Typography (Strict Hierarchy)**:

- **Headings (H1-H6)**: Use `Anton` font, `font-weight: 400`, `text-transform: uppercase`, `letter-spacing: 0.02em`.
- **Base Body Text**: Use `Inter` font, `font-weight: 400`.
- **Labels, Subtext & Intros**: Use `Inter` font, `font-weight: 300` (Crucial for a premium, lightweight editorial feel).
- **Eyebrows/Micro-labels**: `font-size: 11px`, `font-weight: 700`, uppercase, `letter-spacing: 0.12em`.

**Architecture & Layout**:

- **Borders**: Structure the entire layout exclusively using `1px solid var(--line)` borders.
- **Theming**: Implement a CSS Variable-based Light/Dark mode toggled via a `data-theme="dark"` attribute.
- **Panels & Cards**: Wrap content in flat structural cards using `background: var(--panel)`, `border-radius: 8px`, and `border: 1px solid var(--line)`.
- **Footer Mobile Layout**: Footer links must ALWAYS remain in a horizontal row (`flex-direction: row`), even on mobile viewports. Do not stack them vertically.
- **Iconography**: Liberally use 36px squared icon boxes with a light background and 1px border to anchor list items and actions.

**Forms & Buttons (Strictly Pill-Shaped)**:

- **Inputs & Textareas**: MUST be fully rounded (pill-shaped). Use generous padding (e.g., `14px 24px`), `border: 1px solid var(--line)`, and `border-radius: 9999px`.
- **Action Buttons & CTAs**: ALL primary buttons, links, and form submits MUST be fully rounded/pill-shaped (`border-radius: 9999px`). The 8px border-radius is strictly reserved for structural panels, NEVER buttons.
- **Focus States**: Remove default outline rings. Change the 1px border color to the primary brand accent color on focus.

## Modules

### Public

- Clean, highly indexable Homepage
- Full-Text Search Engine (Title, Abstract, Tags)
- Project Details Page
- Controlled Download Gateway

### Authentication

Roles: - Admin - Supervisor - Student

Features: - Register (Student self-register) - Login - Logout - Password hashing - Sessions - RBAC

Admins manage the creation and activation of Supervisor accounts.

### Student

- Dashboard
- Upload Project (PDFs, ZIPs, or Source Code Links)
- Input Metadata (Title, Abstract, Tags)
- Select Supervisor (Dynamic Dropdown)
- Submission Status Tracking

### Supervisor

- Dashboard (Isolated strictly to assigned workload)
- Review Pending Submissions
- Approve / Reject Action Toggle

### Admin

- Dashboard
- System-Wide Pending Reviews (Post-Supervisor Approval)
- Final Publish Action
- User Management
- Tag / Category Management
- System Settings

## Database

Normalized Relational Schema:

- Users Table (id, name, email, password_hash, role)
- Projects Table (id, title, abstract, student_id, supervisor_id, supervisor_status, status)
- Files Table (id, project_id, file_path, file_type)
- Tags Table (id, name)
- Project_Tags Table (junction table)
- Foreign Keys - Indexes - Constraints

## Security

- Prepared Statements (PDO)
- Strict MIME-Type & File Size Validation
- Randomized File Name Hashing
- Isolated Local Storage (Outside web root)
- Role-Based Route Protection Middleware
- XSS Protection

## Folder Structure

/config /components /helpers /middleware /assets /admin /student /supervisor /auth /database /uploads

## Configuration

Create config/connect.php with: - DB_HOST - DB_NAME - DB_USER - DB_PASS

## Deliverables

- Complete PHP source code
- SQL database file
- Assets & CSS files
- README / Setup Instructions
- Final ZIP for XAMPP / Standard Web Servers

## Development Plan

1.  Foundation & Database Schema
2.  Authentication & Access Control
3.  Student Portal & Secure Upload Engine
4.  Supervisor Assignment & Approval Flow
5.  Admin Moderation & Publish Workflow
6.  Public Library, Search, and Download Gateway
7.  Testing, Hardening, and Final Packaging

## Attached Reference Documents

Use these as authoritative functional references: - DPLS project scope
