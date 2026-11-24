import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import type { ImgHTMLAttributes } from 'react';

export default function AppLogo({ className, ...props }: ImgHTMLAttributes<HTMLImageElement>) {
    const { name } = usePage().props;

    return (
        <>
            <div className="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full group-data-[collapsible=icon]:size-8">
                <img
                    src="/relief_logo.png"
                    alt="relief logo"
                    className={`size-full origin-center scale-110 object-cover ${className ?? ''}`}
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