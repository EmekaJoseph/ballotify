export type EventStatus = 'upcoming' | 'active' | 'completed';
export type SelectionMode = 'single' | 'multiple';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    two_factor_enabled: boolean;
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
}

export interface Candidate {
    id: number;
    event_id?: number;
    category_id: number;
    name: string;
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
    categories: Pick<Category, 'id' | 'name'>[];
    candidates: Candidate[];
}

export interface Choice {
    category_id: number;
    candidate_id: number;
}

export interface ResultCandidate {
    id: number;
    name: string;
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
