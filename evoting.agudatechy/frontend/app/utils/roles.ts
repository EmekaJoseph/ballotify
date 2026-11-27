import type { EventRole } from '~/types';

export const ROLE_INFO: Record<EventRole, { label: string; text: string; icon: string }> = {
    owner: { label: 'Owner', text: 'Everything, including the team and deleting the event.', icon: 'bi-star' },
    editor: { label: 'Editor', text: 'Ballot, settings, voters and results. Can’t delete the event or manage the team.', icon: 'bi-pencil-square' },
    voters: { label: 'Voter manager', text: 'Voter list, codes, invitations and reminders. Sees results.', icon: 'bi-people' },
    viewer: { label: 'Viewer', text: 'Read only: overview, ballot, results and activity.', icon: 'bi-eye' },
};

export function roleLabel(role?: EventRole | null): string {
    return role ? ROLE_INFO[role].label : '';
}
