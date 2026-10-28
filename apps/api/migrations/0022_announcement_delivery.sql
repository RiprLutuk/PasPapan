ALTER TABLE announcements ADD COLUMN visible_from TIMESTAMPTZ,
    ADD COLUMN audience_team TEXT,
    ADD COLUMN notification_sent_at TIMESTAMPTZ;
UPDATE announcements SET notification_sent_at=published_at WHERE status='published';
CREATE INDEX announcements_due_idx ON announcements(visible_from) WHERE status='published' AND notification_sent_at IS NULL;

CREATE FUNCTION dispatch_announcements(tenant UUID) RETURNS BIGINT LANGUAGE plpgsql AS $$
DECLARE announcement RECORD; delivered BIGINT:=0;
BEGIN
    FOR announcement IN
        SELECT * FROM announcements a WHERE (tenant IS NULL OR a.company_id=tenant)
        AND a.status='published' AND COALESCE(a.visible_from,a.published_at)<=now()
        AND a.notification_sent_at IS NULL ORDER BY a.published_at,a.id LIMIT 50 FOR UPDATE SKIP LOCKED
    LOOP
        INSERT INTO notifications(id,company_id,user_id,title,body)
        SELECT gen_random_uuid(),m.company_id,m.user_id,announcement.title,'Pengumuman baru tersedia di halaman pengumuman.'
        FROM company_memberships m LEFT JOIN employee_profiles p ON p.company_id=m.company_id AND p.user_id=m.user_id
        WHERE m.company_id=announcement.company_id AND m.employment_status='active' AND m.user_id<>announcement.author_id
        AND (announcement.audience_team IS NULL OR p.team=announcement.audience_team);
        UPDATE announcements SET notification_sent_at=now() WHERE company_id=announcement.company_id AND id=announcement.id;
        delivered:=delivered+1;
    END LOOP;
    RETURN delivered;
END;
$$;
