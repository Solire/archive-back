<?php
/**
 * Configuration de l'affichage des gabarits
 *
 * @package    Back
 * @subpackage Gabarit
 * @author     dev <dev@solire.fr>
 * @license    Solire http://www.solire.fr/
 */

/* EXEMPLE
<?php

$config = array(
    array(
        "label" => "Contenu institutionnel",
        "gabarits" => array(1, 5, 6, 7, 8),
    ),
    array(
        "label" => "Contenu secteur d'activité",
        "gabarits" => array(3, 4),
    ),
);
 */

$config = array(
    0 => array(
        'label' => 'Contenu institutionnel',
        'gabarits' => '*',
        'display' => true,
        /** Si vrais, empêche le chargement des gabarits enfants **/
        'noChild' => false,
        /** Si vrais, conserve le trie des groupes définis dans ce fichier **/
        'sort' => false,
        /** Blocage de l'affichage du type de gabarit **/
        'noType' => false,
        'childName' => 'page(s)',
    ),
);

