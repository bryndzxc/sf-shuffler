import AppLayout from '@/Layouts/AppLayout';
import { useIsDesktop } from '@/hooks';
import { ROLE_META } from '@/roles';
import { TEAM_META, teamColor, teamLabel } from '@/teams';
import { TIER_COLOR, TIER_GLOW, initials } from '@/tiers';
import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const INK = '#0a0b0e';
const opts = { preserveScroll: true };

function EditableTeamName({ slot, fontSize = 16 }) {
    const teamNames = usePage().props.teamNames;
    const label = teamLabel(slot, teamNames);
    const color = teamColor(slot);
    const [editing, setEditing] = useState(false);
    const [val, setVal] = useState(label);

    useEffect(() => setVal(label), [label]);

    const save = () => {
        setEditing(false);
        const next = val.trim();
        if (next !== label) {
            router.patch(route('team-names.update', slot), { name: next }, { preserveScroll: true });
        }
    };

    if (editing) {
        return (
            <input
                autoFocus
                value={val}
                onChange={(e) => setVal(e.target.value)}
                onBlur={save}
                onKeyDown={(e) => {
                    if (e.key === 'Enter') save();
                    if (e.key === 'Escape') {
                        setVal(label);
                        setEditing(false);
                    }
                }}
                maxLength={30}
                style={{ width: '9em', background: '#070809', border: `1px solid ${color}`, borderRadius: 2, padding: '2px 6px', color, fontFamily: "'Oswald'", fontWeight: 700, fontSize, letterSpacing: '.1em', textTransform: 'uppercase' }}
            />
        );
    }

    return (
        <span
            onClick={() => setEditing(true)}
            title="Click to rename team"
            style={{ fontFamily: "'Oswald'", fontWeight: 700, fontSize, letterSpacing: '.12em', color, whiteSpace: 'nowrap', cursor: 'pointer' }}
        >
            {label}
        </span>
    );
}

function Avatar({ tier, name, size = 32 }) {
    return (
        <div style={{ width: size, height: size, flexShrink: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', background: INK, border: `1.5px solid ${TIER_COLOR[tier]}`, clipPath: 'polygon(0 0,100% 0,100% 75%,75% 100%,0 100%)', fontFamily: "'Share Tech Mono'", fontSize: 11, color: TIER_COLOR[tier], boxShadow: `inset 0 0 10px ${TIER_GLOW[tier]}` }}>
            {initials(name)}
        </div>
    );
}

function ShuffleIcon({ color = INK, size = 21 }) {
    return (
        <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
            <path d="M4 7h9l-2.2-2.2M20 7l-3 0M4 17h7l-2.2 2.2M20 17h-9" stroke={color} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M14 7l3.5 5L14 17" stroke={color} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function PlayerLine({ p }) {
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '7px 8px', background: '#101218', border: '1px solid #21252e', borderLeft: `3px solid ${TIER_COLOR[p.tier]}`, borderRadius: 3 }}>
            <Avatar tier={p.tier} name={p.name} size={30} />
            <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 13, letterSpacing: '.05em', color: '#eef0f3', lineHeight: 1.1, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                    {p.name}
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 5, flexWrap: 'wrap', marginTop: 3 }}>
                    <span style={{ flexShrink: 0, fontFamily: "'Oswald'", fontWeight: 700, fontSize: 8, color: INK, background: TIER_COLOR[p.tier], padding: '1px 5px', borderRadius: 1 }}>{p.tier}</span>
                    <span style={{ flexShrink: 0, fontFamily: "'Oswald'", fontWeight: 700, fontSize: 8, letterSpacing: '.06em', color: ROLE_META[p.role].badgeColor, background: ROLE_META[p.role].badgeBg, border: `1px solid ${ROLE_META[p.role].badgeBorder}`, padding: '1px 5px', borderRadius: 2 }}>{ROLE_META[p.role].label}</span>
                </div>
            </div>
        </div>
    );
}

