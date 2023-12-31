<?php

$path_to_input_directory = __DIR__ . DIRECTORY_SEPARATOR . "INPUT-FILES";
$path_to_output_directory = __DIR__ . DIRECTORY_SEPARATOR . "OUTPUT-FILES";
$array_of_items_to_ignore = array( "IGNORE-THIS" );
$global_friendly_urls_boolean = false;
$softwayr_data_delimiter = "---";

include __DIR__ . DIRECTORY_SEPARATOR . "SoftwayrStaticSite.php";

new Softwayr\StaticSite\SoftwayrStaticSite( $path_to_input_directory, $path_to_output_directory, $array_of_items_to_ignore, $global_friendly_urls_boolean, $softwayr_data_delimiter );

