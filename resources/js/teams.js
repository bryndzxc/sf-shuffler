// Team metadata by index, matching the tactical design (NATO phonetic).
// Up to 8 teams (4 games). Distinct colors so paired games read clearly.
export const TEAM_META = [
    { label: 'ALPHA', color: '#f59e0b' },
    { label: 'BRAVO', color: '#5b8bc4' },
    { label: 'CHARLIE', color: '#57b894' },
    { label: 'DELTA', color: '#a87cf2' },
    { label: 'ECHO', color: '#e06c9f' },
    { label: 'FOXTROT', color: '#4cc9d6' },
    { label: 'GOLF', color: '#e8743b' },
    { label: 'HOTEL', color: '#9bc24a' },
    { label: 'INDIA', color: '#e8b04b' },
    { label: 'JULIET', color: '#6f6fe0' },
];

export const MAX_TEAMS = TEAM_META.length;

/** Resolved team label: custom name for the slot, else the NATO default. */
export function teamLabel(index, customNames) {
    const custom = customNames?.[index];
    if (custom && custom.trim()) return custom;
    return TEAM_META[index]?.label ?? `TEAM ${index + 1}`;
}

export function teamColor(index) {
    return TEAM_META[index]?.color ?? '#9aa0ac';
}
