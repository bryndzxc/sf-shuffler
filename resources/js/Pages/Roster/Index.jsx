import AppLayout from '@/Layouts/AppLayout';
import { useIsDesktop } from '@/hooks';
import { ROLE_META, ROLE_ORDER, nextRole } from '@/roles';
import { TIER_COLOR, TIER_GLOW, initials } from '@/tiers';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const opts = { preserveScroll: true };
const INK = '#0a0b0e';

function SectionHeader({ label, right }) {
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 9, marginBottom: 14 }}>
            <div style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 13, letterSpacing: '.22em', color: '#8a909c' }}>
                {label}
            </div>
            <div style={{ flex: 1, height: 1, background: '#1c1f26' }} />
            {right}
        </div>
    );
}

function Avatar({ tier, name, size = 42 }) {
    return (
        <div
            style={{
                width: size,
                height: size,
                flexShrink: 0,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                background: INK,
                border: `1.5px solid ${TIER_COLOR[tier]}`,
                clipPath: 'polygon(0 0,100% 0,100% 75%,75% 100%,0 100%)',
                fontFamily: "'Share Tech Mono'",
                fontSize: size > 38 ? 14 : 12,
                color: TIER_COLOR[tier],
                boxShadow: `inset 0 0 12px ${TIER_GLOW[tier]}`,
            }}
        >
            {initials(name)}
        </div>
    );
}

function PresentToggle({ present, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            title={present ? 'Marked ready — tap to stand down' : 'Tap to mark ready'}
            style={{ flexShrink: 0, width: 62, border: 'none', background: 'none', cursor: 'pointer', padding: 0, textAlign: 'center' }}
        >
            <div style={{ width: 52, height: 26, borderRadius: 14, position: 'relative', background: present ? '#f59e0b' : '#1a1d24', border: `1px solid ${present ? '#f59e0b' : '#2b303a'}`, margin: '0 auto', transition: 'background .15s' }}>
                <div style={{ position: 'absolute', top: 2, left: present ? 28 : 2, width: 20, height: 20, borderRadius: '50%', background: present ? INK : '#565c68', transition: 'left .15s' }} />
            </div>
            <div style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 9, letterSpacing: '.14em', marginTop: 4, color: present ? '#f59e0b' : '#565c68' }}>
                {present ? 'READY' : 'OUT'}
            </div>
        </button>
    );
}

function SquareButton({ onClick, title, hoverColor, children }) {
    const [hover, setHover] = useState(false);
    return (
        <button
            type="button"
            onClick={onClick}
            title={title}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{ flexShrink: 0, width: 30, height: 30, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'none', border: `1px solid ${hover ? hoverColor : '#262a33'}`, borderRadius: 3, cursor: 'pointer', color: hover ? hoverColor : '#565c68', transition: 'border-color .15s,color .15s' }}
        >
            {children}
        </button>
    );
}

function TierBadge({ tier }) {
    return (
        <span
            title="Tier is earned from MMR"
            style={{ flexShrink: 0, whiteSpace: 'nowrap', fontFamily: "'Oswald'", fontWeight: 700, fontSize: 10, letterSpacing: '.04em', color: INK, background: TIER_COLOR[tier], padding: '3px 11px 3px 8px', clipPath: 'polygon(0 0,100% 0,100% 72%,91% 100%,0 100%)' }}
        >
            TIER {tier}
        </span>
    );
}

function RoleBadge({ role, onClick }) {
    const meta = ROLE_META[role];
    return (
        <button
            type="button"
            onClick={onClick}
            title="Click to change role"
            style={{ flexShrink: 0, whiteSpace: 'nowrap', cursor: 'pointer', fontFamily: "'Oswald'", fontWeight: 700, fontSize: 9, letterSpacing: '.08em', color: meta.badgeColor, background: meta.badgeBg, border: `1px solid ${meta.badgeBorder}`, padding: '2px 7px', borderRadius: 2 }}
        >
            {meta.label}
        </button>
    );
}

function PlayerCard({ player, isAdmin }) {
    const [editing, setEditing] = useState(false);
    const [name, setName] = useState(player.name);

    const patch = (data) => router.patch(route('roster.update', player.id), data, opts);

    const saveName = () => {
        const trimmed = name.trim();
        setEditing(false);
        if (trimmed && trimmed !== player.name) patch({ name: trimmed });
        else setName(player.name);
    };

    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '11px 12px', background: '#101218', border: '1px solid #21252e', borderLeft: `3px solid ${TIER_COLOR[player.tier]}`, borderRadius: 3 }}>
            <Avatar tier={player.tier} name={player.name} size={42} />

            <div style={{ flex: 1, minWidth: 0 }}>
                {editing ? (
                    <input
                        autoFocus
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        onBlur={saveName}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') saveName();
                            if (e.key === 'Escape') {
                                setName(player.name);
                                setEditing(false);
                            }
                        }}
                        maxLength={50}
                        style={{ width: '100%', background: '#070809', border: '1px solid #f59e0b', borderRadius: 2, padding: '4px 7px', color: '#eef0f3', fontFamily: "'Oswald'", fontWeight: 600, fontSize: 16, letterSpacing: '.06em' }}
                    />
                ) : (
                    <div
                        onClick={() => setEditing(true)}
                        title="Click to rename"
                        style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 16, letterSpacing: '.06em', color: '#eef0f3', lineHeight: 1.1, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis', cursor: 'pointer' }}
                    >
                        {player.name}
                    </div>
                )}
                <div style={{ display: 'flex', alignItems: 'center', gap: 7, flexWrap: 'wrap', marginTop: 6 }}>
                    <TierBadge tier={player.tier} />
                    <RoleBadge role={player.role} onClick={() => patch({ role: nextRole(player.role) })} />
                    <span style={{ fontFamily: "'Share Tech Mono'", fontSize: 11, color: '#f59e0b' }} title="Match rating (seeded from tier, moves with results)">
                        {player.mmr} MMR
                    </span>
                </div>
            </div>

            <PresentToggle present={player.present} onClick={() => patch({ present: !player.present })} />

            {isAdmin && (
                <SquareButton
                    onClick={() => {
                        if (confirm(`Delete ${player.name} permanently?`))
                            router.delete(route('roster.destroy', player.id), opts);
                    }}
                    title="Delete permanently (admin)"
                    hoverColor="#ef4444"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                        <path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                </SquareButton>
            )}
        </div>
    );
}

