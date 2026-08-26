@php
    // Partial badge status kehadiran.
    // Pakai: <x-attendance-status-badge :recap="$recap" />  atau  :status="$array"
    if (isset($recap) && $recap && ! isset($status)) {
        $status = $recap->status();
    }
    $label = $status['label'] ?? '-';
    $class = $status['class'] ?? 'st-alpa';
@endphp
<span class="st-badge {{ $class }}"><span class="st-dot"></span>{{ $label }}</span>
