import { Head } from '@inertiajs/react';
import { Bell, CalendarDays, CircleCheck, Users } from 'lucide-react';
import { dashboard } from '@/routes';

type DistributionStatus = 'pending' | 'ongoing' | 'completed';

interface Stats {
    totalBeneficiaries: number;
    scheduledDistributions: number;
    completedOperations: number;
}

interface StatusBreakdown {
    completed: number;
    pending: number;
    ongoing: number;
}

interface BarangayCount {
    name: string;
    total: number;
}

interface Activity {
    id: number;
    date: string;
    activity: string;
    barangay: string;
    status: DistributionStatus;
    officer: string;
}

interface Props {
    stats: Stats;
    statusBreakdown: StatusBreakdown;
    beneficiariesPerBarangay: BarangayCount[];
    recentActivities: Activity[];
}

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// "2026-09-16..." -> "Sep 16, 2026" (reads the string directly so timezones can't shift the day)
const formatDate = (value: string | null): string => {
    if (!value) return '—';
    const [year, month, day] = value.slice(0, 10).split('-').map(Number);
    if (!year || !month || !day) return value;
    return `${MONTHS[month - 1]} ${day}, ${year}`;
};

const STATUS_STYLES: Record<DistributionStatus, { label: string; badge: string }> = {
    completed: { label: 'Completed', badge: 'bg-green-100 text-green-700' },
    ongoing: { label: 'Ongoing', badge: 'bg-blue-100 text-blue-700' },
    pending: { label: 'Pending', badge: 'bg-orange-100 text-orange-700' },
};

const BAR_COLORS = ['#1e3a8a', '#2563eb', '#0d9488', '#16a34a', '#22c55e'];

function StatCard({
    label,
    value,
    caption,
    icon,
    iconClass,
}: {
    label: string;
    value: string;
    caption: string;
    icon: React.ReactNode;
    iconClass: string;
}) {
    return (
        <div className="rounded-xl border border-[#d1d5db] bg-white p-5">
            <div className="flex items-start justify-between">
                <p className="text-xs font-semibold tracking-wide text-subtle uppercase">{label}</p>
                <span className={`flex h-8 w-8 items-center justify-center rounded-lg ${iconClass}`}>{icon}</span>
            </div>
            <p className="mt-3 text-3xl font-extrabold text-ink">{value}</p>
            <p className="mt-1 text-xs text-subtle">{caption}</p>
        </div>
    );
}

function StatusDonut({ breakdown }: { breakdown: StatusBreakdown }) {
    const total = breakdown.completed + breakdown.pending + breakdown.ongoing;
    const pct = (n: number) => (total > 0 ? (n / total) * 100 : 0);

    const segments = [
        { key: 'completed', label: 'Completed', value: breakdown.completed, color: '#16a34a' },
        { key: 'pending', label: 'Pending', value: breakdown.pending, color: '#ea580c' },
        { key: 'ongoing', label: 'Ongoing', value: breakdown.ongoing, color: '#2563eb' },
    ];

    // r = 15.9155 makes the circle's circumference exactly 100,
    // so a stroke-dasharray of "40 60" draws 40% of the ring.
    const R = 15.9155;
    let offset = 0;

    return (
        <div className="flex flex-col items-center gap-6 sm:flex-row sm:justify-around">
            <div className="relative h-40 w-40 shrink-0">
                <svg viewBox="0 0 42 42" className="h-full w-full">
                    <circle cx="21" cy="21" r={R} fill="transparent" stroke="#e5e7eb" strokeWidth="5" />
                    <g transform="rotate(-90 21 21)">
                        {segments.map((seg) => {
                            const length = pct(seg.value);
                            const circle = (
                                <circle
                                    key={seg.key}
                                    cx="21"
                                    cy="21"
                                    r={R}
                                    fill="transparent"
                                    stroke={seg.color}
                                    strokeWidth="5"
                                    strokeDasharray={`${length} ${100 - length}`}
                                    strokeDashoffset={-offset}
                                />
                            );
                            offset += length;
                            return circle;
                        })}
                    </g>
                </svg>
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                    <span className="text-2xl font-extrabold text-ink">{pct(breakdown.completed).toFixed(1)}%</span>
                    <span className="text-xs text-subtle">Delivered</span>
                </div>
            </div>

            <ul className="space-y-2 text-sm">
                {segments.map((seg) => (
                    <li key={seg.key} className="flex items-center gap-2 text-ink">
                        <span className="h-3 w-3 rounded-sm" style={{ backgroundColor: seg.color }} />
                        {seg.label} ({Math.round(pct(seg.value))}%)
                    </li>
                ))}
            </ul>
        </div>
    );
}

