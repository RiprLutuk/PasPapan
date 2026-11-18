CREATE OR REPLACE FUNCTION enqueue_notification_email() RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
  INSERT INTO outbox_events (id, company_id, event_type, payload)
  VALUES (gen_random_uuid(), NEW.company_id, 'notification.email', jsonb_build_object('notification_id', NEW.id));
  RETURN NEW;
END;
$$;
DROP TRIGGER IF EXISTS notifications_enqueue_email ON notifications;
CREATE TRIGGER notifications_enqueue_email AFTER INSERT ON notifications
FOR EACH ROW EXECUTE FUNCTION enqueue_notification_email();
