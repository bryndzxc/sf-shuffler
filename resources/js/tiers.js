// Tier metadata shared across the app, matching the tactical design.
// Tier colors (per the plan + design): S red, A amber, B steel blue, C grey.
export const TIER_ORDER = ['S', 'A', 'B', 'C'];

export const TIER_COLOR = {
    S: '#ef4444',
    A: '#f59e0b',
    B: '#5b8bc4',
    C: '#8b91a0',
};

export const TIER_GLOW = {
    S: 'rgba(239,68,68,.45)',
    A: 'rgba(245,158,11,.45)',
    B: 'rgba(91,139,196,.4)',
    C: 'rgba(139,145,160,.3)',
};

/** Next tier in the S → A → B → C → S cycle. */
export const nextTier = (tier) =>
    TIER_ORDER[(TIER_ORDER.indexOf(tier) + 1) % TIER_ORDER.length];

/** Two-letter avatar initials from a callsign. */
export const initials = (name) =>
    (name || '').replace(/[^A-Za-z0-9]/g, '').slice(0, 2).toUpperCase() || '??';
