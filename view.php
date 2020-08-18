<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/control_sesion/lib.php');
require_once($CFG->dirroot.'/blocks/control_sesion/filtro_form.php');
require_once($CFG->libdir.'/excellib.class.php');
global $CFG,$DB,$USER,$OUTPUT,$PAGE,$COURSE,$grupo_filtro,$instancia; 
$usuario=0;
$f="now";
$f="now + 7 DAYS";
$courseid = required_param('c', PARAM_INT);
$instancia = required_param('id', PARAM_INT); 
$viewpage = optional_param('viewpage', false, PARAM_BOOL);
$config=obtener_config($instancia);
$ini=$config->ini;
$interval =$config->interval;
$mes_ini =$config->mes_ini;

$tipo_tabla = optional_param('t',0, PARAM_INT);
$ampliado = optional_param('a',false, PARAM_BOOL);
$descargar = optional_param('d',false, PARAM_BOOL);
$usuario = optional_param('u',$USER->id, PARAM_INT);
$mes = optional_param('m',(new DateTime("now"))->format('m'), PARAM_INT);
$year = optional_param('y',(new DateTime("now"))->format('Y'), PARAM_INT);
$curso = optional_param('cc',ajuste_year_curso(new DateTime("now")), PARAM_INT);
$f=optional_param('f',"now", PARAM_TEXT);
$ff=optional_param('ff',"now", PARAM_TEXT);
$grupo = optional_param('g',0, PARAM_INT);
$fecha = new DateTime($f);
$fecha_fin = new DateTime($ff);
$tipo_filtro=$tipo_tabla; 
$grupo_filtro=$grupo; 
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'control_sesion', $courseid);
}
 
require_login($course);
$context = context_course::instance($COURSE->id);		
$versesiones = has_capability('block/control_sesion:todassesiones', $context);// && $PAGE->user_is_editing($this->instance->id);
$misesion = has_capability('block/control_sesion:misesion', $context);
if ($usuario==0 && !$versesiones)
	$usuario=$USER->id;
if ($usuario==0 || $usuario!=$USER->id || !$config->visibleus)
{
	$PAGE->set_heading(get_string('infotodassesiones', 'block_control_sesion')); 
	require_capability('block/control_sesion:todassesiones', context_course::instance($courseid));
}
else
{
	$PAGE->set_heading(get_string('infosesion', 'block_control_sesion')); 
}
$PAGE->set_url('/blocks/control_sesion/view.php', array('c' => $courseid,'id'=>$instancia));
$PAGE->set_pagelayout('base');

$settingsnode = $PAGE->settingsnav->add("Sesiones");
$url_hoy = new moodle_url('/blocks/control_sesion/view.php', array('c' => $courseid,'id'=>$instancia));
$editnode = $settingsnode->add("Sesiones", $url_hoy);
$editnode->make_active();

$filtro = new filtro_form();
$toform['id'] = $instancia;
$toform['c'] = $courseid;
$toform['u'] = $usuario;
$toform['t'] = $tipo_tabla;
$toform['a'] = $ampliado;
$toform['usuario'] = $usuario;
$toform['fecha'] = strtotime($f);
$toform['fecha_fin'] = strtotime($ff);
$toform['mes'] = $mes;
$toform['year'] = $year;
$toform['curso'] = $curso;

$toform['grupo'] = $grupo;
$filtro->set_data($toform);
$res=array();
for($x=0;$x<=5;$x++)
	$res[$x]="btn-secondary";
$res[$tipo_tabla]="btn-info";
switch ($tipo_tabla)
{
	case 0:$num_dias=1;break;
	case 1:$num_dias=1;break;
	case 2:$num_dias=1;break;
	case 3:$num_dias=30;break;
	case 4:$num_dias=date ( "z",strtotime((new DateTime('2020-12-31'))->format('Y-m-d')));break;
	case 5:$num_dias=30;break;
}
	
