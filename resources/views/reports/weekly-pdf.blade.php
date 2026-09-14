<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Aktivitas Mingguan</title>
    <style>
        @php
            $categoryColors = [
                'maintenance' => '#b45309',
                'project' => '#1d4ed8',
                'support' => '#0f766e',
                'meeting' => '#7e22ce',
                'other' => '#57534e',
            ];
            $statusColors = [
                'selesai' => '#15803d',
                'on_track' => '#a3720a',
                'pending' => '#b3123f',
            ];
            $maxCategoryCount = max(1, $byCategory->max('count'));
            $maxStaffTotal = $byStaff->isNotEmpty() ? max(1, $byStaff->max('total')) : 1;
            $maxDaily = max(1, $dailyCounts->max('count'));
            $statusCount = fn (string $value) => (int) ($byStatus->firstWhere('value', $value)['count'] ?? 0);
            $dayLabel = fn (string $date) => \Illuminate\Support\Carbon::parse($date)->translatedFormat('D');

            // Both charts below are plain table/div box-model (same technique already proven to render
            // in this DomPDF setup for the category/staff/project bars) — not SVG. Two rounds of SVG
            // (arc paths, then stroke-dasharray circles + polyline) both came back completely blank,
            // so pie/line-style visuals are swapped for a 100%-stacked bar and a horizontal bar list,
            // which read the same information without needing vector graphics support DomPDF doesn't
            // reliably have here.
        @endphp

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1c2520;
            line-height: 1.5;
        }

        .header {
            background: #0f172a;
            color: #ffffff;
            padding: 20px 24px;
            margin: -20px -20px 22px;
        }

        .header .eyebrow {
            color: #93c5fd;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 4px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0 0 6px;
            font-weight: bold;
        }

        .header .meta {
            color: #cbd5e1;
            font-size: 10px;
        }

        .stats {
            width: 100%;
            margin-bottom: 22px;
            border-collapse: separate;
        }

        .stats td {
            width: 25%;
            background: #f7f8f7;
            padding: 12px 14px;
            border-left: 4px solid #0f172a;
        }

        .stats td.accent-good {
            border-left-color: #15803d;
        }

        .stats td.accent-warn {
            border-left-color: #a3720a;
        }

        .stats td.accent-crit {
            border-left-color: #b3123f;
        }

        .stats td.gap {
            width: 8px;
            background: transparent;
            border-left: none;
            padding: 0;
        }

        .stats .stat-value {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 19px;
            font-weight: bold;
            color: #0f172a;
        }

        .stats .stat-label {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #6b7280;
            margin-top: 3px;
        }

        /* Status pengerjaan — single-row 100%-stacked bar (a table row of variable-width cells) plus a legend list */
        .stacked-track {
            width: 100%;
            height: 14px;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .stacked-track td {
            height: 14px;
            padding: 0;
        }

        .legend-row {
            padding: 5px 0;
            font-size: 11px;
        }

        .legend-row .num {
            font-family: 'DejaVu Sans Mono', monospace;
            color: #6b7280;
            font-size: 10px;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1c2520;
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 5px;
            margin: 24px 0 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Bar chart rows (category + staff) */
        table.bars td {
            padding: 7px 0;
            vertical-align: middle;
        }

        table.bars td.bar-label {
            width: 150px;
            font-size: 10.5px;
            font-weight: bold;
        }

        table.bars td.bar-track {
            padding: 0 10px;
        }

        table.bars td.bar-count {
            width: 34px;
            text-align: right;
            font-size: 11px;
            font-weight: bold;
        }

        .track-outer {
            background: #eceeec;
            height: 11px;
            width: 320px;
        }

        .track-fill {
            height: 11px;
        }

        .dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            margin-right: 5px;
        }

        .rank {
            display: inline-block;
            width: 16px;
            height: 16px;
            line-height: 16px;
            text-align: center;
            background: #0f172a;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            border-radius: 8px;
            margin-right: 6px;
        }

        .cat-box {
            border: 1px solid #dde3de;
            margin-bottom: 14px;
        }

        .cat-box-header {
            background: #f4f5f4;
            border-bottom: 1px solid #dde3de;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .cat-box-header .count {
            float: right;
            background: #e4e6e4;
            color: #4b544f;
            font-size: 9px;
            font-weight: bold;
            padding: 1px 7px;
            border-radius: 8px;
        }

        table.activities td {
            padding: 7px 12px;
            border-bottom: 1px solid #eceeec;
            vertical-align: top;
        }

        table.activities tr:last-child td {
            border-bottom: none;
        }

        table.activities td.who {
            width: 110px;
            color: #6b7280;
            font-size: 9px;
        }

        table.activities td.desc {
            font-size: 10.5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="eyebrow">Laporan Aktivitas Mingguan</div>
        <h1>{{ $generatedBy }}</h1>
        <div class="meta">{{ $start }} &ndash; {{ $end }} &middot; Dibuat {{ $generatedAt }}</div>
    </div>

    <table class="stats">
        <tr>
            <td>
                <div class="stat-value">{{ $total }}</div>
                <div class="stat-label">Total Aktivitas</div>
            </td>
            <td class="gap"></td>
            <td class="accent-good">
                <div class="stat-value">{{ $statusCount('selesai') }}</div>
                <div class="stat-label">Selesai</div>
            </td>
            <td class="gap"></td>
            <td class="accent-warn">
                <div class="stat-value">{{ $statusCount('on_track') }}</div>
                <div class="stat-label">On Track</div>
            </td>
            <td class="gap"></td>
            <td class="accent-crit">
                <div class="stat-value">{{ $statusCount('pending') }}</div>
                <div class="stat-label">Pending</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Tren Aktivitas Harian</div>
    <table class="bars">
        @foreach ($dailyCounts as $day)
            <tr>
                <td class="bar-label">{{ $dayLabel($day['date']) }}</td>
                <td class="bar-track">
                    <div class="track-outer">
                        <div
                            class="track-fill"
                            style="background: #1d4ed8; width: {{ round($day['count'] / $maxDaily * 100) }}%;"
                        ></div>
                    </div>
                </td>
                <td class="bar-count">{{ $day['count'] }}</td>
            </tr>
        @endforeach
    </table>

    <div class="section-title">Ringkasan per Kategori</div>
    <table class="bars">
        @foreach ($byCategory as $category)
            <tr>
                <td class="bar-label">
                    <span class="dot" style="background: {{ $categoryColors[$category['value']] }};"></span>
                    {{ $category['label'] }}
                </td>
                <td class="bar-track">
                    <div class="track-outer">
                        <div
                            class="track-fill"
                            style="background: {{ $categoryColors[$category['value']] }}; width: {{ round($category['count'] / $maxCategoryCount * 100) }}%;"
                        ></div>
                    </div>
                </td>
                <td class="bar-count">{{ $category['count'] }}</td>
            </tr>
        @endforeach
    </table>

    <div class="section-title">Status Pengerjaan</div>
    <table class="stacked-track">
        <tr>
            @php $hasActiveStatus = false; @endphp
            @foreach ($byStatus as $status)
                @continue($status['count'] <= 0)
                @php $hasActiveStatus = true; @endphp
                <td style="width: {{ round($status['count'] / $total * 100) }}%; background: {{ $statusColors[$status['value']] }};"></td>
            @endforeach
            @unless ($hasActiveStatus)
                <td style="width: 100%; background: #eceeec;"></td>
            @endunless
        </tr>
    </table>
    @foreach ($byStatus as $status)
        <div class="legend-row">
            <span class="dot" style="background: {{ $statusColors[$status['value']] }};"></span>
            {{ $status['label'] }}
            <span class="num">&middot; {{ $status['count'] }} ({{ $total > 0 ? round($status['count'] / $total * 100) : 0 }}%)</span>
        </div>
    @endforeach

    @if ($byStaff->isNotEmpty())
        <div class="section-title">Breakdown per Staff</div>
        <table class="bars">
            @foreach ($byStaff as $index => $staff)
                <tr>
                    <td class="bar-label">
                        <span class="rank">{{ $index + 1 }}</span>
                        {{ $staff['name'] }}
                    </td>
                    <td class="bar-track">
                        <div class="track-outer">
                            <div
                                class="track-fill"
                                style="background: #0f172a; width: {{ round($staff['total'] / $maxStaffTotal * 100) }}%;"
                            ></div>
                        </div>
                    </td>
                    <td class="bar-count">{{ $staff['total'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if (count($projects) > 0)
        <div class="section-title">Proyek Berjalan</div>
        <table class="activities">
            @foreach ($projects as $project)
                <tr>
                    <td class="who">
                        {{ $project['staff'] }}
                        @if ($project['target_selesai'])
                            <br>Target {{ \Illuminate\Support\Carbon::parse($project['target_selesai'])->translatedFormat('d/m') }}
                        @endif
                    </td>
                    <td class="desc">
                        {{ $project['deskripsi'] }}
                        @if ($project['progress_percent'] !== null)
                            <div class="track-outer" style="width: 160px; margin-top: 4px;">
                                <div class="track-fill" style="background: {{ $statusColors[$project['status']] }}; width: {{ $project['progress_percent'] }}%;"></div>
                            </div>
                        @endif
                    </td>
                    <td class="bar-count">{{ $project['progress_percent'] !== null ? $project['progress_percent'].'%' : '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="section-title">Detail Aktivitas</div>
    @php $hasDetails = false; @endphp
    @foreach ($detailsByCategory as $category)
        @continue(count($category['activities']) === 0)
        @php $hasDetails = true; @endphp
        <div class="cat-box">
            <div class="cat-box-header">
                <span class="count">{{ count($category['activities']) }}</span>
                <span class="dot" style="background: {{ $categoryColors[$category['value']] }};"></span>
                {{ $category['label'] }}
            </div>
            <table class="activities">
                @foreach ($category['activities'] as $activity)
                    <tr>
                        <td class="who">
                            {{ $activity['staff'] }} &middot; {{ \Illuminate\Support\Carbon::parse($activity['tanggal'])->translatedFormat('d/m') }}
                            <br><span style="color: {{ $statusColors[$activity['status']] }};">&#9679;</span> {{ \App\Enums\ActivityStatus::from($activity['status'])->label() }}
                        </td>
                        <td class="desc">{{ $activity['deskripsi'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
    @unless ($hasDetails)
        <p style="color: #6b7280;">Belum ada aktivitas yang tercatat minggu ini.</p>
    @endunless
</body>
</html>
