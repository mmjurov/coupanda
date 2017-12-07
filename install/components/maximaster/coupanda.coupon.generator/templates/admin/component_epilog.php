<?php

\CJSCore::RegisterExt('maximaster_coupanda_generator', [
    'js' => $templateFolder . '/generator.js',
    'lang' => $templateFolder . '/lang/' . LANGUAGE_ID . '/generator.php',
    'rel' => ['jquery', 'popup'],
]);

\CJSCore::Init(['maximaster_coupanda_generator']);