function BarangayBars({ data }: { data: BarangayCount[] }) {
    const max = Math.max(...data.map((d) => d.total), 1);

    if (data.length === 0) {
        return <p className="py-10 text-center text-sm text-subtle">No beneficiaries registered yet.</p>;
    }

    return (
        <div className="flex h-48 items-end justify-around gap-3">
            {data.map((item, i) => (
                <div key={item.name} className="flex h-full min-w-0 flex-1 flex-col items-center justify-end">
                    <span className="mb-1 text-xs font-semibold text-ink">{item.total.toLocaleString()}</span>
                    <div
                        className="w-full max-w-14 rounded-t-md"
                        style={{
                            height: `${Math.max((item.total / max) * 100, 3)}%`,
                            backgroundColor: BAR_COLORS[i % BAR_COLORS.length],
                        }}
                    />
                    <span className="mt-2 w-full truncate text-center text-xs text-subtle" title={item.name}>
                        {item.name}
                    </span>
                </div>
            ))}
        </div>
    );
}

export default function Dashboard({ stats, statusBreakdown, beneficiariesPerBarangay, recentActivities }: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-extrabold tracking-tight text-ink">
                            Government Relief Operations Dashboard
                        </h1>
                        <p className="mt-0.5 text-sm text-subtle">
                            Overview of response metrics, barangay status, and real-time distribution pipeline.
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            aria-label="Notifications"
                            className="flex h-9 w-9 items-center justify-center rounded-full border border-[#d1d5db] bg-white text-ink hover:bg-[#e0e4e9]"
                        >
                            <Bell className="h-4 w-4" />
                        </button>
                        <span className="rounded-full border border-[#d1d5db] bg-white px-4 py-1.5 text-sm text-ink">
                            Catanduanes
                        </span>
                    </div>
                </div>

                {/* Stat cards */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        label="Total Beneficiaries"
                        value={stats.totalBeneficiaries.toLocaleString()}
                        caption="Registered families overall"
                        icon={<Users className="h-4 w-4" />}
                        iconClass="bg-blue-100 text-blue-600"
                    />
                    <StatCard
                        label="Scheduled Distribution"
                        value={`${stats.scheduledDistributions.toLocaleString()} ${
                            stats.scheduledDistributions === 1 ? 'Distribution' : 'Distributions'
                        }`}
                        caption="Total distribution schedules created"
                        icon={<CalendarDays className="h-4 w-4" />}
                        iconClass="bg-green-100 text-green-600"
                    />
                    <StatCard
                        label="Completed Operations"
                        value={stats.completedOperations.toLocaleString()}
                        caption="Distributions completed this month"
                        icon={<CircleCheck className="h-4 w-4" />}
                        iconClass="bg-green-100 text-green-600"
                    />
                </div>

                {/* Charts */}
                <div className="grid gap-4 lg:grid-cols-2">
                    <section className="rounded-xl border border-[#d1d5db] bg-white p-5">
                        <h2 className="mb-4 text-base font-bold text-ink">Distribution Status Breakdown</h2>
                        <StatusDonut breakdown={statusBreakdown} />
                    </section>

                    <section className="rounded-xl border border-[#d1d5db] bg-white p-5">
                        <h2 className="mb-4 text-base font-bold text-ink">Beneficiaries per Barangay (Top 5)</h2>
                        <BarangayBars data={beneficiariesPerBarangay} />
                    </section>
                </div>

                {/* Recent activities */}
                <section className="overflow-hidden rounded-xl border border-[#d1d5db] bg-white">
                    <h2 className="px-5 pt-5 pb-3 text-base font-bold text-ink">Recent Distribution Activities</h2>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-y border-[#d1d5db] bg-[#f3f4f6] text-xs text-subtle uppercase">
                                    <th className="px-5 py-2.5 font-semibold">Date</th>
                                    <th className="px-5 py-2.5 font-semibold">Activity / Barangay Location</th>
                                    <th className="px-5 py-2.5 font-semibold">Status</th>
                                    <th className="px-5 py-2.5 font-semibold">Assigned Officer</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recentActivities.length === 0 && (
                                    <tr>
                                        <td colSpan={4} className="px-5 py-10 text-center text-subtle">
                                            No distribution activities yet.
                                        </td>
                                    </tr>
                                )}
                                {recentActivities.map((row) => {
                                    const status = STATUS_STYLES[row.status] ?? STATUS_STYLES.pending;

                                    return (
                                        <tr key={row.id} className="border-b border-[#e5e7eb] last:border-0">
                                            <td className="px-5 py-3 whitespace-nowrap text-subtle">
                                                {formatDate(row.date)}
                                            </td>
                                            <td className="px-5 py-3">
                                                <span className="font-semibold text-ink">{row.activity}</span>
                                                <span className="text-subtle"> — {row.barangay}</span>
                                            </td>
                                            <td className="px-5 py-3">
                                                <span
                                                    className={`rounded-md px-2 py-0.5 text-xs font-semibold ${status.badge}`}
                                                >
                                                    {status.label}
                                                </span>
                                            </td>
                                            <td className="px-5 py-3 text-ink">{row.officer}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
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