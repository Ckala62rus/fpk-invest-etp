<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Протокол аукциона {{ $procedure->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 4px; text-align: left; }
    </style>
</head>
<body>
    <h1>Протокол торгов ЭТП (электронной торговой площадки)</h1>
    <p>Процедура: {{ $procedure->number }} — {{ $procedure->title }}</p>
    <p>Дата формирования: {{ $generatedAt }}</p>
    <table>
        <thead>
            <tr>
                <th>Лот</th>
                <th>Стартовая цена</th>
                <th>Итог</th>
                <th>ИНН победителя</th>
                <th>Организация / ФИО</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lots as $lot)
                @php
                    $winner = $lot->winner;
                    $orgName = $winner?->profile?->name;
                @endphp
                <tr>
                    <td>{{ $lot->name }}</td>
                    <td>{{ $lot->start_price }}</td>
                    <td>{{ $lot->current_price }}</td>
                    <td>{{ $winner?->inn ?? '—' }}</td>
                    <td>{{ $orgName ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
