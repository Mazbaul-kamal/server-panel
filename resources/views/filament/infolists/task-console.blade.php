@php
    $logs = $record->logs()->orderBy('id')->limit(500)->get(['id', 'type', 'output']);
    $lastLogId = $logs->last()?->id ?? 0;
@endphp

<div
    x-data="{
        lines: @js($logs->pluck('output')->implode('')),
        init() {
            const source = new EventSource(@js(route('tasks.stream', ['task' => $record, 'after' => $lastLogId])));

            source.addEventListener('output', (event) => {
                const payload = JSON.parse(event.data);
                this.lines += payload.output;
                this.$nextTick(() => this.$refs.output.scrollTop = this.$refs.output.scrollHeight);
            });

            source.addEventListener('complete', () => source.close());
        },
    }"
    class="rounded-lg border border-white/10 bg-zinc-950 p-4"
>
    <pre
        x-ref="output"
        x-text="lines || 'Waiting for output...'"
        class="h-96 overflow-auto whitespace-pre-wrap font-mono text-xs leading-5 text-zinc-100"
    ></pre>
</div>
