export type LatestNotificationPayload = {
	id: string;
	title: string;
	body: string;
	created_at: string;
};

export type RealtimePulse = {
	timestamp: string;
	unread_notifications: number;
	latest_notification?: LatestNotificationPayload | null;
	pending_approvals: number;
	checked_in_today: number;
	pg_active_connections: number;
	latest_audit_at: string | null;
};

export type LiveNotificationToast = {
	id: string;
	title: string;
	body: string;
	href: string;
};

const STATUS_LABELS: Record<string, string> = {
	pending_manager: 'Menunggu persetujuan manager',
	pending_admin: 'Menunggu persetujuan admin',
	approved: 'Pengajuan Anda telah disetujui',
	rejected: 'Pengajuan Anda ditolak'
};

export const realtimeState = $state<{
	connected: boolean;
	lastPulse: RealtimePulse | null;
	toast: LiveNotificationToast | null;
	permission: NotificationPermission | 'unsupported';
}>({
	connected: false,
	lastPulse: null,
	toast: null,
	permission:
		typeof window !== 'undefined' && 'Notification' in window
			? Notification.permission
			: 'unsupported'
});

let lastSeenNotificationId: string | null = null;
let initializedBaseline = false;
let toastTimer: number | null = null;

function formatNotificationBody(raw: string): string {
	return STATUS_LABELS[raw] ?? raw;
}

export function dismissLiveToast() {
	if (toastTimer !== null && typeof window !== 'undefined') {
		window.clearTimeout(toastTimer);
		toastTimer = null;
	}
	realtimeState.toast = null;
}

export function showLiveToast(notification: LatestNotificationPayload) {
	if (typeof window === 'undefined') return;
	if (toastTimer !== null) window.clearTimeout(toastTimer);
	realtimeState.toast = {
		id: notification.id,
		title: notification.title || 'Notifikasi baru',
		body: formatNotificationBody(notification.body || ''),
		href: '/app/notifications'
	};
	toastTimer = window.setTimeout(() => {
		realtimeState.toast = null;
		toastTimer = null;
	}, 7000);
}

export function syncPwaAppBadge(unreadCount: number) {
	if (typeof navigator === 'undefined') return;
	const nav = navigator as Navigator & {
		setAppBadge?: (count?: number) => Promise<void>;
		clearAppBadge?: () => Promise<void>;
	};
	if (unreadCount > 0 && typeof nav.setAppBadge === 'function') {
		void nav.setAppBadge(unreadCount).catch(() => {});
	} else if (unreadCount <= 0 && typeof nav.clearAppBadge === 'function') {
		void nav.clearAppBadge().catch(() => {});
	}
}

export async function requestPwaNotificationPermission(): Promise<NotificationPermission | 'unsupported'> {
	if (typeof window === 'undefined' || !('Notification' in window)) {
		realtimeState.permission = 'unsupported';
		return 'unsupported';
	}
	try {
		const result = await Notification.requestPermission();
		realtimeState.permission = result;
		return result;
	} catch {
		return realtimeState.permission;
	}
}

export async function dispatchPwaSystemNotification(notification: LatestNotificationPayload) {
	if (typeof window === 'undefined' || !('Notification' in window)) return;
	realtimeState.permission = Notification.permission;
	if (Notification.permission !== 'granted') return;

	const title = notification.title || 'PasPapan';
	const body = formatNotificationBody(notification.body || '');
	const tag = `paspapan-notif-${notification.id}`;

	try {
		if ('serviceWorker' in navigator) {
			const registration = await navigator.serviceWorker.getRegistration();
			if (registration?.active) {
				registration.active.postMessage({
					type: 'SHOW_NOTIFICATION',
					title,
					body,
					tag,
					url: '/app/notifications'
				});
				return;
			}
			if (registration && typeof registration.showNotification === 'function') {
				await registration.showNotification(title, {
					body,
					icon: '/brand/icon-192x192.png',
					badge: '/brand/favicon-32.png',
					tag,
					data: { url: '/app/notifications' }
				});
				return;
			}
		}
		new Notification(title, { body, icon: '/brand/icon-192x192.png', tag });
	} catch {
		// Ignore notification dispatch errors on restricted browsers
	}
}

export function connectRealtimeStream(onPulse?: (pulse: RealtimePulse) => void): () => void {
	if (typeof window === 'undefined' || typeof EventSource === 'undefined') {
		return () => {};
	}

	if ('Notification' in window) {
		realtimeState.permission = Notification.permission;
	}

	const source = new EventSource('/v1/events/stream', { withCredentials: true });

	const handlePulse = (event: MessageEvent) => {
		try {
			const data = JSON.parse(event.data) as RealtimePulse;
			const previousUnread = realtimeState.lastPulse?.unread_notifications ?? 0;
			realtimeState.connected = true;
			realtimeState.lastPulse = data;

			syncPwaAppBadge(data.unread_notifications);

			const latest = data.latest_notification;
			if (!initializedBaseline) {
				initializedBaseline = true;
				lastSeenNotificationId = latest?.id ?? null;
			} else if (
				latest?.id &&
				(latest.id !== lastSeenNotificationId || data.unread_notifications > previousUnread)
			) {
				lastSeenNotificationId = latest.id;
				showLiveToast(latest);
				void dispatchPwaSystemNotification(latest);
				window.dispatchEvent(new Event('paspapan:notifications-updated'));
			} else if (data.unread_notifications !== previousUnread) {
				window.dispatchEvent(new Event('paspapan:notifications-updated'));
			}

			onPulse?.(data);
		} catch {
			// Ignore malformed frame
		}
	};

	source.addEventListener('pulse', handlePulse as EventListener);
	source.onopen = () => {
		realtimeState.connected = true;
	};
	source.onerror = () => {
		realtimeState.connected = false;
	};

	return () => {
		source.removeEventListener('pulse', handlePulse as EventListener);
		source.close();
		realtimeState.connected = false;
	};
}
