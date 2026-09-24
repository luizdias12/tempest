<?php

namespace App\Service;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

class DateTimeService
{
    public static function dateTime(
        ?DateTimeZone $timezone = null
    ): array {
        $date = new DateTime(
            'now',
            $timezone ?? new DateTimeZone('America/Sao_Paulo')
        );

        return [
            'timestamp' => $date->getTimestamp(),

            'atom' => $date->format(DateTimeInterface::ATOM),
            'iso8601' => $date->format(DateTimeInterface::ISO8601),
            'rfc3339' => $date->format(DateTimeInterface::RFC3339),
            'rfc3339_extended' => $date->format(DateTimeInterface::RFC3339_EXTENDED),
            'rfc2822' => $date->format(DateTimeInterface::RFC2822),
            'rss' => $date->format(DateTimeInterface::RSS),
            'w3c' => $date->format(DateTimeInterface::W3C),

            'custom' => [
                'date_only' => $date->format('Y-m-d'),
                'time_only' => $date->format('H:i:s'),
                'datetime' => $date->format('Y-m-d H:i:s'),

                'br_date' => $date->format('d/m/Y'),
                'br_datetime' => $date->format('d/m/Y H:i:s'),

                'day_lead_zero' => $date->format('d'),
                'day' => $date->format('j'),

                'month_lead_zero' => $date->format('m'),
                'month' => $date->format('n'),

                'year_simple' => $date->format('y'),
                'year_full' => $date->format('Y'),
            ],
        ];
    }

    public static function timestamp(): int
    {
        return self::dateTime()['timestamp'];
    }

    public static function today(): string
    {
        return self::dateTime()['custom']['date_only'];
    }

    public static function now(): string
    {
        return self::dateTime()['custom']['time_only'];
    }

    public static function brDateTime(): string
    {
        return self::dateTime()['custom']['br_datetime'];
    }

    public static function hoje(): string
    {
        return self::dateTime()['custom']['br_date'];
    }

    public static function diaAtual(bool $lead_zero = false): string
    {
        $dateTime = self::dateTime();
        return $lead_zero
            ? $dateTime['custom']['day_lead_zero']
            : $dateTime['custom']['day'];
    }

    public static function mesAtual(bool $lead_zero = false): string
    {
        $dateTime = self::dateTime();
        return $lead_zero
            ? $dateTime['custom']['month_lead_zero']
            : $dateTime['custom']['month'];
    }

    public static function anoAtual(bool $full_year = false): string
    {
        $dateTime = self::dateTime();
        return $full_year
            ? $dateTime['custom']['year_full']
            : $dateTime['custom']['year_simple'];
    }

    public static function stringMes(string $mesRef): string
    {
        $meses = [
            '01' => 'Janeiro',
            '02' => 'Fevereiro',
            '03' => 'Março',
            '04' => 'Abril',
            '05' => 'Maio',
            '06' => 'Junho',
            '07' => 'Julho',
            '08' => 'Agosto',
            '09' => 'Setembro',
            '10' => 'Outubro',
            '11' => 'Novembro',
            '12' => 'Dezembro',
        ];

        return $meses[str_pad($mesRef, 2, '0', STR_PAD_LEFT)] ?? '';
    }
}
