<?php

/**
 * Description of gabaritmanager
 *
 * @author thomas
 */
class gabaritManager extends manager
{

    /**
     * <p>Donne l'identifiant d'une page d'après son rewriting et l'identifiant.</p>
     * @param string $rewriting
     * @param int $id_parent
     * @param int $id_version
     * @return int 
     */
    public function getIdByRewriting($id_version, $rewriting, $id_parent = 0)
    {
        $query = "SELECT `id` FROM `gab_page`"
                . " WHERE `suppr` = 0 AND `visible` = 1 AND `id_parent` = $id_parent"
                . " AND `id_version` = $id_version AND `rewriting` = " . $this->_db->quote($rewriting);

        return $this->_db->query($query)->fetchColumn();
    }

    public function getVersion($id_version)
    {
        $query = "SELECT * FROM `version` WHERE `id` = " . $id_version;
        $data = $this->_db->query($query)->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    /**
     * <p>Retourne un objet page à partir de l'identifiant de la page <br />
     * ou un objet page vide à partir de l'idenfiant du gabarit</p>
     * @param int $id_gab_page
     * @param int $id_gabarit
     * @param int $version 
     */
    public function getPage($id_version, $id_gab_page, $id_gabarit = 0, $join = false, $visible = FALSE)
    {
        $page = new gabaritPage();

        if ($id_gab_page) {
            $query = "SELECT * FROM `gab_page` WHERE `id_version` = $id_version AND `id` = $id_gab_page AND `suppr` = 0";
            $meta = $this->_db->query($query)->fetch(PDO::FETCH_ASSOC);

            if (!$meta)
                return FALSE;

            $page->setMeta($meta);
            $id_gabarit = $meta['id_gabarit'];

            $data = $this->getVersion($id_version);
            if (!$data)
                return FALSE;
            $page->setVersion($data);
        }

        $gabarit = $this->getGabarit($id_gabarit);

        $query = "SELECT * FROM `gab_gabarit` WHERE `id` = " . $gabarit->getIdParent();
        $parentData = $this->_db->query($query)->fetch(PDO::FETCH_ASSOC);
        $gabarit->setGabaritParent($parentData);

        if (!$id_gab_page && $gabarit->getIdParent() > 0) {
            $query = "SELECT `p`.`id`, `p`.`titre`, `p`.`rewriting`,"
                    . " `q`.`id` `p_id`, `q`.`titre` `p_titre`, `q`.`rewriting` `p_rewriting`,"
                    . " `r`.`id` `pp_id`, `r`.`titre` `pp_titre`, `r`.`rewriting` `pp_rewriting`"
                    . " FROM `gab_page` `p`"
                    . " LEFT JOIN `gab_page` `q` ON `q`.`id` = `p`.`id_parent` AND `q`.`suppr` = 0 AND `q`.`id_version` = $id_version"
                    . " LEFT JOIN `gab_page` `r` ON `r`.`id` = `q`.`id_parent` AND `r`.`suppr` = 0 AND `r`.`id_version` = $id_version"
                    . " WHERE `p`.`id_gabarit` = " . $gabarit->getIdParent() . " AND `p`.`suppr` = 0 AND `p`.`id_version` = $id_version";

            $parents = $this->_db->query($query)->fetchAll(PDO::FETCH_ASSOC);
            $gabarit->setParents($parents);
        }

        $page->setGabarit($gabarit);
        
        $blocs = $this->getBlocs($gabarit, $id_gab_page);
        $page->setBlocs($blocs);

        if ($id_gab_page) {
            $parents = $this->getParents($meta['id_parent'], $id_version);
            $page->setParents($parents);

            $values = $this->getValues($page);

            if ($values) {
                $page->setValues($values);

                $blocs = $page->getBlocs();
                foreach ($blocs as $blocName => $bloc) {
                    $valuesBloc = $this->getBlocValues($bloc, $id_gab_page, $id_version, $visible);
                    if ($valuesBloc) {
                        $bloc->setValues($valuesBloc);
                        if ($join)
                            $this->getBlocJoinsValues($page, $blocName, $id_gab_page, $id_version, $visible);
                    }
                }
            }
        }
        
        return $page;
    }

    /**
     * <p>Retourne un objet gabarit à partir de l'identifiant du gabarit</p>
     * @param int $id_gabarit
     * @return gabarit 
     */
    public function getGabarit($id_gabarit)
    {
        $query = "SELECT * FROM `gab_gabarit` WHERE `id` = $id_gabarit";
        $row = $this->_db->query($query)->fetch(PDO::FETCH_ASSOC);

        $gabarit = new gabarit($row['id'], $row['id_parent'], $row['name'], $row['label'],  $row['main'],  $row['creable'],  $row['deletable'],  $row['sortable'],  $row['make_hidden'],  $row['editable'],  $row['meta']);
        if ($row['id_api'] > 0) {
            $query = "SELECT `name` FROM `gab_api` WHERE `id` = " . $row['id_api'];
            $api = $this->_db->query($query)->fetchColumn();
            $table = $api . "_" . $row['name'];
        } else {
            $table = $row['name'];
        }
        $gabarit->setTable($table);

        $query = "SELECT IF (`g`.`label` IS NULL, 'general', `g`.`label`), `c`.*"
                . " FROM `gab_champ` `c`"
                . " LEFT JOIN `gab_champ_group` `g` ON `g`.`id` = `c`.`id_group`"
                . " WHERE `id_parent` = $id_gabarit AND `type_parent` = 'gabarit'"
                . " ORDER BY `g`.`ordre`, `c`.`ordre`";
        $champs = $this->_db->query($query)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
        //Parametre
        //TODO a optimiser (1 requete pour champ dyn et champ normaux, filtrer par id champ + type, voir faire des jointure sur gab_champ)
        $gabChampTypeParams = $this->_db->query('
            SELECT gc.id, gcpv.* 
            FROM gab_champ gc
            INNER JOIN gab_champ_param_value gcpv 
                ON gcpv.id_champ = gc.id
            ORDER BY id_group, ordre')->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        $gabChampTypeParamsDefault = $this->_db->query('
            SELECT gct.code, gcp.*, gcp.default_value value
            FROM gab_champ_type gct
            INNER JOIN gab_champ_param gcp 
                ON gct.code = gcp.code_champ_type
            ORDER BY  gct.ordre, gct.code')->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
        foreach ($gabChampTypeParamsDefault as $type => $params) {
            $paramsDefault[$type] = array();
            foreach ($params as $param) {
                $paramsDefault[$type][$param["code"]] = $param["value"];
            }
        }

        //Fin parametre

        foreach ($gabChampTypeParams as $idField => $params) {
            $params2 = array();

            foreach ($params as $param) {
                $params2[$param["code_champ_param"]] = $param["value"];
            }
            foreach ($champs as &$group) {

                foreach ($group as &$champ) {
                    if (!isset($champ["params"]))
                        if (isset($paramsDefault[$champ["type"]]))
                            $champ["params"] = $paramsDefault[$champ["type"]];
                        else
                            $champ["params"] = array();
                    if ($champ["id"] == $idField) {
                        $champ["params"] = array_merge($champ["params"], $params2);
//                        break;
                    }
                }
            }
        }
        $gabarit->setChamps($champs);

        return $gabarit;
    }

    /**
     *
     * @param gabarit $gabarit 
     * @return array
     */
    public function getBlocs($gabarit, $id_gab_page = 0)
    {
        $query = "SELECT * FROM `gab_bloc` WHERE `id_gabarit` = " . $gabarit->getId();
        $rows = $this->_db->query($query)->fetchAll(PDO::FETCH_ASSOC);

        $query = "SELECT * FROM `gab_champ` WHERE `id_parent` = :id_bloc AND `type_parent` = 'bloc' ORDER BY `gab_champ`.`ordre`";
        $stmt = $this->_db->prepare($query);

        //Parametre
        //TODO a optimiser (1 requete pour champ dyn et champ normaux, filtrer par id champ + type, voir faire des jointure sur gab_champ)
        $gabChampTypeParams = $this->_db->query('
            SELECT gc.id, gcpv.* 
            FROM gab_champ gc
            INNER JOIN gab_champ_param_value gcpv 
                ON gcpv.id_champ = gc.id
            ORDER BY id_group, ordre')->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        $gabChampTypeParamsDefault = $this->_db->query('
            SELECT gct.code, gcp.*, gcp.default_value value
            FROM gab_champ_type gct
            INNER JOIN gab_champ_param gcp 
                ON gct.code = gcp.code_champ_type
            ORDER BY  gct.ordre, gct.code')->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        foreach ($gabChampTypeParamsDefault as $type => $params) {
            $paramsDefault[$type] = array();
            foreach ($params as $param) {
                $paramsDefault[$type][$param["code"]] = $param["value"];
            }
        }
        //Fin parametre



        $blocs = array();
        foreach ($rows as $row) {
            $gabarit_bloc = new gabarit($row['id'], 0, $row['name'], $row['label']);

            $table = $gabarit->getTable() . "_" . $row['name'];
            $gabarit_bloc->setTable($table);

            $stmt->bindValue(":id_bloc", $row['id'], PDO::PARAM_INT);
            $stmt->execute();
            $joins = array();
            $champs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            //Parametre
            foreach ($gabChampTypeParams as $idField => $params) {
                $params2 = array();

                foreach ($params as $param) {
                    $params2[$param["code_champ_param"]] = $param["value"];
                }
                foreach ($champs as &$champ) {
                    if (!isset($champ["params"]))
                        if (isset($paramsDefault[$champ["type"]]))
                            $champ["params"] = $paramsDefault[$champ["type"]];
                        else
                            $champ["params"] = array();
                    if ($champ["id"] == $idField) {
                        $champ["params"] = array_merge($champ["params"], $params2);
//                        break;
                    }
                    if ($champ["type"] == "JOIN") {
                        $joins[$champ["id"]] = $champ;
                        unset($champ);
                    }
                }
            }

            //Fin parametre

            $stmt->closeCursor();

            $gabarit_bloc->setChamps($champs);
            $gabarit_bloc->setJoins($joins);

            $bloc = new gabaritBloc();

            $bloc->setGabarit($gabarit_bloc);

            $blocs[$gabarit_bloc->getName()] = $bloc;
        }

        return $blocs;
    }

    /**
     * Retourne la ligne des infos de la table générée à partir d'une page.
     * @param gabaritPage $page 
     * @return array
     */
    public function getValues($page)
    {
        $query = "SELECT * FROM `" . $page->getGabarit()->getTable() . "`"
                . " WHERE `id_gab_page` = " . $page->getMeta("id")
                . " AND `id_version` = " . $page->getMeta("id_version");

        return $this->_db->query($query)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * <p>Retourne les lignes des infos de la table générée à partir d'un bloc<br />
     * et de la page parente.</p>
     * @param gabarit $bloc
     * @param int $id_gab_page identifiant de la page parente.
     * @param int $id_version identifiant de la version.
     * @param bool $visible <p>si faux on récupère les blocs visibles ou non,<br />
     * si vrai on récupère uniquement les blocs visibles.</p>
     * @return type 
     */
    public function getBlocValues($bloc, $id_gab_page, $id_version, $visible = FALSE)
    {
        $query = "SELECT * FROM `" . $bloc->getGabarit()->getTable() . "`"
                . " WHERE `id_gab_page` = " . $id_gab_page . " AND `suppr` = 0"
                . " AND `id_version` = $id_version"
                . ($visible ? " AND `visible` = 1" : "")
                . " ORDER BY `ordre`";
        return $this->_db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     *
     * @param gabaritPage $page
     * @param type $id_gab_page
     * @param type $id_version
     * @param type $visible
     * @return type 
     */
    public function getBlocJoinsValues($page, $name_bloc, $id_gab_page, $id_version, $visible = FALSE)
    {
        $joinFields = array();
        foreach ($page->getBlocs($name_bloc)->getGabarit()->getJoins() as $joinField) {
            $joinFields[$joinField["name"]] = array(
                "values" => array(),
                "table" => $joinField["params"]["TABLE.NAME"],
                "fieldId" => $joinField["params"]["TABLE.FIELD.ID"],
            );

            foreach ($page->getBlocs($name_bloc)->getValues() as $value) {
                if ($value[$joinField["name"]] != 0 && $value[$joinField["name"]] != "")
                    $joinFields[$joinField["name"]]["values"][] = $value[$joinField["name"]];
            }
        }

        if (count($joinFields) == 0)
            return null;
        $parents = array();
        foreach ($joinFields as $joinName => $joinField) {
            if (count($joinField['values']) == 0)
                continue;
            $query = "SELECT `gab_page`.`id`, `gab_page`.* FROM `gab_page` WHERE `id_version` = $id_version " . ($visible ? " AND `visible` = 1" : "") . " AND  `id`  IN (" . implode(",", $joinField['values']) . ") AND `suppr` = 0";
            $meta = $this->_db->query($query)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
            if (!$meta)
                continue;

            $query = "SELECT `" . $joinField["table"] . "`.`id_gab_page`, `" . $joinField["table"] . "`.*"
                    . " FROM `" . $joinField["table"] . "`"
                    . " WHERE `id_gab_page` IN (" . implode(",", array_keys($meta)) . ")"
                    . " AND `" . $joinField["table"] . "`.`id_version` = $id_version";

            $values = $this->_db->query($query)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

            //On recupere les pages jointes
            
            //On recupere les pages jointes
            foreach ($page->getBlocs($name_bloc)->getValues() as $keyValue => $value) {
                if (!isset($meta[$value[$joinName]]))
                    continue;
                if ($meta[$value[$joinName]]["id_parent"] != 0)
                    $parents[] = $meta[$value[$joinName]]["id_parent"];
                $pageJoin = new gabaritPage();
                $pageJoin->setMeta($meta[$value[$joinName]]);
                $pageJoin->setValues($values[$value[$joinName]]);
                $page->getBlocs($name_bloc)->setValue($keyValue, $pageJoin, $joinName);
            }

            //Si on a des parents pour une des valeurs d'un bloc
            $parentsPage = array();
            if (count($parents) > 0) {
                $parentsUnique = array_unique($parents);
                unset($parents);
                $parents = array();
                $query = "SELECT * FROM `gab_page`"
                        . " WHERE `id_version` = $id_version AND `id` IN (" . implode(", ", $parentsUnique) . ") AND `suppr` = 0";
                $parentsMeta = $this->_db->query($query)->fetchAll(PDO::FETCH_ASSOC);
                foreach ($parentsMeta as $parentMeta) {
//                    if (!isset($meta[$value[$joinName]]))
//                        continue;
                    if ($meta[$value[$joinName]]["id_parent"] != 0)
                        $parents[] = $parentMeta["id_parent"];
                    $parentsPage[$parentMeta["id"]] = new gabaritPage();
                    $parentsPage[$parentMeta["id"]]->setMeta($parentMeta);
                }

                //Si on a des grands parents
                $parentsUnique2 = array_unique(array_merge($parentsUnique, $parents));
                //Si on a des grandparents qu'on avait pas recuperer
                if (count($parentsUnique2) > count($parentsUnique)) {
                    $query = "SELECT * FROM `gab_page`"
                            . " WHERE `id_version` = $id_version AND `id` IN (" . implode(", ", $parentsUnique2) . ") AND `suppr` = 0";
                    $parentsMeta2 = array_merge($parentsMeta, $this->_db->query($query)->fetchAll(PDO::FETCH_ASSOC));
                    foreach ($parentsMeta2 as $parentMeta2) {
                        $parentsPage[$parentMeta2["id"]] = new gabaritPage();
                        $parentsPage[$parentMeta2["id"]]->setMeta($parentMeta2);
                    }
                }


                //On remplit les parents et grands parents des pages joins
                foreach ($page->getBlocs($name_bloc)->getValues() as $keyValue => $value) {
                    $pageJoin = $page->getBlocs($name_bloc)->getValue($keyValue, $joinName);
                    $parents = array();
                    //Si on a un parent
                    if(!is_object($pageJoin) || !isset($parentsPage[$pageJoin->getMeta("id_parent")]))
                            continue;
                    if ($pageJoin->getMeta("id_parent") > 0) {
                        
                        $parents[] = $parentsPage[$pageJoin->getMeta("id_parent")];
                        //Si on a un grand parent
                        if ($parentsPage[$pageJoin->getMeta("id_parent")]->getMeta("id_parent") > 0) {
                            $parents[] = $parentsPage[$parentsPage[$pageJoin->getMeta("id_parent")]->getMeta("id_parent")];
                        }
                    }

                    $pageJoin->setParents($parents);
                    //Recuperation des blocs
                    $blocs = $this->getBlocs($this->getGabarit($pageJoin->getMeta("id_gabarit")), $pageJoin->getMeta("id"));
                    foreach ($blocs as $blocName => $bloc) {
                        $valuesBloc = $this->getBlocValues($bloc, $pageJoin->getMeta("id"), $id_version, true);
                        if ($valuesBloc) {
                            $bloc->setValues($valuesBloc);
                        }
                    }
                    $pageJoin->setBlocs($blocs);
                }
            }
        }
    }

    /**
     * <p>Retourne les parents, grand-parents, aïeuls etc.<br />
     * dans un tableau associatif `nom du gabarit` => `objet page correspondant`</p>
     * @param int $id_gab_page_parent
     * @param int $id_version
     * @return array 
     */
    public function getParents($id_gab_page_parent, $id_version)
    {
        $parents = array();
        $version = $this->getVersion($id_version);

        while ($id_gab_page_parent > 0) {
            $query = "SELECT * FROM `gab_page`"
                    . " WHERE `id_version` = $id_version AND `id` = $id_gab_page_parent AND `suppr` = 0";
            $parentMeta = $this->_db->query($query)->fetch(PDO::FETCH_ASSOC);

            $parentPage = new gabaritPage();
            $parentPage->setMeta($parentMeta);

            $parentPage->setVersion($version);

            $gabarit = $this->getGabarit($parentMeta['id_gabarit']);
            $parentPage->setGabarit($gabarit);

            $parents[] = $parentPage;

            $id_gab_page_parent = $parentMeta['id_parent'];
        }

        return $parents;
    }

    /**
     * <p>Retourne un tableau de page a partir de l'identifiant d'un parent.</p>
     * <p>On peut préciser l'identifiant du gabarit.</p>
     * @param int $id_parent
     * @param bool $main si vrai retourne uniqueemnt les gabarits principals (Ceux que l'on a besoin sur toutes les pages)
     * @param int $id_gabarit soit un int soit un array d'int
     * @param bool $visible
     * @param int $id_version
     * @return array 
     */
    public function getList($id_version, $id_parent = FALSE, $id_gabarit = 0, $visible = FALSE, $orderby = "ordre", $sens = "ASC", $debut = 0, $nbre = 0, $main = FALSE)
    {
        $query = "SELECT `p`.*, COUNT(`e`.`id`) `nbre_enfants`"
                . " FROM `gab_page` `p` LEFT JOIN `gab_page` `e` ON `e`.`id_parent` = `p`.`id` AND `e`.`suppr` = 0 AND `e`.`id_version` = $id_version"
                . ($visible ? " AND `e`.`visible` = 1" : "")
                . ($main ? " INNER JOIN `gab_gabarit` `g` ON `p`.`id_gabarit` = `g`.`id` AND `g`.`main` = 1" : "")
                . " WHERE `p`.`suppr` = 0 AND `p`.`id_version` = $id_version"
                . ($visible ? " AND `p`.`visible` = 1" : "");

        if ($id_parent !== FALSE)
            $query .= " AND `p`.`id_parent` = $id_parent"; //`p`.`id_parent` = $id_parent AND 
        if ($id_gabarit) {
            if (is_array($id_gabarit)) {
                if (count($id_gabarit) > 0)
                    $query .= " AND `p`.`id_gabarit` IN (" . implode(", ", $id_gabarit) . ")";
            } else {
                $query .= " AND `p`.`id_gabarit` = $id_gabarit";
            }
        }

        $query .= " GROUP BY `p`.`id`";

        $query .= " ORDER BY `p`.`$orderby` $sens";

        if ($nbre)
            $query .= " LIMIT $debut, $nbre";

        $metas = $this->_db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        $version = $this->getVersion($id_version);

        $pages = array();
        foreach ($metas as $meta) {
            $page = new gabaritPage();
            $page->setMeta($meta);
            $page->setVersion($version);
            $pages[] = $page;
        }

        return $pages;
    }

    /**
     * <p>Retourne les gabarits et leurs pages qui sont checkés comme main</p>
     * @param int $id_version
     * @return array 
     */
    public function getMain($id_version)
    {
        $query = "SELECT `g`.`name`, `p`.*"
                . " FROM `gab_page` `p` LEFT JOIN `gab_page` `e` ON `e`.`id_parent` = `p`.`id` AND `e`.`suppr` = 0 AND `e`.`id_version` = $id_version"
                . " INNER JOIN `gab_gabarit` `g` ON `p`.`id_gabarit` = `g`.`id` AND `g`.`main` = 1"
                . " WHERE `p`.`suppr` = 0 AND `p`.`id_version` = $id_version";
        $query .= " ORDER BY `p`.`ordre` ASC";
        $metas = $this->_db->query($query)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        $pages = array();
        foreach ($metas as $gabaritName => $metasGabarit) {
            foreach ($metasGabarit as $meta) {
                $page = new gabaritPage();
                $page->setMeta($meta);
                $pages[$gabaritName][] = $page;
            }
        }

        return $pages;
    }

    /**
     * <p>identique à getList</p>
     * @param string $term
     * @param int $id_gabarit
     * @param int $id_parent
     * @param bool $visible
     * @param int $id_version
     * @return array
     * @see gabaritManager::getList
     */
    public function getSearch($id_version, $term, $id_gabarit = 0, $id_parent = FALSE, $visible = FALSE)
    {
        $query = "SELECT * FROM `gab_page` WHERE `suppr` = 0 AND `id_version` = "
                . $id_version . " AND `titre` LIKE " . $this->_db->quote("%$term%");

        if ($id_gabarit)
            $query .= " AND `id_gabarit` = $id_gabarit";

        if ($visible)
            $query .= " AND `visible` = 1";

        if ($id_parent != FALSE)
            $query .= " AND `id_parent` = $id_parent";

        $metas = $this->_db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        $version = $this->getVersion($id_version);

        $pages = array();
        foreach ($metas as $meta) {
            $page = new gabaritPage();
            $page->setMeta($meta);
            $page->setVersion($version);

            $pages[] = $page;
        }

        return $pages;
    }

    /**
     * <p>Permet de récupère le premier enfant
     * (exemple : pour les rubriques qui n'ont pas de `view`)</p>
     * @param int $id_parent
     * @param int $id_version
     * @return array 
     */
    public function getFirstChild($id_version, $id_parent = 0)
    {
        $query = "SELECT *"
                . " FROM `gab_page`"
                . " WHERE `id_parent` = $id_parent AND `suppr` = 0 AND `id_version` = $id_version"
                . " AND `visible` = 1"
                . " ORDER BY `ordre`"
                . " LIMIT 0, 1";
        $meta = $this->_db->query($query)->fetch(PDO::FETCH_ASSOC);
        if ($meta) {
            $page = new gabaritPage();
            $page->setMeta($meta);
            return $page;
        }

        return NULL;
    }

    /**
     * <p>Sauve une page et ses blocs dynamique.</p>
     * @param array $donnees
     * @return gabaritPage 
     */
    public function save($donnees)
    {
        $this->_versions = $this->_db->query("SELECT `id` FROM `version`")->fetchAll(PDO::FETCH_COLUMN);

        $updating = ($donnees['id_gab_page'] > 0);

        $version = isset($donnees['id_version']) ? $donnees['id_version'] : 1;

        if ($updating)
            $page = $this->getPage($version, $donnees['id_gab_page'], 0);
        else
            $page = $this->getPage($version, 0, $donnees['id_gabarit']);

        $id_gab_page = $this->_saveMeta($page, $donnees);

        if (!$id_gab_page)
            return NULL;

        $page = $this->getPage($version, $id_gab_page, 0);

        $this->_savePage($page, $donnees);

        $blocs = $page->getBlocs();
        foreach ($blocs as $bloc) {
            $this->_saveBloc($bloc, $id_gab_page, $page->getMeta("id_version"), $donnees);
        }

        $page = $this->getPage($version, $page->getMeta("id"), 0);
        return $page;
    }

    /**
     *
     * @param gabaritPage $page
     * @param array $donnees
     * @return type 
     */
    private function _saveMeta($page, $donnees)
    {
        $updating = $donnees['id_gab_page'] > 0;

        // Insertion dans la table `gab_page`.
        if ($updating) {
            $titre_rew = $page->getVersion("exotique") > 0 ? $donnees['titre_rew'] : $donnees['titre'];
            $rewriting = $this->_db->rewrit($titre_rew, 'gab_page', 'rewriting', "AND `suppr` = 0 AND `id_parent` = " . $page->getMeta("id_parent") . " AND `id_version` = " . $page->getMeta("id_version") . " AND `id` != " . $page->getMeta("id"));

            $query = "UPDATE `gab_page` SET"
                    . " `titre`      = " . $this->_db->quote($donnees['titre']) . ","
                    . ($page->getVersion("exotique") > 0 ? " `titre_rew`      = " . $this->_db->quote($donnees['titre_rew']) . "," : "")
                    . " `bal_title`  = " . $this->_db->quote($donnees['bal_title'] ? $donnees['bal_title'] : $donnees['titre']) . ","
                    . " `bal_key`    = " . $this->_db->quote($donnees['bal_key']) . ","
                    . " `bal_descr`	= " . $this->_db->quote($donnees['bal_descr']) . ","
                    . " `importance`	= " . $donnees['importance'] . ","
                    . " `date_modif`	= NOW(),"
                    . " `no_index`   = " . (isset($donnees['no_index']) && $page->getMeta("id") != 1 ? $donnees['no_index'] : 0)
                    . ", `rewriting`		= " . $this->_db->quote($rewriting)
//                   . ($page->getMeta("rewriting") == "" ? ", `rewriting`		= " . $this->_db->quote($rewriting) : "")
                    . " WHERE `id` = " . $page->getMeta("id")
                    . " AND `id_version` = " . $page->getMeta("id_version");

            if (!$this->_db->query($query))
                return FALSE;

            return $page->getMeta("id");
        }
        else {
            $id_parent = isset($donnees['id_parent']) && $donnees['id_parent'] ? $donnees['id_parent'] : 0;

            $rewriting = $this->_db->rewrit($donnees['titre'], 'gab_page', 'rewriting', "AND `suppr` = 0 AND `id_parent` = $id_parent AND `id_version` = 1");

            $ordre = $this->_db->query("SELECT MAX(`ordre`) FROM `gab_page` WHERE id_parent = $id_parent")->fetchColumn();
            $ordre = $ordre ? $ordre + 1 : 1;

            $id_gab_page = 0;
            foreach ($this->_versions as $version) {
                $query = "INSERT INTO `gab_page` SET "
                        . "`id` = " . ($id_gab_page > 0 ? $id_gab_page : "NULL") . ","
                        . "`id_gabarit` = " . $page->getGabarit()->getId() . ","
                        . "`titre` = " . $this->_db->quote($donnees['titre']) . ","
                        . "`rewriting` = " . ($id_gab_page > 0 || $version['id'] > 1 ? "''" : $this->_db->quote($rewriting)) . ","
                        . "`bal_title` = " . $this->_db->quote($donnees['bal_title'] ? $donnees['bal_title'] : $donnees['titre']) . ","
                        . "`bal_key` = " . $this->_db->quote($donnees['bal_key']) . ","
                        . "`bal_descr` = " . $this->_db->quote($donnees['bal_descr']) . ","
                        . "`no_index` = " . (isset($donnees['no_index']) ? $donnees['no_index'] : 0) . ","
                        . "`importance` = " . $donnees['importance'] . ","
                        . "`id_parent` = " . $id_parent . ", "
                        . "`ordre` = " . $ordre . ","
                        . "`date_crea` = NOW(),"
                        . "`date_modif` = NOW(),"
                        . "`visible` = 0,"
                        . "`id_version` = " . $version['id'];

//                echo "$query<br />";

                if (!$this->_db->exec($query))
                    return FALSE;

                if ($id_gab_page == 0)
                    $id_gab_page = $this->_db->lastInsertId();
            }

            return $id_gab_page;
        }
    }

    /**
     *
     * @param gabaritPage $page
     * @param array $donnees
     * @return type 
     */
    private function _savePage($page, $donnees)
    {
        $updating = $donnees['id_gab_page'] > 0;

        $gabarit = $page->getGabarit();
        $id_gab_page = $page->getMeta("id");
        $id_version = $page->getMeta("id_version");
        $table = $gabarit->getTable();

        $allchamps = $gabarit->getChamps();
        $champsExiste = count($allchamps);

        if ($updating) {
            $query = "";
            $where = "WHERE `id_version` = $id_version AND `id_gab_page` = $id_gab_page";

            $queryT = "";
            $whereT = "WHERE `id_gab_page` = $id_gab_page";
        } else {
            $query = "INSERT INTO `$table` SET `id_gab_page` = $id_gab_page,";
        }

        foreach ($allchamps as $name_group => $champs) {
            foreach ($champs as $champ) {
                if ($champ["visible"] == 0)
                    continue;
                $value = $donnees['champ' . $champ['id']][0];
                if ($champ['type'] != 'WYSIWYG' && $champ['type'] != 'TEXTAREA')
                    $value = str_replace('"', '&quot;', $value);

                if ($champ['typedonnee'] == 'DATE')
                    $value = Tools::formate_date_nombre($value, "/", "-");

                if ($champ['trad'] == 0 && $updating)
                    $queryT .= "`" . $champ['name'] . "` = " . $this->_db->quote($value) . ",";

                $query .= "`" . $champ['name'] . "` = " . $this->_db->quote($value) . ",";
            }
        }

        if ($updating) {
            if ($champsExiste) {
                if ($query != "")
                    $queryTmp = "UPDATE `$table` SET " . substr($query, 0, -1) . " " . $where;
                if (!$this->_db->query($queryTmp)) {
                    echo "echec de l'update d'un " . $gabarit->getLabel() . "<br /><textarea>$queryTmp</textarea>";
                    return FALSE;
                }

                if ($queryT != "")
                    $queryTmp = "UPDATE `$table` SET " . substr($query, 0, -1) . " " . $whereT;

                if (!$this->_db->query($queryTmp)) {
                    echo "echec de l'update d'un " . $gabarit->getLabel() . "<br /><textarea>$queryTmp</textarea>";
                    return FALSE;
                }
            }
        } else {
            foreach ($this->_versions as $id_version) {
                $queryTmp = $query . "`id_version` = $id_version";

                if (!$this->_db->exec($queryTmp)) {
                    echo "echec de l'insertion d'un " . $gabarit->getLabel() . "<br /><textarea>$queryTmp</textarea>";
                    return FALSE;
                }
            }
        }

        return TRUE;
    }

    /**
     *
     * @param gabaritBloc $bloc
     * @param int $id_gab_page
     * @param int $id_version
     * @param array $donnees
     * @return boolean 
     */
    private function _saveBloc($bloc, $id_gab_page, $id_version, &$donnees)
    {
        $gabarit = $bloc->getGabarit();
        $table = $gabarit->getTable();
        $champs = $gabarit->getChamps();

        $ordre = 1;
        foreach ($donnees['id_' . $gabarit->getTable()] as $id_bloc) {
            $updating = ($id_bloc > 0);
            $visible = array_shift($donnees['visible']);
            if ($updating) {
                $query = "UPDATE `$table` SET"
                        . " `ordre` = $ordre,"
                        . " `visible` = $visible,";
            } else {
                $query = "INSERT INTO `$table` SET"
                        . " `id_gab_page` = $id_gab_page,"
//                        . " `id_version`  = $id_version,"
                        . " `ordre`       = $ordre,"
//                        . " `visible`     = $visible,"
                ;
            }

            foreach ($champs as $champ) {
                if ($champ["visible"] == 0)
                    continue;
                $value = array_shift($donnees['champ' . $champ['id']]);

                if ($champ['type'] != 'WYSIWYG' && $champ['type'] != 'TEXTAREA')
                    $value = str_replace('"', '&quot;', $value);

                if ($champ['typedonnee'] == 'DATE')
                    $value = Tools::formate_date_nombre($value, "/", "-");

                $query .= "`" . $champ['name'] . "` = " . $this->_db->quote($value) . ",";
            }


            if ($updating) {
                $queryTmp = substr($query, 0, -1) . " WHERE `id_version` = $id_version AND `id` = $id_bloc";

                if (!$this->_db->query($queryTmp)) {
                    echo "Echec de l'update d'un " . $gabarit->getLabel() . "<br /><textarea>$queryTmp</textarea>";
                    return FALSE;
                }
            } else {
                $id_bloc = 0;
                foreach ($this->_versions as $id_version) {
                    $queryTmp = $query . " `id_version`  = $id_version";

                    if ($id_bloc)
                        $queryTmp .= ", `id` = $id_bloc,  `visible`     = 0";
                    else {
                        $queryTmp .= ", `id` = $id_bloc,  `visible`     = $visible";
                    }

                    if (!$this->_db->exec($queryTmp)) {
                        echo "Echec de l'insertion d'un " . $gabarit->getLabel() . "<br /><textarea>$queryTmp</textarea>";
                        return FALSE;
                    }

                    $id_bloc = $this->_db->lastInsertId();
                }
            }

            $ids_blocs[] = $id_bloc;
            $ordre++;
        }

        $query = "UPDATE `$table` SET `suppr` = NOW()"
                . " WHERE `suppr` = 0 AND `id_gab_page` = $id_gab_page"// AND `id_version` = $id_version"
                . " AND `id` NOT IN (" . implode(",", $ids_blocs) . ")";

        $this->_db->query($query);
        return TRUE;
    }

    /**
     * <p>Sauve une page et ses blocs dynamique.</p>
     * @param array $donnees
     * @return gabaritPage 
     */
    public function previsu($donnees)
    {
        $this->_versions = $this->_db->query("SELECT `id` FROM `version`")->fetchAll(PDO::FETCH_COLUMN);

        $updating = ($donnees['id_gab_page'] > 0);

        $version = isset($donnees['id_version']) ? $donnees['id_version'] : 1;

        if ($updating)
            $page = $this->getPage($version, $donnees['id_gab_page'], 0);
        else
            $page = $this->getPage($version, 0, $donnees['id_gabarit']);

        $id_gab_page = $this->_previsuMeta($page, $donnees);

        if (!$id_gab_page)
            return NULL;

        $page = $this->getPage($version, $id_gab_page, 0);

        $this->_previsuPage($page, $donnees);

        $blocs = $page->getBlocs();
        foreach ($blocs as $bloc) {
            $this->_previsuBloc($bloc, $id_gab_page, $page->getMeta("id_version"), $donnees);
        }

        $page = $this->getPage($version, $page->getMeta("id"), 0);
        return $page;
    }

    /**
     *
     * @param gabaritPage $page
     * @param array $donnees
     * @return type 
     */
    private function _previsuMeta($page, $donnees)
    {
        $updating = $donnees['id_gab_page'] > 0;

        // Insertion dans la table `gab_page`.
        if ($updating) {
//            $titre_rew = $page->getVersion("exotique") > 0 ? $donnees['titre_rew'] : $donnees['titre'];
//            $rewriting = $this->_db->rewrit($titre_rew, 'gab_page', 'rewriting', "AND `suppr` = 0 AND `id_parent` = " . $page->getMeta("id_parent") . " AND `id_version` = " . $page->getMeta("id_version") . " AND `id` != " . $page->getMeta("id"));

//            $query = "UPDATE `gab_page` SET"
//                    . " `titre`      = " . $this->_db->quote($donnees['titre']) . ","
//                    . ($page->getVersion("exotique") > 0 ? " `titre_rew`      = " . $this->_db->quote($donnees['titre_rew']) . "," : "")
//                    . " `bal_title`  = " . $this->_db->quote($donnees['bal_title'] ? $donnees['bal_title'] : $donnees['titre']) . ","
//                    . " `bal_key`    = " . $this->_db->quote($donnees['bal_key']) . ","
//                    . " `bal_descr`	= " . $this->_db->quote($donnees['bal_descr']) . ","
//                    . " `importance`	= " . $donnees['importance'] . ","
//                    . " `date_modif`	= NOW(),"
//                    . " `no_index`   = " . (isset($donnees['no_index']) && $page->getMeta("id") != 1 ? $donnees['no_index'] : 0)
//                    . ", `rewriting`		= " . $this->_db->quote($rewriting)
//                    . " WHERE `id` = " . $page->getMeta("id")
//                    . " AND `id_version` = " . $page->getMeta("id_version");

            $meta = array(
                "titre"             => $donnees['titre'],
                "bal_title"         => $donnees['bal_title'],
                "bal_key"           => $donnees['bal_key'],
                "bal_descr"         => $donnees['bal_descr'],
                "id_version"        => $page->getMeta("id_version"),
                "id"                => $page->getMeta("id"),
            );
            
            $meta = array_merge($page->getMeta(), $meta);
            
//            if (!$this->_db->query($query))
//                return FALSE;

//            return $page->getMeta("id");
        }
        else {
            $id_parent = isset($donnees['id_parent']) && $donnees['id_parent'] ? $donnees['id_parent'] : 0;

//            $rewriting = $this->_db->rewrit($donnees['titre'], 'gab_page', 'rewriting', "AND `suppr` = 0 AND `id_parent` = $id_parent AND `id_version` = 1");
//
//            $ordre = $this->_db->query("SELECT MAX(`ordre`) FROM `gab_page` WHERE id_parent = $id_parent")->fetchColumn();
//            $ordre = $ordre ? $ordre + 1 : 1;

//            $id_gab_page = 0;
//            foreach ($this->_versions as $version) {
//                $query = "INSERT INTO `gab_page` SET "
//                        . "`id` = "             . ($id_gab_page > 0 ? $id_gab_page : "NULL") . ","
//                        . "`id_gabarit` = "     . $page->getGabarit()->getId() . ","
//                        . "`titre` = "          . $this->_db->quote($donnees['titre']) . ","
//                        . "`rewriting` = "      . ($id_gab_page > 0 || $version['id'] > 1 ? "''" : $this->_db->quote($rewriting)) . ","
//                        . "`bal_title` = "      . $this->_db->quote($donnees['bal_title'] ? $donnees['bal_title'] : $donnees['titre']) . ","
//                        . "`bal_key` = "        . $this->_db->quote($donnees['bal_key']) . ","
//                        . "`bal_descr` = "      . $this->_db->quote($donnees['bal_descr']) . ","
//                        . "`no_index` = "       . (isset($donnees['no_index']) ? $donnees['no_index'] : 0) . ","
//                        . "`importance` = "     . $donnees['importance'] . ","
//                        . "`id_parent` = "      . $id_parent . ", "
//                        . "`ordre` = "          . $ordre . ","
//                        . "`date_crea` = NOW(),"
//                        . "`date_modif` = NOW(),"
//                        . "`visible` = 0,"
//                        . "`id_version` = "     . $version['id'];

                $meta = array(
                    "id"                => 0,
                    "id_parent"         => $id_parent,
                    "id_version"        => $page->getMeta("id_version"),
                    "id_gabarit"        => $page->getGabarit()->getId(),
                    "titre"             => $donnees['titre'],
                    "bal_title"         => $donnees['bal_title'],
                    "bal_key"           => $donnees['bal_key'],
                    "bal_descr"         => $donnees['bal_descr'],
                );

//                if (!$this->_db->exec($query))
//                    return FALSE;
//
//                if ($id_gab_page == 0)
//                    $id_gab_page = $this->_db->lastInsertId();
//            }

//            return $id_gab_page;
        }
        
        $page->setMeta($meta);
    }

    /**
     *
     * @param gabaritPage $page
     * @param array $donnees
     * @return type 
     */
    private function _previsuPage($page, $donnees)
    {
//        $updating = $donnees['id_gab_page'] > 0;

        $gabarit        = $page->getGabarit();
//        $table          = $gabarit->getTable();

//        $id_gab_page    = $page->getMeta("id");
//        $id_version     = $page->getMeta("id_version");
//
        $allchamps      = $gabarit->getChamps();
//        $champsExiste   = count($allchamps);

//        if ($updating) {
//            $query = "";
//            $where = "WHERE `id_version` = $id_version AND `id_gab_page` = $id_gab_page";
//
//            $queryT = "";
//            $whereT = "WHERE `id_gab_page` = $id_gab_page";
//        } else {
//            $query = "INSERT INTO `$table` SET `id_gab_page` = $id_gab_page,";
//        }

        $values = array();
        
        foreach ($allchamps as $name_group => $champs) {
            foreach ($champs as $champ) {
                if ($champ["visible"] == 0)
                    continue;
                
                $value = $donnees['champ' . $champ['id']][0];
                
                if ($champ['type'] != 'WYSIWYG' && $champ['type'] != 'TEXTAREA')
                    $value = str_replace('"', '&quot;', $value);

                if ($champ['typedonnee'] == 'DATE')
                    $value = Tools::formate_date_nombre($value, "/", "-");

//                if ($champ['trad'] == 0 && $updating)
//                    $queryT .= "`" . $champ['name'] . "` = " . $this->_db->quote($value) . ",";

//                $query .= "`" . $champ['name'] . "` = " . $this->_db->quote($value) . ",";
                
                $values[$champ['name']] = $value;
            }
        }

//        if ($updating) {
//            if ($champsExiste) {
//                if ($query != "")
//                    $queryTmp = "UPDATE `$table` SET " . substr($query, 0, -1) . " " . $where;
//                if (!$this->_db->query($queryTmp)) {
//                    echo "echec de l'update d'un " . $gabarit->getLabel() . "<br /><textarea>$queryTmp</textarea>";
//                    return FALSE;
//                }
//
//                if ($queryT != "")
//                    $queryTmp = "UPDATE `$table` SET " . substr($query, 0, -1) . " " . $whereT;
//
//                if (!$this->_db->query($queryTmp)) {
//                    echo "echec de l'update d'un " . $gabarit->getLabel() . "<br /><textarea>$queryTmp</textarea>";
//                    return FALSE;
//                }
//            }
//        } else {
//            foreach ($this->_versions as $id_version) {
//                $queryTmp = $query . "`id_version` = $id_version";
//
//                if (!$this->_db->exec($queryTmp)) {
//                    echo "echec de l'insertion d'un " . $gabarit->getLabel() . "<br /><textarea>$queryTmp</textarea>";
//                    return FALSE;
//                }
//            }
//        }

//        return TRUE;
        
        
        $page->setValues($values);
    }

    /**
     *
     * @param gabaritBloc $bloc
     * @param int $id_gab_page
     * @param int $id_version
     * @param array $donnees
     * @return boolean 
     */
    private function _previsuBloc($bloc, $id_gab_page, $id_version, &$donnees)
    {
        $gabarit = $bloc->getGabarit();
//        $table = $gabarit->getTable();
        $champs = $gabarit->getChamps();

        $values = array();
        
//        $ordre = 1;
        foreach ($donnees['id_' . $gabarit->getTable()] as $id_bloc) {
            $value = array();
            
//            $updating = ($id_bloc > 0);
//            $visible = array_shift($donnees['visible']);
//            if ($updating) {
//                $query = "UPDATE `$table` SET"
//                        . " `ordre` = $ordre,"
//                        . " `visible` = $visible,";
//            } else {
//                $query = "INSERT INTO `$table` SET"
//                        . " `id_gab_page` = $id_gab_page,"
//                        . " `ordre`       = $ordre,"
//                ;
//            }

            foreach ($champs as $champ) {
                if ($champ["visible"] == 0)
                    continue;
                $value = array_shift($donnees['champ' . $champ['id']]);

                if ($champ['type'] != 'WYSIWYG' && $champ['type'] != 'TEXTAREA')
                    $value = str_replace('"', '&quot;', $value);

                if ($champ['typedonnee'] == 'DATE')
                    $value = Tools::formate_date_nombre($value, "/", "-");

                $value[$champ['name']] = $value;
//                $query .= "`" . $champ['name'] . "` = " . $this->_db->quote($value) . ",";
            }

            
            $values[] = $value;

//            if ($updating) {
//                $queryTmp = substr($query, 0, -1) . " WHERE `id_version` = $id_version AND `id` = $id_bloc";
//
//                if (!$this->_db->query($queryTmp)) {
//                    echo "Echec de l'update d'un " . $gabarit->getLabel() . "<br /><textarea>$queryTmp</textarea>";
//                    return FALSE;
//                }
//            } else {
//                $id_bloc = 0;
//                foreach ($this->_versions as $id_version) {
//                    $queryTmp = $query . " `id_version`  = $id_version";
//
//                    if ($id_bloc)
//                        $queryTmp .= ", `id` = $id_bloc,  `visible`     = 0";
//                    else {
//                        $queryTmp .= ", `id` = $id_bloc,  `visible`     = $visible";
//                    }
//
//                    if (!$this->_db->exec($queryTmp)) {
//                        echo "Echec de l'insertion d'un " . $gabarit->getLabel() . "<br /><textarea>$queryTmp</textarea>";
//                        return FALSE;
//                    }
//
//                    $id_bloc = $this->_db->lastInsertId();
//                }
//            }

//            $ids_blocs[] = $id_bloc;
//            $ordre++;
        }

//        $query = "UPDATE `$table` SET `suppr` = NOW()"
//                . " WHERE `suppr` = 0 AND `id_gab_page` = $id_gab_page"// AND `id_version` = $id_version"
//                . " AND `id` NOT IN (" . implode(",", $ids_blocs) . ")";

//        $this->_db->query($query);
        
        
        $bloc->setValues($values);
        
        return TRUE;
    }

}
