import { router } from '@inertiajs/react';

// UI only. Laravel is what actually blocks the data.
function lock() {
    if (document.getElementById('x-lock')) return;
    const d = document.createElement('div');
    d.id = 'x-lock';
    d.style.cssText =
        'position:fixed;inset:0;z-index:2147483647;background:#0f172a;color:#fff;display:grid;place-items:center;text-align:center;font-family:sans-serif';
    d.innerHTML = '<div><h1>Access Restricted</h1><p>Please contact support.</p></div>';
    document.body.appendChild(d);
    setInterval(() => location.reload(), 30000); // when restored, the reload loads normally
}

router.on('invalid', (ev: any) => {
    const r = ev.detail.response;
    if (r?.status === 403 && JSON.stringify(r.data ?? '').includes('ACCESS_RESTRICTED')) {
        ev.preventDefault();
        lock();
    }
});