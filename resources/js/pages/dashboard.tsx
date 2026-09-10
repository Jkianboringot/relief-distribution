import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';



export default function Dashboard() {

    return (
        <>
         
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};