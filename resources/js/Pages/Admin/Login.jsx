import { Head, router, useForm } from '@inertiajs/react';

const INK = '#0a0b0e';
const AMBER = '#f59e0b';

export default function AdminLogin({ isAdmin }) {
    const { data, setData, post, processing, errors, reset } = useForm({ password: '' });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.login.attempt'), { onFinish: () => reset('password') });
    };

    return (
        <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 24, background: 'radial-gradient(150% 90% at 50% 0%,#15171d 0%,#0b0c10 50%,#070809 100%)' }}>
            <Head title="Admin" />
            <div style={{ width: '100%', maxWidth: 360, background: '#101218', border: '1px solid #2b303a', borderTop: `3px solid ${AMBER}`, borderRadius: 4, padding: 26, boxShadow: '0 24px 60px rgba(0,0,0,.6)' }}>
                <div style={{ textAlign: 'center', fontFamily: "'Share Tech Mono'", fontSize: 10, letterSpacing: '.28em', color: '#565c68', marginBottom: 6 }}>
                    RESTRICTED
                </div>
                <div style={{ textAlign: 'center', fontFamily: "'Oswald'", fontWeight: 700, fontSize: 26, letterSpacing: '.16em', color: '#f4f5f7', marginBottom: 20 }}>
                    ADMIN ACCESS
                </div>

                {isAdmin ? (
                    <div style={{ textAlign: 'center' }}>
                        <div style={{ fontFamily: "'Oswald'", fontWeight: 600, fontSize: 14, letterSpacing: '.1em', color: '#34d399', marginBottom: 18 }}>
                            ✓ You are signed in as admin.
                        </div>
                        <button
                            type="button"
                            onClick={() => router.post(route('admin.logout'))}
                            style={{ width: '100%', padding: '12px 0', border: '1px solid #2b303a', background: '#161922', color: '#e6e8ec', fontFamily: "'Oswald'", fontWeight: 600, fontSize: 13, letterSpacing: '.14em', borderRadius: 2, cursor: 'pointer' }}
                        >
                            LOG OUT
                        </button>
                    </div>
                ) : (
                    <form onSubmit={submit}>
                        <input
                            type="password"
                            autoFocus
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder="ADMIN PASSWORD"
                            style={{ width: '100%', background: '#070809', border: `1px solid ${errors.password ? '#ef4444' : '#262a33'}`, borderRadius: 2, padding: '12px 13px', color: '#e6e8ec', fontFamily: "'Oswald'", fontWeight: 500, letterSpacing: '.08em', fontSize: 14 }}
                        />
                        {errors.password && (
                            <div style={{ fontFamily: "'Share Tech Mono'", fontSize: 11, color: '#ef4444', marginTop: 8 }}>
                                {errors.password}
                            </div>
                        )}
                        <button
                            type="submit"
                            disabled={processing}
                            style={{ width: '100%', marginTop: 14, padding: '13px 0', border: 'none', background: AMBER, color: INK, fontFamily: "'Oswald'", fontWeight: 700, fontSize: 14, letterSpacing: '.16em', borderRadius: 2, cursor: processing ? 'wait' : 'pointer', opacity: processing ? 0.6 : 1 }}
                        >
                            UNLOCK
                        </button>
                    </form>
                )}

                <a href={route('roster.index')} style={{ display: 'block', textAlign: 'center', marginTop: 16, fontFamily: "'Oswald'", fontWeight: 600, fontSize: 11, letterSpacing: '.16em', color: '#565c68', textDecoration: 'none' }}>
                    ← BACK TO ROSTER
                </a>
            </div>
        </div>
    );
}
