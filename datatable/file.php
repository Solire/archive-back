<?php

namespace App\Back\Datatable;

/**
 * Description of BoardDatatable
 *
 * @author shin
 */
class File extends \Slrfw\Datatable\Datatable {

    /**
     * Permet de gérer les fichiers pas utilisés
     *
     * @param array $aRow Ligne courante de toutes les données (ASSOC)
     * @param array $rowAssoc Ligne courante des données affiché (ASSOC)
     * @param array $row Ligne courante de donnée affiché (NUM)
     * @return void
     */
    public function notUsed($aRow, $rowAssoc, &$row) {
        $row["DT_RowClass"] = "";
        if ($aRow["rewriting_1"] == "") {
            $row["DT_RowClass"] = "translucide";
            $row[5] = "Non";
        }
    }
}

