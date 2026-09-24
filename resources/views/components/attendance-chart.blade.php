@props(['config', 'chartKey' => null, 'height' => 300])

<div
    wire:key="{{ $chartKey ?? md5(json_encode($config)) }}"
    x-data="{
        chart: null,
        init() {
            if (! window.Chart || ! this.$refs.cv) return;
            this.chart = new Chart(this.$refs.cv.getContext('2d'), @js($config));
        },
    }"
    x-init="init()"
>
    <canvas x-ref="cv" style="width: 100%; max-height: {{ $height }}px"></canvas>
</div>
