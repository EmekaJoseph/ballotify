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
    /** Size of the admin-granted account plan (null = Large). */
    pro_account_tier?: PlanSize | null;
    /** Prepaid SMS credits, used once an event's included SMS run out. */
    sms_credits?: number;
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
    /** plurality = most votes wins (choose one / up to N / approval); ranked = ranked choice; motion = Yes/No/Abstain. */
    method?: VotingMethod;
    /** Motions: the full text. */
    description?: string | null;
    /** Motions: simple | two_thirds | three_quarters (of Yes + No). */
    pass_rule?: PassRule | null;
    /** Voter groups allowed to vote in this category (null/empty = everyone). */
    groups?: string[] | null;
}

export type VotingMethod = 'plurality' | 'ranked' | 'motion';
export type PassRule = 'simple' | 'two_thirds' | 'three_quarters';
export type MotionChoice = 'yes' | 'no' | 'abstain';

/** Outcome of a motion: abstentions don't count towards the majority. */
export interface MotionResult { yes: number; no: number; abstain: number; rule: PassRule; decided: boolean; passed: boolean }

/** One round of a ranked-choice count. */
export interface RankedRound {
    counts: { id: number; votes: number }[];
    eliminated: number[];
    /** Ballots with no choices left in the race. */
    exhausted: number;
    continuing: number;
}

export interface Candidate {
    id: number;
    event_id?: number;
    category_id: number;
    name: string;
    description?: string | null;
    image_path: string | null;
    image_url: string | null;
    /** Motions only: the fixed option this is. */
    motion_choice?: MotionChoice | null;
}

export interface Voter {
    id: number;
    event_id: number;
    name: string | null;
    email: string | null;
    phone: string | null;
    member_id?: string | null;
    /** Voter groups (branch, unit, department...): decide which categories they see. */
    groups?: string[] | null;
    /** Weighted votes: how many votes this voter's ballot counts as (1 unless set). */
    weight?: number;
    /** Proxy: the voter who votes for this member (who then can't vote themselves). */
    proxy_voter_id?: number | null;
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
    /** Meeting rules (Pro, all off by default). */
    weighted_votes?: boolean;
    proxies_enabled?: boolean;
    max_proxies?: number;
    quorum_type?: QuorumType | null;
    quorum_value?: number | null;
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
    categories: Pick<Category, 'id' | 'name' | 'max_choices' | 'method' | 'description' | 'pass_rule'>[];
    candidates: Candidate[];
}

export interface Choice {
    category_id: number;
    candidate_id: number;
    /** Ranked-choice categories: 1 = first choice. */
    rank?: number;
}

export interface ResultCandidate {
    id: number;
    name: string;
    description?: string | null;
    image_url: string | null;
    votes: number;
    motion_choice?: MotionChoice | null;
}

export interface Brand {
    color: string | null;
    logo_url: string | null;
}

export interface ResultCategory {
    id: number;
    name: string;
    max_choices?: number | null;
    method?: VotingMethod;
    /** Ballots that answered this category (the base for "% of voters"). */
    ballots?: number;
    /** Winning candidate ids, by the category's method (several = a tie). */
    winners?: number[];
    /** Ranked choice: the rounds of the count. */
    rounds?: RankedRound[] | null;
    /** Motions: text, pass rule and outcome. */
    description?: string | null;
    pass_rule?: PassRule | null;
    motion?: MotionResult | null;
    /** Limited to some voter groups: the groups, and how many voters may vote in it. */
    groups?: string[] | null;
    eligible?: number | null;
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
    /** votes_reset: each test-run reset (when, by whom, how many voters' votes were erased). */
    integrity?: { changes_during_voting: number; votes_reset?: VoteReset[] };
    /** Weighted votes / proxies / quorum in force (null when none are on). */
    rules?: MeetingRulesSummary | null;
    /** Votes over time (turnout chart). */
    timeline?: { bucket_minutes: number; points: { t: string; votes: number }[] };
    categories: ResultCategory[];
}

export type QuorumType = 'percent' | 'count';

/** Quorum progress: `basis` is members ("voters") or their total weight. */
export interface QuorumStatus {
    type: QuorumType;
    value: number;
    basis: 'voters' | 'weight';
    total: number;
    required: number;
    achieved: number;
    met: boolean;
}

