<?php

namespace App\Enums;

enum CustomFieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Decimal = 'decimal';
    case Date = 'date';
    case Boolean = 'boolean';
    case Select = 'select';
    case File = 'file';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Текст',
            self::Number => 'Число',
            self::Decimal => 'Десятичное число',
            self::Date => 'Дата',
            self::Boolean => 'Да/Нет',
            self::Select => 'Выбор из списка',
            self::File => 'Файл',
        };
    }
}
