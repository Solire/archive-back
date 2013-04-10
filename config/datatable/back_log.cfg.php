
<?php

/*
---  ---  ---  ---  ---  ---  ---  ---  ---  ---  --- ---  ---  ---  ---  ---  ---  ---  ---  ---  ---  --- ---  ---  ---  ---

oooooooooo.         .o.       ooooooooooooo       .o.       ooooooooooooo       .o.       oooooooooo.  ooooo        oooooooooooo
`888'   `Y8b       .888.      8'   888   `8      .888.      8'   888   `8      .888.      `888'   `Y8b `888'        `888'     `8
 888      888     .8"888.          888          .8"888.          888          .8"888.      888     888  888          888
 888      888    .8' `888.         888         .8' `888.         888         .8' `888.     888oooo888'  888          888oooo8
 888      888   .88ooo8888.        888        .88ooo8888.        888        .88ooo8888.    888    `88b  888          888    "
 888     d88'  .8'     `888.       888       .8'     `888.       888       .8'     `888.   888    .88P  888       o  888       o
o888bood8P'   o88o     o8888o     o888o     o88o     o8888o     o888o     o88o     o8888o o888bood8P'  o888ooooood8 o888ooooood8


---  ---  ---  ---  ---  ---  ---  ---  ---  ---  --- ---  ---  ---  ---  ---  ---  ---  ---  ---  ---  --- ---  ---  ---  ---
 */




/**
    _______   __ ________  _________ _      _____
    |  ___\ \ / /|  ___|  \/  || ___ \ |    |  ___|
    | |__  \ V / | |__ | .  . || |_/ / |    | |__
    |  __| /   \ |  __|| |\/| ||  __/| |    |  __|
    | |___/ /^\ \| |___| |  | || |   | |____| |___
    \____/\/   \/\____/\_|  |_/\_|   \_____/\____/

 */

$config = array(
    "extra" => array(
        "copy"              => false, //bool Activer la fonctionnalité de copie des données
        "print"             => false, //bool Activer la fonctionnalité de copie des données'impression
        "pdf"               => false, //bool Activer la fonctionnalité d'export pdf
        "csv"               => false, //bool Activer la fonctionnalité d'export csv
        "hide_columns"      => false, //bool Permettre de caché des colonnes
        "highlightedSearch" => true,  //bool mise en surbrillance des termes de recherche
    ),
    "table" => array(
        "name"          => "table_name",            //string Nom de la table à lister
        "title"         => "Liste des contenus",    //string Titre de la page
        "title_item"    => "contenu",               //string Nom des items listés
        "suffix_genre"  => "",                      //string Suffixe genre (exemple: e)
        "fixedheader"   => false,                   //bool header de tableau fixed
    ),
    "columns" => array(  //Définition des colonnes
        //Colonne simple
        array(
            "name"          => "id",    //string Nom de la colonne
            "index"         => true,    //bool Champs indexé (Clé primaire)
            "show"          => true,    //bool Afficher dans le tableau
            "filter_field"  => "text",  //string Type champs de filtre (text/select/date-range)
            "title"         => "Titre", //string Titre affiché dans le header du tableau pour cette colonne
        ),
        //Colonne 1..1 sur autre table
        array(
            "name"  => "id_client",         //string Nom de la colonne
            "from"  => array(
                "table"   => "gab_gabarit", //string Nom de la table jointe
                "columns" => array(
                    array(
                        "name" => "label",  //string Nom de la colonne dans la table jointe
                    ),
                ),
                "index" => array(
                    "id" => "THIS",         //string Nom de la colonne sur laquelle on joins
                )
            ),
            "show"         => true,
            "filter_field" => "select",     //string Type champs de filtre (text/select/date-range)
            "title"        => "Type de contenu",
        ),
        //Colonne simple formaté
        array(
            "name" => "date_crea",
            "php_function" => array(
                "\Slrfw\Library\Tools::RelativeTimeFromDate" //string Fonction statique php à appeler pour chaque valeur
            ),
            "show" => true,
            "filter_field" => "date-range",   //string Type champs de filtre (text/select/date-range)
            "filter_field_date_past" => true, //bool date seulement passé pour le filtre sur la date
            "title" => "Créé",
        ),
        //Colonne simple (non affichée) avec filtre général
        array(
            "name" => "id_version",
            "index" => true,
            "filter" => BACK_ID_VERSION,    //mixed Permet de filtrer tous les résultats
        ),
        //Colonne avancée générée par une fonction + SQL avancé (Permet le filtre dans ce cas de figure)
        array(
            "special" => "buildAction",
            "sql" => "IF(`gab_page`.`visible` = 0, '&#10005; Non visible', '&#10003; Visible')",
            "filter_field" => "select",
            "show" => true,
            "title" => "Actions",
            "name" => "visible",
        ),
    ),
);



