<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\EmploymentType;
use App\Models\GrievanceCategory;
use App\Models\Holiday;
use App\Models\IntegrationSetting;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\NotificationTemplate;
use App\Models\PayLevel;
use App\Models\PayMatrix;
use App\Models\SalaryComponent;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;

class MasterConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $payMatrix = PayMatrix::query()->updateOrCreate(
            ['code' => 'ASSAM-PAY-MATRIX'],
            [
                'name' => 'Assam Pay Matrix',
                'description' => 'Default pay matrix placeholder for HRMS setup.',
                'effective_from' => now()->startOfYear()->toDateString(),
                'status' => 'active',
            ],
        );

        foreach ([
            ['L1', 'Level 1', 1, 18000, 56900, 500],
            ['L2', 'Level 2', 2, 19900, 63200, 600],
            ['L3', 'Level 3', 3, 21700, 69100, 700],
        ] as [$code, $name, $order, $min, $max, $increment]) {
            PayLevel::query()->updateOrCreate(
                ['code' => $code],
                [
                    'pay_matrix_id' => $payMatrix->id,
                    'name' => $name,
                    'level_order' => $order,
                    'min_basic' => $min,
                    'max_basic' => $max,
                    'increment_amount' => $increment,
                    'status' => 'active',
                ],
            );
        }

        foreach ([
            ['BASIC', 'Basic Pay', 'earning', 'fixed', 0, true, false],
            ['DA', 'Dearness Allowance', 'earning', 'percentage', 0, true, false],
            ['HRA', 'House Rent Allowance', 'earning', 'percentage', 0, true, false],
            ['NPS', 'NPS Deduction', 'deduction', 'percentage', 0, false, true],
            ['TAX', 'Income Tax', 'deduction', 'fixed', 0, false, true],
            ['PTAX', 'PTAX', 'deduction', 'fixed', 0, false, true],
        ] as [$code, $name, $type, $calculationType, $amount, $taxable, $deduction]) {
            SalaryComponent::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'calculation_type' => $calculationType,
                    'default_amount' => $amount,
                    'is_taxable' => $taxable,
                    'is_deduction' => $deduction,
                    'status' => 'active',
                ],
            );
        }

        $leaveTypes = [];
        foreach ([
            ['PL', 'Paid Leave', true, false, false],
            ['ML', 'Medical Leave', true, true, false],
            ['MAT', 'Maternity Leave', true, true, false],
            ['PAT', 'Paternity Leave', true, true, false],
        ] as [$code, $name, $paid, $document, $halfDay]) {
            $leaveTypes[$code] = LeaveType::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'is_paid' => $paid,
                    'requires_document' => $document,
                    'allow_half_day' => $halfDay,
                    'status' => 'active',
                ],
            );
        }

        $regularEmploymentTypeId = EmploymentType::query()->where('code', 'REGULAR')->value('id');
        $contractualEmploymentTypeId = EmploymentType::query()->where('code', 'CONTRACTUAL')->value('id');

        foreach ([
            // Paid Leave quota is informational; the real cap is the 2/month bank.
            ['PL', $regularEmploymentTypeId, 24, 31, 0],
            ['ML', $regularEmploymentTypeId, 10, 10, 0],
            ['PL', $contractualEmploymentTypeId, 24, 31, 0],
            ['ML', $contractualEmploymentTypeId, 7, 7, 0],
        ] as [$leaveCode, $employmentTypeId, $quota, $maxDays, $carryForward]) {
            if (! $employmentTypeId) {
                continue;
            }

            LeavePolicy::query()->updateOrCreate(
                [
                    'leave_type_id' => $leaveTypes[$leaveCode]->id,
                    'employment_type_id' => $employmentTypeId,
                    'gender' => 'all',
                    'service_type' => 'all',
                ],
                [
                    'annual_quota' => $quota,
                    'max_days_per_request' => $maxDays,
                    'carry_forward_limit' => $carryForward,
                    'encashable_limit' => 0,
                    'rules' => [],
                    'status' => 'active',
                ],
            );
        }

        foreach ([
            ['Republic Day', now()->year.'-01-26', 'national'],
            ['Independence Day', now()->year.'-08-15', 'national'],
            ['Gandhi Jayanti', now()->year.'-10-02', 'national'],
        ] as [$name, $date, $type]) {
            Holiday::query()->updateOrCreate(
                ['holiday_date' => $date, 'name' => $name],
                [
                    'type' => $type,
                    'state' => 'Assam',
                    'status' => 'active',
                ],
            );
        }

        foreach ([
            ['AADHAAR', 'Aadhaar', true, false],
            ['PAN', 'PAN', true, false],
            ['APPOINTMENT_LETTER', 'Appointment Letter', true, false],
            ['JOINING_LETTER', 'Joining Letter', true, false],
            ['TRANSFER_ORDER', 'Transfer Order', true, false],
            ['CERTIFICATE', 'Certificate', true, false],
        ] as [$code, $name, $verify, $expiry]) {
            DocumentType::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'requires_verification' => $verify,
                    'has_expiry' => $expiry,
                    'status' => 'active',
                ],
            );
        }

        foreach ([
            ['LEAVE_APPROVAL', 'Leave Approval', 'leave', [
                ['Reporting Officer Review', 'reporting_officer', 'approve'],
                ['HR Approval', 'hr', 'approve'],
            ]],
            ['TRANSFER_APPROVAL', 'Transfer Approval', 'transfer', [
                ['HR Review', 'hr', 'approve'],
                ['Department Approval', 'department_admin', 'approve'],
            ]],
            ['PAYROLL_APPROVAL', 'Payroll Approval', 'payroll', [
                ['HR', 'hr', 'submit'],
                ['SPO FM', 'spo_fm', 'approve'],
                ['Deputy MD', 'deputy_md', 'approve'],
                ['FA', 'fa', 'approve'],
                ['Addt Chief Eng', 'addt_chief_eng', 'approve'],
                ['Addt. MD', 'addt_md', 'approve'],
                ['MD', 'md', 'approve'],
            ]],
        ] as [$code, $name, $module, $steps]) {
            $workflow = WorkflowDefinition::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'module' => $module,
                    'status' => 'active',
                ],
            );

            foreach ($steps as $index => [$stepName, $role, $actionType]) {
                WorkflowStep::query()->updateOrCreate(
                    [
                        'workflow_definition_id' => $workflow->id,
                        'sequence' => $index + 1,
                    ],
                    [
                        'name' => $stepName,
                        'role' => $role,
                        'action_type' => $actionType,
                        'sla_hours' => 48,
                        'status' => 'active',
                    ],
                );
            }
        }

        foreach ([
            ['SALARY', 'Salary Issue', 72],
            ['ATTENDANCE', 'Attendance Issue', 48],
            ['TRANSFER', 'Transfer Issue', 120],
            ['SERVICE_RECORD', 'Service Record Issue', 96],
        ] as [$code, $name, $slaHours]) {
            GrievanceCategory::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'sla_hours' => $slaHours,
                    'status' => 'active',
                ],
            );
        }

        foreach ([
            ['EMPLOYEE_LOGIN_CREATED', 'Employee Login Created', 'email', 'Your HRMS login has been created'],
            ['LEAVE_SUBMITTED', 'Leave Submitted', 'in_app', 'Leave application submitted'],
            ['LEAVE_APPROVED', 'Leave Approved', 'email', 'Your leave application has been approved'],
        ] as [$code, $name, $channel, $subject]) {
            NotificationTemplate::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'channel' => $channel,
                    'subject' => $subject,
                    'body' => 'Template body to be configured by Super Admin.',
                    'variables' => [],
                    'status' => 'active',
                ],
            );
        }

        foreach ([
            ['SMS_GATEWAY', 'SMS Gateway', 'sms'],
            ['EMAIL_GATEWAY', 'Email Gateway', 'email'],
            ['DIGILOCKER', 'DigiLocker', 'digilocker'],
            ['ESIGN', 'eSign', 'esign'],
        ] as [$code, $name, $provider]) {
            IntegrationSetting::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'provider' => $provider,
                    'credentials' => [],
                    'configuration' => [],
                    'enabled' => false,
                    'status' => 'inactive',
                ],
            );
        }
    }
}
