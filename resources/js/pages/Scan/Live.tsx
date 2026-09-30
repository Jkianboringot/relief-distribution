import { useCallback, useState } from 'react';
import { Head } from '@inertiajs/react';
import { Camera } from 'lucide-react';
import { Button } from '@/components/ui/button';
import QrScanner from '@/components/qr-scanner';

// The printed QR encodes the full /scan/{qr_code} URL — pull just the code
// back out so we can build that URL again after decoding.
function extractQrCode(decoded: string): string {
    try {
        const url = new URL(decoded);
        const parts = url.pathname.split('/').filter(Boolean);
        return parts[parts.length - 1] ?? decoded;
    } catch {
        return decoded; // wasn't a URL — treat as the raw code
    }
}

export default function Live() {
    const [scanning, setScanning] = useState(true);

    const handleScan = useCallback((decodedText: string) => {
        setScanning(false);
        const code = extractQrCode(decodedText);
        // Full navigation, same as opening the printed QR link — ScanController::show()
        // runs its normal auto-detect-the-schedule-and-release logic from here.
        window.location.href = `/scan/${code}`;
    }, []);

    return (
        <>
            <Head title="Scan to Release" />

            <div className="mx-auto max-w-md p-6">
                <h1 className="mb-1 text-2xl font-bold text-ink">Scan to Release</h1>
                <p className="mb-4 text-sm text-subtle">
                    Point the camera at a beneficiary's QR code. The active distribution is detected
                    automatically.
                </p>

                {scanning ? (
                    <QrScanner active={scanning} onScan={handleScan} />
                ) : (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-[#d1d5db] bg-white p-8 text-center">
                        <p className="text-sm text-subtle">Processing scan…</p>
                    </div>
                )}

                {!scanning && (
                    <Button
                        type="button"
                        variant="outline"
                        className="mt-4 w-full"
                        onClick={() => setScanning(true)}
                    >
                        <Camera className="mr-2 h-4 w-4" />
                        Scan Again
                    </Button>
                )}
            </div>
        </>
    );
}