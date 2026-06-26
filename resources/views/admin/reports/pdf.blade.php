<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IT Help Desk Report</title>

    <style>
        @page {
            margin: 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            background: #ffffff;
        }

        h1, h2, h3, p {
            margin: 0;
        }

        .report-wrapper {
            padding: 8px;
        }

        .header {
            background: #f0fdfa;
            border: 1px solid #bae6fd;
            border-left: 6px solid #0f7b92;
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 20px;
        }

        .header-top {
            width: 100%;
        }

        .eyebrow {
            color: #0f7b92;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .title {
            font-size: 25px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .meta {
            color: #475569;
            font-size: 11px;
        }

        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin-bottom: 18px;
        }

        .summary-card {
            background: #ffffff;
            border: 1px solid #dbe5ec;
            border-radius: 14px;
            padding: 14px;
            min-height: 76px;
        }

        .summary-label {
            color: #64748b;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 8px;
        }

        .summary-value.small {
            font-size: 15px;
            line-height: 1.4;
        }

        .section {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .section-title {
            background: #0f7b92;
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
            padding: 9px 12px;
            border-radius: 10px 10px 0 0;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #dbe5ec;
            border-top: none;
        }

        table.data th,
        table.data td {
            border-bottom: 1px solid #e2e8f0;
            padding: 9px 10px;
            text-align: left;
            vertical-align: top;
        }

        table.data th {
            background: #eef6fa;
            color: #0f7b92;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        table.data tr:nth-child(even) td {
            background: #f8fafc;
        }

        table.data tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #075985;
            font-size: 10px;
            font-weight: bold;
        }

        .muted {
            color: #64748b;
        }

        .ticket-number {
            margin-top: 3px;
            font-size: 10px;
            color: #64748b;
        }

        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #dbe5ec;
            color: #64748b;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="report-wrapper">

        <div class="header">
            <div class="eyebrow">IT Help Desk Reports</div>

            <div class="title">Administrative Report Export</div>

            <p class="meta">
                <strong>Scope:</strong> {{ $rangeLabel }} |
                <strong>Generated:</strong> {{ $generatedAt->format('d M Y, h:i A') }} |
                <strong>User:</strong> {{ $user->name }}
            </p>
        </div>

        <table class="summary-table">
            <tr>
                <td width="25%">
                    <div class="summary-card">
                        <div class="summary-label">Total Tickets</div>
                        <div class="summary-value">{{ $ticketCount }}</div>
                    </div>
                </td>

                <td width="25%">
                    <div class="summary-card">
                        <div class="summary-label">Active Queue</div>
                        <div class="summary-value">{{ $activeTickets }}</div>
                    </div>
                </td>

                <td width="25%">
                    <div class="summary-card">
                        <div class="summary-label">Critical Tickets</div>
                        <div class="summary-value">{{ $criticalTickets }}</div>
                    </div>
                </td>

                <td width="25%">
                    <div class="summary-card">
                        <div class="summary-label">Resolution Speed</div>
                        <div class="summary-value small">{{ $averageResolutionLabel }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section">
            <div class="section-title">Status Distribution</div>

            <table class="data">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($statusBreakdown as $status)
                        <tr>
                            <td><span class="badge">{{ $status['label'] }}</span></td>
                            <td>{{ $status['count'] }}</td>
                            <td>{{ $status['percentage'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Priority Distribution</div>

            <table class="data">
                <thead>
                    <tr>
                        <th>Priority</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($priorityBreakdown as $priority)
                        <tr>
                            <td><span class="badge">{{ $priority['label'] }}</span></td>
                            <td>{{ $priority['count'] }}</td>
                            <td>{{ $priority['percentage'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Top Categories</div>

            <table class="data">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($categoryBreakdown as $category)
                        <tr>
                            <td>{{ $category['name'] }}</td>
                            <td>{{ $category['count'] }}</td>
                            <td>{{ $category['percentage'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="muted">
                                No category data available for this report range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Support Performance</div>

            <table class="data">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Resolved Tickets</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($teamPerformance as $member)
                        <tr>
                            <td>{{ $member['name'] }}</td>
                            <td>{{ $member['role'] }}</td>
                            <td>{{ $member['resolved_count'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="muted">
                                No performance data available for this report range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Recent Activity</div>

            <table class="data">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Time</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($recentActivity as $activity)
                        <tr>
                            <td>
                                {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $activity->action)) }}

                                @if ($activity->ticket)
                                    <div class="ticket-number">
                                        {{ $activity->ticket->ticket_number }}
                                    </div>
                                @endif
                            </td>

                            <td>{{ $activity->description }}</td>

                            <td>{{ $activity->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="muted">
                                No visible activity available for this report range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="footer">
            Generated by IT Help Desk System • Administrative Report
        </div>

    </div>
</body>
</html>