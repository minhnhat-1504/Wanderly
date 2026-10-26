<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Wanderly — {{ $trip->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            background: #ffffff;
        }

        /* ─── COVER PAGE ─────────────────────────────── */
        .cover-page {
            width: 100%;
            min-height: 297mm;
            background: linear-gradient(160deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px;
            page-break-after: always;
            position: relative;
        }

        .cover-logo {
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 32px;
        }

        .cover-app-name {
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .cover-trip-name {
            color: #ffffff;
            font-size: 32px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 16px;
        }

        .cover-destination {
            color: rgba(255,255,255,0.8);
            font-size: 16px;
            margin-bottom: 8px;
        }

        .cover-dates {
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            margin-bottom: 32px;
        }

        .cover-divider {
            width: 60px;
            height: 3px;
            background: rgba(255,255,255,0.4);
            margin: 24px auto;
            border-radius: 2px;
        }

        .cover-meta-row {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 16px;
        }

        .cover-meta-item {
            text-align: center;
        }

        .cover-meta-value {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
        }

        .cover-meta-label {
            color: rgba(255,255,255,0.55);
            font-size: 11px;
            margin-top: 4px;
        }

        .cover-footer {
            position: absolute;
            bottom: 24px;
            color: rgba(255,255,255,0.35);
            font-size: 9px;
        }

        /* ─── CONTENT PAGES ──────────────────────────── */
        .page {
            padding: 24px 28px;
            page-break-after: always;
        }

        .page:last-child { page-break-after: auto; }

        /* Section Heading */
        .section-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }

        .section-heading-icon {
            background: #4338ca;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
        }

        .section-heading-title {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
        }

        /* ─── MEMBERS ────────────────────────────────── */
        .members-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 12px;
        }

        .member-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f3f4f6;
            border-radius: 50px;
            padding: 6px 12px 6px 6px;
        }

        .member-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4338ca, #10b981);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 11px;
            font-weight: 700;
        }

        .member-name { font-size: 11px; font-weight: 600; color: #374151; }
        .member-role { font-size: 10px; color: #6b7280; margin-left: 4px; }

        /* ─── BUDGET SUMMARY ─────────────────────────── */
        .budget-box {
            background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 16px;
        }

        .budget-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid rgba(167,243,208,0.5);
            font-size: 11px;
        }

        .budget-row:last-child { border-bottom: none; font-weight: 700; font-size: 12px; }

        .budget-label { color: #374151; }
        .budget-value { color: #065f46; font-weight: 600; }
        .budget-value.over { color: #dc2626; }

        .progress-bar-wrap {
            background: #d1fae5;
            border-radius: 6px;
            height: 10px;
            margin-top: 12px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 6px;
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .progress-bar-fill.warning { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .progress-bar-fill.danger { background: linear-gradient(90deg, #ef4444, #dc2626); }

        /* ─── DAILY ITINERARY ────────────────────────── */
        .day-block {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .day-header {
            background: linear-gradient(90deg, #4338ca, #7c3aed);
            color: white;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .slot-block {
            margin-bottom: 6px;
            page-break-inside: avoid;
        }

        .slot-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            padding: 4px 0 2px 10px;
        }

        .activity-row {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 7px 10px;
            background: #fafafa;
            border-left: 3px solid #e0e7ff;
            margin-bottom: 4px;
            border-radius: 0 6px 6px 0;
        }

        .activity-time {
            font-size: 9px;
            color: #9ca3af;
            white-space: nowrap;
            min-width: 40px;
        }

        .activity-title {
            font-weight: 600;
            color: #111827;
            font-size: 11px;
            flex: 1;
        }

        .activity-location {
            font-size: 9px;
            color: #6b7280;
            margin-top: 1px;
        }

        .activity-cost {
            font-size: 10px;
            font-weight: 700;
            color: #4338ca;
            white-space: nowrap;
        }

        .no-activities {
            font-size: 10px;
            color: #d1d5db;
            padding: 8px 10px;
            font-style: italic;
        }

        /* ─── CHECKLIST ──────────────────────────────── */
        .checklist-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 4px 8px;
            min-width: 140px;
        }

        .checklist-checkbox {
            width: 12px;
            height: 12px;
            border: 1.5px solid #d1d5db;
            border-radius: 3px;
            background: {{ '' }};
            display: inline-block;
            flex-shrink: 0;
        }

        .checklist-checkbox.done {
            background: #10b981;
            border-color: #10b981;
        }

        /* ─── ROOM BOOKINGS ──────────────────────────── */
        .room-row {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 8px 10px;
            background: #fafafa;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 11px;
        }

        .room-badge {
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 9px;
            font-weight: 700;
            border-radius: 4px;
            padding: 2px 6px;
        }
    </style>
</head>
<body>

{{-- ════════════════════════════════════ COVER PAGE ════════════════════════════════════ --}}
<div class="cover-page">
    <div class="cover-logo">✈</div>
    <p class="cover-app-name">Wanderly</p>
    <h1 class="cover-trip-name">{{ $trip->name }}</h1>
    <p class="cover-destination">📍 {{ $trip->destination }}</p>
    <p class="cover-dates">
        {{ $startDate->format('d M Y') }} — {{ $endDate->format('d M Y') }}
    </p>

    <div class="cover-divider"></div>

    <div class="cover-meta-row">
        <div class="cover-meta-item">
            <div class="cover-meta-value">{{ $tripDays }}</div>
            <div class="cover-meta-label">{{ __('Days') }}</div>
        </div>
        <div class="cover-meta-item">
            <div class="cover-meta-value">{{ $members->count() }}</div>
            <div class="cover-meta-label">{{ __('Members') }}</div>
        </div>
        <div class="cover-meta-item">
            <div class="cover-meta-value">{{ number_format($totalBudget / 1000000, 1) }}M</div>
            <div class="cover-meta-label">{{ __('VND Budget') }}</div>
        </div>
    </div>

    <div class="cover-footer">{{ __('Generated by Wanderly on :date', ['date' => $generatedAt]) }}</div>
</div>

{{-- ════════════════════════════════════ PAGE 2: OVERVIEW ════════════════════════════════ --}}
<div class="page">
    {{-- Members --}}
    @if($members->isNotEmpty())
    <div class="section-heading">
        <div class="section-heading-icon">👥</div>
        <div class="section-heading-title">{{ __('Trip Members') }}</div>
    </div>
    <div class="members-grid" style="margin-bottom: 24px;">
        @foreach($members as $member)
        <div class="member-chip">
            <div class="member-avatar">{{ strtoupper(substr($member->user->name ?? 'U', 0, 1)) }}</div>
            <span class="member-name">{{ $member->user->name ?? __('User') }}</span>
            <span class="member-role">({{ ucfirst($member->role) }})</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Budget Summary --}}
    <div class="section-heading">
        <div class="section-heading-icon">💰</div>
        <div class="section-heading-title">{{ __('Budget Summary') }}</div>
    </div>
    <div class="budget-box">
        <div class="budget-row">
            <span class="budget-label">{{ __('Total Budget') }}</span>
            <span class="budget-value">{{ number_format($totalBudget, 0, ',', '.') }} VND</span>
        </div>
        <div class="budget-row">
            <span class="budget-label">{{ __('Activities Cost') }}</span>
            <span class="budget-value">{{ number_format($trip->activities->sum('cost'), 0, ',', '.') }} VND</span>
        </div>
        <div class="budget-row">
            <span class="budget-label">{{ __('Accommodation') }}</span>
            <span class="budget-value">{{ number_format($trip->roomBookings->where('include_in_budget', true)->sum('price_per_night'), 0, ',', '.') }} VND</span>
        </div>
        <div class="budget-row">
            <span class="budget-label">{{ __('Total Spent') }}</span>
            <span class="budget-value {{ $totalCost > $totalBudget ? 'over' : '' }}">{{ number_format($totalCost, 0, ',', '.') }} VND</span>
        </div>
        <div class="budget-row">
            <span class="budget-label">{{ __('Remaining') }}</span>
            <span class="budget-value {{ $remaining < 0 ? 'over' : '' }}">{{ number_format($remaining, 0, ',', '.') }} VND</span>
        </div>

        @if($totalBudget > 0)
        <div class="progress-bar-wrap">
            @php
                $fillClass = $budgetPercentage < 80 ? '' : ($budgetPercentage < 95 ? 'warning' : 'danger');
            @endphp
            <div class="progress-bar-fill {{ $fillClass }}" style="width: {{ min(100, $budgetPercentage) }}%"></div>
        </div>
        <p style="font-size:9px; color:#6b7280; margin-top:4px; text-align:right;">{{ $budgetPercentage }}% {{ __('utilized') }}</p>
        @endif
    </div>

    {{-- Room Bookings --}}
    @if($roomBookings->isNotEmpty())
    <div class="section-heading" style="margin-top: 16px;">
        <div class="section-heading-icon">🏨</div>
        <div class="section-heading-title">{{ __('Accommodation') }}</div>
    </div>
    @foreach($roomBookings as $room)
    <div class="room-row">
        <span class="room-badge">{{ __('Room') }}</span>
        <span style="flex:1; font-weight:600;">{{ $room->name ?? __('Accommodation') }}</span>
        <span style="color:#6b7280;">{{ $room->check_in_date ?? '' }} → {{ $room->check_out_date ?? '' }}</span>
        <span style="font-weight:700; color:#4338ca;">{{ number_format($room->price_per_night, 0, ',', '.') }} VND/{{ __('night') }}</span>
    </div>
    @endforeach
    @endif
</div>

{{-- ════════════════════════════════════ DAILY ITINERARY ════════════════════════════════ --}}
@foreach($activityDays as $dayNum => $day)
<div class="page">
    <div class="section-heading">
        <div class="section-heading-icon">{{ $dayNum }}</div>
        <div class="section-heading-title">{{ $day['label'] }}</div>
    </div>

    @php $hasActivities = false; @endphp

    @foreach(['morning', 'midday', 'afternoon', 'evening'] as $slot)
        @if($day['slots'][$slot]->isNotEmpty())
        @php $hasActivities = true; @endphp
        <div class="slot-block">
            <div class="slot-label">
                {{ ['morning' => '☀️ Morning', 'midday' => '🌤 Midday', 'afternoon' => '🌇 Afternoon', 'evening' => '🌙 Evening'][$slot] }}
            </div>
            @foreach($day['slots'][$slot] as $activity)
            <div class="activity-row">
                <span class="activity-time">{{ $activity->start_time ?? '' }}</span>
                <div style="flex:1; min-width:0;">
                    <div class="activity-title">{{ $activity->title }}</div>
                    @if($activity->location)
                        <div class="activity-location">📍 {{ $activity->location }}</div>
                    @endif
                    @if($activity->notes)
                        <div class="activity-location" style="color:#9ca3af;">{{ Str::limit($activity->notes, 80) }}</div>
                    @endif
                </div>
                @if($activity->cost > 0)
                <span class="activity-cost">{{ number_format($activity->cost, 0, ',', '.') }}</span>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    @endforeach

    @if(!$hasActivities)
        <p class="no-activities">{{ __('No activities planned for this day.') }}</p>
    @endif

    @if($day['total_cost'] > 0)
    <div style="text-align:right; font-size:10px; color:#4338ca; font-weight:700; margin-top:8px; border-top:1px solid #e5e7eb; padding-top:8px;">
        {{ __('Day Total') }}: {{ number_format($day['total_cost'], 0, ',', '.') }} VND
    </div>
    @endif
</div>
@endforeach

{{-- ════════════════════════════════════ CHECKLIST PAGE ════════════════════════════════ --}}
@if($checklistItems->isNotEmpty())
<div class="page">
    <div class="section-heading">
        <div class="section-heading-icon">✅</div>
        <div class="section-heading-title">{{ __('Packing Checklist') }}</div>
    </div>

    @php
        $grouped = $checklistItems->groupBy('category');
        $categoryLabels = [
            'clothing' => '👔 Clothing', 'electronics' => '📱 Electronics',
            'documents' => '📄 Documents', 'health' => '💊 Health',
            'gear' => '🎒 Gear', 'accessories' => '🕶 Accessories',
            'personal_care' => '🪥 Personal Care', 'general' => '📦 General',
        ];
    @endphp

    @foreach($grouped as $category => $items)
    <div style="margin-bottom:16px;">
        <p style="font-size:10px; font-weight:700; color:#6b7280; margin-bottom:6px; text-transform:uppercase; letter-spacing:1px;">
            {{ $categoryLabels[$category] ?? ucfirst($category) }}
        </p>
        <div class="checklist-grid">
            @foreach($items as $item)
            <div class="checklist-item">
                <span class="checklist-checkbox {{ $item->is_completed ? 'done' : '' }}"></span>
                <span style="{{ $item->is_completed ? 'text-decoration:line-through; color:#9ca3af;' : '' }}">{{ $item->title }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endif

</body>
</html>
