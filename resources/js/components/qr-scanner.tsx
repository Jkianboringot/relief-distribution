import { useEffect, useRef, useState } from 'react';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

interface QrScannerProps {
    onScan: (decodedText: string) => void;
    active: boolean;
    className?: string;
}

const ELEMENT_ID = 'qr-scanner-viewport';

export default function QrScanner({ onScan, active, className }: QrScannerProps) {
    const [error, setError] = useState<string | null>(null);
    const lastScanRef = useRef<string | null>(null);
    const lastScanTimeRef = useRef(0);
    const scannerRef = useRef<Html5Qrcode | null>(null);
    const startPromiseRef = useRef<Promise<void> | null>(null);

    useEffect(() => {
        if (!active) return;

        setError(null);
        const scanner = new Html5Qrcode(ELEMENT_ID, {
            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
            verbose: false,
        });
        scannerRef.current = scanner;

        const startPromise = scanner
            .start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    const now = Date.now();
                    if (decodedText === lastScanRef.current && now - lastScanTimeRef.current < 3000) return;
                    lastScanRef.current = decodedText;
                    lastScanTimeRef.current = now;
                    onScan(decodedText);
                },
                () => {
                    // Fires every frame with no QR in view — expected, ignore.
                },
            )
            .catch((err) => {
                setError('Could not access the camera. Check permissions and try again.');
                console.error(err);
            });

        startPromiseRef.current = startPromise;

        return () => {
            // Must wait for start() to actually finish before calling stop() —
            // calling stop() while start() is still pending throws
            // "Cannot stop, scanner is not running or paused." This matters most
            // in React 18 StrictMode dev, which mounts -> cleans up -> mounts again
            // fast enough for that race to happen every time.
            startPromiseRef.current
                ?.then(() => scanner.stop())
                .then(() => scanner.clear())
                .catch(() => {
                    // Either it never started (permission denied) or was already
                    // stopped — both are fine to ignore on cleanup.
                });
        };
    }, [active, onScan]);

    if (!active) return null;

    return (
        <div className={className}>
            <div
                id={ELEMENT_ID}
                className="overflow-hidden rounded-xl border border-[#d1d5db] bg-black"
                style={{ width: '100%', minHeight: 300 }}
            />
            {error && <p className="mt-2 text-sm text-danger">{error}</p>}
        </div>
    );
}