function TeamColumn({ teamIndex, players, power }) {
    return (
        <div>
            <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', padding: '0 2px 7px', borderBottom: `2px solid ${teamColor(teamIndex)}`, marginBottom: 9 }}>
                <EditableTeamName slot={teamIndex} fontSize={16} />
                <span style={{ textAlign: 'right', lineHeight: 1 }}>
                    <span style={{ display: 'block', fontFamily: "'Share Tech Mono'", fontSize: 17, color: '#f4f5f7' }}>{power}</span>
                    <span style={{ display: 'block', fontFamily: "'Oswald'", fontWeight: 600, fontSize: 8, letterSpacing: '.2em', color: '#565c68', marginTop: 1 }}>POWER</span>
                </span>
            </div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                {players.map((p) => <PlayerLine key={p.id} p={p} />)}
            </div>
        </div>
    );
}

function FormatPicker({ onPick }) {
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '4px 0' }}>
            <span style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 11, letterSpacing: '.18em', color: '#8a909c', marginRight: 2 }}>SERIES</span>
            {[3, 5].map((n) => (
                <button
                    key={n}
                    type="button"
                    onClick={() => onPick(n)}
                    style={{ flex: 1, padding: '10px 0', cursor: 'pointer', background: '#0e1015', border: '1px solid #2b303a', borderRadius: 2, color: '#e6e8ec', fontFamily: "'Oswald'", fontWeight: 700, fontSize: 13, letterSpacing: '.12em' }}
                >
                    BEST OF {n}
                </button>
            ))}
        </div>
    );
}

function MapRow({ map, mapIndex, result, decided, labels, onRecord, onReroll }) {
    const recorded = result !== null && result !== undefined;
    const win = recorded && result !== 'draw' ? labels[Number(result)] : null;

    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 9, padding: '7px 9px', background: '#0d0f14', border: '1px solid #1c1f26', borderRadius: 3, opacity: !recorded && decided ? 0.4 : 1 }}>
            <span style={{ fontFamily: "'Share Tech Mono'", fontSize: 10, color: '#565c68', width: 30, flexShrink: 0 }}>M{mapIndex + 1}</span>
            <span style={{ flex: 1, minWidth: 0, fontFamily: "'Oswald'", fontWeight: 600, fontSize: 13, letterSpacing: '.04em', color: '#e6e8ec', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                {map}
            </span>

            {recorded ? (
                <span style={{ flexShrink: 0, fontFamily: "'Oswald'", fontWeight: 700, fontSize: 10, letterSpacing: '.1em', color: win ? INK : '#9aa0ac', background: win ? win.color : 'rgba(138,144,156,.18)', padding: '3px 9px', borderRadius: 2 }}>
                    {win ? `${win.label} ✓` : 'DRAW'}
                </span>
            ) : decided ? (
                <span style={{ flexShrink: 0, fontFamily: "'Share Tech Mono'", fontSize: 10, color: '#565c68' }}>—</span>
            ) : (
                <span style={{ display: 'flex', alignItems: 'center', gap: 5, flexShrink: 0 }}>
                    <button type="button" title={`Re-roll ${map}`} onClick={onReroll} style={{ width: 26, height: 26, cursor: 'pointer', background: 'none', border: '1px solid #262a33', borderRadius: 3, color: '#8a909c', fontSize: 12 }}>🎲</button>
                    {labels.map((l, i) => (
                        <button key={i} type="button" onClick={() => onRecord(i)} title={`${l.label} won`} style={{ padding: '5px 11px', cursor: 'pointer', background: `${l.color}14`, border: `1px solid ${l.color}55`, borderRadius: 2, color: l.color, fontFamily: "'Oswald'", fontWeight: 700, fontSize: 11, letterSpacing: '.06em' }}>
                            {l.label}
                        </button>
                    ))}
                </span>
            )}
        </div>
    );
}

