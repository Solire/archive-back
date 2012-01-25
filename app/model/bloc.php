<?php
/**
 * Description of bloc
 *
 * @author thomas
 */
class bloc {
    /**
     * identifiant de gab_page
     * @var int
     */
    protected $_id;
    
    /**
     *
     * @var gabarit 
     */
    protected $_gabarit;
    
    /**
     *
     * @var array 
     */
    protected $_values = array(array());

    public function __construct() {}
    
    public function setId($id) {
        $this->_id = $id;
    }
    
    /**
     *
     * @param gabarit $gabarit 
     */
    public function setGabarit($gabarit) {
        $this->_gabarit = $gabarit;
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
     * @return gabarit 
     */
    public function getGabarit() {
        return $this->_gabarit;
    }
    
    /**
     *
     * @param string $key
     * @return mixed 
     */
    public function getValues() {        
        return $this->_values;
    }
    
    /**
     *
     * @return type 
     */
	public function buildForm() {		
		$form = "\n"
              . '<fieldset><legend>' . $this->_gabarit->getName() . '(s)</legend><div class="sort-box">';
        $champs = $this->_gabarit->getChamps();
        
        foreach ($this->_values as $value) {            
            $form .= '<fieldset class="sort-elmt" style="margin-left:30px;"><legend>' . $this->_gabarit->getName() . '</legend><div>'
                   . '<div class="line">'
                   . '<label for="visible-' . $this->_gabarit->getId() . '-' . (isset($value['id']) ? $value['id'] : 0) . '-' . (isset($value['id_version']) ? $value['id_version'] : 1) . '">Visible</label>'
                   . '<input type="checkbox" id="visible-' . $this->_gabarit->getId() . '-' . (isset($value['id']) ? $value['id'] : 0) . '-' . (isset($value['id_version']) ? $value['id_version'] : 1) . '" class="changevisible"' . (isset($value['visible']) && $value['visible'] ? ' checked="checked"' : '') . ' />'
                   . '<input type="hidden" value="' . (isset($value['visible']) && $value['visible'] ? 1 : 0) . '" name="visible[]" />'
                   . '</div><div' . (isset($value['visible']) && $value['visible'] ? '' : ' class="translucide"') . '>'
                   . '<input type="hidden" name="id_' . $this->_gabarit->getTable() . '[]" value="' . (isset($value['id']) ? $value['id'] : '') . '" />';

            foreach ($champs as $champ) {
                $value_champ = isset($value[$champ['name']]) ? $value[$champ['name']] : '';
                $id_champ = (isset($this->_meta['id_version']) ? $this->_meta['id_version'] : '') . (isset($value['id']) ? $value['id'] : 0);
                $form .= $this->_buildChamp($champ, $value_champ, $id_champ);
            }
            
            $form .= '</div></div>'
                   . '<div>'
                   . '<a href="#" class="button bleu delBloc' . ( count($value) > 1 ? '' : ' translucide' )
                   . '" style="float:right;"><span class="bleu"><img src="img/back/supprimer.png" border="0" alt="supprimer"/></span></a>'
                   . '<a href="#" class="button bleu sort-move"><span class="bleu"><img src="img/back/deplacer.png" alt="Déplacer" /></span></a>'
                   . '</div>'
                   . '</fieldset>';
        }
        		
        $form .= '<div class="buttonright"><a class="button bleu addBloc" href="#"><span class="bleu">Ajouter un bloc</span></a></div>';
		$form .= '</div></fieldset>';
		
		return $form;
	}
    
    /**
     *
     * @param type $champ
     * @param type $value
     * @param type $idpage
     * @return string 
     */
	protected function _buildChamp($champ, $value, $idpage) {
		$form = '';

		$label = $champ['label'];
        $classes = 'form-controle form-' . $champ['oblig'] . ' form-' . strtolower($champ['typedonnee']);
        $id = 'champ' . $champ['id'] . '_' . $idpage ;
        
		if ($champ['typedonnee'] == 'DATE')
            $value = dateFRtoUS($value, '-', '/');

		switch ($champ['type']) {
			case 'WYSIWYG' :
				$form = '<div class="line' . $champ['media'] . '">'
                      . '<label for="' . $id . '">'
                      . '<span>' . $label . '</span><br /><br />'
                      . '<span class="switch-editor"><span>html</span>&nbsp;<span class="translucide">visuel</span></span>'
                      . '</label>'
                      . '<textarea name="champ' . $champ['id'] . '[]" id="' . $id
                      . '" class="' . $classes . ' tiny' . '" rows="5" cols="20">'
                      . $value . '</textarea>'
                      . '</div>';
			break;

			case 'TEXTAREA' :					
				$form = '<div class="line' . $champ['media'] . '">'
                      . '<label for="' . $id . '"><span>' . $label . '</span></label>'
                      . '<textarea name="champ' . $champ['id'] . '[]" id="' . $id
                      . '" class="' . $classes . '" rows="5" cols="20">' . $value . '</textarea>'
                      . '</div>';
			break;

			case 'TEXT' :					
				$form = '<div class="line' . $champ['media'] . '">'
                      . '<label for="' . $id . '"><span>' . $label . '</span></label>'
                      . '<input type="text" name="champ' . $champ['id'] . '[]" id="' . $id
                      . '" class="' . $classes . '" value="' . $value . '" />'
                      . '</div>';
			break;

			case 'FILE' :				
				$form = '<div class="line' . $champ['media'] . '">'
                      . '<label for="' . $id . '"><span>' . $label . '</span></label>'
                      . '<input type="text" name="champ' . $champ['id'] . '[]" id="' . $id
                      . '" class="' . $classes . '" value="' . $value . '" />' . '<a href="'
                      . ($value ? Registry::get("base") . Registry::get("upload-path") . DIRECTORY_SEPARATOR . $this->_id . DIRECTORY_SEPARATOR . $value : '')
                      . '" class="previsu">' . $value . '</a>'
                      . '</div>';
			break;

			case 'CHECKBOX' :				
				$form = '<div class="line' . $champ['media'] . '">'
                      . '<label for="' . $id . '"><span>' . $label . '</span></label>'
                      . '<input type="checkbox" name="champ' . $champ['id'] . '[]" id="' . $id
                      . '" class="' . $classes . '"' . ($value > 0 ? ' checked="checked"' : '') . '/>'
                      . '</div>';
			break;
		}
		
		return $form;
	}

}