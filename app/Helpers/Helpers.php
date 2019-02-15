<?php

/**
 * Removes all html tags
 *
 * @param string $value
 * @return string
 */
function plain($value)
{
    return html_entity_decode(strip_tags($value));
}

/**
 * @param $value
 * @param int $limit
 * @return string
 */
function excerpt($value, $limit = 84)
{
    $value = str_replace(["\n", "\t", "\r"], ' ', plain($value));
    $value = trim(preg_replace('/ {2,}/', ' ', $value));

    return str_limit($value, $limit);
}

/**
 * Zwraca tablice najczesciej wykorzystywanych slow kluczowych w tekscie
 *
 * @param string $text
 * @param int $limit Limit slow kluczowych
 * @return array
 */
function keywords($text, $limit = 10)
{
    $text = preg_replace('/[^a-zA-Z0-9 -]/', '', mb_strtolower(plain($text), 'UTF-8'));
    $keywords = [];

    foreach (explode(' ', $text) as $word) {
        if (mb_strlen($word, 'UTF-8') >= 3) {
            $keywords[] = $word;
        }
    }

    $keywords = array_count_values($keywords);
    arsort($keywords);

    $keywords = array_keys($keywords);

    if ($limit) {
        $keywords = array_slice($keywords, 0, $limit);
    }

    return $keywords;
}

/**
 * @param \Coyote\Services\Stream\Activities\Activity|string $activity
 * @param \Coyote\Services\Stream\Objects\ObjectInterface|null $object
 * @param \Coyote\Services\Stream\Objects\ObjectInterface|null $target
 */
function stream($activity, $object = null, $target = null)
{
    $manager = app(\Coyote\Services\Stream\Manager::class);

    return $manager->save($activity, $object, $target);
}

/**
 * Creates CDN assets url
 *
 * @param string $path
 * @param null|bool $secure
 * @return string
 * @throws \Exception
 */
function cdn($path, $secure = null)
{
    $path = trim($path, '/');
    $pathinfo = pathinfo($path);

    if (in_array($pathinfo['extension'] ?? '', ['css', 'js'])) {
        $path = manifest(trim($pathinfo['basename'], '/'));
    }

    return asset($path, $secure);
}

/**
 * Get the path to a versioned Mix file.
 *
 * @param  string  $path
 * @return string
 *
 * @throws \Exception
 */
function manifest($path)
{
    static $manifest;

    if (!$manifest) {
        if (!file_exists($manifestPath = public_path('manifest.json'))) {
            throw new Exception('The webpack manifest does not exist.');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
    }

    if (!array_key_exists($path, $manifest)) {
        throw new Exception(
            "Unable to locate webpack mix file: {$path}. Please check your ".
            'webpack.mix.js output paths and try again.'
        );
    }

    return $manifest[$path];
}

/**
 * Uppercase first character of each word
 *
 * @param $string
 * @return string
 */
function capitalize($string)
{
    return mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
}

/**
 * @param string|\Carbon\Carbon $dateTime
 * @param bool $diffForHumans
 * @return string
 */
function format_date($dateTime, $diffForHumans = true)
{
    $format = auth()->check() ? auth()->user()->date_format : '%Y-%m-%d %H:%M';

    $dateTime = carbon($dateTime);
    $now = \Carbon\Carbon::now();

    if (!$diffForHumans) {
        return $dateTime->formatLocalized($format);
    } elseif ($dateTime->diffInHours($now) < 1) {
        return $dateTime->diffForHumans(null, true) . ' temu';
    } elseif ($dateTime->isToday()) {
        return 'dziś, ' . $dateTime->format('H:i');
    } elseif ($dateTime->isYesterday()) {
        return 'wczoraj, ' . $dateTime->format('H:i');
    } else {
        return $dateTime->formatLocalized($format);
    }
}

/**
 * @param $dateTime
 * @return \Carbon\Carbon
 */
function carbon($dateTime)
{
    if (is_null($dateTime)) {
        $dateTime = new \Carbon\Carbon();
    } elseif (!$dateTime instanceof \Carbon\Carbon) {
        $dateTime = new \Carbon\Carbon($dateTime);
    }

    return $dateTime;
}