if ($fromform = $filtro->get_data()) 
{
	$mes=$fromform->mes;
	$year=$fromform->year;
	$curso=$fromform->curso;
	$usuario=$fromform->usuario;
	$grupo=$fromform->grupo;
	$grupo_filtro=$grupo; 
	$f = new DateTime("now");
	$ff = new DateTime("now");
	if ($tipo_tabla<3)
		$f->setTimestamp($fromform->fecha);
	if ($tipo_tabla<3)
		$ff->setTimestamp($fromform->fecha_fin);
	if ($versesiones && ($tipo_tabla==0 || $tipo_tabla==3 || $tipo_tabla==4))
		$usuario=0;
	$fecha=$f;
	$fecha_fin=$ff;
	
}
		
$resultados=lista_usuarios($instancia,$usuario,$ampliado,$tipo_tabla,$fecha->format('Y-m-d'),$num_dias,$mes,$year,$curso,$grupo,$fecha_fin->format('Y-m-d'));
if ($descargar)
{
	descargar_tabla_datos($resultados);
	return;
}
$url_base=array('c' => $courseid,'id'=>$instancia,"m"=>$mes,"y"=>$year,"cc"=>$curso,"f"=>$fecha->format('Y-m-d'),"u"=>$usuario,"g"=>$grupo,"ff"=>$fecha_fin->format('Y-m-d'));
$url_tabla = new moodle_url('/blocks/control_sesion/view.php' , 
	array_merge($url_base,array('u' => 0,'t' => 0,"d"=>false,'a' => true)));
$url_usuario = new moodle_url('/blocks/control_sesion/view.php' , 
	array_merge($url_base,array('u' => $usuario,'t' => 1,'a' => true,"d"=>false)));
$url_semanal = new moodle_url('/blocks/control_sesion/view.php' , 
	array_merge($url_base,array('u' => $usuario,'t' => 2,'a' => true,"d"=>false)));
$url_mensual = new moodle_url('/blocks/control_sesion/view.php' , 
	array_merge($url_base,array('u' => 0,'t' => 3,'a' => true,"d"=>false)));
$url_anual = new moodle_url('/blocks/control_sesion/view.php' , 
	array_merge($url_base,array('u' => 0,'t' => 4,'a' => true,"d"=>false)));
$url_detalle_mes = new moodle_url('/blocks/control_sesion/view.php' , 
	array_merge($url_base,array('u' => $usuario,'t' => 5,'a' => true,"d"=>false)));
$url_descarga = new moodle_url('/blocks/control_sesion/view.php' , 
	array_merge($url_base,array('u' => 0,'t' => $tipo_tabla,"d"=>true)));
echo $OUTPUT->header();



echo html_writer::start_tag('div',array("class"=>"btn-group","id"=>"grupo_cabecera"));
//if ($versesiones)
{
	echo html_writer::start_tag('a',array('href'=>$url_tabla));
	echo "<button class='btn-group form-control ".$res[0]."'>".get_string('resumen', 'block_control_sesion')."</button>";
	echo html_writer::end_tag('a')."";
}
echo html_writer::start_tag('a',array('href'=>$url_usuario));
echo "<button class='btn-group form-control ".$res[1]."'>".get_string('detallado', 'block_control_sesion')."</button>";
echo html_writer::end_tag('a');
echo html_writer::start_tag('a',array('href'=>$url_semanal));
echo "<button class='btn-group form-control ".$res[2]."'>".get_string('por_dias', 'block_control_sesion')."</button>";
echo html_writer::end_tag('a');
echo html_writer::start_tag('a',array('href'=>$url_mensual));
echo "<button class='btn-group form-control ".$res[3]."'>".get_string('por_semanas', 'block_control_sesion')."</button>";
echo html_writer::end_tag('a');
echo html_writer::start_tag('a',array('href'=>$url_anual));
echo "<button class='btn-group form-control ".$res[4]."'>".get_string('por_meses', 'block_control_sesion')."</button>";
echo html_writer::end_tag('a');
echo html_writer::start_tag('a',array('href'=>$url_detalle_mes));
echo "<button class='btn-group form-control ".$res[5]."'>".get_string('detalle_mes', 'block_control_sesion')."</button>";
echo html_writer::end_tag('a');
echo html_writer::end_tag('div')."<br><br>";
$filtro->display();
echo html_writer::end_tag('div');
echo texto_tabla_datos($resultados);
echo html_writer::start_tag('a',array('href'=>$url_descarga));
echo "<button class='btn btn-info'>".get_string('descargar','block_control_sesion')."</button>";
echo html_writer::end_tag('a');


?>