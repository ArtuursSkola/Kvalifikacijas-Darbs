<?php

function latvia_city_normalize(string $s): string
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    $tr = [
        'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k',
        'ļ' => 'l', 'ņ' => 'n', 'š' => 's', 'ū' => 'u', 'ž' => 'z',
    ];
    return strtr($s, $tr);
}

function latvia_city_coordinates(string $pilseta): array
{
    $n = latvia_city_normalize($pilseta);
    $cities = [
        'riga' => [56.9496, 24.1052],
        'daugavpils' => [55.8721, 26.5362],
        'liepaja' => [56.5047, 21.0108],
        'jelgava' => [56.6524, 23.7278],
        'jurmala' => [56.9680, 23.7704],
        'ventspils' => [57.3899, 21.5727],
        'rezekne' => [56.5033, 27.3423],
        'valmiera' => [57.5411, 25.4275],
        'jekabpils' => [56.4994, 25.8573],
        'ogre' => [56.8172, 24.6140],
        'tukums' => [56.9676, 23.1553],
        'cesis' => [57.3119, 25.2748],
        'salaspils' => [56.8601, 24.3494],
        'kuldiga' => [56.9683, 21.9745],
        'sigulda' => [57.1517, 24.8643],
        'bauska' => [56.4079, 24.1936],
        'saldus' => [56.6636, 22.4881],
        'dobele' => [56.6244, 23.2815],
        'talsi' => [57.2467, 22.5813],
        'ludza' => [56.5465, 27.7189],
        'olaine' => [56.7852, 23.9367],
        'marupe' => [56.9039, 24.0617],
        'adazi' => [57.0764, 24.3203],
        'smiltene' => [57.4244, 25.9016],
        'valka' => [57.7752, 26.0118],
        'gulbene' => [57.1726, 26.7529],
        'madona' => [56.8532, 26.2170],
        'aluksne' => [57.4254, 27.0460],
        'balvi' => [57.1323, 27.2653],
        'kraslava' => [55.8951, 27.1683],
        'preili' => [56.2944, 26.7246],
        'aizkraukle' => [56.6047, 25.2552],
        'limbazi' => [57.5123, 24.7191],
        'rigas' => [56.9496, 24.1052],
    ];
    if ($n !== '' && isset($cities[$n])) {
        return $cities[$n];
    }
    $first = preg_split('/[\s,]+/u', $n, 2)[0] ?? '';
    if ($first !== '' && isset($cities[$first])) {
        return $cities[$first];
    }
    return [56.8796, 24.6032];
}

function map_home_jitter(float $lat, float $lng, int $id): array
{
    $h = (($id * 2654435761) & 0x7fffffff) % 1000000;
    $dx = (($h % 200) - 100) * 0.00012;
    $dy = (((int)($h / 200) % 200) - 100) * 0.00012;
    return [$lat + $dx, $lng + $dy];
}
