export const documentVariables = [
 { key: 'employee_name', label: 'Nama karyawan' },
 { key: 'employee_code', label: 'Kode karyawan' },
 { key: 'company_name', label: 'Nama perusahaan' },
 { key: 'date', label: 'Tanggal pengajuan' },
 { key: 'division', label: 'Divisi karyawan' },
 { key: 'job_title', label: 'Jabatan' }
] as const;

/** One pass, like the API renderer: values must never introduce another substitution. */
export function previewDocumentTemplate(body: string, values: Record<string, string>): string {
 return body.replace(/\{\{([a-z_]+)\}\}/g, (token, key: string) =>
  documentVariables.some(variable => variable.key === key) && Object.hasOwn(values, key) ? values[key] : token
 );
}
