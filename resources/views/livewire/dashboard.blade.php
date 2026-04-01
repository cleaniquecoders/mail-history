<div wire:poll.10s>
    {{-- Period Selector --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <h2 class="text-xl font-semibold">Dashboard</h2>
        <div class="flex items-center gap-3">
            <select wire:model.live="trendInterval" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
            </select>
            <select wire:model.live="days" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                <option value="7">Last 7 days</option>
                <option value="14">Last 14 days</option>
                <option value="30">Last 30 days</option>
                <option value="60">Last 60 days</option>
                <option value="90">Last 90 days</option>
            </select>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-8">
        @php
            $statusColors = [
                'Sending' => 'text-amber-600 dark:text-amber-400',
                'Sent' => 'text-blue-600 dark:text-blue-400',
                'Delivered' => 'text-emerald-600 dark:text-emerald-400',
                'Opened' => 'text-indigo-600 dark:text-indigo-400',
                'Clicked' => 'text-purple-600 dark:text-purple-400',
                'Bounced' => 'text-red-600 dark:text-red-400',
                'Complained' => 'text-orange-600 dark:text-orange-400',
                'Failed' => 'text-gray-600 dark:text-gray-400',
            ];
        @endphp

        @foreach ($summary['statuses'] as $status => $count)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $status }}</p>
                <p class="mt-1 text-2xl font-bold {{ $statusColors[$status] ?? 'text-gray-900 dark:text-gray-100' }}">{{ number_format($count) }}</p>
                @if ($summary['total'] > 0)
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $summary['rates'][$status] ?? 0 }}%</p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="grid gap-8 lg:grid-cols-3">
        {{-- Trends Table --}}
        <div class="lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h3 class="font-semibold">Trends</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs font-medium uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                <th class="px-5 py-3">Period</th>
                                <th class="px-3 py-3 text-right">Total</th>
                                <th class="px-3 py-3 text-right">Delivered</th>
                                <th class="px-3 py-3 text-right">Opened</th>
                                <th class="px-3 py-3 text-right">Bounced</th>
                                <th class="px-3 py-3 text-right">Failed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($trends as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-5 py-2.5 font-mono text-xs">{{ $row->period }}</td>
                                    <td class="px-3 py-2.5 text-right font-semibold">{{ $row->total }}</td>
                                    <td class="px-3 py-2.5 text-right text-emerald-600 dark:text-emerald-400">{{ (int) $row->delivered }}</td>
                                    <td class="px-3 py-2.5 text-right text-indigo-600 dark:text-indigo-400">{{ (int) $row->opened }}</td>
                                    <td class="px-3 py-2.5 text-right text-red-600 dark:text-red-400">{{ (int) $row->bounced }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-500">{{ (int) $row->failed }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-gray-400">No data for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Stale Alerts --}}
            @if ($stale->isNotEmpty())
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800/50 dark:bg-amber-900/20">
                    <h3 class="flex items-center gap-2 font-semibold text-amber-800 dark:text-amber-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        Stale Emails
                    </h3>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">{{ $stale->count() }} email(s) stuck in "Sending" for over 1 hour.</p>
                </div>
            @endif

            {{-- Provider Breakdown --}}
            @if ($providers->isNotEmpty())
                <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <h3 class="font-semibold">By Provider</h3>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($providers as $provider)
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-sm font-medium capitalize">{{ $provider->provider }}</span>
                                <div class="flex gap-3 text-xs">
                                    <span class="text-emerald-600 dark:text-emerald-400" title="Delivered">{{ (int) $provider->delivered }}</span>
                                    <span class="text-indigo-600 dark:text-indigo-400" title="Opened">{{ (int) $provider->opened }}</span>
                                    <span class="text-red-600 dark:text-red-400" title="Bounced">{{ (int) $provider->bounced }}</span>
                                    <span class="font-semibold" title="Total">{{ $provider->total }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Top Bounced --}}
            @if ($topBounced->isNotEmpty())
                <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <h3 class="font-semibold text-red-600 dark:text-red-400">Top Bounced</h3>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($topBounced as $entry)
                            <div class="flex items-center justify-between px-5 py-2.5">
                                <span class="truncate text-sm">{{ $entry['recipient'] }}</span>
                                <span class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300">{{ $entry['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Top Complained --}}
            @if ($topComplained->isNotEmpty())
                <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <h3 class="font-semibold text-orange-600 dark:text-orange-400">Top Spam Complaints</h3>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($topComplained as $entry)
                            <div class="flex items-center justify-between px-5 py-2.5">
                                <span class="truncate text-sm">{{ $entry['recipient'] }}</span>
                                <span class="ml-2 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">{{ $entry['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="mt-8">
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="font-semibold">Recent Activity</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs font-medium uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            <th class="px-5 py-3">Event</th>
                            <th class="px-3 py-3">Hash</th>
                            <th class="px-3 py-3">Provider</th>
                            <th class="px-3 py-3">IP</th>
                            <th class="px-3 py-3 text-right">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($activity as $event)
                            @php
                                $badgeColors = [
                                    'delivered' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    'opened' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                    'clicked' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                    'bounced' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                    'complained' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                    'failed' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                ];
                            @endphp
                            <tr class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                wire:click="viewTimeline('{{ $event['hash'] }}')"
                            >
                                <td class="px-5 py-2.5">
                                    <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeColors[$event['type']] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $event['type'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 font-mono text-xs text-gray-500">{{ Str::limit($event['hash'] ?? '—', 12) }}</td>
                                <td class="px-3 py-2.5 text-xs capitalize text-gray-500">{{ $event['provider'] ?? '—' }}</td>
                                <td class="px-3 py-2.5 font-mono text-xs text-gray-400">{{ $event['ip_address'] ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-right text-xs text-gray-400">
                                    {{ $event['occurred_at'] ? \Illuminate\Support\Carbon::parse($event['occurred_at'])->diffForHumans() : '—' }}
                                </td>
                            </tr>

                            {{-- Inline Timeline --}}
                            @if ($viewingHash && $viewingHash === $event['hash'])
                                <tr>
                                    <td colspan="5" class="bg-gray-50 px-5 py-4 dark:bg-gray-800/30">
                                        <p class="mb-2 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Timeline for {{ Str::limit($viewingHash, 20) }}</p>
                                        @if ($timeline && $timeline->isNotEmpty())
                                            <div class="space-y-1.5">
                                                @foreach ($timeline as $step)
                                                    <div class="flex items-center gap-3 text-xs">
                                                        <span class="inline-block w-20 rounded-full px-2 py-0.5 text-center font-medium {{ $badgeColors[$step['type']] ?? 'bg-gray-100 text-gray-700' }}">
                                                            {{ $step['type'] }}
                                                        </span>
                                                        <span class="font-mono text-gray-400">
                                                            {{ $step['occurred_at'] ? \Illuminate\Support\Carbon::parse($step['occurred_at'])->format('Y-m-d H:i:s') : '—' }}
                                                        </span>
                                                        <span class="capitalize text-gray-500">{{ $step['provider'] ?? '' }}</span>
                                                        @if ($step['url'])
                                                            <span class="truncate text-purple-500" title="{{ $step['url'] }}">{{ Str::limit($step['url'], 40) }}</span>
                                                        @endif
                                                        @if ($step['ip_address'])
                                                            <span class="font-mono text-gray-400">{{ $step['ip_address'] }}</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-xs text-gray-400">No events recorded.</p>
                                        @endif
                                    </td>
                                </tr>
                                @php $viewingHash = null; @endphp {{-- Only show once per hash --}}
                            @endif
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-gray-400">No recent activity.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
