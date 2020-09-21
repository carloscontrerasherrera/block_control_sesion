<?php
//código para configurar la configuración del bloque
//$interval = required_param('aroleid', PARAM_INT);
class block_control_sesion_edit_form extends block_edit_form {
 
    protected function specific_definition($mform) {
		global $COURSE,$DB,$CFG;
        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));
 
        // A sample string variable with a default value.
        $mform->addElement('text', 'config_title', get_string('titulo', 'block_control_sesion'));
		$mform->setDefault('config_title', '');
		$mform->setType('config_title', PARAM_TEXT);
		$mform->addElement('text', 'config_text', get_string('mensaje', 'block_control_sesion'));
        $mform->setDefault('config_text', '');
        $mform->setType('config_text', PARAM_RAW);        
		for ($i = 0; $i <= 23; $i++) {
			$h[$i] = $i;
		}
		$mform->addElement('select', 'config_ini', get_string('hora_ini', 'block_control_sesion'), $h);
		$mform->setDefault('config_ini', '7');
        $mform->setType('config_ini', PARAM_RAW);   
		
		$meses=[];
		for ($y=1;$y<=12;$y++)
			array_push($meses,nombre_mes($y));
		$options = array_combine(array(1,2,3,4,5,6,7,8,9,10,11,12),$meses);
		$grupo_ele[] =$mform->addElement('select', 'config_mes_ini', get_string('mes_ini', 'block_control_sesion'), $options);
		$mform->setDefault('config_mes_ini', '9');
		$mform->setType('config_mes_ini', PARAM_INT);
		
		
        $mform->addElement('text', 'config_interval', get_string('interval', 'block_control_sesion'));
		$mform->setDefault('config_interval', '20');
		$mform->setType('config_interval', PARAM_INT);
		
        $mform->addElement('selectyesno', 'config_visibleus', get_string('visible_us', 'block_control_sesion'));
		$mform->setDefault('config_visibleus', 1);
		$mform->setType('config_visibleus', PARAM_INT);
		$grupos=array("Todos");
		$ids=array(0);
		if ($res=$DB->get_records_sql('SELECT id, name from {groups} g WHERE courseid='.$COURSE->id.' ORDER BY id DESC'))
		{
			//print "----------";
			foreach($res as $g)
			{
				array_push($grupos,$g->name);
				array_push($ids,$g->id);
			}
		}	
		$options = array_combine($ids,$grupos);
		$select=$mform->addElement('select', 'config_grupo', get_string('defaultgroup', 'block_control_sesion'), $options);
		//$mform->setDefault('config_grupo', 0);
		$mform->setType('config_grupo', PARAM_INT);
		$mform->addElement('selectyesno', 'config_mostrarcol', get_string('mostrarcol', 'block_control_sesion'));
		$mform->setDefault('config_mostrarcol', 1);
		$mform->setType('config_mostrarcol', PARAM_INT);
		
		$mform->addElement('text', 'config_red', '<div style="padding:10px;background-color:red;">'.get_string('interac_dia', 'block_control_sesion').' &lt;</div>');
		$mform->setDefault('config_red', '3');
		$mform->setType('config_red', PARAM_INT);
		$mform->addElement('text', 'config_orange', '<div style="padding:10px;background-color:orange;">'.get_string('interac_dia', 'block_control_sesion').' &lt;</div>');
		$mform->setDefault('config_orange', '10');
		$mform->setType('config_orange', PARAM_INT);
		$mform->addElement('text', 'config_yellow', '<div style="padding:10px;background-color:yellow;">'.get_string('interac_dia', 'block_control_sesion').' &lt;</div>');
		$mform->setDefault('config_yellow', '20');
		$mform->setType('config_yellow', PARAM_INT);
		$mform->hideIf('config_red', 'config_mostrarcol', 'eq', 0);
		$mform->hideIf('config_orange', 'config_mostrarcol', 'eq', 0);
		$mform->hideIf('config_yellow', 'config_mostrarcol', 'eq', 0);
		
		
		//print_object($mform->config);
		//$mform->setDefault('grupo', $courseconfig->grupo);
    }
}
?>