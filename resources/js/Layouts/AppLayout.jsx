import { useIsDesktop } from '@/hooks';
import { Head, Link, usePage } from '@inertiajs/react';

const AMBER = '#f59e0b';
const INK = '#0a0b0e';
const DIM = '#5a606c';

function Logo({ size = 38 }) {
    const inner = Math.round(size * 0.52);
    return (
        <div
            style={{
                width: size,
                height: size,
                flexShrink: 0,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                background: AMBER,
                clipPath: 'polygon(0 0,100% 0,100% 72%,72% 100%,0 100%)',
            }}
        >
            <svg width={inner} height={inner} viewBox="0 0 24 24" fill="none">
                <path d="M12 2L3 6v6c0 5 3.8 8.5 9 10 5.2-1.5 9-5 9-10V6l-9-4z" fill={INK} />
                <path d="M12 7l4 2.5v3c0 2.3-1.7 3.8-4 4.5-2.3-.7-4-2.2-4-4.5v-3L12 7z" fill={AMBER} />
            </svg>
        </div>
    );
}

function Icon({ name, color }) {
    const common = { width: 22, height: 22, viewBox: '0 0 24 24', fill: 'none' };
    if (name === 'roster') {
        return (
            <svg {...common}>
                <circle cx="8" cy="8" r="3.2" stroke={color} strokeWidth="1.8" />
                <path d="M2.5 19c0-3 2.5-5 5.5-5s5.5 2 5.5 5" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
                <circle cx="17" cy="9" r="2.4" stroke={color} strokeWidth="1.6" />
                <path d="M16 14.2c2.6-.3 5 1.4 5 4.3" stroke={color} strokeWidth="1.6" strokeLinecap="round" />
            </svg>
        );
    }
    if (name === 'intel') {
        return (
            <svg {...common}>
                <path d="M4 20V11M10 20V5M16 20v-6M22 20H2" stroke={color} strokeWidth="1.8" strokeLinecap="round" />
            </svg>
        );
    }
    if (name === 'history') {
        return (
            <svg {...common}>
                <path d="M3.5 12a8.5 8.5 0 1 0 2.4-5.9M3.5 4v3h3" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                <path d="M12 7.5V12l3 2" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        );
    }
    return (
        <svg {...common}>
            <path d="M4 7h9l-2.2-2.2M20 7l-3 0M4 17h7l-2.2 2.2M20 17h-9" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M14 7l3.5 5L14 17" stroke={color} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

const NAV = [
    { key: 'roster', label: 'ROSTER', icon: 'roster', href: '/roster', match: '/roster' },
    { key: 'deploy', label: 'DEPLOY', icon: 'deploy', href: '/shuffle', match: '/shuffle' },
    { key: 'leaderboards', label: 'LEADERBOARDS', icon: 'intel', href: '/stats', match: '/stats' },
    { key: 'history', label: 'HISTORY', icon: 'history', href: '/matches', match: '/matches' },
];

function ReadyCounter({ ready, total, size = 'lg' }) {
    const big = size === 'lg';
    return (
        <div style={{ textAlign: big ? 'left' : 'right' }}>
            <div style={{ fontFamily: "'Share Tech Mono'", fontSize: big ? 26 : 17, color: '#f4f5f7', lineHeight: 1 }}>
                {ready}
                <span style={{ color: DIM }}>/{total}</span>
            </div>
            <div style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: big ? 10 : 9.5, letterSpacing: '.2em', color: '#8a909c', marginTop: big ? 0 : 3, lineHeight: 1.3 }}>
                {big ? (
                    <>
                        PLAYERS<br />READY
                    </>
                ) : (
                    'READY'
                )}
            </div>
        </div>
    );
}

