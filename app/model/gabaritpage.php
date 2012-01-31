<?php
/**
 * Description of page
 *
 * @author thomas
 */
class gabaritPage extends gabaritBloc {
    /**
     *
     * @var array
     */
    private $_meta = array();
    
    /**
     *
     * @var array
     */
    private $_blocs = array();

    /**
     *
     * @var array 
     */
    private $_parents = array();
    
    /**
     *
     * @param array $meta 
     */
    public function __construct() {   
        $this->_values = array();
    }
    
    
    // SETTERS
    
    /**
     *
     * @param array $meta 
     */
    public function setMeta($meta) {
        $this->_meta = $meta;
        $this->_id = $meta['id'];
    }
    
    /**
     *
     * @param array $values 
     */
    public function setValues($values) {
        $this->_values = $values;
    }
    
    /**
     *
     * @param array $blocs tableau de page 
     */
    public function setBlocs($blocs) {
        $this->_blocs = $blocs;
    }
    
    /**
     *
     * @param array $parents 
     */
    public function setParents($parents) {
        $this->_parents = $parents;
    }
    
    /**
     *
     * @param gabaritPage $child 
     */
    public function setChildren($children) {
        $this->_children= $children;
    }
    
    /**
     *
     * @param gabaritPage $child 
     */
    public function getChildren() {
        return $this->_children;
    }
    
    /**
     *
     * @param gabaritPage $firstChild 
     */
    public function setFirstChild($firstChild) {
        $this->_firstChild = $firstChild;
    }
    
    // GETTERS
    
    /**
     *
     * @param string $key
     * @return mixed 
     */
    public function getMeta($key = NULL) {
        if ($key != NULL) {
            if (is_array($this->_meta) && array_key_exists($key, $this->_meta))
                return $this->_meta[$key];
            
            return NULL;
        }
        
        return $this->_meta;
    }
    
    /**
     *
     * @param string $key
     * @return mixed 
     */
    public function getValues($key = NULL) {
        if (is_array($this->_values) && array_key_exists($key, $this->_values))
            return $this->_values[$key];
        
        if ($key == NULL)
            return $this->_values;
        
        return '';
    }
    
    /**
     *
     * @return type 
     */
    public function getBlocs($name = NULL) {
        if ($name == NULL || !isset ($this->_blocs[$name]))
            return $this->_blocs;
        
        return $this->_blocs[$name];
    }  

    /**
     *
     * @param int $id_gabarit
     * @return gabaritPage 
     */
    public function getParent($i) {
        if (array_key_exists($i, $this->_parents))
            return $this->_parents[$id_gabarit];
        
        return FALSE;
    }
    
    /**
     *
     * @param int $id_gabarit
     * @return gabaritPage 
     */
    public function getParents() {
        return $this->_parents;
    }
    
    public function getFirstChild(){
        return $this->_firstChild;
    }
    
