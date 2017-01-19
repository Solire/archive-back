<?php

$config = array(
    "extra" => array(
        "copy" => false,
        "print" => false,
        "pdf" => false,
        "csv" => false,
        "hide_columns" => false,
    ),
    "table" => array(
        "detail"    =>  false,
        "name" => "traduction",
        "title" => "Edition des textes statiques",
        "title_item" => "texte",
        "suffix_genre" => "",
        "fixedheader" => true,
    ),
    "columns" => array(
        array(
            "name" => "cle",
            "index" => true,
            "show" => true,
            "filter_field" => "text",
            "title" => "Texte initial",
        ),
        array(
            "name" => "valeur",
            "editable" => true,
            "show" => true,
            "filter_field" => "text",
            "title" => "Correspondance",
        ),
        array(
            "name" => "aide",
//            "editable" => true,
            "show" => true,
            "filter_field" => "text",
            "title" => "Aide",
        ),
        array(
            "name" => "id_version",
            "index" => true,
            "filter" => BACK_ID_VERSION,
        ),
        array(
            "name" => "id_api",
            "index" => true,
            "filter" => BACK_ID_API,
        ),
    ),
);