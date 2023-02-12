<?php

class UrlHelper
{
    public static function getParamsFromUrl($url)
    {
        $url = parse_url($_SERVER['REQUEST_URI']);
        parse_str($url['query'], $params);

        return $params;
    }
}