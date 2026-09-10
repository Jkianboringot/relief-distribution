import { useEffect } from 'react';
import { toast } from 'sonner';

interface FlashAlertsProps {
    flash?: { message?: string; error?: string };
}

export default function FlashAlerts({ flash }: FlashAlertsProps) {
useEffect(() => {
    if (flash?.message) {
        toast.success(flash.message, { id: 'flash-message', duration: 4000 });
    }
    if (flash?.error) {
        toast.error(flash.error, { id: 'flash-error', duration: Infinity, closeButton: true });
    }
}, [flash?.message, flash?.error]);

    return null;
}