export interface MeetingRulesSummary {
    weighted: boolean;
    proxies: boolean;
    max_proxies: number | null;
    quorum: QuorumStatus | null;
    /** Members whose vote was cast by their proxy. */
    proxy_votes?: number;
    total_weight?: number;
    voted_weight?: number;
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
    /** Groups, e.g. ["Lagos", "Youth"] (or "Lagos; Youth"). */
    groups?: string[] | string | null;
    /** Weighted votes: shares / units (empty = 1). */
    weight?: number | string | null;
}

export interface VoterGroup { name: string; voters: number }

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
    pro_tier?: PlanSize | null;
    upgraded_via: 'payment' | 'admin' | null;
    user: { id: number; name: string; email: string; suspended_at: string | null; pro_account_at: string | null; pro_account_tier?: PlanSize | null; is_admin: boolean } | null;
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

export type ProTier = 'small' | 'standard' | 'large';
/** Every plan size: the three sold at checkout, plus Unlimited (set by the platform admin only). */
export type PlanSize = ProTier | 'unlimited';

/** A Pro size: price per event, voter cap and SMS included (1 per voter). */
export interface PlanTier { key: ProTier; name: string; price_naira: number; max_voters: number; sms_included: number }

/** An event's SMS: included with its Pro size, used so far, and the owner's prepaid credits. */
export interface SmsStatus { included: number; used: number; left: number; credits: number; unlimited: boolean; price_naira: number }

export interface EventLimits {
    plan: 'free' | 'pro';
    /** Pro size (null on Free). */
    tier?: PlanSize | null;
    tier_name?: string | null;
    sms?: SmsStatus | null;
    /** null = no cap (billing switched off). */
    max_voters: number | null;
    messaging: boolean;
    branding: boolean;
    /** Own logo and colour on the ballot (Pro). */
    custom_branding: boolean;
    /** Co-organisers (Pro). */
    team: boolean;
    /** Weighted votes, proxies and quorum. */
    meeting_rules?: boolean;
    /** null = no cap (Pro Unlimited). */
    max_members: number | null;
    /** Pro because the whole account was upgraded by the admin. */
    via_account: boolean;
}

export interface PlanInfo {
    billing_enabled: boolean;
    payments_enabled: boolean;
    currency: string;
    pro_price_naira: number;
    /** Pro sizes, smallest first. */
    tiers: PlanTier[];
    /** Price of one extra SMS (credits). */
    sms_price_naira: number;
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
    pro_small_price_naira: number;
    pro_small_max_voters: number;
    pro_large_price_naira: number;
    pro_large_max_voters: number;
    sms_price_naira: number;
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
    kind?: 'upgrade' | 'sms';
    tier?: ProTier | null;
    sms_credits?: number;
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
    categories: { id: number; name: string; suggestions: string[]; nominees: { name: string; count: number; reasons: string[]; on_ballot: boolean }[] }[];
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
    /** suggestions: names the organiser listed; people may pick one or type their own. */
    categories: { id: number; name: string; suggestions: string[] }[];
}

/** A "reset votes and results" (test run): always shown with the results. */
export interface VoteReset { at: string | null; by: string | null; voters: number }

export interface CertificatePayload {
    event: { id: number; name: string; organiser: string | null; voting_mode: VotingMode; starts_at: string | null; ends_at: string | null };
    stats: { voters?: number; ballots?: number; receipts?: number; votes?: number; payments?: number };
    /** Weighted votes / proxies / quorum, when used. */
    rules?: MeetingRulesSummary | null;
    categories: {
        name: string; candidates: { name: string; votes: number }[]; winners: string[];
        /** Ranked choice: candidate votes are first choices; the final round decided it. */
        method?: 'ranked' | 'motion'; rounds?: number; final_round?: { name: string; votes: number }[];
        motion?: MotionResult;
    }[];
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

export interface CandidateProfile {
    id: number;
    name: string;
    description: string | null;
    image_url: string | null;
    category: string | null;
    event: { name: string; status: EventStatus; link_token: string; brand_color?: string | null };
    editable: boolean;
}

export interface Bank {
    name: string;
    code: string;
}