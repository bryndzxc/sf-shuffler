import AppLayout from '@/Layouts/AppLayout';
import { useIsDesktop } from '@/hooks';
import { teamColor, teamLabel } from '@/teams';
import { Link, usePage } from '@inertiajs/react';

const INK = '#0a0b0e';

function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return (
        d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) +
        ' · ' +
        d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
    );
}

function MatchCard({ match, isDesktop, names }) {
    const draw = match.winner_team === null;
    const cols = Math.min(match.teams.length, isDesktop ? 4 : 2);

    return (
        <div style={{ background: '#101218', border: '1px solid #21252e', borderRadius: 3, padding: '12px 14px' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 11, gap: 10 }}>
                <span style={{ display: 'flex', alignItems: 'baseline', gap: 8, minWidth: 0 }}>
                    <span style={{ fontFamily: "'Share Tech Mono'", fontSize: 11, color: '#6b7280', whiteSpace: 'nowrap' }}>
                        {formatDate(match.played_at)}
                    </span>
                    {match.map && (
                        <span style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 11, letterSpacing: '.08em', color: '#8a909c', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                            · {match.map}
                        </span>
                    )}
                </span>
                {draw ? (
                    <span style={{ fontFamily: "'Oswald'", fontWeight: 700, fontSize: 10, letterSpacing: '.16em', color: '#9aa0ac', background: 'rgba(138,144,156,.12)', padding: '3px 9px', borderRadius: 2 }}>
                        DRAW
                    </span>
                ) : (
                    <span style={{ fontFamily: "'Oswald'", fontWeight: 700, fontSize: 10, letterSpacing: '.14em', color: teamColor(match.winner_team), background: `${teamColor(match.winner_team)}1f`, padding: '3px 9px', borderRadius: 2, whiteSpace: 'nowrap' }}>
                        {teamLabel(match.winner_team, names)} WON
                    </span>
                )}
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: `repeat(${cols}, 1fr)`, gap: 8, alignItems: 'start' }}>
                {match.teams.map((playerNames, i) => {
                    const won = match.winner_team === i;
                    const color = teamColor(i);
                    return (
                        <div key={i} style={{ borderLeft: `3px solid ${color}`, background: won ? `${color}12` : '#0d0f14', borderRadius: '0 3px 3px 0', padding: '7px 9px' }}>
                            <div style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'space-between', gap: 6, marginBottom: 5 }}>
                                <span style={{ fontFamily: "'Oswald'", fontWeight: 700, fontSize: 12, letterSpacing: '.12em', color }}>
                                    {teamLabel(i, names)}
                                </span>
                                <span style={{ fontFamily: "'Share Tech Mono'", fontSize: 10, color: '#565c68' }}>
                                    {match.powers?.[i] ?? 0} PWR
                                </span>
                            </div>
                            <div style={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                                {playerNames.map((name, j) => (
                                    <span key={j} style={{ fontFamily: "'Barlow'", fontSize: 12, color: '#c8ccd4', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                        {name}
                                    </span>
                                ))}
                                {playerNames.length === 0 && (
                                    <span style={{ fontFamily: "'Share Tech Mono'", fontSize: 10, color: '#565c68' }}>—</span>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

function PageLink({ href, children }) {
    const disabled = !href;
    const style = {
        padding: '7px 14px',
        border: `1px solid ${disabled ? '#1c1f26' : '#2b303a'}`,
        background: '#101218',
        color: disabled ? '#3a3f49' : '#c8ccd4',
        fontFamily: "'Oswald'",
        fontWeight: 600,
        fontSize: 12,
        letterSpacing: '.12em',
        borderRadius: 2,
        textDecoration: 'none',
        pointerEvents: disabled ? 'none' : 'auto',
    };
    return disabled ? <span style={style}>{children}</span> : <Link href={href} preserveScroll style={style}>{children}</Link>;
}

export default function MatchesIndex({ matches }) {
    const isDesktop = useIsDesktop();
    const names = usePage().props.teamNames;
    const data = matches.data;

    return (
        <AppLayout title="History">
            <div style={{ display: 'flex', alignItems: 'center', gap: 9, marginBottom: 14 }}>
                <div style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 13, letterSpacing: '.16em', color: '#8a909c', whiteSpace: 'nowrap', flexShrink: 0 }}>
                    MATCH HISTORY
                </div>
                <div style={{ flex: 1, height: 1, background: '#1c1f26' }} />
                <div style={{ fontFamily: "'Share Tech Mono'", fontSize: 11, color: '#565c68', whiteSpace: 'nowrap', flexShrink: 0 }}>
                    {matches.total} LOGGED
                </div>
            </div>

            {data.length === 0 ? (
                <div style={{ textAlign: 'center', padding: '42px 20px', border: '1px dashed #21252e', borderRadius: 3 }}>
                    <div style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 15, letterSpacing: '.1em', color: '#8a909c' }}>NO MATCHES LOGGED</div>
                    <div style={{ fontFamily: "'Share Tech Mono'", fontSize: 11, color: '#565c68', marginTop: 6 }}>
                        Record a result on the Deploy screen and it shows up here.
                    </div>
                </div>
            ) : (
                <>
                    <div style={{ display: isDesktop ? 'grid' : 'flex', gridTemplateColumns: isDesktop ? 'repeat(auto-fill,minmax(440px,1fr))' : undefined, flexDirection: isDesktop ? undefined : 'column', gap: 10 }}>
                        {data.map((m) => (
                            <MatchCard key={m.id} match={m} isDesktop={isDesktop} names={names} />
                        ))}
                    </div>

                    {(matches.prev_page_url || matches.next_page_url) && (
                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 12, marginTop: 18 }}>
                            <PageLink href={matches.prev_page_url}>PREV</PageLink>
                            <span style={{ fontFamily: "'Share Tech Mono'", fontSize: 11, color: '#565c68' }}>
                                {matches.current_page} / {matches.last_page}
                            </span>
                            <PageLink href={matches.next_page_url}>NEXT</PageLink>
                        </div>
                    )}
                </>
            )}
        </AppLayout>
    );
}
