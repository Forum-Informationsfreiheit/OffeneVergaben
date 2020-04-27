<div class="table {{ isset($tableClass) ? $tableClass : '' }}">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</div>
