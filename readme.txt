=== OLAMA Student Gateway ===
Contributors: olama
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 0.8.9
License: GPLv2 or later

Family-first, read-only portal for information produced by OLAMA Core, OLAMA Users, OLAMA School and other OLAMA service plugins.

== Installation ==

1. Activate OLAMA Core and OLAMA Users.
2. Activate OLAMA Student Gateway.
3. Grant the gateway capabilities to the intended family role in OLAMA Users.
4. Add `[olama_student_gateway]` to the portal page. The legacy `[olama_family_gateway]` shortcode is also supported.

== Security model ==

The plugin never accepts a family identifier from the browser. It resolves the family from the authenticated OLAMA Users identity and validates every selected student through OLAMA Core ownership data.

== Provider contracts ==

External plugins may register a provider on `olama_student_gateway_register_providers` or publish module data through these filters:

* `olama_student_gateway_exams_data`
* `olama_student_gateway_evaluations_data`
* `olama_student_gateway_attendance_data`
* `olama_student_gateway_transportation_data`
* `olama_student_gateway_messages_data`

== Changelog ==

= 0.8.9 =
* Simplifies sidebar student links to a single first-name, grade, and section line.

= 0.8.8 =
* Shows first names with grade and section in student lists.
* Consolidates shared transportation into one first-student card and marks unregistered families as walking.

= 0.8.7 =
* Replaces the sidebar family summary with a linked student list.
* Adds responsive family-profile and student transportation cards to the family page.

= 0.8.6 =
* Replaces technical online-exam statuses with parent-friendly availability labels and priority ordering.

= 0.8.5 =
* Simplifies the dashboard to today's homework, today's plan, and an exam-count date range.
* Removes weekly-plan previews, transportation, and school-supplies cards from the dashboard.

= 0.8.4 =
* Renders each exams navigation item as its own focused page instead of a section within one long page.

= 0.8.3 =
* Separates exams from performance, with direct navigation to the schedule, online exams, online results, and exam hall.

= 0.8.2 =
* Organizes the student navigation into family information, daily follow-up, video, performance, and messages groups.
* Adds inbox and compose modes for message providers.

= 0.8.1 =
* Replaces the compressed mobile electronic-exams table with readable RTL exam cards and full-width actions.

= 0.6.0 =
* Uses the canonical OLAMA School shortcode reports for weekly plans, class schedules, and exam schedules.
* Corrects the teachers and office-hours RTL alignment.
* Places the electronic-exam action first and clarifies the start/resume label.

= 0.5.0 =
* Introduces the navy, teal, and gold responsive visual system with Readex Pro typography.
* Refines the parent-facing dashboard, mobile navigation, cards, controls, and accessibility states.

= 0.4.6 =

* Standardized Arabic family terminology on «عائلة» instead of «أسرة».

= 0.4.5 =

* Prints a direct versioned gateway stylesheet link on embedded exam routes when WordPress incorrectly considers an omitted style handle complete.

= 0.4.4 =

* Loads jQuery and the Exam Engine dependency graph before the page head for embedded exam and result views.

= 0.4.3 =

* Ensures gateway and Dashicons styles are printed when access plugins render the shortcode after the page head.

= 0.4.2 =

* Runs the Exam Engine inside the existing gateway page instead of relying on an optional `/exams/` page.
* Preserves the selected student when returning from the Exam Engine.

= 0.4.1 =

* Added a secure start/resume action for available electronic exams in the student gateway.
* Shows clear upcoming, ended, and unavailable states outside the exam window.

= 0.4.0 =

* Added a student-scoped video library for the active year, semester, grade, section and scheduled subjects.
* Shows only videos published by the guardian-safe OLAMA Media Library service.

= 0.3.0 =

* Added the student's weekly class schedule from OLAMA School.
* Added the teachers assigned to the student's class and their current office hours.
* Improved the weekly plan presentation and Arabic day labels.

= 0.2.2 =

* Granted family roles every Student Gateway section by default while preserving administrator-controlled revocation.

= 0.2.1 =

* Seeded the existing read-only weekly plan, exams, transportation and stores services for family roles while preserving administrator-controlled revocation.

= 0.2.0 =

* Introduced the redesigned family and student portal shell.
* Added capability-aware dashboard summaries for weekly plans, exams, transportation and stores.
* Improved the real family login screen and responsive navigation accessibility.
