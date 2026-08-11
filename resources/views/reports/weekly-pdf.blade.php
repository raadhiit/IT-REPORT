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
        }

        .header {
            background: #0f172a;
            color: #ffffff;
            padding: 18px 22px;
            margin: -20px -20px 18px;
        }

        .header h1 {
            font-size: 19px;
            margin: 0 0 4px;
            font-weight: bold;
        }

        .header .meta {
            color: #cbd5e1;
            font-size: 10px;
        }

        .stats {
            width: 100%;
            margin-bottom: 20px;
        }

        .stats td {
            width: 33.33%;
            background: #f4f5f4;
            padding: 10px 14px;
        }

        .stats td + td {
            border-left: 6px solid #ffffff;
        }

        .stats .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
        }

        .stats .stat-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-top: 2px;
        }

        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #5b655f;
            border-bottom: 1px solid #dde3de;
            padding-bottom: 4px;
            margin: 20px 0 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Bar chart rows (category + staff) */
        table.bars td {
            padding: 6px 0;
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

        table.staff th,
        table.staff td {
            text-align: left;
            padding: 5px 0;
            border-bottom: 1px solid #dde3de;
        }

        table.staff td.total {
            text-align: right;
            width: 60px;
        }

        .cat-heading {
            font-size: 12px;
            font-weight: bold;
            margin: 14px 0 6px;
        }

        ul.activities {
            margin: 0;
            padding-left: 0;
            list-style: none;
        }

        ul.activities li {
            margin-bottom: 5px;
        }

        .who {
            color: #5b655f;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Aktivitas Mingguan &mdash; {{ $generatedBy }}</h1>
        <div class="meta">{{ $start }} &ndash; {{ $end }} &middot; Dibuat {{ $generatedAt }}</div>
    </div>

    <table class="stats">
        <tr>
            <td>
                <div class="stat-value">{{ $total }}</div>
                <div class="stat-label">Total Aktivitas</div>
            </td>
            <td>
                <div class="stat-value">{{ $topCategory && $topCategory['count'] > 0 ? $topCategory['label'] : '—' }}</div>
                <div class="stat-label">Kategori Terbanyak</div>
            </td>
            <td>
                <div class="stat-value">{{ $byStaff->isNotEmpty() ? $byStaff->count() : '—' }}</div>
                <div class="stat-label">Staff Aktif</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Distribusi Kategori</div>
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
        <div class="section-title">Per Staff</div>
        <table class="bars">
            @foreach ($byStaff as $staff)
                <tr>
                    <td class="bar-label">{{ $staff['name'] }}</td>
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
    @foreach ($detailsByCategory as $category)
        @continue(count($category['activities']) === 0)
        <div class="cat-heading">
            <span class="dot" style="background: {{ $categoryColors[$category['value']] }};"></span>
            {{ $category['label'] }} ({{ count($category['activities']) }})
        </div>
        <ul class="activities">
            @foreach ($category['activities'] as $activity)
                <li>
                    <span class="who">{{ $activity['staff'] }} &middot; {{ \Illuminate\Support\Carbon::parse($activity['tanggal'])->translatedFormat('d/m') }}</span>
                    &mdash; {{ $activity['deskripsi'] }}
                </li>
            @endforeach
        </ul>
    @endforeach
</body>
</html>