    /**
     *
     * @param type $mobile
     * @return string 
     */
    public function getForm($action, $retour, $upload_path, $mobile = FALSE) {        
        $metaId = isset($this->_meta['id']) ? $this->_meta['id'] : 0;
        $metaLang = isset($this->_meta['id_version']) ? $this->_meta['id_version'] : 1;
        
        $parentSelect = '';
        
        if ($metaId && $this->_meta['id_parent'] > 0) {            
            $parentSelect = '<div class="line">'
                          . '<label for="id_parent">' . $this->_gabarit->getGabaritParent("label") . '</label>'
                          . '<select disabled="disabled"><option>' . $this->getParent(0)->getMeta("titre") . '</option></select>'
                          . '<input type="hidden" disabled="disabled" name="id_parent" value="' . $this->getParent(0)->getMeta("id") . '" />'
                          . '</div>';
        }
        elseif (!$metaId && $this->_gabarit->getIdParent() > 0) {
            $parentSelect = '<div class="line">'
                          . '<label for="id_parent">' . $this->_gabarit->getGabaritParent("label") . '</label>'
                          . $this->_gabarit->getParentsSelect()
                          . '</div>';
        }
        
        $form = '<form action="' . $action . '" method="post" enctype="multipart/form-data">'
		      . '<input type="hidden" name="id_gabarit" value="' . $this->_gabarit->getId() . '" />'
			  . '<input type="hidden" name="id_gab_page" value="' . $metaId . '" />'
			  . '<input type="hidden" name="id_version" value="' . $metaLang . '" />'
              
              . $parentSelect
              . '<div class="line">'
              . '<label for="titre-' . $metaLang . '">Titre</label>'
              . '<input type="text" name="titre" id="titre-' . $metaLang . '" value="' . (isset($this->_meta['titre']) ? $this->_meta['titre'] : '') . '" class="form-controle form-oblig form-mix" />'
              . '</div>'

              . '<fieldset><legend>Balise Meta</legend><div style="display:none;">'

              . '<div class="line">'
              . '<label for="rewriting-' . $metaLang . '">Rewriting</label>'
              . '<input type="text" name="rewriting" id="rewriting-' . $metaLang . '" value="' . (isset($this->_meta['rewriting']) ? $this->_meta['rewriting'] : '') . '" disabled="disabled" />'
              . '</div>'

              . '<div class="line">'
              . '<label for="bal_title-' . $metaLang . '">Title</label>'
              . '<input type="text" name="bal_title" id="bal_title-' . $metaLang . '" value="' . (isset($this->_meta['bal_title']) ? $this->_meta['bal_title'] : '') . '" size="80" maxlength="80" />'
              . '</div>'

              . '<div class="line">'
              . '<label for="bal_descr-' . $metaLang . '">Description</label>'
              . '<input type="text" name="bal_descr" id="bal_descr-' . $metaLang . '" value="' . (isset($this->_meta['bal_descr']) ? $this->_meta['bal_descr'] : '') . '" size="80" maxlength="250" />'
              . '</div>'

              . '<div class="line">'
              . '<label for="bal_key-' . $metaLang . '">Keywords (<i>séparés par des ,</i>)</label>'
              . '<input type="text" name="bal_key" id="bal_key-' . $metaLang . '" value="' . (isset($this->_meta['bal_key']) ? $this->_meta['bal_key'] : '') . '" size="80" maxlength="250" />'
              . '</div>'

              . '<div class="line">'
              . '<label for="importance-' . $metaLang . '">Importance (<i>de 0,1 à 0,9</i>)</label>'
              . '<select name="importance" id="importance-' . $metaLang . '">';

        for ($ii = 1 ; $ii < 10 ; $ii++)
            $form .= '<option value="' . $ii . '"' . (isset($this->_meta['importance']) && $ii == $this->_meta['importance'] ? ' selected="selected"' : '') . '>' . $ii . '</option>';

        $form .= '</select>'
               . '</div>'

               . '<div class="line">'
               . '<label for="no_index' . $metaLang . '">No-index</label>'
               . '<input type="checkbox" name="no_index" id="no_index' . $metaLang . '"' . (isset($this->_meta['no_index']) && $this->_meta['no_index'] > 0 ? ' checked="checked"' : '') . ' />'
               . '</div>'

               . '</div>'
               . '</fieldset>';

		$form .= $this->buildForm($upload_path);
		
		$form .= '<div class="buttonfixed">'
               . ($mobile ? '<a href="#" class="button bleu changemedia"><span class="bleu">Version mobile</span></a>' : '')
               . '<a href="#" class="button vert formajaxsubmit" style="clear:both;"><span class="vert">Valider</span></a>'
               . '<a href="#" class="button vert uploader_popup" style="clear:both;"><span class="vert">Fichiers</span></a>'
               . '<!--a href="#" class="button vert formprev" style="clear:both;"><span class="vert">Prévisualiser</span></a-->'
               . '<a href="' . $retour
               . ($metaId ? '?id_gab_page=' . $metaId : '')
               . '" class="button vert" style="clear:both;"><span class="vert">Retour</span></a>'
               . '</div>'
               . '</form>';
		
		return $form;
	}
	
    /**
     *
     * @return type 
     */
	public function buildForm($upload_path) {      
        $form = '<input type="hidden" name="id_' . $this->_gabarit->getTable() . '" value="' . (isset($this->_values['id']) ? $this->_values['id'] : '') . '" />';
        
        $allchamps = $this->_gabarit->getChamps();
        
        $id_gab_page = isset($this->_meta['id']) ? $this->_meta['id'] : 0;
        
        foreach ($allchamps as $name_group => $champs) {
            $form .= '<fieldset><legend>' . $name_group . '</legend><div>';
            foreach ($champs as $champ) {
                $value = isset($this->_values[$champ['name']]) ? $this->_values[$champ['name']] : '';
                $id = isset($this->_meta['id_version']) ? $this->_meta['id_version'] : '';
                $form .= $this->_buildChamp($champ, $value, $id, $upload_path, $id_gab_page);
            }
            $form .= '</div></fieldset>';
        }
        
        foreach ($this->_blocs as $blocName => $bloc)
            $form .=  $bloc->buildForm($upload_path, $id_gab_page);
        
		return $form;
	}
    
}
