<?php

return [
    'target_score' => 95,

    'modules' => [
        'attendance' => [
            'label' => 'Attendance, Dynamic QR, GPS, Photo, Offline Sync',
            'score' => 94,
            'weight' => 14,
            'status' => 'production_ready',
            'evidence' => [
                'tests/Feature/DynamicBarcodeTest.php',
                'tests/Feature/OfflineAttendanceSyncTest.php',
                'tests/Feature/AttendanceRiskScoringTest.php',
                'guides/attendance-threat-model.md',
            ],
            'gaps' => [
                'Browser/PWA device coverage is required; native delivery will use a separate API client.',
            ],
        ],
        'hr_lifecycle' => [
            'label' => 'HR Lifecycle, Checklist, Documents, Compliance',
            'score' => 90,
            'weight' => 11,
            'status' => 'production_ready',
            'evidence' => [
                'tests/Feature/HrChecklistFlowTest.php',
                'tests/Feature/EmployeeDocumentRequestFlowTest.php',
                'tests/Feature/HrComplianceReminderServiceTest.php',
                'guides/features.md',
            ],
            'gaps' => [
                'More customer-specific lifecycle templates should be added from real deployments.',
            ],
        ],
        'approval_finance' => [
            'label' => 'Approvals, Reimbursement, Cash Advance, Overtime, WFH',
            'score' => 91,
            'weight' => 12,
            'status' => 'production_ready',
            'evidence' => [
                'tests/Feature/ApprovalWorkflowTest.php',
                'tests/Feature/AdminLeaveApprovalTest.php',
                'tests/Feature/WorkFromHomeRequestFlowTest.php',
                'tests/Feature/ManagerInboxAuthorizationTest.php',
            ],
            'gaps' => [
                'Approval matrix is reusable, but more workflow-specific customer policies should be fixture-tested.',
            ],
        ],
        'payroll_indonesia' => [
            'label' => 'Payroll Indonesia, PPh21 TER, BPJS, THR, Coretax',
            'score' => 87,
            'weight' => 12,
            'status' => 'release_candidate',
            'evidence' => [
                'tests/Feature/IndonesiaPayrollCalculatorTest.php',
                'tests/Feature/MyPayslipsTest.php',
                'tests/Feature/SecurityMatrixTest.php',
                'guides/security-model.md',
            ],
            'gaps' => [
                'Needs more real payroll fixture packs for edge tax cases before calling it 10/10.',
            ],
        ],
        'security_rbac_tenant' => [
            'label' => 'Security, RBAC, Multi-company Isolation',
            'score' => 93,
            'weight' => 14,
            'status' => 'production_ready',
            'evidence' => [
                'tests/Feature/SecurityMatrixTest.php',
                'tests/Feature/SecurityIsolationMatrixTest.php',
                'tests/Feature/MultiCompanyIsolationTest.php',
                'tests/Feature/PolicyDirectCoverageTest.php',
                'guides/security-model.md',
            ],
            'gaps' => [
                'Run tenant isolation smoke again whenever adding a company-scoped model.',
            ],
        ],
        'operations_workspace' => [
            'label' => 'Operations, Client, Project, Task, Visit Evidence',
            'score' => 79,
            'weight' => 7,
            'status' => 'release_candidate',
            'evidence' => [
                'tests/Feature/OperationalWorkspaceTest.php',
                'tests/Feature/MyOperationalTasksTest.php',
                'tests/Feature/MultiCompanyIsolationTest.php',
            ],
            'gaps' => [
                'Needs more create/edit/detail/export polish from real operator workflows.',
            ],
        ],
        'commercial_crm' => [
            'label' => 'Commercial, CRM, Quotation, Invoice, Sales Pipeline',
            'score' => 82,
            'weight' => 7,
            'status' => 'release_candidate',
            'evidence' => [
                'tests/Feature/CommercialWorkspaceTest.php',
                'tests/Feature/MultiCompanyIsolationTest.php',
                'resources/views/pdf/employee-document-template.blade.php',
            ],
            'gaps' => [
                'Pipeline forecast and AR collection summaries are covered, but quotation approval and deeper CRM activity reporting still need product hardening.',
            ],
        ],
        'accounting' => [
            'label' => 'Accounting, AR/AP, Ledger, Closing, Reports',
            'score' => 80,
            'weight' => 7,
            'status' => 'release_candidate',
            'evidence' => [
                'tests/Feature/AccountingWorkspaceTest.php',
                'tests/Feature/MultiCompanyIsolationTest.php',
                'app/Models/AccountingTaxFiling.php',
                'guides/features.md',
            ],
            'gaps' => [
                'Tax filing workflow is now present, but still needs reconciliation-heavy customer fixture packs before production finance sign-off.',
            ],
        ],
        'collaboration' => [
            'label' => 'Chat, Cloud Files, Meetings, Realtime',
            'score' => 73,
            'weight' => 5,
            'status' => 'foundation',
            'evidence' => [
                'tests/Feature/CollaborationWorkspaceTest.php',
                'guides/operations.md',
            ],
            'gaps' => [
                'Personal/group chat has scoped search and secure file delivery, but still needs moderation, retention automation, and richer history search.',
            ],
        ],
        'ops_readiness' => [
            'label' => 'VPS Operations, Queue, Scheduler, Backup, DB Portability',
            'score' => 88,
            'weight' => 8,
            'status' => 'release_candidate',
            'evidence' => [
                'tests/Feature/AdminRouteSplitAndHealthTest.php',
                'tests/Feature/BackupSecurityHardeningTest.php',
                'tests/Unit/DatabasePortabilityStaticTest.php',
                'guides/database-portability.md',
            ],
            'gaps' => [
                'Production queue/scheduler/backup heartbeat must be observed on the target VPS after deploy.',
            ],
        ],
    ],
];