export default function AppLayout({ title, children }) {
    const isDesktop = useIsDesktop();
    const page = usePage();
    const url = page.url;
    const counts = page.props.rosterCounts || { ready: 0, total: 0 };

    const isActive = (item) => url.startsWith(item.match);

    const outerStyle = isDesktop
        ? { height: '100vh', display: 'flex', background: 'radial-gradient(150% 90% at 0% 0%,#15171d 0%,#0b0c10 50%,#070809 100%)' }
        : { height: '100vh', display: 'flex', justifyContent: 'center', background: '#070809' };

    const mainStyle = isDesktop
        ? { flex: 1, minWidth: 0, height: '100vh', display: 'flex', flexDirection: 'column', position: 'relative' }
        : { width: '100%', maxWidth: 440, height: '100%', display: 'flex', flexDirection: 'column', position: 'relative', background: 'radial-gradient(120% 60% at 50% -8%,#15171d 0%,#0a0b0e 48%,#070809 100%)', boxShadow: '0 0 0 1px #1c1f26' };

    const screenStyle = isDesktop
        ? { padding: '32px 48px 48px', width: '100%' }
        : { padding: '16px 14px 92px' };

    return (
        <div style={outerStyle}>
            {title && <Head title={title} />}

            {isDesktop && (
                <aside style={{ width: 252, flexShrink: 0, height: '100vh', display: 'flex', flexDirection: 'column', padding: '24px 16px', borderRight: '1px solid #1c1f26', background: 'rgba(10,11,14,.55)' }}>
                    <Link href="/" style={{ display: 'flex', alignItems: 'center', gap: 11, padding: '0 6px', textDecoration: 'none' }}>
                        <Logo size={38} />
                        <div style={{ fontFamily: "'Oswald'", fontWeight: 700, fontSize: 18, letterSpacing: '.13em', lineHeight: 1, color: '#f4f5f7' }}>
                            SF&middot;SHUFFLER
                        </div>
                    </Link>

                    <nav style={{ marginTop: 30, display: 'flex', flexDirection: 'column', gap: 5 }}>
                        {NAV.map((item) => {
                            const active = isActive(item);
                            const color = active ? AMBER : '#8a909c';
                            return (
                                <Link
                                    key={item.key}
                                    href={item.href}
                                    style={{ display: 'flex', alignItems: 'center', gap: 13, padding: '12px 13px', borderLeft: `2px solid ${active ? AMBER : 'transparent'}`, background: active ? 'rgba(245,158,11,.10)' : 'transparent', borderRadius: '0 3px 3px 0', textDecoration: 'none' }}
                                >
                                    <span style={{ color, display: 'flex' }}>
                                        <Icon name={item.icon} color={color} />
                                    </span>
                                    <span style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 14, letterSpacing: '.14em', color }}>
                                        {item.label}
                                    </span>
                                </Link>
                            );
                        })}
                    </nav>

                    <div style={{ flex: 1 }} />

                    <div style={{ borderTop: '1px solid #1c1f26', padding: '16px 8px 4px' }}>
                        <ReadyCounter ready={counts.ready} total={counts.total} size="lg" />
                    </div>
                </aside>
            )}

            <div style={mainStyle}>
                {!isDesktop && (
                    <>
                        <div style={{ flexShrink: 0, position: 'relative', padding: '14px 16px 12px', borderBottom: '1px solid #1c1f26', display: 'flex', alignItems: 'center', gap: 11, background: 'rgba(10,11,14,.7)', backdropFilter: 'blur(8px)', zIndex: 5 }}>
                            <Logo size={34} />
                            <div style={{ flex: 1, minWidth: 0, fontFamily: "'Oswald'", fontWeight: 700, fontSize: 19, letterSpacing: '.14em', lineHeight: 1, color: '#f4f5f7' }}>
                                SF&middot;SHUFFLER
                            </div>
                            <ReadyCounter ready={counts.ready} total={counts.total} size="sm" />
                        </div>
                        <div style={{ flexShrink: 0, height: 2, background: 'linear-gradient(90deg,#f59e0b,#7c5210 40%,transparent)' }} />
                    </>
                )}

                <div style={{ flex: 1, overflowY: 'auto', overflowX: 'hidden' }}>
                    <div style={screenStyle}>{children}</div>
                </div>

                {!isDesktop && (
                    <div style={{ flexShrink: 0, display: 'flex', background: 'rgba(9,10,13,.92)', backdropFilter: 'blur(10px)', borderTop: '1px solid #1c1f26', padding: '7px 8px calc(7px + env(safe-area-inset-bottom))', zIndex: 5 }}>
                        {NAV.map((item) => {
                            const active = isActive(item);
                            const color = active ? AMBER : DIM;
                            return (
                                <Link
                                    key={item.key}
                                    href={item.href}
                                    style={{ flex: 1, padding: '7px 0 5px', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 5, position: 'relative', textDecoration: 'none' }}
                                >
                                    <div style={{ position: 'absolute', top: 0, left: '50%', transform: 'translateX(-50%)', width: 26, height: 2, background: active ? AMBER : 'transparent', borderRadius: 2 }} />
                                    <span style={{ color, display: 'flex' }}>
                                        <Icon name={item.icon} color={color} />
                                    </span>
                                    <span style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 10, letterSpacing: '.16em', color }}>
                                        {item.label}
                                    </span>
                                </Link>
                            );
                        })}
                    </div>
                )}
            </div>
        </div>
    );
}
