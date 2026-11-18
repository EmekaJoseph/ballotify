import type { EventStatus } from '~/types';

export const STATUS_META: Record<EventStatus, { label: string; cls: string; icon: string }> = {
    active: { label: 'Live', cls: 'pill-active pill-live', icon: 'bi-broadcast' },
    upcoming: { label: 'Scheduled', cls: 'pill-upcoming', icon: 'bi-clock' },
    completed: { label: 'Closed', cls: 'pill-completed', icon: 'bi-check2-circle' },
};

export function formatDate(value: string | null | undefined, withTime = false): string {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString(undefined, withTime
        ? { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' }
        : { day: 'numeric', month: 'short', year: 'numeric' });
}

/** Human description of an event's voting window. */
export function formatWindow(start: string | null, end: string | null): string {
    if (!start && !end) return 'Always open';
    if (start && !end) return `From ${formatDate(start, true)}`;
    if (!start && end) return `Until ${formatDate(end, true)}`;
    return `${formatDate(start, true)} → ${formatDate(end, true)}`;
}

/** ISO string → value for <input type="datetime-local"> in the user's timezone. */
export function toLocalInput(value: string | null | undefined): string {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

/** <input type="datetime-local"> value → ISO string (UTC) or null. */
export function fromLocalInput(value: string): string | null {
    if (!value) return null;
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? null : d.toISOString();
}

export function percent(part: number, total: number): number {
    if (!total) return 0;
    return Math.round((part / total) * 1000) / 10;
}

export function formatNumber(n: number | null | undefined): string {
    return (n ?? 0).toLocaleString();
}

export function initials(name: string | null | undefined): string {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/).filter(Boolean);
    const first = parts[0]?.[0] ?? '';
    const last = parts.length > 1 ? parts[parts.length - 1]?.[0] ?? '' : '';
    return (first + last).toUpperCase() || '?';
}

const AVATAR_COLORS = ['#0e7c77', '#7c5cff', '#0ea5e9', '#f59e0b', '#ec4899', '#16a34a', '#6366f1', '#ef4444'];

/** Stable colour for a name, used for initials avatars. */
export function colorFor(seed: string | number | null | undefined): string {
    const s = String(seed ?? '');
    let h = 0;
    for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0;
    return AVATAR_COLORS[h % AVATAR_COLORS.length]!;
}

/** Trigger a client-side file download. */
export function downloadFile(filename: string, content: string, type = 'text/csv;charset=utf-8') {
    const blob = new Blob([content], { type });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}

export function toCsv(rows: (string | number | null | undefined)[][]): string {
    const esc = (v: string | number | null | undefined) => {
        let s = v == null ? '' : String(v);
        // Neutralise spreadsheet formula injection.
        if (/^[=+\-@]/.test(s)) s = `'${s}`;
        return /[",\n\r]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
    };
    return '﻿' + rows.map(r => r.map(esc).join(',')).join('\r\n');
}

export function slugify(s: string): string {
    return s.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') || 'event';
}
