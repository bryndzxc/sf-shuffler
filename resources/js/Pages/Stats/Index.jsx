import AppLayout from '@/Layouts/AppLayout';
import { useIsDesktop } from '@/hooks';
import { ROLE_META } from '@/roles';
import { TIER_COLOR, TIER_GLOW, initials } from '@/tiers';

const INK = '#0a0b0e';

function rankColor(rank) {
    return rank === 1 ? '#f59e0b' : rank === 2 ? '#c8ccd4' : rank === 3 ? '#cd7f32' : '#5a606c';
}

function streakChip(streak) {
    if (streak > 0) return { label: 'W' + streak, color: '#34d399', bg: 'rgba(52,211,153,.12)' };
    if (streak < 0) return { label: 'L' + -streak, color: '#ef4444', bg: 'rgba(239,68,68,.12)' };
    return { label: '—', color: '#8a909c', bg: 'rgba(138,144,156,.1)' };
}

function LeaderRow({ row, rank }) {
    const tierColor = TIER_COLOR[row.tier];
    const role = ROLE_META[row.role];
    const winPct = Math.round(row.win_rate * 100);
    const streak = streakChip(row.streak);
    const top = rank <= 3;

    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 11, padding: '11px 12px', background: top ? '#12141a' : '#0e1015', border: `1px solid ${rank === 1 ? 'rgba(245,158,11,.4)' : '#1c1f26'}`, borderRadius: 3 }}>
            <div style={{ width: 24, flexShrink: 0, textAlign: 'center', fontFamily: "'Oswald'", fontWeight: 700, fontSize: 18, color: rankColor(rank), lineHeight: 1 }}>
                {rank}
            </div>

            <div style={{ width: 40, height: 40, flexShrink: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', background: INK, border: `1.5px solid ${tierColor}`, clipPath: 'polygon(0 0,100% 0,100% 75%,75% 100%,0 100%)', fontFamily: "'Share Tech Mono'", fontSize: 13, color: tierColor, boxShadow: `inset 0 0 12px ${TIER_GLOW[row.tier]}` }}>
                {initials(row.name)}
            </div>

            <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 7 }}>
                    <span style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 15, letterSpacing: '.05em', color: '#eef0f3', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                        {row.name}
                    </span>
                    <span style={{ flexShrink: 0, fontFamily: "'Oswald'", fontWeight: 700, fontSize: 9, color: INK, background: tierColor, padding: '1px 5px', borderRadius: 1 }}>
                        {row.tier}
                    </span>
                    <span style={{ flexShrink: 0, fontFamily: "'Oswald'", fontWeight: 700, fontSize: 9, letterSpacing: '.06em', color: role.badgeColor, background: role.badgeBg, border: `1px solid ${role.badgeBorder}`, padding: '1px 6px', borderRadius: 2 }}>
                        {role.label}
                    </span>
                </div>
                <div style={{ display: 'flex', height: 4, borderRadius: 3, overflow: 'hidden', background: '#070809', marginTop: 7 }}>
                    <div style={{ width: `${winPct}%`, background: tierColor }} />
                </div>
                <div style={{ fontFamily: "'Share Tech Mono'", fontSize: 10, color: '#565c68', marginTop: 5 }}>
                    <span style={{ color: '#f59e0b' }}>{row.mmr} MMR</span> · {row.wins}W · {row.games} GP
                </div>
            </div>

            <div style={{ flexShrink: 0, textAlign: 'right' }}>
                <div style={{ fontFamily: "'Share Tech Mono'", fontSize: 20, color: '#f4f5f7', lineHeight: 1 }}>
                    {winPct}%
                </div>
                <div style={{ display: 'inline-block', fontFamily: "'Oswald'", fontWeight: 600, fontSize: 10, letterSpacing: '.08em', marginTop: 5, color: streak.color, background: streak.bg, padding: '2px 7px', borderRadius: 2 }}>
                    {streak.label}
                </div>
            </div>
        </div>
    );
}

export default function StatsIndex({ leaderboard, matchCount }) {
    const isDesktop = useIsDesktop();

    const listStyle = isDesktop
        ? { display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(380px,1fr))', gap: 10 }
        : { display: 'flex', flexDirection: 'column', gap: 8 };

    return (
        <AppLayout title="Leaderboards">
            <div style={{ display: 'flex', alignItems: 'center', gap: 9, marginBottom: 14 }}>
                <div style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 13, letterSpacing: '.16em', color: '#8a909c', whiteSpace: 'nowrap', flexShrink: 0 }}>
                    LEADERBOARDS
                </div>
                <div style={{ flex: 1, height: 1, background: '#1c1f26' }} />
                <div style={{ fontFamily: "'Share Tech Mono'", fontSize: 11, color: '#565c68', whiteSpace: 'nowrap', flexShrink: 0 }}>
                    BY WIN RATE
                </div>
            </div>

            {leaderboard.length === 0 ? (
                <div style={{ textAlign: 'center', padding: '42px 20px', border: '1px dashed #21252e', borderRadius: 3, fontFamily: "'Oswald'", fontWeight: 600, letterSpacing: '.1em', color: '#8a909c' }}>
                    NO PLAYERS YET
                </div>
            ) : (
                <>
                    {matchCount === 0 && (
                        <div style={{ fontFamily: "'Share Tech Mono'", fontSize: 11, color: '#565c68', marginBottom: 12 }}>
                            No matches recorded yet — stats fill in once you log results on Deploy.
                        </div>
                    )}
                    <div style={listStyle}>
                        {leaderboard.map((row, i) => (
                            <LeaderRow key={row.id} row={row} rank={i + 1} />
                        ))}
                    </div>
                </>
            )}
        </AppLayout>
    );
}