function AddBlock({ roles, full, max }) {
    const { data, setData, post, processing, reset } = useForm({ name: '', role: 'rifle' });

    const submit = () => {
        if (full || !data.name.trim() || processing) return;
        post(route('roster.store'), { preserveScroll: true, onSuccess: () => reset('name') });
    };

    return (
        <div style={{ background: '#101218', border: '1px solid #21252e', borderRadius: 3, padding: 14, marginBottom: 20 }}>
            <div style={{ display: 'flex', gap: 8, marginBottom: 9 }}>
                <input
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && submit()}
                    disabled={full}
                    placeholder={full ? `ROSTER FULL — ${max} MAX` : 'ADD PLAYER CALLSIGN'}
                    maxLength={50}
                    style={{ flex: 1, minWidth: 0, background: '#070809', border: '1px solid #262a33', borderRadius: 2, padding: '10px 11px', color: full ? '#565c68' : '#e6e8ec', fontFamily: "'Oswald'", fontWeight: 500, letterSpacing: '.08em', fontSize: 14 }}
                />
                <button
                    type="button"
                    onClick={submit}
                    disabled={processing || full}
                    style={{ flexShrink: 0, background: full ? '#161922' : '#f59e0b', border: 'none', borderRadius: 2, padding: '0 16px', color: full ? '#565c68' : INK, fontFamily: "'Oswald'", fontWeight: 700, letterSpacing: '.12em', fontSize: 13, cursor: full ? 'not-allowed' : 'pointer' }}
                >
                    ADD
                </button>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 7 }}>
                <span style={{ fontFamily: "'Oswald'", fontSize: 10, letterSpacing: '.16em', color: '#565c68', marginRight: 2 }}>ROLE</span>
                {roles.map((ro) => {
                    const active = data.role === ro;
                    const col = ROLE_META[ro].color;
                    return (
                        <button
                            key={ro}
                            type="button"
                            onClick={() => setData('role', ro)}
                            style={{ flex: 1, padding: '7px 0', borderRadius: 2, cursor: 'pointer', fontFamily: "'Oswald'", fontWeight: 700, fontSize: 12, letterSpacing: '.1em', background: active ? col : '#0a0b0e', color: active ? INK : col, border: `1px solid ${active ? col : '#262a33'}` }}
                        >
                            {ROLE_META[ro].label}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

function MiniButton({ onClick, children }) {
    const [hover, setHover] = useState(false);
    return (
        <button
            type="button"
            onClick={onClick}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{ padding: '5px 11px', border: `1px solid ${hover ? '#f59e0b' : '#2b303a'}`, background: '#101218', color: hover ? '#f59e0b' : '#9aa0ac', fontFamily: "'Oswald'", fontWeight: 600, fontSize: 11, letterSpacing: '.1em', borderRadius: 2, cursor: 'pointer', transition: 'border-color .15s,color .15s' }}
        >
            {children}
        </button>
    );
}

export default function RosterIndex({ players, roles, maxRoster = 50, isAdmin = false }) {
    const isDesktop = useIsDesktop();
    const full = players.length >= maxRoster;

    const listStyle = isDesktop
        ? { display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(300px,1fr))', gap: 10 }
        : { display: 'flex', flexDirection: 'column', gap: 8 };

    return (
        <AppLayout title="Roster">
            <SectionHeader
                label="ROSTER"
                right={
                    <div style={{ display: 'flex', alignItems: 'center', gap: 7 }}>
                        <MiniButton onClick={() => router.post(route('roster.present.all'), {}, opts)}>ALL READY</MiniButton>
                        <MiniButton onClick={() => router.post(route('roster.present.clear'), {}, opts)}>CLEAR</MiniButton>
                    </div>
                }
            />

            <AddBlock roles={roles || ROLE_ORDER} full={full} max={maxRoster} />

            {players.length === 0 ? (
                <div style={{ textAlign: 'center', padding: '42px 20px', border: '1px dashed #21252e', borderRadius: 3, fontFamily: "'Oswald'", fontWeight: 600, letterSpacing: '.1em', color: '#8a909c' }}>
                    NO PLAYERS YET — ADD YOUR FIRST ABOVE
                </div>
            ) : (
                <div style={listStyle}>
                    {players.map((p) => (
                        <PlayerCard key={p.id} player={p} isAdmin={isAdmin} />
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