function SeriesSection({ game, labels }) {
    const s = game.series;

    const start = (bestOf) => router.post(route('series.start'), { game: game.index, bestOf }, opts);
    const reset = () => router.post(route('series.reset'), { game: game.index }, opts);
    const reroll = (mapIndex) => router.post(route('series.reroll'), { game: game.index, mapIndex }, opts);
    const record = (mapIndex, winner) =>
        router.post(route('matches.store'), { game: game.index, mapIndex, winner: String(winner) }, opts);

    if (!s) return <FormatPicker onPick={start} />;

    const decided = s.winner !== null;

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 1 }}>
                <span style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 10, letterSpacing: '.16em', color: '#8a909c' }}>
                    BEST OF {s.bestOf} · FIRST TO {s.needed}
                </span>
                <span style={{ display: 'flex', alignItems: 'center', gap: 7 }}>
                    <span style={{ fontFamily: "'Share Tech Mono'", fontSize: 13 }}>
                        <span style={{ color: labels[0].color }}>{s.wins[0]}</span>
                        <span style={{ color: '#565c68' }}> – </span>
                        <span style={{ color: labels[1].color }}>{s.wins[1]}</span>
                    </span>
                    <button type="button" onClick={reset} title="Change format / clear series" style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#565c68', fontFamily: "'Oswald'", fontWeight: 600, fontSize: 10, letterSpacing: '.1em', padding: 0 }}>
                        RESET
                    </button>
                </span>
            </div>

            {s.maps.map((map, mi) => (
                <MapRow
                    key={mi}
                    map={map}
                    mapIndex={mi}
                    result={s.results[mi]}
                    decided={decided}
                    labels={labels}
                    onRecord={(w) => record(mi, w)}
                    onReroll={() => reroll(mi)}
                />
            ))}

            {decided && (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, marginTop: 3, padding: '11px 0', border: `1px solid ${labels[s.winner].color}55`, background: `${labels[s.winner].color}12`, borderRadius: 2, fontFamily: "'Oswald'", fontWeight: 700, fontSize: 12, letterSpacing: '.14em', color: labels[s.winner].color }}>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" /></svg>
                    {labels[s.winner].label} WINS THE SERIES {s.wins[0]}–{s.wins[1]}
                </div>
            )}
        </div>
    );
}

function GameCard({ game }) {
    const teamNames = usePage().props.teamNames;
    const [a, b] = game.teams;
    const [pa, pb] = game.powers;
    const total = pa + pb;
    const spread = Math.abs(pa - pb);
    const balColor = spread <= 1 ? '#34d399' : spread <= 3 ? '#f59e0b' : '#ef6a4d';
    const labels = game.teamIndices.map((idx) => ({ label: teamLabel(idx, teamNames), color: teamColor(idx) }));

    return (
        <div style={{ background: 'rgba(16,18,24,.5)', border: '1px solid #1c1f26', borderRadius: 4, padding: 12, marginBottom: 12 }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 10 }}>
                <span style={{ fontFamily: "'Oswald'", fontWeight: 700, fontSize: 13, letterSpacing: '.2em', color: '#c8ccd4' }}>GAME {game.index + 1}</span>
                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, fontFamily: "'Share Tech Mono'", fontSize: 11, color: balColor }}>
                    <span style={{ width: 7, height: 7, borderRadius: '50%', background: balColor }} />
                    {spread === 0 ? 'EVEN' : `+${spread}`}
                </span>
            </div>

            <div style={{ display: 'flex', height: 6, borderRadius: 3, overflow: 'hidden', background: '#070809', border: '1px solid #1c1f26', gap: 1, marginBottom: 11 }}>
                <div style={{ width: `${total ? (pa / total) * 100 : 50}%`, background: TEAM_META[game.teamIndices[0]].color }} />
                <div style={{ width: `${total ? (pb / total) * 100 : 50}%`, background: TEAM_META[game.teamIndices[1]].color }} />
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10, alignItems: 'start', marginBottom: 11 }}>
                <TeamColumn teamIndex={game.teamIndices[0]} players={a} power={pa} />
                <TeamColumn teamIndex={game.teamIndices[1]} players={b} power={pb} />
            </div>

            <SeriesSection game={game} labels={labels} />
        </div>
    );
}

