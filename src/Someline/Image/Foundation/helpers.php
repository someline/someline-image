<?php


if (!function_exists('json_encode_safe')) {

    function json_encode_safe($value, $options = 0, $depth = 512)
    {
        $encoded = json_encode($value, $options, $depth);
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $encoded;
            case JSON_ERROR_DEPTH:
                throw new Exception('Maximum stack depth exceeded');
            case JSON_ERROR_STATE_MISMATCH:
                throw new Exception('Underflow or the modes mismatch');
            case JSON_ERROR_CTRL_CHAR:
                throw new Exception('Unexpected control character found');
            case JSON_ERROR_SYNTAX:
                throw new Exception('Syntax error, malformed JSON');
            case JSON_ERROR_UTF8:
                $clean = utf8ize($value);
                return json_encode_safe($clean, $options, $depth);
            default:
                throw new Exception('Unknown error');
        }
    }
}

if (!function_exists('utf8ize')) {

    function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = utf8ize($value);
            }
        } else if (is_string($mixed)) {
            return utf8_encode($mixed);
        }
        return $mixed;
    }

}

if (!function_exists('read_exif_data_safe')) {

    function read_exif_data_safe($file, $sections_needed = null, $sub_arrays = null, $read_thumbnail = null)
    {
        $data = null;
        try {
            $data = read_exif_data($file);
        } catch (\Exception $e) {
            $data = null;
        }
        return $data;
    }

}
