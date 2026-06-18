import { useEffect, useState } from 'react';

/** Reactively track a CSS media query (SSR-safe). */
export function useMediaQuery(query) {
    const get = () =>
        typeof window !== 'undefined' && window.matchMedia(query).matches;

    const [matches, setMatches] = useState(get);

    useEffect(() => {
        const mq = window.matchMedia(query);
        const onChange = () => setMatches(mq.matches);
        onChange();
        mq.addEventListener
            ? mq.addEventListener('change', onChange)
            : mq.addListener(onChange);
        return () =>
            mq.removeEventListener
                ? mq.removeEventListener('change', onChange)
                : mq.removeListener(onChange);
    }, [query]);

    return matches;
}

/** Design breakpoint: sidebar layout at ≥900px, single-column app below. */
export const useIsDesktop = () => useMediaQuery('(min-width: 900px)');
