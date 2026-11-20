export type EventStatus = 'upcoming' | 'active' | 'completed';
export type VotingMode = 'codes' | 'paid';
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
    /** Granted by the platform admin: every event of this account is Pro. */
    pro_account_at?: string | null;
    /** Where paid-voting money is sent. */
    payout_bank_name?: string | null;
    payout_account_number?: string | null;
    payout_account_name?: string | null;
    payout_bank_code?: string | null;
    /** Set once Paystack pays the organiser automatically (split payments). */
    paystack_subaccount_code?: string | null;
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
    member_id?: string | null;
    code: string;
    used_at: string | null;
    /** When the code was last delivered (email, SMS or shared on WhatsApp). */
    invited_at: string | null;
    invited_via?: 'email' | 'sms' | 'whatsapp' | null;
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
    voting_mode: VotingMode;
    vote_price_naira: number | null;
    brand_color?: string | null;
    brand_logo_url?: string | null;
    reminder_at?: string | null;
    reminder_channel?: InviteChannel | null;
    reminder_sent_at?: string | null;
    /** Voters can request their own code with an ID on the list. */
    self_registration?: boolean;
    member_id_label?: string | null;
    /** Default language of the public voting pages (voters can switch). */
    language?: string;
    /** Name of the account that owns the event. */
    owner_name?: string | null;
    ussd_enabled?: boolean;
    ussd_code?: string | null;
    /** The shortcode voters dial (null = USSD not set up on this platform). */
    ussd_service_code?: string | null;
    nominations_enabled?: boolean;
    nominations_ends_at?: string | null;
    /** This user's role and what it allows. */
    my_role?: EventRole;
    abilities?: EventAbility[];
    members_count?: number;
    /** Paid voting: votes bought so far. */
    votes_sold?: number;
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
    /** Default language of the voting pages. */
    language?: string;
    id: number;
    name: string;
    description: string | null;
    starts_at: string | null;
    ends_at: string | null;
    status: EventStatus;
    results_visibility: ResultsVisibility;
    voting_mode: VotingMode;
    vote_price_naira: number | null;
    /** Paid voting: Paystack is set up. */
    payments_enabled: boolean | null;
    min_payment_naira: number | null;
    /** Free events show "Powered by Ballotify". */
    branding?: boolean;
    /** Pro events: the organiser's own colour and logo. */
    brand?: Brand | null;
    self_registration?: boolean;
    member_id_label?: string;
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

export interface Brand {
    color: string | null;
    logo_url: string | null;
}

export interface ResultCategory {
    id: number;
    name: string;
    max_choices?: number | null;
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
        voting_mode?: VotingMode;
        branding?: boolean;
        brand?: Brand | null;
        language?: string;
    };
    stats: {
        total_votes: number;
        total_voters: number;
        voted: number;
        total_candidates: number;
    };
    integrity?: { changes_during_voting: number };
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
    paid_votes_gross_naira: number;
    paid_votes_fees_naira: number;
}

export interface PlatformUser extends User {
    events_count: number;
}

export interface PlatformEvent {
    id: number;
    name: string;
    status: EventStatus;
    voting_mode: VotingMode;
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
    /** Own logo and colour on the ballot (Pro). */
    custom_branding: boolean;
    /** Co-organisers (Pro). */
    team: boolean;
    max_members: number;
    /** Pro because the whole account was upgraded by the admin. */
    via_account: boolean;
}

export interface PlanInfo {
    billing_enabled: boolean;
    payments_enabled: boolean;
    currency: string;
    pro_price_naira: number;
    free: { max_active_events: number; max_voters: number; messaging: boolean; branding: boolean };
    pro: { max_voters: number; messaging: boolean; branding: boolean; custom_branding?: boolean; team?: boolean };
    paid_votes?: { fee_percent: number; min_payment_naira: number };
    /** Dashboard only. */
    account_pro?: boolean;
    /** Dashboard only: how many live events this account may run. */
    event_quota?: EventQuota;
}

export interface EventQuota {
    /** Drafts are always allowed. */
    can_create: boolean;
    /** Whether another free event may get voters (go live). */
    can_go_live: boolean;
    /** null = no limit (Pro account or billing off). */
    max: number | null;
    /** The live Free events using up the allowance. */
    open_events: { id: number; name: string; ends_at: string | null }[];
}

