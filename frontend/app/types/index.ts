export type EventStatus = 'upcoming' | 'active' | 'completed';
export type SelectionMode = 'single' | 'multiple';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    two_factor_enabled: boolean;
    /** Platform owner: can open the admin area. */
    is_admin?: boolean;
    suspended_at?: string | null;
    /** Granted by the platform admin: every event of this account is Pro. */
    pro_account_at?: string | null;
    created_at?: string;
}

export type ResultsVisibility = 'live' | 'after_close' | 'private';

export interface AuditEntry {
    id: number;
    action: string;
    description: string;
    during_voting: boolean;
    created_at: string;
    user: { id: number; name: string } | null;
}

export interface Category {
    id: number;
    event_id: number;
    name: string;
    /** Multiple-choice events: most picks allowed (null = no limit). */
    max_choices?: number | null;
}

export interface Candidate {
    id: number;
    event_id?: number;
    category_id: number;
    name: string;
    description?: string | null;
    image_path: string | null;
    image_url: string | null;
}

export interface Voter {
    id: number;
    event_id: number;
    name: string | null;
    email: string | null;
    phone: string | null;
    code: string;
    used_at: string | null;
    /** When the code was last delivered by email/SMS. */
    invited_at: string | null;
    /** Last delivery error, if any. */
    invite_error: string | null;
    created_at?: string;
}

export interface BallotEvent {
    id: number;
    name: string;
    description: string | null;
    link_token: string;
    starts_at: string | null;
    ends_at: string | null;
    status: EventStatus;
    selection_mode: SelectionMode;
    results_visibility: ResultsVisibility;
    /** Structure frozen: first vote cast or voting closed. */
    ballot_locked: boolean;
    created_at: string;
    plan?: 'free' | 'pro';
    upgraded_via?: 'payment' | 'admin' | null;
    /** What this event may do on its plan (Free or Pro). */
    limits?: EventLimits;
    categories_count?: number;
    candidates_count?: number;
    voters_count?: number;
    voted_count?: number;
    categories?: Category[];
    candidates?: Candidate[];
    voters?: Voter[];
}

/** What the public ballot endpoint returns for an open event. */
export interface PublicEvent {
    id: number;
    name: string;
    description: string | null;
    starts_at: string | null;
    ends_at: string | null;
    status: EventStatus;
    selection_mode: SelectionMode;
    results_visibility: ResultsVisibility;
    /** Free events show "Powered by Ballotify". */
    branding?: boolean;
    categories: Pick<Category, 'id' | 'name' | 'max_choices'>[];
    candidates: Candidate[];
}

export interface Choice {
    category_id: number;
    candidate_id: number;
}

export interface ResultCandidate {
    id: number;
    name: string;
    description?: string | null;
    image_url: string | null;
    votes: number;
}

export interface ResultCategory {
    id: number;
    name: string;
    candidates: ResultCandidate[];
    leader: ResultCandidate | null;
}

export interface EventResults {
    event: {
        id: number;
        name: string;
        status: EventStatus;
        starts_at: string | null;
        ends_at: string | null;
        results_visibility?: ResultsVisibility;
        branding?: boolean;
    };
    stats: {
        total_votes: number;
        total_voters: number;
        voted: number;
        total_candidates: number;
    };
    integrity?: { changes_during_voting: number };
    categories: ResultCategory[];
}

export interface GeneratedCode {
    name: string | null;
    email?: string | null;
    phone?: string | null;
    code: string;
}

export interface VoterContact {
    name?: string | null;
    email?: string | null;
    phone?: string | null;
}

export type InviteChannel = "email" | "sms" | "both";

export interface MessagingStatus {
    email: { live: boolean; provider: string };
    sms: { live: boolean; provider: string };
    queue: string;
}

// ---------------------------------------------------------------- platform admin

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface PlatformOverview {
    users: number;
    users_last_30_days: number;
    suspended_users: number;
    events: number;
    events_live: number;
    voters: number;
    ballots_cast: number;
    votes_counted: number;
    pro_events: number;
    revenue_naira: number;
    revenue_last_30_days_naira: number;
}

export interface PlatformUser extends User {
    events_count: number;
}

export interface PlatformEvent {
    id: number;
    name: string;
    status: EventStatus;
    selection_mode: SelectionMode;
    results_visibility: ResultsVisibility;
    starts_at: string | null;
    ends_at: string | null;
    created_at: string;
    voters_count: number;
    voted_count: number;
    plan: 'free' | 'pro';
    upgraded_via: 'payment' | 'admin' | null;
    user: { id: number; name: string; email: string; suspended_at: string | null; pro_account_at: string | null; is_admin: boolean } | null;
}

export interface CandidateImportRow {
    category: string;
    name: string;
    description?: string | null;
}

export interface CandidateImportResult {
    created: number;
    categories_created: number;
    skipped: { row: number; reason: string }[];
}

// ---------------------------------------------------------------- billing

export interface EventLimits {
    plan: 'free' | 'pro';
    /** null = no cap (billing switched off). */
    max_voters: number | null;
    messaging: boolean;
    branding: boolean;
    /** Pro because the whole account was upgraded by the admin. */
    via_account: boolean;
}

export interface PlanInfo {
    billing_enabled: boolean;
    payments_enabled: boolean;
    currency: string;
    pro_price_naira: number;
    free: { max_active_events: number; max_voters: number; messaging: boolean; branding: boolean };
    pro: { max_voters: number; messaging: boolean; branding: boolean };
    /** Dashboard only. */
    account_pro?: boolean;
    /** Dashboard only: whether this account may create another event right now. */
    event_quota?: EventQuota;
}

export interface EventQuota {
    can_create: boolean;
    /** null = no limit (Pro account or billing off). */
    max: number | null;
    /** The open Free events using up the allowance. */
    open_events: { id: number; name: string; ends_at: string | null }[];
}

export interface PlanSettings {
    pro_price_naira: number;
    free_max_active_events: number;
    free_max_voters: number;
    pro_max_voters: number;
    billing_enabled: boolean;
    payments_enabled: boolean;
}

export interface PlatformPayment {
    id: number;
    reference: string;
    event_id: number | null;
    event_name: string | null;
    amount_kobo: number;
    currency: string;
    status: 'pending' | 'success' | 'failed';
    channel: string | null;
    failure_reason: string | null;
    paid_at: string | null;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
}
