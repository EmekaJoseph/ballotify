export type EventStatus = 'upcoming' | 'active' | 'completed';
export type MemberRole = 'editor' | 'voters' | 'viewer';
export type EventRole = 'owner' | MemberRole;
export type EventAbility = 'view' | 'voters' | 'manage' | 'own';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    two_factor_enabled: boolean;
    /** Platform owner: can open the admin area. */
    is_admin?: boolean;
    suspended_at?: string | null;
    /** When the platform admin approved the account (null = waiting). */
    approved_at?: string | null;
    /** A new account the admin hasn't approved yet: it can't create events. */
    awaiting_approval?: boolean;
    /** First-run tour seen. */
    onboarded_at?: string | null;
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

/** A post on the ballot. Most votes wins. */
export interface Category {
    id: number;
    event_id: number;
    name: string;
    /** Most picks allowed: 1 = choose one (default), null = no limit. */
    max_choices?: number | null;
    /** Candidates shown in random order on each ballot. */
    shuffle_candidates?: boolean;
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
    /** The organiser's own ID for this voter (matric number, member number...). */
    member_id?: string | null;
    code: string;
    used_at: string | null;
    /** When the code was last delivered (by email, or shared on WhatsApp). */
    invited_at: string | null;
    invited_via?: 'email' | 'whatsapp' | null;
    reminded_at?: string | null;
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
    results_visibility: ResultsVisibility;
    /** The organisation's logo on the public pages. */
    brand_logo_url?: string | null;
    /** Scheduled email reminder to voters who haven't voted. */
    reminder_at?: string | null;
    reminder_sent_at?: string | null;
    /** Name of the account that owns the event. */
    owner_name?: string | null;
    nominations_enabled?: boolean;
    nominations_ends_at?: string | null;
    /** This user's role and what it allows. */
    my_role?: EventRole;
    abilities?: EventAbility[];
    members_count?: number;
    /** Structure frozen: first vote cast or voting closed. */
    ballot_locked: boolean;
    created_at: string;
    categories_count?: number;
    candidates_count?: number;
    voters_count?: number;
    voted_count?: number;
    categories?: Category[];
    candidates?: Candidate[];
    voters?: Voter[];
}

/** The event's logo on the public pages. */
export interface Brand {
    logo_url: string | null;
}

/** What the public ballot endpoint returns for an open event. */
export interface PublicEvent {
    id: number;
    name: string;
    description: string | null;
    starts_at: string | null;
    ends_at: string | null;
    status: EventStatus;
    results_visibility: ResultsVisibility;
    brand?: Brand | null;
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
    max_choices?: number | null;
    /** Ballots that answered this category (the base for "% of voters"). */
    ballots?: number;
    /** Winning candidate ids (several = a tie). */
    winners?: number[];
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
        brand?: Brand | null;
    };
    stats: {
        total_votes: number;
        total_voters: number;
        voted: number;
        total_candidates: number;
    };
    /** votes_reset: each test-run reset (when, by whom, how many voters' votes were erased). */
    integrity?: { changes_during_voting: number; votes_reset?: VoteReset[] };
    /** Votes over time (turnout chart). */
    timeline?: { bucket_minutes: number; points: { t: string; votes: number }[] };
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
    member_id?: string | null;
}

export interface MessagingStatus {
    email: { live: boolean; provider: string };
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
    /** New accounts waiting for the admin's approval. */
    awaiting_approval: number;
}

export interface PlatformUser extends User {
    events_count: number;
}

export interface PlatformEvent {
    id: number;
    name: string;
    status: EventStatus;
    results_visibility: ResultsVisibility;
    starts_at: string | null;
    ends_at: string | null;
    created_at: string;
    voters_count: number;
    voted_count: number;
    user: { id: number; name: string; email: string; suspended_at: string | null; approved_at: string | null; is_admin: boolean } | null;
}

export interface SystemHealth {
    queue: { driver: string; pending: number | null; failed: number; needs_worker: boolean };
    scheduler: { last_run: string | null; running: boolean };
    mail: { driver: string; from: string | null };
    broadcasting: string;
    delivery_errors: { last_7_days: number; recent: { event: string | null; event_id: number; error: string; at: string }[] };
    failed_jobs: { uuid: string; queue: string; job: string; error: string; failed_at: string }[];
}

// ---------------------------------------------------------------- import, templates, team

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

export interface EventTemplate {
    key: string;
    name: string;
    summary: string;
    icon: string;
    results_visibility: ResultsVisibility;
    categories: string[];
}

export interface TeamMember {
    id: number;
    email: string;
    name: string | null;
    role: MemberRole;
    /** Has a confirmed account linked. */
    joined: boolean;
    created_at?: string;
}

export interface TeamInfo {
    owner: { name: string; email: string };
    members: TeamMember[];
}

export interface ReceiptCheck {
    valid: boolean;
    message: string;
    counted_at?: string | null;
}

// ---------------------------------------------------------------- nominations, certificates

export interface NominationSummary {
    open: boolean;
    enabled: boolean;
    ends_at: string | null;
    total: number;
    categories: { id: number; name: string; suggestions: string[]; nominees: { name: string; count: number; reasons: string[]; on_ballot: boolean }[] }[];
}

export interface NominationForm {
    name: string;
    description: string | null;
    open: boolean;
    ends_at: string | null;
    voting_starts_at: string | null;
    brand: Brand | null;
    /** suggestions: names the organiser listed; people may pick one or type their own. */
    categories: { id: number; name: string; suggestions: string[] }[];
    /** Names one person may put forward per category. */
    per_category?: number;
}

/** The whole nomination form, sent at once: what was kept, and what wasn't (with why). */
export interface NominationResult {
    message: string;
    accepted: { category_id: number; category: string; name: string }[];
    refused: { category_id: number; category: string | null; name: string; reason: string }[];
}

/** A "reset votes and results" (test run): always shown with the results. */
export interface VoteReset { at: string | null; by: string | null; voters: number }

export interface CertificatePayload {
    event: { id: number; name: string; organiser: string | null; starts_at: string | null; ends_at: string | null };
    stats: { voters?: number; ballots?: number; receipts?: number };
    categories: { name: string; candidates: { name: string; votes: number }[]; winners: string[] }[];
    issued_at: string;
    /** Test-run resets before these results (only present when there were any). */
    votes_reset?: VoteReset[];
}

export interface ResultCertificateInfo {
    serial: string;
    issued_at: string;
    signature: string;
    payload: CertificatePayload;
    verify_url: string;
    certificate_url: string;
    /** Public verification only. */
    valid?: boolean;
    matches_current?: boolean | null;
    message?: string;
}
