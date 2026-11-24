import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import type { ImgHTMLAttributes } from 'react';

export default function AppLogo({ className, ...props }: ImgHTMLAttributes<HTMLImageElement>) {
    const { name } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                <img
            src="/relief_logo.png"
            alt="relief logo"
            className={`h-8 w-8 object-contain ${className ?? ''}`}
            {...props}
        />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {name}
                </span>
            </div>
        </>
    );
}
