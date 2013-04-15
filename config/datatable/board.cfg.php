<?php

$config = array(
    "extra" => array(
        "copy" => false,
        "print" => false,
        "pdf" => false,
        "csv" => false,
        "hide_columns" => false,
        "highlightedSearch" => true,
    ),
    "table" => array(
        "name" => "gab_page",
        "title" => "",
        "title_item" => "contenu",
        "suffix_genre" => "",
        "fixedheader" => false,
        "postDataProcessing" => "disallowDeleted",
    ),
    "columns" => array(
        /* Champs requis pour les actions */
        array(
            "name" => "id_gabarit",
        ),
        array(
            "name" => "id_version",
        ),
        array(
            "name" => "id",
        ),
        array(
            "name" => "suppr",
        ),
        array(
            "name" => "visible",
        ),
        array(
            "name" => "rewriting",
        ),
        /*         * ***************************** */
        array(
            "name" => "titre",
            "index" => true,
            "show" => true,
            "filter_field" => "text",
            "title" => "Titre",
        ),
        array(
            "name" => "id_gabarit",
            "from" => array(
                "table" => "gab_gabarit",
                "columns" => array(
                    array(
                        "name" => "label",
                    ),
                ),
                "index" => array(
                    "id" => "THIS",
                )
            ),
            "show" => true,
            "filter_field" => "select",
            "title" => "Type de contenu",
        ), array(
            "name" => "date_crea",
            "php_function" => array(
                "\Slrfw\Tools::RelativeTimeFromDate"
            ),
            "show" => true,
            "filter_field" => "date-range",
            "filter_field_date_past" => true,
            "title" => "Créé",
        ),
        array(
            "name" => "date_modif",
            "php_function" => array(
                "\Slrfw\Tools::RelativeTimeFromDate"
            ),
            "show" => true,
            "filter_field" => "date-range",
            "filter_field_date_past" => true,
            "title" => "Édité",
            "default_sorting" => true,
            "default_sorting_direction" => "desc",
        ),
        array(
            "name" => "id_version",
            "index" => true,
            "filter" => BACK_ID_VERSION,
        ),
//        array(
//            "name" => "suppr",
//            "filter" => 0,
//        )
    ),
);
//Si autre langue que version de base
if (BACK_ID_VERSION != 1) {
    $config["columns"][] = array(
        "special" => "buildTraduit",
        "sql" => "IF(`gab_page`.`rewriting` = '', '&#10005; Non traduit', '&#10003; Traduit')",
        "show" => true,
        "title" => "Traduction",
        "filter_field" => "select",
        "name"  =>  "rewriting"
    );
}
$config["columns"][] = array(
    "special" => "buildAction",
    "sql" => "IF(`gab_page`.`suppr` = 1, '&#8709; Supprimé', IF(`gab_page`.`visible` = 0, '&#10005; Non visible', '&#10003; Visible'))",
    "filter_field" => "select",
    "show" => true,
    "title" => "Actions",
    "name"  =>  "visible",
);