function ReserveStrip({ reserves }) {
    return (
        <div style={{ marginTop: 6 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 9, marginBottom: 10 }}>
                <span style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 12, letterSpacing: '.2em', color: '#8a909c' }}>RESERVES</span>
                <div style={{ flex: 1, height: 1, background: '#1c1f26' }} />
                <span style={{ fontFamily: "'Share Tech Mono'", fontSize: 11, color: '#565c68' }}>{reserves.length} BENCHED</span>
            </div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 7 }}>
                {reserves.map((p) => (
                    <span key={p.id} style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '5px 9px 5px 6px', background: '#0e1015', border: '1px solid #1c1f26', borderLeft: `3px solid ${TIER_COLOR[p.tier]}`, borderRadius: 3, opacity: 0.7 }}>
                        <span style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 12, letterSpacing: '.05em', color: '#c8ccd4' }}>{p.name}</span>
                        <span style={{ fontFamily: "'Oswald'", fontWeight: 700, fontSize: 8, letterSpacing: '.06em', color: ROLE_META[p.role].badgeColor }}>{ROLE_META[p.role].label}</span>
                    </span>
                ))}
            </div>
        </div>
    );
}

function playerLine(p) {
    const role = p.role === 'sniper' ? 'Sniper' : 'Rifle';
    return `  • ${p.name} [${role}] (${p.tier})`;
}

function discordText(games, bye, reserves, names) {
    const rule = '─'.repeat(22);
    const team = (idx, players, pwr) => `${teamLabel(idx, names)} [PWR ${pwr}]\n` + (players.map(playerLine).join('\n') || '  • (none)');
    const blocks = games.map((g) =>
        `▌ GAME ${g.index + 1}\n` +
        team(g.teamIndices[0], g.teams[0], g.powers[0]) + '\n' +
        team(g.teamIndices[1], g.teams[1], g.powers[1])
    );
    if (bye) blocks.push(`▌ ${teamLabel(bye.teamIndex, names)} — awaiting opponent\n` + (bye.players.map(playerLine).join('\n')));
    let txt = '```\n[SF] TEAM SHUFFLE\n' + rule + '\n' + blocks.join('\n\n');
    if (reserves.length) txt += '\n\nRESERVES: ' + reserves.map((p) => `${p.name} [${p.role === 'sniper' ? 'Sniper' : 'Rifle'}]`).join(', ');
    return txt + '\n```';
}

