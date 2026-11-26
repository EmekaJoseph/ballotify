import type { PlanSize } from '~/types';

/** Display names of every plan size (Unlimited is only ever set by the platform admin). */
export const PLAN_SIZE_NAMES: Record<PlanSize, string> = {
    small: 'Pro Small',
    standard: 'Pro Medium',
    large: 'Pro Large',
    unlimited: 'Pro Unlimited',
};

/** "free" or a Pro size: what the admin plan pickers choose between. */
export type AdminPlanChoice = 'free' | PlanSize;

/** Options for the admin plan pickers, smallest first. */
export function adminPlanOptions(freeLabel = 'Free'): { value: AdminPlanChoice; label: string }[] {
    return [
        { value: 'free', label: freeLabel },
        ...(Object.keys(PLAN_SIZE_NAMES) as PlanSize[]).map(k => ({ value: k as AdminPlanChoice, label: PLAN_SIZE_NAMES[k] })),
    ];
}
