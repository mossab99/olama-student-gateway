# Provider contracts

Producing plugins can publish gateway-safe read models without giving the gateway access to their private tables.

## Registering a provider

Hook `olama_student_gateway_register_providers` and register an object implementing `Olama_Student_Gateway_Provider_Interface`.

```php
add_action('olama_student_gateway_register_providers', function ($registry) {
    $registry->register(new My_Student_Evaluation_Gateway_Provider());
});
```

The `get_data()` context contains the canonical `family`, `students`, active `student`, `family_uid`, `family_id`, academic context and study year. A producer must still return only published, guardian-visible records.

## Filter-based bridge

The initial release also supports these read filters:

```text
olama_student_gateway_exams_data
olama_student_gateway_exam_results_data
olama_student_gateway_official_marks_data
olama_student_gateway_evaluations_data
olama_student_gateway_attendance_data
olama_student_gateway_transportation_data
olama_student_gateway_messages_data
```

Example:

```php
add_filter('olama_student_gateway_evaluations_data', function ($data, $context) {
    return My_Evaluation_Service::published_for_student(
        $context['student']['student_uid'],
        $context['study_year']
    );
}, 10, 2);
```

Do not use a family ID supplied by the browser. Do not return drafts, internal supervisor notes, unpublished marks, other recipients, authentication secrets, or raw Oracle payloads.

Any future write operation, such as replying to a teacher message, must call the producing plugin service and perform its own nonce, capability and ownership checks.

