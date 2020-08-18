<?php
//Código de como se muestra el bloque en la barra lateral
require_once($CFG->dirroot.'/blocks/control_sesion/lib.php');

class block_control_sesion extends block_base {
    public function init() {
        $this->title = get_string('control_sesion', 'block_control_sesion');
    }
    // The PHP tag and the curly bracket for the class definition 
    // will only be closed after there is another function added in the next section.
	public function get_content() {
		global $COURSE, $DB, $PAGE,$USER;
		$context = context_course::instance($COURSE->id);		
		//date_default_timezone_set('UTC');
		if ($this->content !== null) {
		  return $this->content;
		}
		$instancia=$this->instance->id;
		$this->content         =  new stdClass;
		$this->content->title   = get_string('title_block', 'block_control_sesion');
		$this->content->text   = '';
		if (! empty($this->config->text)) {
			$this->content->text = $this->config->text;
		}	 
		// Check to see if we are in editing mode
		//$canmanage = $PAGE->user_is_editing($this->instance->id);
		// Check to see if we are in editing mode and that we can manage pages.
		$versesiones = has_capability('block/control_sesion:todassesiones', $context);// && $PAGE->user_is_editing($this->instance->id);
		$misesion = has_capability('block/control_sesion:misesion', $context);
		$id=0;
		if (!$versesiones)
			$id=$USER->id;
		$this->content->text .=texto_tabla_datos(lista_usuarios($instancia,$USER->id,false,1,(new DateTime("now"))->format("Y-m-d"),1,1,2020,2020,0,(new DateTime("now"))->format("Y-m-d")));
		//print_object($this->config);
		if (empty($this->config->visibleus))
		{
			//bloque no configurado todavía
			if ($versesiones)
			{
				$this->content->footer = get_string('sin_config', 'block_control_sesion');
			}
		}
		else if ($this->config->visibleus || $versesiones)
		{
			$g=0;
			if (!empty($this->config->grupo))
				$g=$this->config->grupo;
			$url = new moodle_url('/blocks/control_sesion/view.php', array( 'c'=>$COURSE->id,'id'=>$instancia, 'u' => $id,"g"=>$g/* ,"f"=>(new DateTime("now"))->format('Y-m-d'),"ff"=>(new DateTime("now"))->format('Y-m-d') */));
			$this->content->footer = html_writer::link($url, get_string('verdetalle', 'block_control_sesion'));
		}
		return $this->content;
	}
	public function specialization() {
		if (isset($this->config)) {
			if (empty($this->config->title)) {
				$this->title = '<b>'.get_string('defaulttitle', 'block_control_sesion').'</b>';            
			} else {
				$this->title = '<b>'.$this->config->title.'</b>';
			}  
		}
	}
	
/*	public function instance_delete() {
		global $DB;
		$DB->delete_records('block_control_sesion', array('blockid' => $this->instance->id));
	}*/
}


?>