export interface PlanSettings {
    pro_price_naira: number;
    free_max_active_events: number;
    free_max_voters: number;
    pro_max_voters: number;
    paid_vote_fee_percent: number;
    billing_enabled: boolean;
    payments_enabled: boolean;
    /** Configured providers in order of preference, e.g. ['paystack', 'flutterwave']. */
    payment_providers?: string[];
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

// ---------------------------------------------------------------- templates, team, paid voting

export interface EventTemplate {
    key: string;
    name: string;
    summary: string;
    icon: string;
    voting_mode: VotingMode;
    results_visibility: ResultsVisibility;
    vote_price_naira: number | null;
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

export interface Earnings {
    currency: string;
    fee_percent: number;
    votes_sold: number;
    payments: number;
    gross_naira: number;
    fee_naira: number;
    net_naira: number;
    paid_out_naira: number;
    balance_naira: number;
    late_payments: number;
    payouts: { amount_naira: number; note: string | null; created_at: string }[];
    recent: { candidate: string; quantity: number; amount_naira: number; buyer: string; status: string; paid_at: string | null }[];
    /** Paid straight to the organiser by Paystack (split payments). */
    auto_paid_naira?: number;
    automatic_payouts?: boolean;
}

export interface ReceiptCheck {
    valid: boolean;
    message: string;
    counted_at?: string | null;
    status?: string;
    votes?: number;
    candidate?: string;
}

export interface MyPayment {
    reference: string;
    event_id: number | null;
    event_name: string | null;
    amount_naira: number;
    currency: string;
    status: 'pending' | 'success' | 'failed';
    channel: string | null;
    paid_at: string | null;
    created_at: string;
}

export interface PaymentReceiptInfo {
    reference: string;
    item: string;
    event_name: string | null;
    amount_naira: number;
    currency: string;
    channel: string | null;
    paid_at: string | null;
    billed_to: { name: string; email: string };
    seller: { name: string; email: string | null };
}

export interface PlatformPayout {
    id: number;
    name: string;
    status: EventStatus;
    ends_at: string | null;
    organiser: { id: number; name: string; email: string } | null;
    bank: { bank_name: string | null; account_number: string; account_name: string | null } | null;
    gross_naira: number;
    fee_naira: number;
    net_naira: number;
    paid_out_naira: number;
    auto_paid_naira?: number;
    balance_naira: number;
    late_payments: number;
}

export interface SystemHealth {
    queue: { driver: string; pending: number | null; failed: number; needs_worker: boolean };
    scheduler: { last_run: string | null; running: boolean };
    mail: { driver: string; from: string | null };
    sms: { driver: string | null };
    paystack: boolean;
    flutterwave?: boolean;
    /** Paystack failed to start a checkout in the last few minutes (payers went to the backup). */
    paystack_down?: boolean;
    broadcasting: string;
    delivery_errors: { last_7_days: number; recent: { event: string | null; event_id: number; error: string; at: string }[] };
    failed_jobs: { uuid: string; queue: string; job: string; error: string; failed_at: string }[];
}
// ---------------------------------------------------------------- nominations, certificates, banks

export interface NominationSummary {
    open: boolean;
    enabled: boolean;
    ends_at: string | null;
    total: number;
    categories: { id: number; name: string; nominees: { name: string; count: number; reasons: string[]; on_ballot: boolean }[] }[];
}

export interface NominationForm {
    /** Default language of the voting pages. */
    language?: string;
    name: string;
    description: string | null;
    open: boolean;
    ends_at: string | null;
    voting_starts_at: string | null;
    brand: Brand | null;
    categories: { id: number; name: string }[];
}

export interface CertificatePayload {
    event: { id: number; name: string; organiser: string | null; voting_mode: VotingMode; starts_at: string | null; ends_at: string | null };
    stats: { voters?: number; ballots?: number; receipts?: number; votes?: number; payments?: number };
    categories: { name: string; candidates: { name: string; votes: number }[]; winners: string[] }[];
    issued_at: string;
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

export interface CandidateProfile {
    name: string;
    description: string | null;
    image_url: string | null;
    category: string | null;
    event: { name: string; status: EventStatus };
    editable: boolean;
}

export interface Bank {
    name: string;
    code: string;
}