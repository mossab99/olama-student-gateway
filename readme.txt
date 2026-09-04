=== OLAMA Student Gateway ===
Contributors: olama
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 0.2.0
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

= 0.2.0 =

* Introduced the redesigned family and student portal shell.
* Added capability-aware dashboard summaries for weekly plans, exams, transportation and stores.
* Improved the real family login screen and responsive navigation accessibility.