export default function ShuffleIndex({ games, bye, reserves, presentCount, snipersReady, riflesReady }) {
    const isDesktop = useIsDesktop();
    const teamNames = usePage().props.teamNames;
    const [copied, setCopied] = useState(false);
    const [shuffling, setShuffling] = useState(false);

    const reshuffle = () =>
        router.post(route('shuffle.run'), {}, {
            preserveScroll: true,
            onStart: () => setShuffling(true),
            onFinish: () => setShuffling(false),
        });

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(discordText(games, bye, reserves, teamNames));
            setCopied(true);
            setTimeout(() => setCopied(false), 1600);
        } catch {
            setCopied(false);
        }
    };

    const hasTeams = games.length > 0 || !!bye;
    const teamCount = games.length * 2 + (bye ? 1 : 0);

    return (
        <AppLayout title="Deploy">
            <div style={{ width: '100%' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 16 }}>
                    <div style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 13, letterSpacing: '.22em', color: '#8a909c' }}>DEPLOY</div>
                    <div style={{ flex: 1, height: 1, background: '#1c1f26' }} />
                    {hasTeams && (
                        <div style={{ fontFamily: "'Share Tech Mono'", fontSize: 11, color: '#565c68' }}>
                            {teamCount} TEAMS · {games.length} {games.length === 1 ? 'GAME' : 'GAMES'}
                        </div>
                    )}
                    <div style={{ fontFamily: "'Share Tech Mono'", fontSize: 11, color: '#565c68' }}>{presentCount} READY</div>
                </div>

                <button type="button" onClick={reshuffle} disabled={shuffling || presentCount === 0} style={{ width: '100%', padding: '18px 0', border: 'none', cursor: presentCount === 0 ? 'not-allowed' : 'pointer', background: '#f59e0b', color: INK, fontFamily: "'Oswald'", fontWeight: 700, fontSize: 18, letterSpacing: '.2em', clipPath: 'polygon(0 0,100% 0,100% 68%,97% 100%,3% 100%,0 68%)', boxShadow: '0 8px 24px rgba(245,158,11,.28)', marginBottom: 18, display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 13, opacity: shuffling || presentCount === 0 ? 0.55 : 1 }}>
                    <ShuffleIcon />
                    {shuffling ? 'SHUFFLING…' : 'SHUFFLE TEAMS'}
                </button>

                {presentCount === 0 ? (
                    <EmptyState
                        title="AWAITING DEPLOYMENT"
                        subtitle="No players marked ready"
                    />
                ) : !hasTeams ? (
                    <EmptyState
                        title="NOT ENOUGH FOR A TEAM"
                        subtitle={`A team needs 1 sniper + 4 rifles. Ready: ${snipersReady} sniper${snipersReady === 1 ? '' : 's'}, ${riflesReady} rifle${riflesReady === 1 ? '' : 's'}.`}
                    >
                        {reserves.length > 0 && <ReserveStrip reserves={reserves} />}
                    </EmptyState>
                ) : (
                    <>
                        {games.map((g) => (
                            <GameCard key={g.index} game={g} />
                        ))}

                        {bye && (
                            <div style={{ background: 'rgba(16,18,24,.5)', border: '1px dashed #2b303a', borderRadius: 4, padding: 12, marginBottom: 12 }}>
                                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 10 }}>
                                    <EditableTeamName slot={bye.teamIndex} fontSize={13} />
                                    <span style={{ fontFamily: "'Share Tech Mono'", fontSize: 10, color: '#565c68' }}>AWAITING OPPONENT</span>
                                </div>
                                <div style={{ display: 'grid', gridTemplateColumns: isDesktop ? '1fr 1fr' : '1fr', gap: 6 }}>
                                    {bye.players.map((p) => <PlayerLine key={p.id} p={p} />)}
                                </div>
                            </div>
                        )}

                        {reserves.length > 0 && <ReserveStrip reserves={reserves} />}

                        <div style={{ display: 'flex', gap: 10, marginTop: 14 }}>
                            <button type="button" onClick={reshuffle} disabled={shuffling} style={{ flex: 1, padding: '14px 0', border: '1px solid #2b303a', cursor: 'pointer', background: '#161922', color: '#e6e8ec', fontFamily: "'Oswald'", fontWeight: 600, fontSize: 13.5, letterSpacing: '.12em', borderRadius: 2, display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8 }}>
                                <ShuffleIcon color="#e6e8ec" size={16} />
                                RESHUFFLE
                            </button>
                            <button type="button" onClick={copy} style={{ flex: 1.4, padding: '14px 0', border: `1px solid ${copied ? '#34d399' : '#2b303a'}`, cursor: 'pointer', background: copied ? '#34d399' : '#1a1d24', color: copied ? INK : '#e6e8ec', fontFamily: "'Oswald'", fontWeight: 600, fontSize: 13.5, letterSpacing: '.12em', borderRadius: 2 }}>
                                {copied ? 'COPIED ✓' : 'COPY FOR DISCORD'}
                            </button>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}

function EmptyState({ title, subtitle, children }) {
    return (
        <div style={{ textAlign: 'center', padding: '38px 20px', border: '1px dashed #21252e', borderRadius: 3, background: 'repeating-linear-gradient(135deg,transparent,transparent 9px,rgba(245,158,11,.025) 9px,rgba(245,158,11,.025) 18px)' }}>
            <div style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 15, letterSpacing: '.1em', color: '#8a909c' }}>{title}</div>
            <div style={{ fontFamily: "'Share Tech Mono'", fontSize: 11, color: '#565c68', marginTop: 6 }}>{subtitle}</div>
            <div style={{ marginTop: 16, textAlign: 'left' }}>{children}</div>
            <Link href={route('roster.index')} style={{ display: 'inline-block', marginTop: 16, padding: '10px 18px', background: '#f59e0b', color: INK, fontFamily: "'Oswald'", fontWeight: 700, fontSize: 13, letterSpacing: '.12em', borderRadius: 2, textDecoration: 'none' }}>
                GO TO ROSTER
            </Link>
        </div>
    );
}
