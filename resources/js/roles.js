// Combat role metadata, matching the tactical design.
// Sniper = purple accent; rifle = muted grey. One sniper per team on shuffle.
export const ROLE_ORDER = ['rifle', 'sniper'];

export const ROLE_META = {
    rifle: {
        label: 'RIFLE',
        color: '#9aa0ac',
        badgeColor: '#8a909c',
        badgeBg: 'transparent',
        badgeBorder: '#2b303a',
    },
    sniper: {
        label: 'SNIPER',
        color: '#a87cf2',
        badgeColor: '#c3a3f5',
        badgeBg: 'rgba(168,124,242,.15)',
        badgeBorder: '#7c5cb8',
    },
};

/** Toggle between the two roles. */
export const nextRole = (role) => (role === 'sniper' ? 'rifle' : 'sniper');
