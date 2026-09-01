@component('mail::message')
# Приглашение на участие в закупке

Вам направлено приглашение на процедуру **{{ $procedure->number }}** — {{ $procedure->title }}.

@if($procedure->ends_at)
Срок подачи предложений: **{{ $procedure->ends_at->format('d.m.Y H:i') }}**
@endif

@component('mail::button', ['url' => config('app.url')])
Перейти на ЭТП
@endcomponent

С уважением,<br>
{{ config('app.name') }}
@endcomponent
