<?php

namespace Database\Seeders;

use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentTemplate;
use App\Models\EmployeeDocumentType;
use App\Support\EmployeeDocumentRequestService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class EmployeeDocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('employee_document_types') || ! Schema::hasTable('employee_document_templates')) {
            return;
        }

        app(EmployeeDocumentRequestService::class)->seedDefaultTypes();

        foreach ($this->templatesByType() as $code => $templates) {
            $type = EmployeeDocumentType::query()->where('code', $code)->first();

            if (! $type) {
                continue;
            }

            foreach ($templates as $index => $template) {
                EmployeeDocumentTemplate::query()->updateOrCreate(
                    [
                        'document_type_id' => $type->id,
                        'name' => $template['name'],
                    ],
                    [
                        'paper_size' => $template['paper_size'] ?? 'a4',
                        'orientation' => $template['orientation'] ?? 'portrait',
                        'body' => $template['body'],
                        'footer' => $template['footer'] ?? '{{ company.name }} · {{ company.support_contact }}',
                        'is_active' => $index === 0,
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    private function templatesByType(): array
    {
        return [
            EmployeeDocumentRequest::TYPE_EMPLOYMENT_CERTIFICATE => [
                [
                    'name' => 'Standard Employment Certificate',
                    'body' => $this->employmentCertificateBody(),
                ],
                [
                    'name' => 'External Employment Verification',
                    'body' => $this->employmentVerificationBody(),
                ],
            ],
            'paklaring' => [
                [
                    'name' => 'Standard Paklaring',
                    'body' => $this->paklaringBody(),
                ],
                [
                    'name' => 'Paklaring with Conduct Note',
                    'body' => $this->paklaringConductBody(),
                ],
            ],
            EmployeeDocumentRequest::TYPE_SALARY_STATEMENT => [
                [
                    'name' => 'Standard Salary Statement',
                    'body' => $this->salaryStatementBody(),
                ],
                [
                    'name' => 'Income Confirmation Letter',
                    'body' => $this->incomeConfirmationBody(),
                ],
            ],
            'npwp' => [
                [
                    'name' => 'NPWP Upload Request',
                    'body' => $this->uploadRequestBody('NPWP'),
                ],
                [
                    'name' => 'Tax Data Reminder',
                    'body' => $this->taxReminderBody(),
                ],
            ],
            'bank_account' => [
                [
                    'name' => 'Bank Account Upload Request',
                    'body' => $this->uploadRequestBody('rekening bank'),
                ],
                [
                    'name' => 'Payroll Bank Update Request',
                    'body' => $this->bankUpdateBody(),
                ],
            ],
            EmployeeDocumentRequest::TYPE_VISA_LETTER => [
                [
                    'name' => 'Visa Support Letter',
                    'body' => $this->visaLetterBody(),
                ],
                [
                    'name' => 'Bank Reference Employment Letter',
                    'body' => $this->bankReferenceBody(),
                ],
            ],
            EmployeeDocumentRequest::TYPE_OTHER => [
                [
                    'name' => 'General Administration Letter',
                    'body' => $this->generalLetterBody(),
                ],
                [
                    'name' => 'Manual Fulfillment Notice',
                    'body' => $this->manualFulfillmentBody(),
                ],
            ],
        ];
    }

    private function employmentCertificateBody(): string
    {
        return '<h2 style="text-align:center;">SURAT KETERANGAN KERJA</h2>
<p>Kepada pihak yang berkepentingan,</p>
<p>Dengan ini menerangkan bahwa <strong>{{ employee.name }}</strong> dengan NIP <strong>{{ employee.nip }}</strong> adalah karyawan {{ company.name }} pada posisi <strong>{{ employee.job_title }}</strong> di divisi <strong>{{ employee.division }}</strong>.</p>
<p>Surat ini diterbitkan untuk keperluan: <strong>{{ request.purpose }}</strong>.</p>
<p>{{ request.details }}</p>
<p>Demikian surat ini dibuat agar dapat dipergunakan sebagaimana mestinya.</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">Hormat kami,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }

    private function employmentVerificationBody(): string
    {
        return '<h2 style="text-align:center;">EMPLOYMENT VERIFICATION LETTER</h2>
<p>To whom it may concern,</p>
<p>This letter confirms that <strong>{{ employee.name }}</strong> with employee ID <strong>{{ employee.nip }}</strong> is employed by {{ company.name }} as <strong>{{ employee.job_title }}</strong> in the <strong>{{ employee.division }}</strong> division.</p>
<p>This verification is issued for: <strong>{{ request.purpose }}</strong>.</p>
<p>{{ request.details }}</p>
<p>Please contact {{ company.support_contact }} for further confirmation.</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">Authorized representative,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }

    private function salaryStatementBody(): string
    {
        return '<h2 style="text-align:center;">SURAT KETERANGAN PENGHASILAN</h2>
<p>Kepada pihak yang berkepentingan,</p>
<p>Dengan ini menerangkan bahwa <strong>{{ employee.name }}</strong> dengan NIP <strong>{{ employee.nip }}</strong> bekerja pada {{ company.name }} sebagai <strong>{{ employee.job_title }}</strong> di divisi <strong>{{ employee.division }}</strong>.</p>
<p>Surat keterangan penghasilan ini diterbitkan untuk keperluan: <strong>{{ request.purpose }}</strong>.</p>
<p>{{ request.details }}</p>
<p>Informasi ini dibuat berdasarkan data administrasi perusahaan yang berlaku pada tanggal dokumen diterbitkan.</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">Finance / HR Department,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }

    private function paklaringBody(): string
    {
        return '<h2 style="text-align:center;">SURAT KETERANGAN PENGALAMAN KERJA</h2>
<p>Kepada pihak yang berkepentingan,</p>
<p>Dengan ini menerangkan bahwa <strong>{{ employee.name }}</strong> dengan NIP <strong>{{ employee.nip }}</strong> pernah bekerja di {{ company.name }} sebagai <strong>{{ employee.job_title }}</strong> pada divisi <strong>{{ employee.division }}</strong>.</p>
<p>Surat pengalaman kerja ini diterbitkan untuk keperluan: <strong>{{ request.purpose }}</strong>.</p>
<p>{{ request.details }}</p>
<p>Demikian surat keterangan ini dibuat agar dapat dipergunakan sebagaimana mestinya.</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">Hormat kami,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }

    private function paklaringConductBody(): string
    {
        return '<h2 style="text-align:center;">PAKLARING</h2>
<p>Kepada pihak yang berkepentingan,</p>
<p>{{ company.name }} menerangkan bahwa <strong>{{ employee.name }}</strong> dengan NIP <strong>{{ employee.nip }}</strong> tercatat sebagai karyawan pada posisi <strong>{{ employee.job_title }}</strong> di divisi <strong>{{ employee.division }}</strong>.</p>
<p>Selama masa kerja, yang bersangkutan menjalankan tugas dan tanggung jawab sesuai ketentuan perusahaan.</p>
<p>Keperluan dokumen: <strong>{{ request.purpose }}</strong>.</p>
<p>{{ request.details }}</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">Hormat kami,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }

    private function incomeConfirmationBody(): string
    {
        return '<h2 style="text-align:center;">INCOME CONFIRMATION LETTER</h2>
<p>To whom it may concern,</p>
<p>{{ company.name }} confirms that <strong>{{ employee.name }}</strong> is an active employee with employee ID <strong>{{ employee.nip }}</strong>.</p>
<p>This letter is prepared to support: <strong>{{ request.purpose }}</strong>.</p>
<p>{{ request.details }}</p>
<p>All employment-related information should be verified through {{ company.support_contact }}.</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">Finance / HR Department,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }

    private function uploadRequestBody(string $document): string
    {
        return '<h2 style="text-align:center;">PERMINTAAN DOKUMEN KARYAWAN</h2>
<p>Halo <strong>{{ employee.name }}</strong>,</p>
<p>Mohon mengunggah dokumen <strong>'.$document.'</strong> melalui menu Document Requests.</p>
<p>Tujuan permintaan: <strong>{{ request.purpose }}</strong>.</p>
<p>Batas waktu: <strong>{{ request.due_date }}</strong>.</p>
<p>{{ request.details }}</p>
<p>Pastikan file jelas, valid, dan sesuai kebutuhan administrasi HR/Finance.</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">HR / Finance Department,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }

    private function taxReminderBody(): string
    {
        return '<h2 style="text-align:center;">PENGINGAT DATA PAJAK KARYAWAN</h2>
<p>Halo <strong>{{ employee.name }}</strong>,</p>
<p>Untuk kebutuhan administrasi pajak dan payroll, mohon memastikan dokumen NPWP yang diunggah sudah benar dan terbaru.</p>
<p>Keperluan: <strong>{{ request.purpose }}</strong>.</p>
<p>Batas waktu: <strong>{{ request.due_date }}</strong>.</p>
<p>{{ request.details }}</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">Finance Department,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }

    private function bankUpdateBody(): string
    {
        return '<h2 style="text-align:center;">PERMINTAAN UPDATE DATA REKENING PAYROLL</h2>
<p>Halo <strong>{{ employee.name }}</strong>,</p>
<p>Mohon mengunggah bukti rekening bank yang akan digunakan untuk proses payroll.</p>
<p>Keperluan: <strong>{{ request.purpose }}</strong>.</p>
<p>Batas waktu: <strong>{{ request.due_date }}</strong>.</p>
<p>{{ request.details }}</p>
<p>Pastikan nama pemilik rekening sesuai dengan data karyawan.</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">Finance Department,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }

    private function visaLetterBody(): string
    {
        return '<h2 style="text-align:center;">VISA SUPPORT LETTER</h2>
<p>To whom it may concern,</p>
<p>This letter confirms that <strong>{{ employee.name }}</strong> with employee ID <strong>{{ employee.nip }}</strong> is employed by {{ company.name }} as <strong>{{ employee.job_title }}</strong> in the <strong>{{ employee.division }}</strong> division.</p>
<p>This document is issued to support: <strong>{{ request.purpose }}</strong>.</p>
<p>{{ request.details }}</p>
<p>Please contact {{ company.support_contact }} for verification if required.</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">Authorized representative,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }

    private function bankReferenceBody(): string
    {
        return '<h2 style="text-align:center;">SURAT REFERENSI KERJA UNTUK BANK</h2>
<p>Kepada pihak bank yang berkepentingan,</p>
<p>Dengan ini menerangkan bahwa <strong>{{ employee.name }}</strong> dengan NIP <strong>{{ employee.nip }}</strong> bekerja di {{ company.name }} sebagai <strong>{{ employee.job_title }}</strong>.</p>
<p>Surat ini diterbitkan untuk keperluan: <strong>{{ request.purpose }}</strong>.</p>
<p>{{ request.details }}</p>
<p>Dokumen ini hanya digunakan untuk keperluan administrasi sesuai permintaan karyawan.</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">Hormat kami,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }

    private function generalLetterBody(): string
    {
        return '<h2 style="text-align:center;">{{ request.document_type }}</h2>
<p>Kepada pihak yang berkepentingan,</p>
<p>Dokumen ini dibuat untuk karyawan <strong>{{ employee.name }}</strong> dengan NIP <strong>{{ employee.nip }}</strong>.</p>
<p>Keperluan: <strong>{{ request.purpose }}</strong>.</p>
<p>{{ request.details }}</p>
<p>Informasi pendukung dapat dikonfirmasi melalui {{ company.support_contact }}.</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">Hormat kami,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }

    private function manualFulfillmentBody(): string
    {
        return '<h2 style="text-align:center;">PEMBERITAHUAN PEMENUHAN DOKUMEN</h2>
<p>Halo <strong>{{ employee.name }}</strong>,</p>
<p>Permintaan dokumen <strong>{{ request.document_type }}</strong> sedang diproses oleh admin.</p>
<p>Keperluan: <strong>{{ request.purpose }}</strong>.</p>
<p>Batas waktu: <strong>{{ request.due_date }}</strong>.</p>
<p>{{ request.details }}</p>
<p>Admin akan memperbarui status request setelah dokumen siap.</p>
<p style="margin-top:32px;">{{ date.today }}</p>
<p style="margin-top:28px;">HR / Admin Department,</p>
<p style="margin-top:48px;"><strong>{{ company.name }}</strong></p>';
    }
}
