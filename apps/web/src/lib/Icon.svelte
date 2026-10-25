<script lang="ts">
	import { House, CalendarDays, Clock3, ClipboardList, ClipboardCheck, CircleUserRound, Bell, Check, Users, LogIn, LogOut,
		Menu, X, ArrowRight, ArrowLeft, MapPin, Eye, EyeOff, ChevronDown, PanelLeft, SwitchCamera, RefreshCw,
		MailCheck, QrCode, CircleHelp, ShieldCheck, KeyRound, Building2, Mail, Lock, Sparkles, Activity, Database, Code, Search,
		Wallet, Banknote, ArrowLeftRight, Megaphone, FileText, Package, TrendingUp, ScanFace, Briefcase,
		Send, EllipsisVertical, Languages, Palette, Sun, Moon, IdCard, Smartphone, BadgeCheck, Plus } from '@lucide/svelte';
	let { name, size = 20 }: { name: string; size?: number } = $props();
	const icons = { home: House, calendar: CalendarDays, clock: Clock3, request: ClipboardList,
		tasks: ClipboardCheck, profile: CircleUserRound,
		bell: Bell, check: Check, people: Users, login: LogIn, logout: LogOut, menu: Menu, close: X,
		arrow: ArrowRight, back: ArrowLeft, pin: MapPin, eye: Eye, 'eye-off': EyeOff, chevron: ChevronDown,
		panel: PanelLeft, read: MailCheck, qr: QrCode, switch: SwitchCamera, refresh: RefreshCw,
		shield: ShieldCheck, key: KeyRound, building: Building2, mail: Mail, lock: Lock,
		sparkles: Sparkles, activity: Activity, database: Database, code: Code, search: Search,
		wallet: Wallet, banknote: Banknote, swap: ArrowLeftRight, megaphone: Megaphone,
		file: FileText, box: Package, chart: TrendingUp, face: ScanFace, briefcase: Briefcase,
		send: Send, more: EllipsisVertical, language: Languages, palette: Palette,
		sun: Sun, moon: Moon, 'id-card': IdCard, smartphone: Smartphone, verified: BadgeCheck, plus: Plus };
	let Glyph = $derived(icons[name as keyof typeof icons] ?? CircleHelp);
</script>

<Glyph {size} strokeWidth={1.75} aria-hidden="true" focusable="false" />
