import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface PageProps {
    outcome: 'success' | 'error';
    message: string;
    schedule_title?: string | null;
}

export default function Result({ outcome, message, schedule_title }: PageProps) {
    const isSuccess = outcome === 'success';

    return (
        <>
            <Head title={isSuccess ? 'Box Released' : 'Cannot Release'} />

            <div
                className={`flex min-h-screen flex-col items-center justify-center gap-6 p-8 text-center ${
                    isSuccess ? 'bg-emerald-50' : 'bg-red-50'
                }`}
            >
                {isSuccess ? (
                    <CheckCircle2 className="h-24 w-24 text-emerald-600" />
                ) : (
                    <XCircle className="h-24 w-24 text-red-600" />
                )}

                <div>
                    <h1 className={`text-3xl font-extrabold ${isSuccess ? 'text-emerald-800' : 'text-red-800'}`}>
                        {isSuccess ? 'Box Released' : 'Cannot Release'}
                    </h1>
                    {schedule_title && (
                        <p className="mt-1 text-sm text-subtle">{schedule_title}</p>
                    )}
                    <p className="mt-3 max-w-md text-base text-ink">{message}</p>
                </div>

                <Link href="/">
                    <Button
                        type="button"
                        className="bg-brand-orange px-8 py-6 text-lg font-bold text-white hover:bg-brand-orange-hover"
                    >
                        Scan Next
                    </Button>
                </Link>
            </div>
        </>
    );
}