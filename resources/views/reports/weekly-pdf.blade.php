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
            $maxCategoryCount = max(1, $byCategory->max('count'));
            $maxStaffTotal = $byStaff->isNotEmpty() ? max(1, $byStaff->max('total')) : 1;
            $topCategory = $byCategory->sortByDesc('count')->first();
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
            width: 33.33%;
            background: #f7f8f7;
            padding: 12px 16px;
            border-left: 4px solid #0f172a;
        }

        .stats td + td {
            border-left-color: #0f172a;
        }

        .stats td.accent-cat {
            border-left-color: #0f766e;
        }

        .stats td.accent-staff {
            border-left-color: #6b7280;
        }

        .stats td.gap {
            width: 10px;
            background: transparent;
            border-left: none;
            padding: 0;
        }

        .stats .stat-value {
            font-size: 21px;
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
            <td class="accent-cat">
                <div class="stat-value">{{ $topCategory && $topCategory['count'] > 0 ? $topCategory['label'] : '—' }}</div>
                <div class="stat-label">Kategori Terbanyak</div>
            </td>
            <td class="gap"></td>
            <td class="accent-staff">
                <div class="stat-value">{{ $byStaff->isNotEmpty() ? $byStaff->count() : '—' }}</div>
                <div class="stat-label">Staff Aktif</div>
            </td>
        </tr>
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
                        <td class="who">{{ $activity['staff'] }} &middot; {{ \Illuminate\Support\Carbon::parse($activity['tanggal'])->translatedFormat('d/m') }}</td>
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
