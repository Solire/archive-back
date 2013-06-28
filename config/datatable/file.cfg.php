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
        "name" => "media_fichier",
        "title" => "",
        "title_item" => "fichier",
        "suffix_genre" => "",
        "fixedheader" => false,
        "postDataProcessing" => "notUsed",
    ),
    "columns" => array(
        array(
            "name" => "id",
            "index" => true,
            "show" => false,
        ),
        array(
            "name" => "id_gab_page",
            "show" => false,
        ),
        array(
            "name" => "rewriting",
            "title" => "",
            "content"   =>  '
                <a  class="previsu" href="[#id_gab_page#]/[#THIS#]" title="[#THIS#]">
                    <img src="[#id_gab_page#]/mini/[#THIS#]" alt="[#THIS#]" />
                </a>',
            "show" => true,
            "sorting"   =>  false,
        ),
        array(
            "name" => "taille",
            "title" => "Taille",
            'format' => array(
                "number" => array(
                    "type"   => "formatSize",
                ),
            ),
            "show" => true,
        ),
        array(
            "name" => "width",
            "title" => "Largeur",
            "show" => true,
        ),
        array(
            "name" => "height",
            "title" => "Hauteur",
            "show" => true,
        ),
        array(
            "name" => "date_crea",
            'format' => array(
                "datetime" => array(
                    "type"   => "RelativeTime",
                    "modeDate"  =>  true,
                ),
            ),
            "show" => true,
//            "filter_field" => "date-range",
//            "filter_field_date_past" => true,
            "title" => "Date / Heure",
            "default_sorting" => true,
            "default_sorting_direction" => "desc",
        ),
        array(
            "name" => "rewriting",
            "from" => array(
                "type"  =>  "LEFT",
                "table" => "media_fichier_utilise",
                "groupby"   =>  "rewriting",
                "columns" => array(
                    array(
                        "name" => "id_version",
                        "sql" => "if(id_version IS NULL, 'Non' , 'Oui' )",
                    ),
                ),
                "index" => array(
                    "rewriting" => "THIS",
                )
            ),
//            "filter_field" =>   "select",
            "show" => true,
            "title" => "Utilisé",
        ),
        array(
            "name" => "rewriting",
            "title" => "Actions",
            "content"  => '
                <div class="btn-toolbar">
                            <div class="btn-group">
                                <a href="[#id_gab_page#]/[#THIS#]" class="btn btn-info previsu" title="Visualiser"><i class="icon-camera"></i></a>
                                <a href="#" class="btn btn-warning delete-file" title="Supprimer"><i class="icon-trash"></i></a>
                            </div>
                        </div>
            ',
            "show"  =>  true,
            "sorting"   =>  false,
        ),
        array(
            "name" => "suppr",
            "filter" => "0000-00-00",
        )
    ),
);
