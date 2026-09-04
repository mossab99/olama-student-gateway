# OLAMA Student Gateway architecture

## Responsibility

The Student Gateway is a presentation and orchestration plugin. It owns no family, student, academic, examination, financial, transportation, inventory, evaluation, attendance, or messaging source records.

The authenticated WordPress user is resolved through `olama_users_get_identity()`. Only an active `family` identity is accepted. Its `oracle_identifier` is used to load the canonical family from `olama_core()->families()`.

The gateway never reads a family number from a request. A requested `student_uid` is accepted only after both of these checks pass:

1. The student occurs in the canonical family member collection.
2. `olama_core()->students()->belongs_to_family()` confirms the relationship.

## Access control

OLAMA Users owns role and capability assignment. The gateway declares these capabilities:

| Capability | Purpose |
| --- | --- |
| `olama_student_gateway_access` | Enter the gateway |
| `olama_student_gateway_family_view` | View family profile fields |
| `olama_student_gateway_finance_view` | View family financial information |
| `olama_student_gateway_weekly_plan_view` | View approved weekly plans |
| `olama_student_gateway_exams_view` | View exams, hall and released results |
| `olama_student_gateway_evaluations_view` | View published evaluations |
| `olama_student_gateway_attendance_view` | View attendance |
| `olama_student_gateway_transportation_view` | View transportation |
| `olama_student_gateway_stores_view` | View issued supplies and books |
| `olama_student_gateway_messages_view` | View messages when the service exists |

Capabilities decide feature access. Family ownership validation always remains mandatory for record-level isolation.

## Current provider coverage

| Gateway provider | Producer | Status |
| --- | --- | --- |
| Family, students, academic context | OLAMA Core | Integrated |
| Family financial card | OLAMA Core | Integrated |
| Weekly plan | OLAMA School | Integrated; approved/published records only |
| Exam schedule and hall | OLAMA Exam Management | Integrated; approved schedule only |
| Online exam catalogue | OLAMA Exam Engine | Integrated; published/active exams only |
| Online exam results | OLAMA Exam Engine | Contract ready; producer read service required |
| Official marks | Oracle | Contract ready; future adapter |
| Transportation registration | OLAMA Core | Integrated |
| Operational transportation | OLAMA Transportation | Contract ready |
| Supplies and books | OLAMA Stores | Integrated through assignment model |
| Evaluations | OLAMA Student Evaluation | Contract ready; producer read service required |
| Attendance | OLAMA Student Evaluation | Contract ready; producer read service required |
| Messages | OLAMA Messages | Contract ready; future conversation service |

## Page integration

Add the shortcode below to the WordPress portal page:

```text
[olama_student_gateway]
```

The shortcode renders the existing WordPress login form for guests. OLAMA Users continues to authenticate family numbers and mobile-based passwords. The legacy `[olama_family_gateway]` shortcode is supported as an alias for existing portal pages.

When the Members plugin applies page-level Content Permissions to the portal page, the gateway replaces only Members' denial message with the gateway shortcode. This allows guests to reach the login form and makes `olama_student_gateway_access` the authoritative post-login access check without exposing any other protected page content.
