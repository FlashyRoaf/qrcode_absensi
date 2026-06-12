import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    phone: string;
    is_admin: boolean;
    role: string;
    device_id: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Qrcode {
    id: number;
    token: string;
    shift: string;
    division: string;
    expires_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Shift {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    created_at: string;
    updated_at: string;
}

export interface Division {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface WeeklyReport {
    id: number;
    user: User;
    week_start: string;
    total_minutes: number;
    status: 'memenuhi' | 'tidak_memenuhi';
}

// Hukuman
export interface Penalty {
    id: number;
    user: User;
    weekly_report: WeeklyReport;
    status: 'pending' | 'uploaded' | 'approved' | 'rejected' | 'exempted';
    proof_path: string | null;
    rejection_reason: string | null;
    approved_at: string | null;
}

export interface ExemptWeek {
    id: number;
    week_start: string;
    reason: string | null;
}

export type BreadcrumbItemType = BreadcrumbItem;
