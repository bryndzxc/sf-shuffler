import AppLayout from '@/Layouts/AppLayout';
import { useIsDesktop } from '@/hooks';
import { ROLE_META } from '@/roles';
import { TEAM_META, teamColor, teamLabel } from '@/teams';
import { TIER_COLOR, TIER_GLOW, initials } from '@/tiers';
import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const INK = '#0a0b0e';

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

function GameCard({ game, onRecord }) {
    const [a, b] = game.teams;
    const [pa, pb] = game.powers;
    const total = pa + pb;
    const spread = Math.abs(pa - pb);
    const balColor = spread <= 1 ? '#34d399' : spread <= 3 ? '#f59e0b' : '#ef6a4d';

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

            {game.recorded ? (
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, padding: '11px 0', border: '1px solid #1c3a2a', background: 'rgba(52,211,153,.07)', borderRadius: 2, fontFamily: "'Oswald'", fontWeight: 700, fontSize: 12, letterSpacing: '.16em', color: '#34d399' }}>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="#34d399" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" /></svg>
                    RESULT LOGGED
                </div>
            ) : (
                <button type="button" onClick={() => onRecord(game)} style={{ width: '100%', padding: '11px 0', border: '1px solid #4a3a16', cursor: 'pointer', background: 'rgba(245,158,11,.08)', color: '#f59e0b', fontFamily: "'Oswald'", fontWeight: 700, fontSize: 12, letterSpacing: '.16em', borderRadius: 2, display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8 }}>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M5 21V4M5 4h11l-2 3 2 3H5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" /></svg>
                    RECORD RESULT
                </button>
            )}
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

function discordText(games, bye, reserves, names) {
    const rule = '─'.repeat(22);
    const team = (idx, players, pwr) => `${teamLabel(idx, names)} [PWR ${pwr}]\n` + (players.map((p) => `  • ${p.name} (${p.tier})`).join('\n') || '  • (none)');
    const blocks = games.map((g) =>
        `▌ GAME ${g.index + 1}\n` +
        team(g.teamIndices[0], g.teams[0], g.powers[0]) + '\n' +
        team(g.teamIndices[1], g.teams[1], g.powers[1])
    );
    if (bye) blocks.push(`▌ ${teamLabel(bye.teamIndex, names)} — awaiting opponent\n` + (bye.players.map((p) => `  • ${p.name} (${p.tier})`).join('\n')));
    let txt = '```\n[SF] TEAM SHUFFLE\n' + rule + '\n' + blocks.join('\n\n');
    if (reserves.length) txt += '\n\nRESERVES: ' + reserves.map((p) => p.name).join(', ');
    return txt + '\n```';
}

export default function ShuffleIndex({ games, bye, reserves, presentCount, snipersReady, riflesReady }) {
    const isDesktop = useIsDesktop();
    const teamNames = usePage().props.teamNames;
    const [copied, setCopied] = useState(false);
    const [shuffling, setShuffling] = useState(false);
    const [modalGame, setModalGame] = useState(null);
    const [phase, setPhase] = useState('ask');
    const [winner, setWinner] = useState(null);

    const reshuffle = () =>
        router.post(route('shuffle.run'), {}, {
            preserveScroll: true,
            onStart: () => setShuffling(true),
            onFinish: () => setShuffling(false),
        });

    const openRecord = (game) => {
        setModalGame(game);
        setPhase('ask');
        setWinner(null);
    };

    const recordWin = (w) =>
        router.post(route('matches.store'), { game: modalGame.index, winner: String(w) }, {
            preserveScroll: true,
            onSuccess: () => {
                setWinner(w);
                setPhase('done');
                setTimeout(() => setModalGame(null), 1500);
            },
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
                            <GameCard key={g.index} game={g} onRecord={openRecord} />
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

                {modalGame && (
                    <RecordModal game={modalGame} phase={phase} winner={winner} onPick={recordWin} onClose={() => setModalGame(null)} />
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

function RecordModal({ game, phase, winner, onPick, onClose }) {
    const teamNames = usePage().props.teamNames;
    const labels = game.teamIndices.map((i) => ({ label: teamLabel(i, teamNames), color: teamColor(i) }));
    const isDraw = winner === 'draw';
    const winColor = isDraw ? '#9aa0ac' : labels[winner]?.color;
    const winSub = isDraw ? 'DRAW' : (labels[winner]?.label ?? '') + ' WON';

    return (
        <div onClick={onClose} style={{ position: 'fixed', inset: 0, zIndex: 50, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 24, background: 'rgba(5,6,8,.82)', backdropFilter: 'blur(4px)' }}>
            <div onClick={(e) => e.stopPropagation()} style={{ width: '100%', maxWidth: 362, background: '#101218', border: '1px solid #2b303a', borderTop: '3px solid #f59e0b', borderRadius: 4, padding: 22, boxShadow: '0 24px 60px rgba(0,0,0,.6)' }}>
                {phase === 'ask' ? (
                    <div>
                        <div style={{ textAlign: 'center', fontFamily: "'Share Tech Mono'", fontSize: 10, letterSpacing: '.24em', color: '#565c68', marginBottom: 5 }}>GAME {game.index + 1} OUTCOME</div>
                        <div style={{ textAlign: 'center', fontFamily: "'Oswald'", fontWeight: 700, fontSize: 27, letterSpacing: '.14em', color: '#f4f5f7', marginBottom: 20 }}>WHO WON?</div>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                            {labels.map((meta, i) => (
                                <button key={i} type="button" onClick={() => onPick(i)} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 17px', border: `1px solid ${meta.color}55`, cursor: 'pointer', background: `${meta.color}14`, borderRadius: 3 }}>
                                    <span style={{ fontFamily: "'Oswald'", fontWeight: 700, fontSize: 18, letterSpacing: '.16em', color: meta.color }}>{meta.label}</span>
                                    <span style={{ fontFamily: "'Share Tech Mono'", fontSize: 13, color: '#8a909c' }}>{game.powers[i]} PWR</span>
                                </button>
                            ))}
                            <button type="button" onClick={() => onPick('draw')} style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '13px 17px', border: '1px solid #2b303a', cursor: 'pointer', background: '#161922', borderRadius: 3 }}>
                                <span style={{ fontFamily: "'Oswald'", fontWeight: 700, fontSize: 16, letterSpacing: '.2em', color: '#9aa0ac' }}>DRAW</span>
                            </button>
                        </div>
                        <button type="button" onClick={onClose} style={{ width: '100%', marginTop: 15, background: 'none', border: 'none', cursor: 'pointer', fontFamily: "'Oswald'", fontWeight: 600, fontSize: 12, letterSpacing: '.18em', color: '#565c68', padding: 6 }}>CANCEL</button>
                    </div>
                ) : (
                    <div style={{ textAlign: 'center', padding: '10px 0 6px' }}>
                        <div style={{ width: 62, height: 62, margin: '0 auto 18px', borderRadius: '50%', border: '2px solid #34d399', display: 'flex', alignItems: 'center', justifyContent: 'center', boxShadow: '0 0 20px rgba(52,211,153,.35)' }}>
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="#34d399" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" /></svg>
                        </div>
                        <div style={{ fontFamily: "'Oswald'", fontWeight: 700, fontSize: 23, letterSpacing: '.14em', color: '#f4f5f7' }}>MATCH LOGGED</div>
                        <div style={{ fontFamily: "'Oswald'", fontWeight: 700, fontSize: 14, letterSpacing: '.18em', marginTop: 9, color: winColor }}>{winSub}</div>
                    </div>
                )}
            </div>
        </div>
    );
}