/**
 *
    ___  ___  ___    _____ _____ _   _ ______ _____ _____ _   _______  ___ _____ _____ _____ _   _
    |  \/  | / _ \  /  __ \  _  | \ | ||  ___|_   _|  __ \ | | | ___ \/ _ \_   _|_   _|  _  | \ | |
    | .  . |/ /_\ \ | /  \/ | | |  \| || |_    | | | |  \/ | | | |_/ / /_\ \| |   | | | | | |  \| |
    | |\/| ||  _  | | |   | | | | . ` ||  _|   | | | | __| | | |    /|  _  || |   | | | | | | . ` |
    | |  | || | | | | \__/\ \_/ / |\  || |    _| |_| |_\ \ |_| | |\ \| | | || |  _| |_\ \_/ / |\  |
    \_|  |_/\_| |_/  \____/\___/\_| \_/\_|    \___/ \____/\___/\_| \_\_| |_/\_/  \___/ \___/\_| \_/

        |\ | _  _   .
        | \|(_)|||  . back_log

 */

$config = array(
    'table' =>
    array(
        'title' => 'Liste des logs',
        'title_item' => 'log',
        'suffix_genre' => '',
        'fixedheader' => false,
        'name' => 'back_log',
    ),
    'extra' =>
    array(
        'copy' => false,
        'print' => false,
        'pdf' => false,
        'csv' => false,
        'hide_columns' => false,
        'highlightedSearch' => false,
    ),
    'columns' =>
    array(
        array(
            'name' => 'logId',
            'show' => true,
            'filter_field' => 'text',
            'title' => 'Id',
            "default_sorting" => true,
            "default_sorting_direction" => "desc",
        ),
        array(
            'name' => 'logDate',
            'show' => true,
            'filter_field' => 'text',
            'title' => 'Date',
        ),
        array(
            'name' => 'logTime',
            'show' => true,
            'filter_field' => 'text',
            'title' => 'Heure',
        ),
        array(
            'name' => 'logIp',
            'show' => true,
            'filter_field' => 'select',
            'title' => 'IP',
        ),
        array(
            'name' => 'logString',
            'show' => true,
            'filter_field' => 'select',
            'title' => 'Type',
        ),
        array(
            'name' => 'logStrOpt',
            'show' => true,
            'filter_field' => 'text',
            'title' => 'Détails',
        ),
        array(
            'name' => 'logIdUser',
            'show' => true,
            "from" => array(
                "table" => "utilisateur", //string Nom de la table jointe
                "type"  =>  "LEFT",
                "columns" => array(
                    array(
                        "name" => "nom", //string Nom de la colonne dans la table jointe
                    ),
                    array(
                        "value" => " ", //string Nom de la colonne dans la table jointe
                    ),
                    array(
                        "name" => "prenom", //string Nom de la colonne dans la table jointe
                    ),
                ),
                "index" => array(
                    "id" => "THIS", //string Nom de la colonne sur laquelle on joins
                )
            ),
            'filter_field' => 'select',
            'title' => 'Utilisateur',
        ),
    ),
);