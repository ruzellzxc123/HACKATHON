# Teacher Evaluation System

A role-based web application for evaluating university teachers with composite scoring, real-time dashboards, audit trails, and AI-powered performance summaries via Google Gemini Flash 2.5.

## Features

- **Role-based login**: Student / Program Head / Dean / Admin
- **Separate evaluation forms** per rater with weighted criteria:
  - Students (50%): Teaching Clarity, Engagement, Fairness
  - Program Head (30%): Curriculum, Assessment, Mentoring
  - Dean (20%): Attendance, Commitment, Quality
- **Composite scoring**: Weighted average dashboard with Chart.js
- **Anonymity for students**: Student evaluations are anonymous (rater_id = NULL)
- **Audit trail**: Every login, evaluation, export, and admin action is logged
- **Real-time dashboards**: Bar charts and doughnut charts per teacher/program
- **PDF/CSV export**: Scores, audit logs, and printable reports
- **Automated email reminders**: Queue-based reminder system
- **AI-Generated Performance Summaries**: Uses Google Gemini Flash 2.5 to analyze evaluation data and generate strengths, weaknesses, and actionable recommendations

## Quick Start

1. Start Apache and MySQL in XAMPP
2. Open `http://localhost/HACKATHON/setup.php` to create the database
3. Login with demo accounts (password for all: **password**):
   - **Admin**: admin@school.edu
   - **Dean**: dean@school.edu
   - **Program Head**: ph@school.edu
   - **Student**: student@school.edu

## Gemini AI Setup (Optional)

To enable AI-generated teacher performance summaries:

1. Get a free API key from [Google AI Studio](https://aistudio.google.com/app/apikey)
2. Open `config/config.php`
3. Replace the empty API key:
   ```php
   define('GEMINI_API_KEY', 'YOUR_API_KEY_HERE');
   ```
4. The model is set to `gemini-flash-2.5` by default. You can change it if needed:
   ```php
   define('GEMINI_MODEL', 'gemini-flash-2.5');
   ```

### How AI Summaries Work

- Navigate to **Reports** and select a teacher + evaluation period
- Click **"Generate AI Summary"**
- The system sends all evaluation scores, comments, and metadata to Gemini Flash 2.5
- Gemini generates a structured report with:
  - **Executive Summary** - Overall performance assessment
  - **Key Strengths** - Evidence-based strengths with score citations
  - **Areas for Improvement** - Constructive growth areas
  - **Actionable Recommendations** - Specific steps for improvement
  - **Conclusion** - Encouraging closing statement
- The summary is saved to the database and included in PDF exports

## File Structure

```
HACKATHON/
  config/config.php          # DB config, constants, Gemini API key
  includes/
    db.php                   # PDO connection
    functions.php            # Scoring engine, audit log, auth helpers
    gemini.php               # Gemini API integration & prompt builder
  sql/schema.sql             # Full DB schema + seed data
  assets/css/style.css       # Responsive styling
  exports/
    csv_export.php           # CSV download (scores/audit)
    pdf_export.php           # Print-friendly PDF with AI summary
  cron/reminders.php         # CLI cron for email reminders
  admin/
    teachers.php             # Manage faculty
    periods.php              # Evaluation windows
    users.php                # Role management
    audit.php                # View audit logs
    reminders.php            # Queue/send email reminders
  index.php                  # Login
  dashboard.php              # Role-based dashboard with Chart.js
  evaluate.php               # Anonymous, role-specific evaluation forms
  reports.php                # Teacher reports with AI summary generation
  setup.php                  # One-click DB initializer
```

## Security

- Sensitive directories (`config/`, `includes/`, `sql/`, `cron/`) blocked via `.htaccess`
- All DB queries use prepared statements (PDO)
- Passwords hashed with `password_hash()` (PASSWORD_BCRYPT)
- Session-based authentication with role validation

## Cron Setup (Optional)

For automated email reminders, set up a cron job or Windows Task Scheduler to run:

```bash
php cron/reminders.php
```

## Tech Stack

- **Backend**: PHP (vanilla), MySQL, PDO
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Charts**: Chart.js (CDN)
- **AI**: Google Gemini Flash 2.5 API (v1beta)

