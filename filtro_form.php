<?php 
//formulario para los filtros de cabecera
require_once("{$CFG->libdir}/formslib.php");

class filtro_form extends moodleform {
    function definition() {
		//se añade un campo, se captura desde una variable en view,se pasa en las urls,se añade a to_form y se hace un set_data y se obtiene desde from_form
		global $tipo_filtro,$grupo_filtro,$DB,$COURSE;
        $mform =& $this->_form;
		//comprueba si hay nuevo grupo seleccionado en el filtro_form
		// sin no, coge el pasado por parámetro que es la última selección
		$grupo = optional_param('grupo',-1, PARAM_INT);
		$grupo_filtro=$grupo;
		if ($grupo==-1)
		{
			$grupo = optional_param('g',-1, PARAM_INT);
			$grupo_filtro=$grupo;
		}
		$grupos=array(get_string('todos','block_control_sesion'));
		$ids=array(0);
		if ($res=$DB->get_records_sql('SELECT id, name from mdl_groups g WHERE courseid='.$COURSE->id.' ORDER BY id DESC'))
		{
			foreach($res as $g)
			{
				array_push($grupos,$g->name);
				array_push($ids,$g->id);
			}
		}	
		$context = context_course::instance($COURSE->id);		
		$versesiones = has_capability('block/control_sesion:todassesiones', $context); 
		if ($versesiones)
		{
			$options = array_combine($ids,$grupos);
			$select=$mform->addElement('select', 'grupo', get_string('group'), $options);
			$mform->setType('grupo', PARAM_INT);
		}
		else
		{
			$mform->addElement('hidden', 'grupo');
			$mform->setType('grupo', PARAM_INT);			
		}
		$grupo_ele=array();
		if ($versesiones && $tipo_filtro!=0 && $tipo_filtro!=3 && $tipo_filtro!=4 )
		{
			$nombres=array(get_string('todos','block_control_sesion'));
			$usuarios=array(0);
			if ($grupo_filtro==0)
				$sql_us='SELECT * FROM {user} WHERE id in (SELECT r.userid FROM mdl_role_assignments r, mdl_context o where o.id=r.contextid and o.instanceid='.$COURSE->id.')';
			else
				$sql_us='SELECT * FROM {user} WHERE id in (SELECT r.userid FROM {groups_members} r WHERE r.groupid='.$grupo_filtro.')';
			if ($res=$DB->get_records_sql($sql_us))
			{
				foreach($res as $u)
				{
					array_push($nombres,$u->firstname.' '.$u->lastname);
					array_push($usuarios,$u->id);
				}
			}	
			$options = array_combine($usuarios,$nombres);
			$mform->addElement('select', 'usuario', get_string('user'), $options);
		}
		else{
			$mform->addElement('hidden', 'usuario');
			$mform->setType('usuario', PARAM_INT);
		}
		if ($tipo_filtro==3 || $tipo_filtro==5)
		{
			$meses=[];
			for ($y=1;$y<=12;$y++)
				array_push($meses,nombre_mes($y));
			$grupo_ele[] =$mform->createElement('static', 'description', '',get_string('month').':&nbsp;&nbsp;');
			$options = array_combine(array(1,2,3,4,5,6,7,8,9,10,11,12),$meses/* array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre') */);
			$grupo_ele[] =$mform->createElement('select', 'mes', get_string('month'), $options);
		}
		else
		{
			$mform->addElement('hidden', 'mes');
			$mform->setType('mes', PARAM_INT);
		}
		$hoy=new DateTime("now");
		$mform->setDefault('mes', $hoy->format('m'));
		if ($tipo_filtro>=3)
		{
			$mform->addElement('hidden', 'fecha');
			$mform->setType('fecha', PARAM_RAW);
			if ($tipo_filtro==4)//por curso completo
			{
				$grupo_ele[] =$mform->createElement('static', 'description', '',get_string('curso', 'block_control_sesion').':&nbsp;&nbsp;');
				$nombres=array();$valores=array();
				for($x=$hoy->format('Y')-10;$x<=ajuste_year_curso($hoy);$x++)
				{
					array_push($nombres,$x.'-'.($x+1));
					array_push($valores,$x);
				}
				$options = array_combine($valores,$nombres);
				$grupo_ele[] =$mform->createElement('select', 'curso', get_string('curso', 'block_control_sesion'), $options);
				$mform->setType('curso', PARAM_INT);
	
				$mform->addElement('hidden', 'year');
				$mform->setType('year', PARAM_INT);
			}
			else
			{ //por semanas o mes detallado
				$grupo_ele[] =$mform->createElement('static', 'description', '',get_string('year').':&nbsp;&nbsp;');
				$options = array_combine(range($hoy->format('Y')-10,$hoy->format('Y')), range($hoy->format('Y')-10,$hoy->format('Y')));
				$grupo_ele[] =$mform->createElement('select', 'year', get_string('year'), $options);
				$mform->setType('year', PARAM_INT);
				$mform->addElement('hidden', 'curso');
				$mform->setType('curso', PARAM_INT);
			}
			
			
			$mform->addElement('hidden', 'fecha_fin');
			$mform->setType('fecha_fin', PARAM_RAW);
		}
		else
		{
			$grupo_ele[] =$mform->createElement('date_selector', 'fecha', '');
			$mform->setType('fecha', PARAM_RAW);
			//if ($tipo_filtro==0 || $tipo_filtro==2)
			{
				$grupo_ele[] =$mform->createElement('date_selector', 'fecha_fin', get_string('hasta','block_control_sesion'));
				$mform->setType('fecha_fin', PARAM_RAW);
			}
			/* else
			{
				$mform->addElement('hidden', 'fecha_fin');
				$mform->setType('fecha_fin', PARAM_RAW);
			} */
			$mform->addElement('hidden', 'year');
			$mform->setType('year', PARAM_INT);
			$mform->addElement('hidden', 'curso');
			$mform->setType('curso', PARAM_INT);
		}
		$mform->setDefault('year', (new DateTime("now"))->format('Y'));
		$mform->setDefault('curso', ajuste_year_curso($hoy));
		$grupo_ele[] =$mform->createElement('submit', 'submitbutton', get_string('filtrar','block_control_sesion'));
		$mform->addGroup($grupo_ele, 'grupo_ele_filtro', get_string('fecha_sesion','block_control_sesion'), ' ', false);
		
		$mform->setAdvanced('optional');
		$mform->addElement('hidden', 'blockid');
		$mform->setType('blockid', PARAM_RAW);
		$mform->addElement('hidden', 'c');
		$mform->setType('c', PARAM_RAW);
		$mform->addElement('hidden','id','0');
		$mform->setType('id', PARAM_RAW);
		$mform->addElement('hidden','t',0);
		$mform->setType('t', PARAM_RAW);
		$mform->addElement('hidden','a',false);
		$mform->setType('a', PARAM_RAW);
		
	}
}	
		
?>