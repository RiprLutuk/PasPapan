ALTER TABLE employee_profiles ADD COLUMN division_id UUID,ADD COLUMN job_title_id UUID,
ADD FOREIGN KEY(company_id,division_id) REFERENCES organization_reference_data(company_id,id),
ADD FOREIGN KEY(company_id,job_title_id) REFERENCES organization_reference_data(company_id,id);
INSERT INTO organization_reference_data(id,company_id,kind,name)
SELECT gen_random_uuid(),company_id,'division',team FROM (SELECT DISTINCT company_id,team FROM employee_profiles WHERE length(trim(team)) BETWEEN 1 AND 100) p
ON CONFLICT(company_id,kind,name) DO NOTHING;
INSERT INTO organization_reference_data(id,company_id,kind,name)
SELECT gen_random_uuid(),company_id,'job_title',job_title FROM (SELECT DISTINCT company_id,job_title FROM employee_profiles WHERE length(trim(job_title)) BETWEEN 1 AND 100) p
ON CONFLICT(company_id,kind,name) DO NOTHING;
UPDATE employee_profiles p SET division_id=r.id FROM organization_reference_data r WHERE p.company_id=r.company_id AND r.kind='division' AND r.name=p.team;
UPDATE employee_profiles p SET job_title_id=r.id FROM organization_reference_data r WHERE p.company_id=r.company_id AND r.kind='job_title' AND r.name=p.job_title;
